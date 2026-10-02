<?php

use App\Models\Curso;
use App\Models\ModeloQuestionario;
use App\Models\Pergunta;
use App\Models\User;
use App\Models\UsuarioPermissao;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('composes publishes previews and duplicates an institutional model', function () {
    $user = User::factory()->administrador()->create();
    UsuarioPermissao::factory()->for($user, 'usuario')->create();
    $pergunta = Pergunta::factory()->create(['texto' => 'Sinto-me acolhido no campus?']);

    $this->actingAs($user)->post(route('modelos-questionario.store'), [
        'nome' => 'Modelo institucional 1',
        'escopo_modelo' => 'institucional',
        'curso_id' => null,
        'tipo' => 'monitoramento',
        'descricao' => null,
        'ativo' => true,
    ])->assertRedirect();

    $modelo = ModeloQuestionario::query()->sole();

    $this->actingAs($user)->post(route('modelos-questionario.publish', $modelo))
        ->assertSessionHasErrors('modelo');

    $this->actingAs($user)->post(route('modelos-questionario.perguntas.store', $modelo), [
        'pergunta_id' => $pergunta->id,
        'obrigatoria' => true,
    ])->assertRedirect();

    $this->actingAs($user)->get(route('modelos-questionario.show', $modelo))
        ->assertOk()->assertSee('Sinto-me acolhido no campus?');

    $this->actingAs($user)->post(route('modelos-questionario.publish', $modelo))
        ->assertRedirect();

    expect($modelo->fresh()->publicado_em)->not->toBeNull();

    $this->actingAs($user)->patch(route('modelos-questionario.status', $modelo), ['ativo' => false])
        ->assertRedirect();

    expect($modelo->fresh()->ativo)->toBeFalse();

    $this->actingAs($user)->post(route('modelos-questionario.perguntas.store', $modelo), [
        'pergunta_id' => $pergunta->id,
        'obrigatoria' => true,
    ])->assertForbidden();

    $this->actingAs($user)->post(route('modelos-questionario.duplicate', $modelo), ['nome' => 'Modelo institucional 2'])
        ->assertRedirect();

    expect(ModeloQuestionario::query()->where('nome', 'Modelo institucional 2')->firstOrFail())
        ->publicado_em->toBeNull()
        ->itens()->count()->toBe(1);
});

it('rejects questions from another course in a course model', function () {
    $curso = Curso::factory()->create();
    $outroCurso = Curso::factory()->create();
    $user = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($user, 'usuario')->paraCurso($curso)->create();
    $modelo = ModeloQuestionario::factory()->create(['curso_id' => $curso->id, 'escopo_modelo' => 'curso']);
    $pergunta = Pergunta::factory()->create(['curso_origem_id' => $outroCurso->id]);

    $this->actingAs($user)->post(route('modelos-questionario.perguntas.store', $modelo), [
        'pergunta_id' => $pergunta->id,
        'obrigatoria' => true,
    ])->assertSessionHasErrors('pergunta_id');

    expect($modelo->itens()->count())->toBe(0);
});

it('renders the questionnaire management pages for institutional staff', function () {
    $user = User::factory()->administrador()->create();
    UsuarioPermissao::factory()->for($user, 'usuario')->create();
    $pergunta = Pergunta::factory()->create();
    $modelo = ModeloQuestionario::factory()->create();

    $this->actingAs($user)->get(route('dimensoes.index'))->assertOk();
    $this->actingAs($user)->get(route('dimensoes.create'))->assertOk();
    $this->actingAs($user)->get(route('perguntas.index'))->assertOk();
    $this->actingAs($user)->get(route('perguntas.create'))->assertOk();
    $this->actingAs($user)->get(route('perguntas.edit', $pergunta))->assertOk();
    $this->actingAs($user)->get(route('modelos-questionario.index'))->assertOk();
    $this->actingAs($user)->get(route('modelos-questionario.create'))->assertOk();
    $this->actingAs($user)->get(route('modelos-questionario.edit', $modelo))->assertOk();
});
