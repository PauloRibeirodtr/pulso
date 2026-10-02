<?php

use App\Models\Curso;
use App\Models\Estudante;
use App\Models\Turma;
use App\Models\UsuarioPermissao;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

it('allows a server with course access to reset the student password', function () {
    $curso = Curso::factory()->create();
    $turma = Turma::factory()->for($curso)->create([
        'nome_amigavel' => 'INFO2026A',
    ]);
    $estudante = Estudante::factory()->for($turma)->create();
    $servidor = User::factory()->servidor()->create();
    UsuarioPermissao::factory()
        ->for($servidor, 'usuario')
        ->paraCurso($curso)
        ->create();

    $this->actingAs($servidor)
        ->from('/dashboard')
        ->put(route('estudantes.password.reset', $estudante))
        ->assertRedirect('/dashboard')
        ->assertSessionHas('status');

    expect(Hash::check('INFO2026A', $estudante->usuario->fresh()->password))->toBeTrue();
});

it('allows campus and institution permissions to reset student passwords', function (string $scope) {
    $curso = Curso::factory()->create();
    $turma = Turma::factory()->for($curso)->create([
        'nome_amigavel' => 'ADM2026B',
    ]);
    $estudante = Estudante::factory()->for($turma)->create();
    $servidor = User::factory()->servidor()->create();
    $permissao = UsuarioPermissao::factory()->for($servidor, 'usuario');

    if ($scope === 'campus') {
        $permissao->paraCampus($curso->campus)->create();
    } else {
        $permissao->create();
    }

    $this->actingAs($servidor)
        ->put(route('estudantes.password.reset', $estudante))
        ->assertRedirect();

    expect(Hash::check('ADM2026B', $estudante->usuario->fresh()->password))->toBeTrue();
})->with(['campus', 'instituicao']);

it('denies password reset outside the server authorized scope', function () {
    $cursoAutorizado = Curso::factory()->create();
    $outraTurma = Turma::factory()->create();
    $estudante = Estudante::factory()->for($outraTurma)->create();
    $servidor = User::factory()->servidor()->create();
    UsuarioPermissao::factory()
        ->for($servidor, 'usuario')
        ->paraCurso($cursoAutorizado)
        ->create();

    $senhaAnterior = $estudante->usuario->password;

    $this->actingAs($servidor)
        ->put(route('estudantes.password.reset', $estudante))
        ->assertForbidden();

    expect($estudante->usuario->fresh()->password)->toBe($senhaAnterior);
});

it('denies password reset when the permission is inactive', function () {
    $curso = Curso::factory()->create();
    $outroCurso = Curso::factory()->create();
    $estudante = Estudante::factory()->for(Turma::factory()->for($curso))->create();
    $servidor = User::factory()->servidor()->create();
    UsuarioPermissao::factory()
        ->for($servidor, 'usuario')
        ->paraCurso($outroCurso)
        ->create();
    UsuarioPermissao::factory()
        ->for($servidor, 'usuario')
        ->paraCurso($curso)
        ->create(['ativo' => false]);

    $this->actingAs($servidor)
        ->put(route('estudantes.password.reset', $estudante))
        ->assertForbidden();
});
