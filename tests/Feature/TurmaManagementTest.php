<?php

use App\Models\Curso;
use App\Models\Turma;
use App\Models\UsuarioPermissao;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('allows a course scoped user to create and update a class in their course', function () {
    $curso = Curso::factory()->create();
    $servidor = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($servidor, 'usuario')->paraCurso($curso)->create();

    $this->actingAs($servidor)
        ->post(route('turmas.store'), [
            'curso_id' => $curso->id,
            'nome_amigavel' => '  INFO  2026 A  ',
            'codigo_academico' => '  12345  ',
            'ano_ingresso' => 2026,
            'periodo_ingresso' => '1º semestre',
            'ativo' => true,
        ])
        ->assertRedirectToRoute('turmas.index')
        ->assertSessionHas('status');

    $turma = Turma::query()->sole();

    expect($turma)
        ->curso_id->toBe($curso->id)
        ->nome_amigavel->toBe('INFO 2026 A')
        ->codigo_academico->toBe('12345');

    $this->actingAs($servidor)
        ->put(route('turmas.update', $turma), [
            'curso_id' => $curso->id,
            'nome_amigavel' => 'INFO 2026 A',
            'codigo_academico' => '12345',
            'ano_ingresso' => 2026,
            'periodo_ingresso' => '1º semestre',
            'ativo' => false,
        ])
        ->assertRedirectToRoute('turmas.index');

    expect($turma->fresh()->ativo)->toBeFalse();
});

it('rejects class creation outside the user course scope', function () {
    $cursoPermitido = Curso::factory()->create();
    $outroCurso = Curso::factory()->create();
    $servidor = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($servidor, 'usuario')->paraCurso($cursoPermitido)->create();

    $this->actingAs($servidor)
        ->post(route('turmas.store'), [
            'curso_id' => $outroCurso->id,
            'nome_amigavel' => 'Turma fora do escopo',
            'codigo_academico' => '99999',
            'ano_ingresso' => 2026,
            'periodo_ingresso' => '1',
            'ativo' => true,
        ])
        ->assertSessionHasErrors('curso_id');

    expect(Turma::query()->count())->toBe(0);
});

it('limits classes to the user course scope', function () {
    $cursoPermitido = Curso::factory()->create();
    $turmaPermitida = Turma::factory()->for($cursoPermitido)->create([
        'nome_amigavel' => 'Turma Permitida',
    ]);
    $turmaOculta = Turma::factory()->create([
        'nome_amigavel' => 'Turma Oculta',
    ]);
    $servidor = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($servidor, 'usuario')->paraCurso($cursoPermitido)->create();

    $this->actingAs($servidor)
        ->get(route('turmas.index'))
        ->assertOk()
        ->assertSee($turmaPermitida->nome_amigavel)
        ->assertDontSee($turmaOculta->nome_amigavel);
});

it('allows a campus scoped user to manage classes only in their campus', function () {
    $cursoPermitido = Curso::factory()->create();
    $outroCurso = Curso::factory()->create();
    $servidor = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($servidor, 'usuario')->paraCampus($cursoPermitido->campus)->create();

    $this->actingAs($servidor)
        ->post(route('turmas.store'), [
            'curso_id' => $cursoPermitido->id,
            'nome_amigavel' => 'Turma autorizada',
            'codigo_academico' => 'A-01',
            'ano_ingresso' => 2026,
            'periodo_ingresso' => '1',
            'ativo' => true,
        ])
        ->assertRedirectToRoute('turmas.index');

    $this->actingAs($servidor)
        ->post(route('turmas.store'), [
            'curso_id' => $outroCurso->id,
            'nome_amigavel' => 'Turma não autorizada',
            'codigo_academico' => 'B-01',
            'ano_ingresso' => 2026,
            'periodo_ingresso' => '1',
            'ativo' => true,
        ])
        ->assertSessionHasErrors('curso_id');

    expect(Turma::query()->pluck('nome_amigavel')->all())->toBe(['Turma autorizada']);
});

it('prevents changes to classes outside the user scope', function () {
    $cursoPermitido = Curso::factory()->create();
    $turmaOculta = Turma::factory()->create();
    $servidor = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($servidor, 'usuario')->paraCurso($cursoPermitido)->create();

    $this->actingAs($servidor)
        ->put(route('turmas.update', $turmaOculta), [
            'curso_id' => $turmaOculta->curso_id,
            'nome_amigavel' => 'Alteração indevida',
            'codigo_academico' => $turmaOculta->codigo_academico,
            'ano_ingresso' => $turmaOculta->ano_ingresso,
            'periodo_ingresso' => $turmaOculta->periodo_ingresso,
            'ativo' => false,
        ])
        ->assertForbidden();

    expect($turmaOculta->fresh())
        ->nome_amigavel->not->toBe('Alteração indevida')
        ->ativo->toBeTrue();
});
