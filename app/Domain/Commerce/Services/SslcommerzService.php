<?php

namespace App\Domain\Commerce\Services;

use App\Models\Customer;
use App\Models\Order;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SslcommerzService
{
    protected string $storeId;

    protected string $storePassword;

    protected bool $isSandbox;

    public function __construct()
    {
        $this->storeId = (string) config('services.sslcommerz.store_id', env('SSLCOMMERZ_STORE_ID', 'testbox'));
        $this->storePassword = (string) config('services.sslcommerz.store_password', env('SSLCOMMERZ_STORE_PASSWORD', 'qwerty'));
        $this->isSandbox = (bool) config('services.sslcommerz.is_sandbox', env('SSLCOMMERZ_IS_SANDBOX', true));
    }

    /**
     * Get base URL depending on sandbox/production environment.
     */
    public function getBaseUrl(): string
    {
        return $this->isSandbox
            ? 'https://sandbox.sslcommerz.com'
            : 'https://securepay.sslcommerz.com';
    }

    /**
     * Initialize a hosted checkout session on SSLCOMMERZ v4.
     *
     * @throws Exception
     */
    public function initiatePayment(Order $order, Customer $customer, string $country = 'Bangladesh'): string
    {
        $apiUrl = $this->getBaseUrl().'/gwprocess/v4/api.php';

        $cusName = $customer->user?->name
            ?: ($customer->email ? explode('@', $customer->email)[0] : 'Valued Customer');

        $totalAmount = number_format($order->total_minor / 100, 2, '.', '');

        $payload = [
            'store_id' => $this->storeId,
            'store_passwd' => $this->storePassword,
            'total_amount' => $totalAmount,
            'currency' => $order->currency ?: 'USD',
            'tran_id' => $order->order_number,
            'success_url' => route('payment.sslcommerz.success'),
            'fail_url' => route('payment.sslcommerz.fail'),
            'cancel_url' => route('payment.sslcommerz.cancel'),
            'ipn_url' => route('payment.sslcommerz.ipn'),

            // Customer details (strictly digital, non-shipping)
            'cus_name' => $cusName,
            'cus_email' => $customer->email,
            'cus_add1' => 'Dhaka',
            'cus_city' => 'Dhaka',
            'cus_country' => $country,
            'cus_phone' => '01700000000',

            // Product profile
            'product_name' => "DevStore Order {$order->order_number}",
            'product_category' => 'Digital Goods',
            'product_profile' => 'non-physical-goods',
            'shipping_method' => 'NO',
            'num_of_item' => max(1, $order->items()->count()),
        ];

        Log::info('Initiating SSLCOMMERZ checkout session', [
            'order_number' => $order->order_number,
            'total_amount' => $totalAmount,
            'currency' => $order->currency,
            'is_sandbox' => $this->isSandbox,
        ]);

        $response = Http::asForm()
            ->timeout(30)
            ->post($apiUrl, $payload);

        if (! $response->successful()) {
            Log::error('SSLCOMMERZ session initialization HTTP failure', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new Exception('Failed to connect to SSLCOMMERZ payment gateway: '.$response->body());
        }

        $data = $response->json();

        // Check for GatewayPageURL in response
        if (isset($data['status']) && strtoupper($data['status']) === 'SUCCESS' && ! empty($data['GatewayPageURL'])) {
            return (string) $data['GatewayPageURL'];
        }

        // If returned in alternative format or failed
        $errorMessage = $data['failedreason'] ?? ($data['status'] ?? 'Unknown gateway error');
        Log::error('SSLCOMMERZ gateway rejected initialization', ['response' => $data]);

        throw new Exception('SSLCOMMERZ Gateway Error: '.$errorMessage);
    }

    /**
     * Mandatory synchronous server-to-server Order Validation API call.
     * Never trusts the IPN/callback body directly without this validation.
     *
     * @return array{isValid: bool, data: array, error: ?string}
     */
    public function validateOrder(string $valId): array
    {
        $validationUrl = $this->getBaseUrl().'/validator/api/validationserverAPI.php';

        Log::info('Executing mandatory server-to-server SSLCOMMERZ order validation', [
            'val_id' => $valId,
        ]);

        $response = Http::timeout(30)->get($validationUrl, [
            'val_id' => $valId,
            'store_id' => $this->storeId,
            'store_passwd' => $this->storePassword,
            'format' => 'json',
        ]);

        if (! $response->successful()) {
            Log::error('SSLCOMMERZ validation API HTTP error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'isValid' => false,
                'data' => [],
                'error' => 'Validation server communication error: '.$response->status(),
            ];
        }

        $data = $response->json();

        if (! is_array($data)) {
            return [
                'isValid' => false,
                'data' => [],
                'error' => 'Invalid JSON returned from validation server',
            ];
        }

        $status = strtoupper($data['status'] ?? '');
        $isValid = in_array($status, ['VALID', 'VALIDATED'], true);

        if (! $isValid) {
            Log::warning('SSLCOMMERZ validation returned non-valid status', [
                'val_id' => $valId,
                'status' => $status,
                'data' => $data,
            ]);

            return [
                'isValid' => false,
                'data' => $data,
                'error' => "Transaction validation status: {$status}",
            ];
        }

        return [
            'isValid' => true,
            'data' => $data,
            'error' => null,
        ];
    }
}
