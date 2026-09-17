<?php

use App\Models\Customer;
use App\Models\User;
use App\Notifications\MagicLoginLinkNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

it('dispatches temporary signed magic link notification expiring in 15 minutes', function () {
    Notification::fake();

    $response = $this->post('/magic-link', [
        'email' => 'customer@example.com',
    ]);

    $response->assertSessionHas('status');

    Notification::assertSentOnDemand(
        MagicLoginLinkNotification::class,
        function (MagicLoginLinkNotification $notification, array $channels, object $notifiable) {
            expect($notifiable->routes['mail'])->toBe('customer@example.com')
                ->and($notification->expiresInMinutes)->toBe(15)
                ->and($notification->signedUrl)->toContain('magic-link/verify')
                ->and($notification->signedUrl)->toContain('signature=');

            return true;
        }
    );
});

it('authenticates existing customer user via valid signed magic link and redirects to dashboard', function () {
    $user = User::factory()->create([
        'email' => 'buyer@example.com',
    ]);

    $signedUrl = URL::temporarySignedRoute(
        'magic-link.verify',
        now()->addMinutes(15),
        ['email' => 'buyer@example.com']
    );

    $response = $this->get($signedUrl);

    $response->assertRedirect('/dashboard');
    expect(Auth::check())->toBeTrue()
        ->and(Auth::id())->toBe($user->id);
});

it('rejects tampered or expired magic links', function () {
    $tamperedUrl = URL::temporarySignedRoute(
        'magic-link.verify',
        now()->addMinutes(15),
        ['email' => 'buyer@example.com']
    ).'&tampered=1';

    $response = $this->get($tamperedUrl);
    $response->assertRedirect(route('magic-link.create'));
    $response->assertSessionHasErrors('email');
    expect(Auth::check())->toBeFalse();
});

it('allows guest customer without user account to access orders and set password', function () {
    // 1. Create a guest customer with orders but NO user_id
    $customer = Customer::factory()->create([
        'email' => 'guestbuyer@example.com',
        'user_id' => null,
    ]);

    $signedUrl = URL::temporarySignedRoute(
        'magic-link.verify',
        now()->addMinutes(15),
        ['email' => 'guestbuyer@example.com']
    );

    // 2. Access via magic link
    $response = $this->get($signedUrl);
    $response->assertRedirect(route('customer.orders'));
    $response->assertSessionHas('guest_customer_id', $customer->id);

    // 3. View orders page as guest
    $ordersResponse = $this->get(route('customer.orders'));
    $ordersResponse->assertOk();
    $ordersResponse->assertSee('Guest access session active');

    // 4. View set-password screen
    $setPasswordScreen = $this->get(route('customer.set-password'));
    $setPasswordScreen->assertOk();

    // 5. Submit new password
    $setPasswordResponse = $this->post(route('customer.set-password.store'), [
        'password' => 'SecurePassword123!',
        'password_confirmation' => 'SecurePassword123!',
    ]);

    $setPasswordResponse->assertRedirect(route('dashboard'));

    // Customer record should now be linked to the newly provisioned user
    $customer->refresh();
    expect($customer->user_id)->not->toBeNull();

    $newUser = User::find($customer->user_id);
    expect($newUser)->not->toBeNull()
        ->and($newUser->email)->toBe('guestbuyer@example.com')
        ->and(Auth::id())->toBe($newUser->id);
});

it('automatically links or creates customer record during standard registration', function () {
    // Pre-existing customer from previous guest checkout
    $guestCustomer = Customer::factory()->create([
        'email' => 'newuser@example.com',
        'user_id' => null,
    ]);

    $response = $this->post('/register', [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'Password1234!',
        'password_confirmation' => 'Password1234!',
    ]);

    $response->assertRedirect('/dashboard');

    $guestCustomer->refresh();
    expect($guestCustomer->user_id)->not->toBeNull();

    $user = User::where('email', 'newuser@example.com')->first();
    expect($guestCustomer->user_id)->toBe($user->id)
        ->and($user->isCustomer())->toBeTrue();
});
