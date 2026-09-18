<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\SupportTicketStatus;
use App\Models\Customer;
use App\Models\LicenseActivation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SupportTicket;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class CustomerOrderAccessController extends Controller
{
    /**
     * Display the customer digital library (products, license keys, order history, support).
     */
    public function library(Request $request): View|RedirectResponse
    {
        $customer = $this->resolveCustomer();

        if (! $customer) {
            return redirect()->route('magic-link.create')
                ->withErrors(['email' => 'Please sign in or request a magic link to access your digital library.']);
        }

        $isGuest = ! Auth::check() || is_null($customer->user_id);

        $paidOrderItems = OrderItem::whereHas('order', function ($query) use ($customer) {
            $query->where('customer_id', $customer->id)
                ->where('status', OrderStatus::Paid);
        })
            ->with(['product', 'version', 'downloadGrants', 'license'])
            ->latest()
            ->get();

        $products = $paidOrderItems->map(function (OrderItem $item) {
            return [
                'item' => $item,
                'grant' => $item->downloadGrants->first(),
                'license' => $item->license,
                'version' => $item->version,
            ];
        });

        $licenses = $customer->licenses()
            ->with(['activations', 'product', 'orderItem'])
            ->latest()
            ->get();

        $orders = $customer->orders()
            ->with('items')
            ->latest()
            ->get();

        $supportTickets = $customer->supportTickets()
            ->with(['order', 'license'])
            ->latest()
            ->get();

        return view('customer.library', [
            'customer' => $customer,
            'isGuest' => $isGuest,
            'products' => $products,
            'licenses' => $licenses,
            'orders' => $orders,
            'supportTickets' => $supportTickets,
        ]);
    }

    /**
     * Download styled PDF invoice for an order.
     */
    public function downloadInvoice(Request $request, Order $order): Response|RedirectResponse
    {
        $customer = $this->resolveCustomer();

        if (! $customer) {
            return redirect()->route('magic-link.create')
                ->withErrors(['email' => 'Please sign in or request a magic link to access your invoices.']);
        }

        if ($order->customer_id !== $customer->id) {
            abort(403, 'Unauthorized access to this order invoice.');
        }

        $order->load(['items.license', 'customer']);

        $pdf = Pdf::loadView('invoices.pdf', [
            'order' => $order,
        ]);

        return $pdf->download("invoice-{$order->order_number}.pdf");
    }

    /**
     * Submit a support message linked to an order or license.
     */
    public function storeSupportTicket(\App\Http\Requests\StoreSupportTicketRequest $request): RedirectResponse
    {
        $customer = $this->resolveCustomer();

        if (! $customer) {
            return redirect()->route('magic-link.create')
                ->withErrors(['email' => 'Please sign in or request a magic link to submit support tickets.']);
        }

        $validated = $request->validated();

        if (! empty($validated['order_id'])) {
            $validOrder = $customer->orders()->where('id', $validated['order_id'])->exists();
            if (! $validOrder) {
                return back()->withErrors(['order_id' => 'The selected order does not belong to your account.'])->withInput();
            }
        }

        if (! empty($validated['license_id'])) {
            $validLicense = $customer->licenses()->where('id', $validated['license_id'])->exists();
            if (! $validLicense) {
                return back()->withErrors(['license_id' => 'The selected license does not belong to your account.'])->withInput();
            }
        }

        $ticket = $customer->supportTickets()->create([
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'order_id' => $validated['order_id'] ?? null,
            'license_id' => $validated['license_id'] ?? null,
            'status' => SupportTicketStatus::Open,
        ]);

        return redirect()->route('customer.library', ['tab' => 'support'])
            ->with('status', 'Your support ticket has been submitted. Our engineering team will review it shortly.');
    }

    /**
     * Alias for digital library (for backward compatibility).
     */
    public function orders(Request $request): View|RedirectResponse
    {
        return $this->library($request);
    }

    /**
     * Customer self-service deactivation of an active license installation.
     */
    public function deactivate(Request $request, LicenseActivation $activation): JsonResponse
    {
        $customer = $this->resolveCustomer();

        if (! $customer) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $license = $activation->license;

        if (! $license || $license->customer_id !== $customer->id) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        if (! $activation->is_active) {
            return response()->json([
                'success' => true,
                'message' => 'Already deactivated.',
                'activations_remaining' => max(0, $license->max_activations - $license->current_activations_count),
            ]);
        }

        $activation->update([
            'is_active' => false,
            'deactivated_at' => now(),
        ]);

        $newCount = max(0, $license->current_activations_count - 1);
        $license->update(['current_activations_count' => $newCount]);

        return response()->json([
            'success' => true,
            'message' => 'Installation deactivated successfully.',
            'activations_remaining' => max(0, $license->max_activations - $newCount),
        ]);
    }

    /**
     * Resolve the active customer from authenticated user or guest session.
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

    /**
     * Display the set password form for guest purchasers.
     */
    public function showSetPassword(): View|RedirectResponse
    {
        if (! session()->has('guest_customer_id')) {
            return redirect()->route('dashboard');
        }

        $customer = Customer::find(session('guest_customer_id'));

        if (! $customer || $customer->user_id) {
            return redirect()->route('customer.orders');
        }

        return view('customer.set-password', [
            'customer' => $customer,
        ]);
    }

    /**
     * Convert guest purchaser into a full user account by setting a password.
     */
    public function storeSetPassword(Request $request): RedirectResponse
    {
        if (! session()->has('guest_customer_id')) {
            return redirect()->route('login');
        }

        $customer = Customer::findOrFail(session('guest_customer_id'));

        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::where('email', $customer->email)->first();

        if ($user) {
            $user->update([
                'password' => Hash::make($request->password),
            ]);
        } else {
            $user = User::create([
                'name' => $customer->name ?? 'Customer',
                'email' => $customer->email,
                'password' => Hash::make($request->password),
                'email_verified_at' => now(),
            ]);
        }

        $customer->update(['user_id' => $user->id]);

        session()->forget(['guest_customer_id', 'guest_customer_email']);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('status', 'Your account has been created! You can now log in anytime with your email and password.');
    }
}
