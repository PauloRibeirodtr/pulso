<?php

use App\Models\User;
use App\TipoUsuario;

it('redirects an authenticated user to the dashboard for their profile', function (
    TipoUsuario $tipoUsuario,
    string $dashboardRoute,
) {
    $user = new User([
        'name' => 'Test User',
        'identificador_institucional' => '1234567',
        'tipo_usuario' => $tipoUsuario,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route($dashboardRoute));

    $this->actingAs($user)
        ->get(route($dashboardRoute))
        ->assertSuccessful()
        ->assertSee('Pulso IFMS')
        ->assertSee('Test User');
})->with([
    'student' => [TipoUsuario::Estudante, 'estudante.dashboard'],
    'server' => [TipoUsuario::Admin, 'servidor.dashboard'],
]);
