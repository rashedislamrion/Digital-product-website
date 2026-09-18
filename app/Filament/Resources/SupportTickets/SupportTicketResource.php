<?php

namespace App\Filament\Resources\SupportTickets;

use App\Enums\SupportTicketStatus;
use App\Filament\Resources\SupportTickets\Pages\ListSupportTickets;
use App\Filament\Resources\SupportTickets\Pages\ViewSupportTicket;
use App\Models\SupportTicket;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    protected static string|UnitEnum|null $navigationGroup = 'Customers & Support';

    protected static ?string $navigationLabel = 'Support Tickets';

    protected static ?string $modelLabel = 'Support Ticket';

    protected static ?string $pluralModelLabel = 'Support Tickets';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('support.manage_tickets') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ticket Overview')
                    ->schema([
                        TextInput::make('customer.email')
                            ->label('Customer Email')
                            ->disabled(),

                        TextInput::make('status')
                            ->disabled(),

                        TextInput::make('order.order_number')
                            ->label('Related Order')
                            ->disabled()
                            ->placeholder('None'),

                        TextInput::make('license.license_key_masked')
                            ->label('Related License')
                            ->disabled()
                            ->placeholder('None'),

                        TextInput::make('subject')
                            ->disabled()
                            ->columnSpanFull(),

                        Textarea::make('message')
                            ->label('Initial Customer Inquiry')
                            ->disabled()
                            ->rows(4)
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Ticket Replies & Internal Notes Thread')
                    ->schema([
                        Placeholder::make('thread')
                            ->label('Conversation History')
                            ->content(function (SupportTicket $record) {
                                $replies = $record->replies()->with('user')->oldest()->get();
                                if ($replies->isEmpty()) {
                                    return 'No agent replies recorded yet.';
                                }

                                $lines = [];
                                foreach ($replies as $reply) {
                                    $author = $reply->user?->name ?? 'Support Agent';
                                    $type = $reply->is_internal_note ? '[INTERNAL NOTE]' : '[REPLY]';
                                    $date = $reply->created_at->format('M d, Y H:i');
                                    $lines[] = "--- {$type} by {$author} at {$date} ---\n{$reply->message}\n";
                                }

                                return implode("\n", $lines);
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.email')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('subject')
                    ->searchable()
                    ->limit(40),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state?->value ?? $state) {
                        'open' => 'danger',
                        'pending' => 'warning',
                        'closed' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('order.order_number')
                    ->label('Order #')
                    ->placeholder('None')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Opened')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'pending' => 'Pending',
                        'closed' => 'Closed',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),

                Action::make('reply')
                    ->label('Reply / Update')
                    ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                    ->color('primary')
                    ->form([
                        Textarea::make('reply_message')
                            ->label('Your Response')
                            ->required()
                            ->rows(4),

                        Select::make('new_status')
                            ->label('Ticket Status')
                            ->options([
                                'open' => 'Open',
                                'pending' => 'Pending Customer Action',
                                'closed' => 'Closed',
                            ])
                            ->default(fn (SupportTicket $record) => $record->status->value)
                            ->required(),

                        Toggle::make('is_internal_note')
                            ->label('Internal Note (visible to staff only)')
                            ->default(false),
                    ])
                    ->action(function (SupportTicket $record, array $data) {
                        $record->replies()->create([
                            'user_id' => auth()->id(),
                            'message' => $data['reply_message'],
                            'is_internal_note' => $data['is_internal_note'],
                        ]);

                        $record->update([
                            'status' => SupportTicketStatus::from($data['new_status']),
                        ]);

                        Notification::make()
                            ->title('Ticket Updated')
                            ->body('Response has been recorded and ticket status updated.')
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
            'index' => ListSupportTickets::route('/'),
            'view' => ViewSupportTicket::route('/{record}'),
        ];
    }
}
