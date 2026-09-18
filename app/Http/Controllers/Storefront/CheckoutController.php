<?php

namespace App\Http\Controllers\Storefront;

use App\Domain\Commerce\Services\CartService;
use App\Domain\Commerce\Services\SslcommerzService;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected SslcommerzService $sslcommerzService,
    ) {}

    /**
     * Display the single-page checkout view.
     */
    public function show(): View|RedirectResponse
    {
        $items = $this->cartService->getItems();

        if ($items->isEmpty()) {
            return redirect()->route('products.index')
                ->with('status', 'Your cart is empty. Add a product to proceed to checkout.');
        }

        $countries = [
            'Bangladesh',
            'United States',
            'United Kingdom',
            'Canada',
            'Germany',
            'Australia',
            'India',
            'Singapore',
            'United Arab Emirates',
            'Netherlands',
        ];

        return view('storefront.checkout', [
            'items' => $items,
            'subtotalFormatted' => $this->cartService->getSubtotalFormatted(),
            'discountFormatted' => $this->cartService->getDiscountFormatted(),
            'totalFormatted' => $this->cartService->getTotalFormatted(),
            'subtotalMinor' => $this->cartService->getSubtotalMinor(),
            'discountMinor' => $this->cartService->getDiscountMinor(),
            'totalMinor' => $this->cartService->getTotalMinor(),
            'appliedCoupon' => $this->cartService->getAppliedCoupon(),
            'countries' => $countries,
            'defaultEmail' => auth()->user()?->email ?? '',
        ]);
    }

    /**
     * Process checkout form submission, create pending Order and OrderItems, and redirect to gateway.
     */
    public function process(\App\Http\Requests\CheckoutRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $items = $this->cartService->getItems();

        if ($items->isEmpty()) {
            return redirect()->route('products.index')
                ->withErrors(['cart' => 'Your cart is currently empty.']);
        }

        $subtotalMinor = $this->cartService->getSubtotalMinor();
        $discountMinor = $this->cartService->getDiscountMinor();
        $totalMinor = $this->cartService->getTotalMinor();
        $appliedCoupon = $this->cartService->getAppliedCoupon();

        try {
            $order = DB::transaction(function () use ($validated, $items, $subtotalMinor, $discountMinor, $totalMinor, $appliedCoupon) {
                // 1. Passive customer lookup or auto-provisioning
                $customer = Customer::firstOrCreate(
                    ['email' => strtolower(trim($validated['email']))],
                    [
                        'user_id' => auth()->id(),
                        'currency' => 'USD',
                    ]
                );

                if (auth()->check() && ! $customer->user_id) {
                    $customer->update(['user_id' => auth()->id()]);
                }

                // 2. Generate unique order number (e.g., ORD-202609-A7F2)
                $orderNumber = 'ORD-'.date('Ym').'-'.strtoupper(Str::random(6));

                // 3. Create Pending Order
                $order = Order::create([
                    'order_number' => $orderNumber,
                    'customer_id' => $customer->id,
                    'status' => OrderStatus::Pending,
                    'currency' => 'USD',
                    'subtotal_minor' => $subtotalMinor,
                    'discount_minor' => $discountMinor,
                    'tax_minor' => 0,
                    'total_minor' => $totalMinor,
                    'payment_gateway' => $validated['payment_method'],
                    'coupon_id' => $appliedCoupon?->id,
                    'coupon_code' => $appliedCoupon?->code,
                ]);

                // 4. Create Order Items snapshotting immutable titles and tiers
                foreach ($items as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item->product_id,
                        'product_version_id' => $item->product->latestPublishedVersion?->id,
                        'price_id' => $item->price_id,
                        'historical_product_title' => $item->product->title,
                        'historical_tier_name' => $item->tier_name,
                        'unit_amount_minor' => $item->unit_amount_minor,
                    ]);
                }

                // Record coupon usage if applied
                $appliedCoupon?->recordUsage();

                return $order;
            });

            // 5. Clear the session cart now that order is safely staged
            $this->cartService->clear();

            // 6. Connect to selected payment gateway
            if ($validated['payment_method'] === 'paddle') {
                // In local / staging / test environments or direct hosted checkout:
                // Redirect to confirmation with gateway=paddle query parameter
                $paddleConfirmationUrl = route('orders.confirmation', [
                    'order_number' => $order->order_number,
                    'gateway' => 'paddle',
                ]);

                return redirect()->away($paddleConfirmationUrl);
            }

            // SSLCOMMERZ Hosted Checkout API
            $customer = $order->customer;
            $gatewayUrl = $this->sslcommerzService->initiatePayment($order, $customer, $validated['country']);

            return redirect()->away($gatewayUrl);

        } catch (Exception $e) {
            Log::error('Checkout processing failure', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['checkout' => 'Payment gateway connection error: '.$e->getMessage()]);
        }
    }

    /**
     * Display payment failed page.
     */
    public function failed(Request $request): View
    {
        $orderNumber = $request->query('order');
        $reason = $request->query('reason');

        $order = null;
        if ($orderNumber) {
            $order = Order::where('order_number', $orderNumber)->first();
        }

        return view('storefront.payment-failed', [
            'order' => $order,
            'orderNumber' => $orderNumber,
            'reason' => $reason,
        ]);
    }
}
