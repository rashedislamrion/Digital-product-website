<?php

use App\Models\User;
use Database\Seeders\AdminUserSeeder;

it('renders the public storefront homepage with 200 ok', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

it('renders the filament admin panel login page with 200 ok', function () {
    $response = $this->get('/admin/login');

    $response->assertStatus(200);
});

it('allows seeded super admin to access the filament admin panel', function () {
    $this->seed(AdminUserSeeder::class);

    $admin = User::where('email', 'admin@example.com')->first();

    $response = $this->actingAs($admin)->get('/admin');

    $response->assertStatus(200);
});
