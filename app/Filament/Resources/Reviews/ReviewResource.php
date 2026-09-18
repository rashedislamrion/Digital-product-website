<?php

namespace App\Filament\Resources\Reviews;

use App\Enums\ReviewStatus;
use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Filament\Resources\Reviews\Pages\ViewReview;
use App\Models\Review;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static string|UnitEnum|null $navigationGroup = 'Customers & Support';

    protected static ?string $navigationLabel = 'Reviews & Ratings';

    protected static ?string $modelLabel = 'Review';

    protected static ?string $pluralModelLabel = 'Reviews & Ratings';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('reviews.moderate_publish') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer Review Details')
                    ->schema([
                        TextInput::make('product.title')
                            ->label('Product')
                            ->disabled(),

                        TextInput::make('customer.email')
                            ->label('Customer')
                            ->disabled(),

                        TextInput::make('rating')
                            ->label('Rating (1 - 5)')
                            ->disabled(),

                        TextInput::make('status')
                            ->disabled(),

                        TextInput::make('title')
                            ->label('Review Headline')
                            ->disabled()
                            ->columnSpanFull(),

                        Textarea::make('review_text')
                            ->label('Review Body')
                            ->disabled()
                            ->rows(4)
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Merchant Public Response')
                    ->schema([
                        Placeholder::make('existing_reply')
                            ->label('Current Merchant Response')
                            ->content(fn (Review $record) => $record->merchant_reply ?: 'No merchant reply added yet.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('rating')
                    ->label('Stars')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 4 => 'success',
                        $state === 3 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn ($state) => str_repeat('★', $state) . " ({$state})")
                    ->sortable(),

                TextColumn::make('product.title')
                    ->label('Product')
                    ->searchable()
                    ->limit(25),

                TextColumn::make('customer.email')
                    ->label('Customer')
                    ->searchable(),

                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->limit(30),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state?->value ?? $state) {
                        'published' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        'hidden' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending Moderation',
                        'published' => 'Published',
                        'rejected' => 'Rejected',
                        'hidden' => 'Hidden',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),

                // Action: Approve Review (Publish)
                Action::make('approve')
                    ->label('Approve')
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->visible(fn (Review $record) => $record->status !== ReviewStatus::Published)
                    ->action(function (Review $record) {
                        $record->update(['status' => ReviewStatus::Published]);

                        Notification::make()
                            ->title('Review Published')
                            ->body('The review is now live on the storefront.')
                            ->success()
                            ->send();
                    }),

                // Action: Reject Review
                Action::make('reject')
                    ->label('Reject')
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('danger')
                    ->visible(fn (Review $record) => $record->status !== ReviewStatus::Rejected)
                    ->action(function (Review $record) {
                        $record->update(['status' => ReviewStatus::Rejected]);

                        Notification::make()
                            ->title('Review Rejected')
                            ->body('The review has been rejected and will not be displayed.')
                            ->danger()
                            ->send();
                    }),

                // Action: Merchant Reply
                Action::make('reply')
                    ->label('Reply as Merchant')
                    ->icon(Heroicon::OutlinedChatBubbleBottomCenterText)
                    ->color('info')
                    ->form([
                        Textarea::make('merchant_reply')
                            ->label('Public Merchant Response')
                            ->required()
                            ->default(fn (Review $record) => $record->merchant_reply)
                            ->rows(4),
                    ])
                    ->action(function (Review $record, array $data) {
                        $record->update([
                            'merchant_reply' => $data['merchant_reply'],
                            'replied_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Merchant Reply Saved')
                            ->body('Your public reply has been attached to the review.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReviews::route('/'),
            'view' => ViewReview::route('/{record}'),
        ];
    }
}
