<?php

use App\Models\Campus;
use App\Models\Curso;
use App\Models\UsuarioPermissao;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('allows a campus scoped user to create a course in their campus', function () {
    $campus = Campus::factory()->create();
    $servidor = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($servidor, 'usuario')->paraCampus($campus)->create();

    $this->actingAs($servidor)
        ->post(route('cursos.store'), [
            'campus_id' => $campus->id,
            'nome' => '  Técnico em   Informática  ',
            'sigla' => 'info',
            'ativo' => true,
        ])
        ->assertRedirectToRoute('cursos.index')
        ->assertSessionHas('status');

    $curso = Curso::query()->sole();

    expect($curso)
        ->campus_id->toBe($campus->id)
        ->nome->toBe('Técnico em Informática')
        ->sigla->toBe('INFO');
});

it('rejects course creation outside the user campus scope', function () {
    $campusPermitido = Campus::factory()->create();
    $outroCampus = Campus::factory()->create();
    $servidor = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($servidor, 'usuario')->paraCampus($campusPermitido)->create();

    $this->actingAs($servidor)
        ->post(route('cursos.store'), [
            'campus_id' => $outroCampus->id,
            'nome' => 'Curso fora do escopo',
            'sigla' => 'CFE',
            'ativo' => true,
        ])
        ->assertSessionHasErrors('campus_id');

    expect(Curso::query()->count())->toBe(0);
});

it('limits courses to the user scope and allows updating their course', function () {
    $cursoPermitido = Curso::factory()->create(['nome' => 'Curso Permitido']);
    Curso::factory()->create(['nome' => 'Curso Oculto']);
    $servidor = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($servidor, 'usuario')->paraCurso($cursoPermitido)->create();

    $this->actingAs($servidor)
        ->get(route('cursos.index'))
        ->assertOk()
        ->assertSee('Curso Permitido')
        ->assertDontSee('Curso Oculto');

    $this->actingAs($servidor)
        ->put(route('cursos.update', $cursoPermitido), [
            'campus_id' => $cursoPermitido->campus_id,
            'nome' => 'Curso Atualizado',
            'sigla' => 'CA',
            'ativo' => false,
        ])
        ->assertRedirectToRoute('cursos.index');

    expect($cursoPermitido->fresh())
        ->nome->toBe('Curso Atualizado')
        ->ativo->toBeFalse();
});

it('prevents a course scoped user from creating courses or moving their course', function () {
    $curso = Curso::factory()->create();
    $outroCampus = Campus::factory()->create();
    $servidor = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($servidor, 'usuario')->paraCurso($curso)->create();

    $this->actingAs($servidor)->get(route('cursos.create'))->assertForbidden();

    $this->actingAs($servidor)
        ->put(route('cursos.update', $curso), [
            'campus_id' => $outroCampus->id,
            'nome' => $curso->nome,
            'sigla' => $curso->sigla,
            'ativo' => true,
        ])
        ->assertSessionHasErrors('campus_id');

    expect($curso->fresh()->campus_id)->not->toBe($outroCampus->id);
});

it('prevents changes to courses outside the user scope', function () {
    $cursoPermitido = Curso::factory()->create();
    $cursoOculto = Curso::factory()->create();
    $servidor = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($servidor, 'usuario')->paraCurso($cursoPermitido)->create();

    $this->actingAs($servidor)
        ->put(route('cursos.update', $cursoOculto), [
            'campus_id' => $cursoOculto->campus_id,
            'nome' => 'Alteração indevida',
            'sigla' => $cursoOculto->sigla,
            'ativo' => false,
        ])
        ->assertForbidden();

    expect($cursoOculto->fresh())
        ->nome->not->toBe('Alteração indevida')
        ->ativo->toBeTrue();
});
