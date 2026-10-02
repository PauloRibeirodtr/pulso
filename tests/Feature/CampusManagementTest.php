<?php

use App\Models\Campus;
use App\Models\Curso;
use App\Models\UsuarioPermissao;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('allows an institution user to create and update a campus', function () {
    $servidor = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($servidor, 'usuario')->create();

    $this->actingAs($servidor)
        ->post(route('campi.store'), [
            'nome' => '  Campus   Campo Grande  ',
            'sigla' => 'cg',
            'ativo' => true,
        ])
        ->assertRedirectToRoute('campi.index')
        ->assertSessionHas('status');

    $campus = Campus::query()->sole();

    expect($campus)
        ->nome->toBe('Campus Campo Grande')
        ->sigla->toBe('CG')
        ->ativo->toBeTrue();

    $this->actingAs($servidor)
        ->put(route('campi.update', $campus), [
            'nome' => 'Campus Campo Grande',
            'sigla' => 'CG',
            'ativo' => false,
        ])
        ->assertRedirectToRoute('campi.index');

    expect($campus->fresh()->ativo)->toBeFalse();
});

it('limits the campus list to the user academic scope', function () {
    $campusPermitido = Campus::factory()->create(['nome' => 'Campus Permitido']);
    Campus::factory()->create(['nome' => 'Campus Oculto']);
    $curso = Curso::factory()->for($campusPermitido)->create();
    $servidor = User::factory()->servidor()->create();
    UsuarioPermissao::factory()
        ->for($servidor, 'usuario')
        ->paraCurso($curso)
        ->create();

    $this->actingAs($servidor)
        ->get(route('campi.index'))
        ->assertOk()
        ->assertSee('Campus Permitido')
        ->assertDontSee('Campus Oculto');
});

it('does not allow campus scoped users to change campus records', function () {
    $campus = Campus::factory()->create();
    $servidor = User::factory()->servidor()->create();
    UsuarioPermissao::factory()
        ->for($servidor, 'usuario')
        ->paraCampus($campus)
        ->create();

    $this->actingAs($servidor)
        ->get(route('campi.edit', $campus))
        ->assertForbidden();

    $this->actingAs($servidor)
        ->put(route('campi.update', $campus), [
            'nome' => 'Nome indevido',
            'sigla' => 'NI',
            'ativo' => false,
        ])
        ->assertForbidden();

    expect($campus->fresh())
        ->nome->not->toBe('Nome indevido')
        ->ativo->toBeTrue();
});

it('validates campus uniqueness', function () {
    Campus::factory()->create([
        'nome' => 'Campus Dourados',
        'sigla' => 'DR',
    ]);
    $servidor = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($servidor, 'usuario')->create();

    $this->actingAs($servidor)
        ->post(route('campi.store'), [
            'nome' => 'Campus Dourados',
            'sigla' => 'DR',
            'ativo' => true,
        ])
        ->assertSessionHasErrors(['nome', 'sigla']);

    expect(Campus::query()->count())->toBe(1);
});
