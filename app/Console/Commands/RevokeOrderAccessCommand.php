<?php

namespace App\Console\Commands;

use App\Domain\Commerce\Services\RevokeOrderAccess;
use App\Models\Order;
use Illuminate\Console\Command;

class RevokeOrderAccessCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:revoke {order : The order number or ULID} {--reason= : Reason for revocation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refund an order and revoke all related download grants and software licenses';

    /**
     * Execute the console command.
     */
    public function handle(RevokeOrderAccess $revokeService): int
    {
        $orderIdentifier = $this->argument('order');
        $reason = $this->option('reason') ?: 'Manual administrative revocation via Artisan';

        $order = Order::where('order_number', $orderIdentifier)
            ->orWhere('id', $orderIdentifier)
            ->first();

        if (! $order) {
            $this->error("Order '{$orderIdentifier}' not found.");

            return Command::FAILURE;
        }

        $this->info("Revoking access for Order #{$order->order_number}...");

        $revokeService->execute($order, null, $reason);

        $this->info("Order #{$order->order_number} status updated to 'refunded', download grants revoked, and licenses invalidated.");

        return Command::SUCCESS;
    }
}
