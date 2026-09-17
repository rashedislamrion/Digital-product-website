<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('verifies exact role permissions matrix matching research section 4.3', function () {
    $superAdmin = User::where('email', 'admin@example.com')->first();
    $finance = User::where('email', 'finance@example.com')->first();
    $catalog = User::where('email', 'catalog@example.com')->first();
    $support = User::where('email', 'support@example.com')->first();
    $reviewer = User::where('email', 'reviewer@example.com')->first();

    // Super Admin has all 11 permissions
    expect($superAdmin->can('catalog.create_update'))->toBeTrue()
        ->and($superAdmin->can('catalog.publish_version'))->toBeTrue()
        ->and($superAdmin->can('orders.view_financials'))->toBeTrue()
        ->and($superAdmin->can('orders.issue_refund'))->toBeTrue()
        ->and($superAdmin->can('licenses.view_keys'))->toBeTrue()
        ->and($superAdmin->can('licenses.reset_revoke'))->toBeTrue()
        ->and($superAdmin->can('downloads.reset_limit'))->toBeTrue()
        ->and($superAdmin->can('reviews.moderate_publish'))->toBeTrue()
        ->and($superAdmin->can('support.manage_tickets'))->toBeTrue()
        ->and($superAdmin->can('webhooks.view_replay'))->toBeTrue()
        ->and($superAdmin->can('system.manage_settings'))->toBeTrue();

    // Finance Manager
    expect($finance->can('orders.view_financials'))->toBeTrue()
        ->and($finance->can('orders.issue_refund'))->toBeTrue()
        ->and($finance->can('catalog.create_update'))->toBeFalse()
        ->and($finance->can('licenses.view_keys'))->toBeFalse()
        ->and($finance->can('reviews.moderate_publish'))->toBeFalse()
        ->and($finance->can('system.manage_settings'))->toBeFalse();

    // Catalog Editor
    expect($catalog->can('catalog.create_update'))->toBeTrue()
        ->and($catalog->can('catalog.publish_version'))->toBeTrue()
        ->and($catalog->can('orders.view_financials'))->toBeFalse()
        ->and($catalog->can('licenses.view_keys'))->toBeFalse()
        ->and($catalog->can('reviews.moderate_publish'))->toBeFalse();

    // Support Agent
    expect($support->can('licenses.view_keys'))->toBeTrue()
        ->and($support->can('licenses.reset_revoke'))->toBeTrue()
        ->and($support->can('downloads.reset_limit'))->toBeTrue()
        ->and($support->can('support.manage_tickets'))->toBeTrue()
        ->and($support->can('orders.view_financials'))->toBeFalse()
        ->and($support->can('catalog.create_update'))->toBeFalse()
        ->and($support->can('reviews.moderate_publish'))->toBeFalse();

    // Review Moderator
    expect($reviewer->can('reviews.moderate_publish'))->toBeTrue()
        ->and($reviewer->can('catalog.create_update'))->toBeFalse()
        ->and($reviewer->can('orders.view_financials'))->toBeFalse()
        ->and($reviewer->can('licenses.view_keys'))->toBeFalse()
        ->and($reviewer->can('system.manage_settings'))->toBeFalse();
});

it('restricts customer accounts from accessing the Filament admin panel', function () {
    $customerUser = User::factory()->create([
        'email' => 'customer@example.com',
    ]);

    expect($customerUser->isStaff())->toBeFalse()
        ->and($customerUser->isCustomer())->toBeTrue();

    $response = $this->actingAs($customerUser)->get('/admin');
    $response->assertStatus(403);
});

it('allows Super Admin to access all administrative pages', function () {
    $superAdmin = User::where('email', 'admin@example.com')->first();

    $this->actingAs($superAdmin)->get('/admin/catalog-management')->assertOk();
    $this->actingAs($superAdmin)->get('/admin/finance-orders')->assertOk();
    $this->actingAs($superAdmin)->get('/admin/support-desk')->assertOk();
    $this->actingAs($superAdmin)->get('/admin/review-moderation')->assertOk();
    $this->actingAs($superAdmin)->get('/admin/system-settings')->assertOk();
});

it('restricts Finance Manager to finance pages only', function () {
    $finance = User::where('email', 'finance@example.com')->first();

    $this->actingAs($finance)->get('/admin/finance-orders')->assertOk();
    $this->actingAs($finance)->get('/admin/catalog-management')->assertForbidden();
    $this->actingAs($finance)->get('/admin/support-desk')->assertForbidden();
    $this->actingAs($finance)->get('/admin/review-moderation')->assertForbidden();
    $this->actingAs($finance)->get('/admin/system-settings')->assertForbidden();
});

it('restricts Catalog Editor to catalog pages only', function () {
    $catalog = User::where('email', 'catalog@example.com')->first();

    $this->actingAs($catalog)->get('/admin/catalog-management')->assertOk();
    $this->actingAs($catalog)->get('/admin/finance-orders')->assertForbidden();
    $this->actingAs($catalog)->get('/admin/support-desk')->assertForbidden();
    $this->actingAs($catalog)->get('/admin/review-moderation')->assertForbidden();
    $this->actingAs($catalog)->get('/admin/system-settings')->assertForbidden();
});

it('restricts Support Agent to support pages only', function () {
    $support = User::where('email', 'support@example.com')->first();

    $this->actingAs($support)->get('/admin/support-desk')->assertOk();
    $this->actingAs($support)->get('/admin/catalog-management')->assertForbidden();
    $this->actingAs($support)->get('/admin/finance-orders')->assertForbidden();
    $this->actingAs($support)->get('/admin/review-moderation')->assertForbidden();
    $this->actingAs($support)->get('/admin/system-settings')->assertForbidden();
});

it('restricts Review Moderator to review pages only', function () {
    $reviewer = User::where('email', 'reviewer@example.com')->first();

    $this->actingAs($reviewer)->get('/admin/review-moderation')->assertOk();
    $this->actingAs($reviewer)->get('/admin/catalog-management')->assertForbidden();
    $this->actingAs($reviewer)->get('/admin/finance-orders')->assertForbidden();
    $this->actingAs($reviewer)->get('/admin/support-desk')->assertForbidden();
    $this->actingAs($reviewer)->get('/admin/system-settings')->assertForbidden();
});

it('redirects staff members to /admin upon login from the storefront', function () {
    $response = $this->post('/login', [
        'email' => 'finance@example.com',
        'password' => 'password',
    ]);

    $response->assertRedirect('/admin');
});
