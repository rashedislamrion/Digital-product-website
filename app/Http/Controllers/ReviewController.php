<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\ReviewStatus;
use App\Models\Customer;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    /**
     * Submit a customer review for a purchased product.
     */
    public function store(\App\Http\Requests\StoreReviewRequest $request, Product $product): RedirectResponse
    {
        $customer = $this->resolveCustomer();

        if (! $customer) {
            return redirect()->route('magic-link.create')
                ->withErrors(['email' => 'Please sign in to submit a review for this product.']);
        }

        $validated = $request->validated();

        // Find a paid order_item for this customer and product that doesn't have a review yet
        $eligibleOrderItem = OrderItem::where('product_id', $product->id)
            ->whereHas('order', function ($query) use ($customer) {
                $query->where('customer_id', $customer->id)
                    ->where('status', OrderStatus::Paid);
            })
            ->whereDoesntHave('review')
            ->first();

        if (! $eligibleOrderItem) {
            return back()->withErrors([
                'review_text' => 'Reviews are strictly restricted to verified purchasers. You either have not purchased this product or have already submitted a review for your purchase.',
            ]);
        }

        Review::create([
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'order_item_id' => $eligibleOrderItem->id,
            'rating' => $validated['rating'],
            'title' => $validated['title'] ?? null,
            'review_text' => $validated['review_text'],
            'status' => ReviewStatus::Pending,
        ]);

        return back()->with('status', 'Thank you! Your verified review has been submitted and is pending moderation.');
    }

    /**
     * Resolve active customer from authentication or guest session.
     */
    private function resolveCustomer(): ?Customer
    {
        if (Auth::check()) {
            $user = Auth::user();

            return $user->customer ?? Customer::where('email', $user->email)->first();
        }

        if (session()->has('guest_customer_id')) {
            return Customer::find(session('guest_customer_id'));
        }

        return null;
    }
}
