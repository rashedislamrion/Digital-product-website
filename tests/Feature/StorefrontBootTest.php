<?php

it('renders the public storefront homepage with 200 ok', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

it('renders the filament admin panel login page with 200 ok', function () {
    $response = $this->get('/admin/login');

    $response->assertStatus(200);
});
