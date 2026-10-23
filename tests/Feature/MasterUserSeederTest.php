<?php

use App\Models\AplicacaoPesquisa;
use App\Models\Campus;
use App\Models\Curso;
use App\Models\Dimensao;
use App\Models\Estudante;
use App\Models\ModeloQuestionario;
use App\Models\Pergunta;
use App\Models\RegistroAuditoria;
use App\Models\SolicitacaoAcompanhamento;
use App\Models\Turma;
use App\Models\User;
use App\Models\UsuarioPermissao;
use App\NivelEscopo;
use App\TipoUsuario;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\MasterUserSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['auth.master' => [
        'username' => 'master',
        'password' => 'senha-inicial-segura',
        'name' => 'Administrador inicial',
        'email' => 'master@ifms.edu.br',
    ]]);
});

it('seeds an active administrator with a hashed password and institutional permission', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::query()->sole();
    $permission = $user->permissoes()->sole();

    expect($user->identificador_institucional)->toBe('master')
        ->and($user->name)->toBe('Administrador inicial')
        ->and($user->email)->toBe('master@ifms.edu.br')
        ->and($user->tipo_usuario)->toBe(TipoUsuario::Admin)
        ->and($user->ativo)->toBeTrue()
        ->and($user->password)->not->toBe('senha-inicial-segura')
        ->and(Hash::check('senha-inicial-segura', $user->password))->toBeTrue()
        ->and($permission->nivel_escopo)->toBe(NivelEscopo::Instituicao)
        ->and($permission->campus_id)->toBeNull()
        ->and($permission->curso_id)->toBeNull()
        ->and($permission->ativo)->toBeTrue();

    foreach ([Campus::class, Curso::class, Turma::class, Estudante::class, User::class, Dimensao::class, Pergunta::class, ModeloQuestionario::class, AplicacaoPesquisa::class, SolicitacaoAcompanhamento::class, RegistroAuditoria::class] as $model) {
        expect($user->can('viewAny', $model))->toBeTrue();
    }

    $this->post(route('login.store'), [
        'identificador_institucional' => 'master',
        'password' => 'senha-inicial-segura',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

it('preserves the account and permissions when seeded again', function () {
    $this->seed(MasterUserSeeder::class);

    $user = User::query()->sole();
    $user->update(['password' => 'senha-alterada', 'ativo' => false]);
    $user->permissoes()->update(['ativo' => false]);
    $passwordHash = $user->fresh()->password;

    $this->seed(MasterUserSeeder::class);

    expect(User::query()->count())->toBe(1)
        ->and(UsuarioPermissao::query()->count())->toBe(1)
        ->and($user->fresh()->password)->toBe($passwordHash)
        ->and($user->fresh()->ativo)->toBeFalse()
        ->and($user->permissoes()->sole()->ativo)->toBeFalse();
});

it('does not promote an existing account with the configured username', function () {
    $user = User::factory()->estudante()->create(['identificador_institucional' => 'master']);
    $originalAttributes = $user->fresh()->getAttributes();

    $this->seed(MasterUserSeeder::class);

    expect($user->fresh()->getAttributes())->toBe($originalAttributes)
        ->and($user->permissoes()->exists())->toBeFalse();
});

it('skips creation when bootstrap credentials are not configured', function () {
    config(['auth.master.username' => null, 'auth.master.password' => null]);

    $this->seed(MasterUserSeeder::class);

    expect(User::query()->exists())->toBeFalse()
        ->and(UsuarioPermissao::query()->exists())->toBeFalse();
});

it('rejects incomplete or invalid configuration without creating an account', function (string $key, mixed $value) {
    config(["auth.master.{$key}" => $value]);

    expect(fn () => $this->seed(MasterUserSeeder::class))->toThrow(ValidationException::class);

    expect(User::query()->exists())->toBeFalse()
        ->and(UsuarioPermissao::query()->exists())->toBeFalse();
})->with([
    'missing username' => ['username', null],
    'missing password' => ['password', null],
    'short password' => ['password', '123'],
    'username with whitespace' => ['username', ' master '],
    'invalid email' => ['email', 'invalid'],
]);

it('allows the initial administrator to have no recovery email', function (?string $email) {
    config(['auth.master.email' => $email]);

    $this->seed(MasterUserSeeder::class);

    expect(User::query()->sole()->email)->toBeNull();
})->with([null, '']);
