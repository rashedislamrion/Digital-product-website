<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Domain\Commerce\Services\RevokeOrderAccess;
use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refund')
                ->label('Issue Refund')
                ->icon(Heroicon::OutlinedArrowUturnLeft)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Refund Order & Revoke Entitlements')
                ->modalDescription('Are you sure you want to refund this order? All digital download grants and software license keys associated with this order will be permanently revoked.')
                ->visible(fn (Order $record): bool => auth()->user()?->can('orders.issue_refund') && $record->status === OrderStatus::Paid)
                ->action(function (Order $record, RevokeOrderAccess $revokeService) {
                    $revokeService->execute($record, auth()->user(), 'Admin initiated refund from order view page');

                    Notification::make()
                        ->title('Order Refunded')
                        ->body("Order #{$record->order_number} has been refunded and all digital access revoked.")
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),
        ];
    }
}
