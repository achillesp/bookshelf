<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests see the homepage', function () {
    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Welcome'));
});

test('signed-in Readers visiting the homepage land on their Dashboard', function () {
    $reader = User::factory()->create();

    $response = $this->actingAs($reader)->get(route('home'));

    $response->assertRedirect(route('dashboard'));
});
