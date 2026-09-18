<?php

namespace App\Filament\Resources\ProductVersions;

use App\Enums\ProductVersionStatus;
use App\Filament\Resources\ProductVersions\Pages\CreateProductVersion;
use App\Filament\Resources\ProductVersions\Pages\EditProductVersion;
use App\Filament\Resources\ProductVersions\Pages\ListProductVersions;
use App\Jobs\ScanProductFileJob;
use App\Models\AuditLog;
use App\Models\ProductFile;
use App\Models\ProductVersion;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use UnitEnum;

class ProductVersionResource extends Resource
{
    protected static ?string $model = ProductVersion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static string|UnitEnum|null $navigationGroup = 'Catalog Management';

    protected static ?string $navigationLabel = 'Releases & Versions';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('catalog.publish_version') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Callout::make('security_notice')
                    ->warning()
                    ->title('Automated Quarantine & Malware Verification')
                    ->description('Release files are stored on secure private storage with nonces and UUID paths. Releases cannot be published until checksum and antivirus verification completes.')
                    ->columnSpanFull(),

                Section::make('Release Specification')
                    ->schema([
                        Select::make('product_id')
                            ->label('Product')
                            ->relationship('product', 'title')
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('version_number')
                            ->label('Version Number (SemVer)')
                            ->placeholder('1.2.0')
                            ->required()
                            ->maxLength(50),

                        TextInput::make('min_runtime_version')
                            ->label('Minimum Runtime Requirement')
                            ->placeholder('e.g., PHP 8.3+, Node 22')
                            ->maxLength(100),

                        Select::make('status')
                            ->options(ProductVersionStatus::class)
                            ->default(ProductVersionStatus::Quarantined)
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Transitions from Quarantined to Published via the Publish action once security verified.'),

                        FileUpload::make('release_archive')
                            ->label('Release Archive Package (ZIP)')
                            ->disk('s3_secure')
                            ->directory(fn () => 'releases/'.(string) Str::uuid())
                            ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file): string => (string) Str::uuid().'.'.$file->getClientOriginalExtension())
                            ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed', 'multipart/x-zip'])
                            ->maxSize(102400)
                            ->formatStateUsing(fn (?ProductVersion $record): ?string => $record?->files()->latest()->first()?->storage_path)
                            ->helperText('Upload release binaries directly to private object storage. SHA-256 hash computed on save.')
                            ->columnSpanFull(),

                        MarkdownEditor::make('changelog_markdown')
                            ->label('Changelog Notes (Markdown)')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.title')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('version_number')
                    ->label('Version')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (ProductVersionStatus $state): string => match ($state) {
                        ProductVersionStatus::Published => 'success',
                        ProductVersionStatus::Quarantined => 'warning',
                        ProductVersionStatus::Deprecated => 'gray',
                    })
                    ->sortable(),

                IconColumn::make('is_scanned_safe')
                    ->label('Scan Safe')
                    ->getStateUsing(fn (ProductVersion $record): bool => (bool) $record->files()->where('is_scanned_safe', true)->exists())
                    ->boolean(),

                TextColumn::make('files.checksum_sha256')
                    ->label('SHA-256 Checksum')
                    ->limit(10)
                    ->tooltip(fn (ProductVersion $record): ?string => $record->files()->first()?->checksum_sha256)
                    ->copyable()
                    ->fontFamily('mono')
                    ->color('gray'),

                TextColumn::make('released_at')
                    ->label('Released At')
                    ->dateTime()
                    ->placeholder('Pending Publish')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'title'),

                SelectFilter::make('status')
                    ->options(ProductVersionStatus::class),
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

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductVersions::route('/'),
            'create' => CreateProductVersion::route('/create'),
            'edit' => EditProductVersion::route('/{record}/edit'),
        ];
    }

    public static function processUploadedArchive(ProductVersion $record, ?string $archivePath): void
    {
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
        $fileName = "{$record->product->slug}-v{$record->version_number}.zip";

        $productFile = ProductFile::updateOrCreate(
            ['product_version_id' => $record->id],
            [
                'storage_disk' => 's3_secure',
                'storage_path' => $archivePath,
                'file_name' => $fileName,
                'file_size_bytes' => $sizeBytes,
                'mime_type' => 'application/zip',
                'checksum_sha256' => $checksum,
                'is_scanned_safe' => false,
            ]
        );

        ScanProductFileJob::dispatch($productFile->id);
    }
}
