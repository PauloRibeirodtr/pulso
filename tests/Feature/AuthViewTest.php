<?php

use App\Models\User;

it('renders the guest authentication views', function (string $route, array $parameters = []) {
    $this->get(route($route, $parameters))->assertSuccessful();
})->with([
    'login' => ['login'],
    'forgot password' => ['password.request'],
    'reset password' => ['password.reset', ['token' => 'test-token']],
]);

it('renders the authenticated authentication views', function (string $route) {
    $user = new User([
        'name' => 'Test User',
        'identificador_institucional' => '1234567',
    ]);

    $this->actingAs($user)
        ->get(route($route))
        ->assertSuccessful();
})->with([
    'password confirmation' => ['password.confirm'],
    'password change' => ['password.change'],
]);
