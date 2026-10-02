<?php

use App\Models\Estudante;
use App\Models\Turma;
use App\Models\UsuarioPermissao;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(LazilyRefreshDatabase::class);

it('shows institutional identifier login without public registration', function () {
    $this->get(route('login'))
        ->assertSuccessful()
        ->assertSee('Matrícula ou SIAPE')
        ->assertSee('Recuperar senha de servidor')
        ->assertDontSee('Criar uma conta');

    $this->get('/register')->assertNotFound();
    $this->post('/register')->assertNotFound();
});

it('authenticates an active student using registration number and password', function () {
    $turma = Turma::factory()->create(['nome_amigavel' => 'ADM2026A']);
    $usuario = User::factory()->estudante()->create([
        'identificador_institucional' => '202600001',
        'password' => 'ADM2026A',
    ]);
    Estudante::factory()->for($usuario, 'usuario')->for($turma)->create();

    $this->post(route('login.store'), [
        'identificador_institucional' => '202600001',
        'password' => 'ADM2026A',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($usuario);

    expect($usuario->fresh()->ultimo_acesso_em)->not->toBeNull();
});

it('authenticates a server with an active permission using SIAPE', function () {
    $servidor = User::factory()->servidor()->create([
        'identificador_institucional' => '1234567',
        'password' => 'senha-segura',
    ]);
    UsuarioPermissao::factory()->for($servidor, 'usuario')->create();

    $this->post(route('login.store'), [
        'identificador_institucional' => '1234567',
        'password' => 'senha-segura',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($servidor);
});

it('authenticates a technical administrator without granting academic scope', function () {
    $administrador = User::factory()->administrador()->create([
        'identificador_institucional' => '7654321',
        'password' => 'senha-segura',
    ]);

    $this->post(route('login.store'), [
        'identificador_institucional' => '7654321',
        'password' => 'senha-segura',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($administrador);
    expect($administrador->permissoes)->toBeEmpty();
});

it('rejects inactive accounts and servers without an active permission', function () {
    $inativo = User::factory()->estudante()->inativo()->create([
        'password' => 'senha-segura',
    ]);
    $semPermissao = User::factory()->servidor()->create([
        'password' => 'senha-segura',
    ]);

    foreach ([$inativo, $semPermissao] as $usuario) {
        $this->post(route('login.store'), [
            'identificador_institucional' => $usuario->identificador_institucional,
            'password' => 'senha-segura',
        ])->assertSessionHasErrors('identificador_institucional');

        $this->assertGuest();
    }
});

it('ends an existing session after the account loses access', function () {
    $inativo = User::factory()->estudante()->inativo()->create();

    $this->actingAs($inativo)
        ->get(route('dashboard'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('identificador_institucional');

    $this->assertGuest();
});

it('allows an authenticated user to change their password optionally', function () {
    $usuario = User::factory()->estudante()->create(['password' => 'senha-atual']);

    $this->actingAs($usuario)
        ->put(route('user-password.update'), [
            'current_password' => 'senha-atual',
            'password' => 'nova-senha-segura',
            'password_confirmation' => 'nova-senha-segura',
        ])
        ->assertSessionHas('status', 'password-updated');

    expect(Hash::check('nova-senha-segura', $usuario->fresh()->password))->toBeTrue();
});

it('sends password recovery only to active servers and administrators', function () {
    Notification::fake();

    $servidor = User::factory()->servidor()->create([
        'email' => 'servidor@ifms.edu.br',
    ]);
    UsuarioPermissao::factory()->for($servidor, 'usuario')->create();
    $estudante = User::factory()->estudante()->create([
        'email' => 'estudante@ifms.edu.br',
    ]);

    $this->post(route('password.email'), ['email' => $servidor->email])
        ->assertSessionHas('status');

    $this->post(route('password.email'), ['email' => $estudante->email])
        ->assertSessionHasErrors('email');

    Notification::assertSentTo($servidor, ResetPassword::class);
    Notification::assertNotSentTo($estudante, ResetPassword::class);
});

it('allows an active authorized server to complete email password recovery', function () {
    $servidor = User::factory()->servidor()->create([
        'email' => 'recuperacao@ifms.edu.br',
        'password' => 'senha-antiga',
    ]);
    UsuarioPermissao::factory()->for($servidor, 'usuario')->create();
    $token = Password::broker('servidores')->createToken($servidor);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $servidor->email,
        'password' => 'nova-senha-segura',
        'password_confirmation' => 'nova-senha-segura',
    ])->assertRedirect(route('login'));

    $this->assertGuest();

    expect(Hash::check('nova-senha-segura', $servidor->fresh()->password))->toBeTrue();
});

it('does not require email verification to access the dashboard', function () {
    $usuario = User::factory()->estudante()->unverified()->create();

    $this->actingAs($usuario)
        ->get(route('dashboard'))
        ->assertRedirect(route('estudante.dashboard'));

    $this->actingAs($usuario)
        ->get(route('estudante.dashboard'))
        ->assertSuccessful();

    $this->get('/email/verify')->assertNotFound();
});
