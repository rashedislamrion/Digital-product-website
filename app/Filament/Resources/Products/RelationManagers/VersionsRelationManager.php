<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Enums\ProductVersionStatus;
use App\Jobs\ScanProductFileJob;
use App\Models\AuditLog;
use App\Models\ProductFile;
use App\Models\ProductVersion;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class VersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    protected static ?string $title = 'Releases & Version Binaries';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Callout::make('quarantine_notice')
                    ->warning()
                    ->title('Automated Malware & Integrity Quarantine')
                    ->description('All uploaded packages are saved to private encrypted storage with randomized UUID paths and quarantined until automated checksum and virus scanning completes.')
                    ->columnSpanFull(),

                TextInput::make('version_number')
                    ->label('Version Number (SemVer)')
                    ->placeholder('2.4.0')
                    ->required()
                    ->maxLength(50),

                TextInput::make('min_runtime_version')
                    ->label('Minimum Runtime')
                    ->placeholder('e.g., PHP 8.3+, Node 20+')
                    ->maxLength(100),

                Select::make('status')
                    ->options(ProductVersionStatus::class)
                    ->default(ProductVersionStatus::Quarantined)
                    ->disabled()
                    ->dehydrated()
                    ->helperText('Status remains Quarantined until file passes antivirus scanning, then published via the Publish action.'),

                FileUpload::make('release_archive')
                    ->label('Product ZIP Package')
                    ->disk('s3_secure')
                    ->directory(fn () => 'releases/'.(string) Str::uuid())
                    ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file): string => (string) Str::uuid().'.'.$file->getClientOriginalExtension())
                    ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed', 'multipart/x-zip'])
                    ->maxSize(102400) // 100MB
                    ->formatStateUsing(fn (?ProductVersion $record): ?string => $record?->files()->latest()->first()?->storage_path)
                    ->helperText('Stored securely on private object storage. SHA-256 hash computed on upload.')
                    ->columnSpanFull(),

                MarkdownEditor::make('changelog_markdown')
                    ->label('Release Changelog (Markdown)')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('version_number')
            ->columns([
                TextColumn::make('version_number')
                    ->label('Version')
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('min_runtime_version')
                    ->label('Runtime')
                    ->placeholder('—'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (ProductVersionStatus $state): string => match ($state) {
                        ProductVersionStatus::Published => 'success',
                        ProductVersionStatus::Quarantined => 'warning',
                        ProductVersionStatus::Deprecated => 'gray',
                    }),

                IconColumn::make('file_safe')
                    ->label('Scan Safe')
                    ->getStateUsing(fn (ProductVersion $record): bool => (bool) $record->files()->where('is_scanned_safe', true)->exists())
                    ->boolean(),

                TextColumn::make('files.checksum_sha256')
                    ->label('SHA-256 Checksum')
                    ->limit(12)
                    ->tooltip(fn (ProductVersion $record): ?string => $record->files()->first()?->checksum_sha256)
                    ->copyable()
                    ->fontFamily('mono')
                    ->color('gray'),

                TextColumn::make('released_at')
                    ->label('Released')
                    ->dateTime()
                    ->placeholder('Unpublished')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->after(function (ProductVersion $record, array $data) {
                        $this->handleUploadedFile($record, $data);
                    }),
            ])
            ->recordActions([
                Action::make('publish')
                    ->label('Publish Release')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Publish Release Version')
                    ->modalDescription('Publishing makes this release immediately available for customer downloads and license fulfillment.')
                    ->visible(fn (): bool => auth()->user()?->can('catalog.publish_version') ?? false)
                    ->disabled(fn (ProductVersion $record): bool => $record->status === ProductVersionStatus::Published || ! $record->files()->where('is_scanned_safe', true)->exists())
                    ->action(function (ProductVersion $record) {
                        if (! $record->files()->where('is_scanned_safe', true)->exists()) {
                            Notification::make()
                                ->danger()
                                ->title('File Quarantine Active')
                                ->body('Cannot publish a release whose digital assets have not passed security scanning.')
                                ->send();

                            return;
                        }

                        $oldStatus = $record->status->value ?? (string) $record->status;
                        $record->update([
                            'status' => ProductVersionStatus::Published,
                            'released_at' => now(),
                        ]);

                        AuditLog::create([
                            'user_id' => auth()->id(),
                            'action' => 'product_version.publish',
                            'target_type' => ProductVersion::class,
                            'target_id' => $record->id,
                            'ip_address' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                            'metadata_before' => ['status' => $oldStatus],
                            'metadata_after' => ['status' => 'published', 'released_at' => now()->toIso8601String()],
                            'created_at' => now(),
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Release Published')
                            ->body("Version {$record->version_number} is now live in the catalog.")
                            ->send();
                    }),

                EditAction::make()
                    ->after(function (ProductVersion $record, array $data) {
                        $this->handleUploadedFile($record, $data);
                    }),

                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected function handleUploadedFile(ProductVersion $record, array $data): void
    {
        $archivePath = $data['release_archive'] ?? null;

        if (! $archivePath || ! Storage::disk('s3_secure')->exists($archivePath)) {
            return;
        }

        $existing = $record->files()->where('storage_path', $archivePath)->first();
        if ($existing) {
            return;
        }

        $fileContents = Storage::disk('s3_secure')->get($archivePath);
        $checksum = hash('sha256', $fileContents);
        $sizeBytes = Storage::disk('s3_secure')->size($archivePath);
        $fileName = basename($archivePath);

        $productFile = ProductFile::updateOrCreate(
            ['product_version_id' => $record->id],
            [
                'storage_disk' => 's3_secure',
                'storage_path' => $archivePath,
                'file_name' => "{$record->product->slug}-v{$record->version_number}.zip",
                'file_size_bytes' => $sizeBytes,
                'mime_type' => 'application/zip',
                'checksum_sha256' => $checksum,
                'is_scanned_safe' => false,
            ]
        );

        ScanProductFileJob::dispatch($productFile->id);
    }
}
