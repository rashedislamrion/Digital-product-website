<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use App\Notifications\MagicLoginLinkNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class MagicLinkController extends Controller
{
    /**
     * Show the magic link request form.
     */
    public function create(): View
    {
        return view('auth.magic-link');
    }

    /**
     * Generate temporary signed URL and dispatch notification.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = strtolower(trim($request->email));

        // Generate HMAC-SHA256 signed URL valid for 15 minutes
        $signedUrl = URL::temporarySignedRoute(
            'magic-link.verify',
            now()->addMinutes(15),
            ['email' => $email]
        );

        Notification::route('mail', $email)->notify(
            new MagicLoginLinkNotification($signedUrl, 15)
        );

        return back()->with('status', 'A secure sign-in link has been sent to your email address. It will expire in 15 minutes.');
    }

    /**
     * Verify the temporary signed URL and authenticate the user or guest customer.
     */
    public function verify(Request $request): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            return redirect()->route('magic-link.create')
                ->withErrors(['email' => 'This magic sign-in link has expired or is invalid. Please request a new one.']);
        }

        $email = strtolower(trim((string) $request->query('email')));

        $user = User::where('email', $email)->first();

        if ($user) {
            // If staff member accesses via magic link, direct to admin
            if ($user->isStaff()) {
                Auth::login($user);
                $request->session()->regenerate();

                return redirect()->intended('/admin');
            }

            // Customer user
            Auth::login($user);
            $request->session()->regenerate();

            // Link any orphaned customer record for this email
            $customer = Customer::where('email', $email)->first();
            if ($customer && ! $customer->user_id) {
                $customer->update(['user_id' => $user->id]);
            }

            return redirect()->intended(route('dashboard'));
        }

        // Check for guest customer purchase
        $customer = Customer::where('email', $email)->first();

        if ($customer) {
            $request->session()->regenerate();
            session([
                'guest_customer_id' => $customer->id,
                'guest_customer_email' => $customer->email,
            ]);

            return redirect()->route('customer.orders');
        }

        return redirect()->route('magic-link.create')
            ->withErrors(['email' => 'No active customer account or order found for this email address.']);
    }
}
