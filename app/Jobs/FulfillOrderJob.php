<?php

namespace App\Jobs;

use App\Domain\Licensing\Services\LicenseService;
use App\Enums\OrderStatus;
use App\Enums\ProductType;
use App\Mail\OrderConfirmationMail;
use App\Models\DownloadGrant;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class FulfillOrderJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $orderId,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(mixed $action = null): void
    {
        $grantAction = ($action instanceof \App\Domain\Commerce\Actions\GrantOrderEntitlements)
            ? $action
            : app(\App\Domain\Commerce\Actions\GrantOrderEntitlements::class);

        $grantAction->execute($this->orderId);
    }
}
