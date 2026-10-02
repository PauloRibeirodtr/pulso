<?php

use App\Models\Campus;
use App\Models\UsuarioPermissao;
use App\Models\User;
use App\NivelEscopo;
use App\TipoUsuario;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

it('creates a server with an initial institutional permission and a hashed password', function () {
    $gestor = User::factory()->administrador()->create();
    UsuarioPermissao::factory()->for($gestor, 'usuario')->create();

    $this->actingAs($gestor)->post(route('servidores.store'), [
        'siape' => ' 12345678 ',
        'nome' => '  Maria   Oliveira ',
        'email' => 'MARIA@IFMS.EDU.BR',
        'password' => 'senha-segura-123',
        'password_confirmation' => 'senha-segura-123',
        'ativo' => true,
        'nivel_escopo' => 'instituicao',
        'papel' => 'gestao institucional',
    ])->assertRedirectToRoute('servidores.index');

    $servidor = User::query()->where('identificador_institucional', '12345678')->sole();
    expect($servidor->tipo_usuario)->toBe(TipoUsuario::Servidor);
    expect($servidor->name)->toBe('Maria Oliveira');
    expect($servidor->email)->toBe('maria@ifms.edu.br');
    expect(Hash::check('senha-segura-123', $servidor->password))->toBeTrue();
    expect($servidor->permissoes()->sole()->nivel_escopo)->toBe(NivelEscopo::Instituicao);
});

it('lists servers and renders create and edit forms for institutional managers', function () {
    $gestor = User::factory()->administrador()->create();
    UsuarioPermissao::factory()->for($gestor, 'usuario')->create();
    $servidor = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($servidor, 'usuario')->create();

    $this->actingAs($gestor)->get(route('servidores.index'))
        ->assertOk()->assertSee($servidor->name)->assertDontSee('Conta própria');
    $this->actingAs($gestor)->get(route('servidores.create'))
        ->assertOk()->assertSee('Primeira permissão');
    $this->actingAs($gestor)->get(route('servidores.edit', $servidor))
        ->assertOk()->assertSee('Permissões cadastradas');
});

it('updates server data and can deactivate the account without changing the password', function () {
    $gestor = User::factory()->administrador()->create();
    UsuarioPermissao::factory()->for($gestor, 'usuario')->create();
    $servidor = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($servidor, 'usuario')->create();
    $senhaAnterior = $servidor->password;

    $this->actingAs($gestor)->put(route('servidores.update', $servidor), [
        'siape' => $servidor->identificador_institucional,
        'nome' => 'Novo Nome',
        'email' => 'novo@ifms.edu.br',
        'ativo' => false,
    ])->assertRedirectToRoute('servidores.edit', $servidor);

    $servidor->refresh();

    expect($servidor->name)->toBe('Novo Nome');
    expect($servidor->email)->toBe('novo@ifms.edu.br');
    expect($servidor->ativo)->toBeFalse();
    expect($servidor->password)->toBe($senhaAnterior);
});

it('restricts server management to institutional scope', function () {
    $campus = Campus::factory()->create();
    $gestorCampus = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($gestorCampus, 'usuario')->paraCampus($campus)->create();
    $servidor = User::factory()->servidor()->create();

    $this->actingAs($gestorCampus)->get(route('servidores.index'))->assertForbidden();
    $this->actingAs($gestorCampus)->get(route('servidores.create'))->assertForbidden();
    $this->actingAs($gestorCampus)->put(route('servidores.update', $servidor), [
        'siape' => $servidor->identificador_institucional,
        'nome' => 'Indevido', 'ativo' => false,
    ])->assertForbidden();

    expect($servidor->fresh()->name)->not->toBe('Indevido');
});

it('does not edit student or administrator accounts through server routes', function () {
    $gestor = User::factory()->administrador()->create();
    UsuarioPermissao::factory()->for($gestor, 'usuario')->create();
    $estudante = User::factory()->estudante()->create();

    $this->actingAs($gestor)->get(route('servidores.edit', $estudante))->assertForbidden();
    $this->actingAs($gestor)->get(route('servidores.edit', $gestor))->assertForbidden();
});

it('rejects duplicate SIAPE during registration', function () {
    $gestor = User::factory()->administrador()->create();
    UsuarioPermissao::factory()->for($gestor, 'usuario')->create();
    $servidor = User::factory()->servidor()->create(['identificador_institucional' => '99887766']);

    $this->actingAs($gestor)->post(route('servidores.store'), [
        'siape' => '99887766', 'nome' => 'Outro servidor',
        'password' => 'senha-segura-123',
        'password_confirmation' => 'senha-segura-123',
        'ativo' => true,
        'nivel_escopo' => 'instituicao',
        'papel' => 'gestao',
    ])->assertSessionHasErrors('siape');

    expect(User::query()->where('identificador_institucional', $servidor->identificador_institucional)->count())->toBe(1);
});
