<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class CustomerOrderAccessController extends Controller
{
    /**
     * Display the customer order library (for authenticated user or verified guest session).
     */
    public function orders(Request $request): View|RedirectResponse
    {
        $customer = null;
        $isGuest = false;

        if (Auth::check()) {
            $user = Auth::user();
            $customer = $user->customer ?? Customer::where('email', $user->email)->first();
        } elseif (session()->has('guest_customer_id')) {
            $customer = Customer::find(session('guest_customer_id'));
            $isGuest = true;
        }

        if (! $customer) {
            return redirect()->route('magic-link.create')
                ->withErrors(['email' => 'Please sign in or request a magic link to access your purchases.']);
        }

        $orders = $customer->orders()
            ->with(['items.product', 'items.license', 'items.downloadGrants'])
            ->latest()
            ->get();

        return view('customer.orders', [
            'customer' => $customer,
            'orders' => $orders,
            'isGuest' => $isGuest || is_null($customer->user_id),
        ]);
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
