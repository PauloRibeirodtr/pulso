<?php

use App\Models\Curso;
use App\Models\Dimensao;
use App\Models\ModeloQuestionario;
use App\Models\Pergunta;
use App\Models\User;
use App\Models\UsuarioPermissao;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('limits course questions to the user scope', function () {
    $curso = Curso::factory()->create();
    $outroCurso = Curso::factory()->create();
    $dimensao = Dimensao::factory()->create();
    $user = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($user, 'usuario')->paraCurso($curso)->create();

    $payload = [
        'dimensao_id' => $dimensao->id,
        'curso_origem_id' => $curso->id,
        'texto' => '  Tenho recursos para estudar? ',
        'sensivel' => false,
        'ativo' => true,
    ];

    $this->actingAs($user)->post(route('perguntas.store'), $payload)
        ->assertRedirectToRoute('perguntas.index');

    expect(Pergunta::query()->sole()->texto)->toBe('Tenho recursos para estudar?');

    $this->actingAs($user)->post(route('perguntas.store'), [...$payload, 'curso_origem_id' => $outroCurso->id])
        ->assertSessionHasErrors('curso_origem_id');

    $this->actingAs($user)->post(route('perguntas.store'), [...$payload, 'curso_origem_id' => null])
        ->assertSessionHasErrors('curso_origem_id');
});

it('freezes questions included in a published model', function () {
    $user = User::factory()->administrador()->create();
    UsuarioPermissao::factory()->for($user, 'usuario')->create();
    $pergunta = Pergunta::factory()->create();
    $modelo = ModeloQuestionario::factory()->create(['publicado_em' => now()]);
    $modelo->itens()->create(['pergunta_id' => $pergunta->id, 'ordem' => 1]);

    $this->actingAs($user)->put(route('perguntas.update', $pergunta), [
        'dimensao_id' => $pergunta->dimensao_id,
        'curso_origem_id' => null,
        'texto' => 'Mudança indevida',
        'sensivel' => false,
        'ativo' => true,
    ])->assertForbidden();

    expect($pergunta->fresh()->texto)->not->toBe('Mudança indevida');
});
