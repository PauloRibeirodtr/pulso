<?php

use App\Models\AplicacaoPesquisa;
use App\Models\Estudante;
use App\Models\Pergunta;
use App\Models\Turma;
use App\Models\User;
use App\Models\UsuarioPermissao;
use App\StatusParticipacao;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function pesquisaAbertaParaEstudante(): array
{
    $turma = Turma::factory()->create();
    $estudante = Estudante::factory()->for($turma)->create();
    $aplicacao = AplicacaoPesquisa::factory()->for($turma)->create([
        'status' => 'aberta',
        'data_abertura' => now()->subDay(),
        'data_encerramento' => now()->addDays(3),
    ]);
    $participacao = $aplicacao->participacoes()->create(['estudante_id' => $estudante->id]);
    $pergunta = Pergunta::factory()->create();
    $primeira = $aplicacao->perguntas()->create([
        'pergunta_id' => $pergunta->id,
        'origem' => 'institucional',
        'ordem' => 1,
        'obrigatoria' => true,
        'texto_snapshot' => 'Sinto-me acolhido no IFMS?',
        'orientacao_escala_snapshot' => 'menor_maior_atencao',
        'valor_minimo_snapshot' => 1,
        'valor_maximo_snapshot' => 5,
    ]);
    $segunda = $aplicacao->perguntas()->create([
        'pergunta_id' => Pergunta::factory()->create()->id,
        'origem' => 'curso',
        'ordem' => 2,
        'obrigatoria' => false,
        'texto_snapshot' => 'Tenho apoio para estudar?',
        'orientacao_escala_snapshot' => 'menor_maior_atencao',
        'valor_minimo_snapshot' => 1,
        'valor_maximo_snapshot' => 5,
    ]);

    return [$estudante, $aplicacao, $participacao, $primeira, $segunda];
}

it('shows only the authenticated student own research', function () {
    [$estudante, $aplicacao, $participacao] = pesquisaAbertaParaEstudante();
    $outro = Estudante::factory()->for($estudante->turma)->create();
    $outraParticipacao = $aplicacao->participacoes()->create(['estudante_id' => $outro->id]);
    $servidor = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($servidor, 'usuario')->create();

    $this->actingAs($estudante->usuario)->get(route('estudante.dashboard'))
        ->assertOk()->assertSee($aplicacao->nome)->assertSee('Responder pesquisa');
    $this->actingAs($estudante->usuario)->get(route('estudante.pesquisas.show', $participacao))
        ->assertOk()->assertSee('Sinto-me acolhido no IFMS?');
    $this->actingAs($estudante->usuario)->get(route('estudante.pesquisas.show', $outraParticipacao))
        ->assertForbidden();
    $this->actingAs($servidor)->get(route('estudante.pesquisas.show', $participacao))
        ->assertForbidden();
});

it('saves progress and finalizes only after all required answers are present', function () {
    [$estudante, , $participacao, $primeira, $segunda] = pesquisaAbertaParaEstudante();

    $this->actingAs($estudante->usuario)->post(route('estudante.pesquisas.finish', $participacao), [
        'respostas' => [$segunda->id => 4],
    ])->assertSessionHasErrors('respostas');

    expect($participacao->respostas()->count())->toBe(0);

    $this->actingAs($estudante->usuario)->post(route('estudante.pesquisas.save', $participacao), [
        'respostas' => [$primeira->id => 2],
        'comentario' => 'Gostaria de mais espaços de estudo.',
        'solicitar_conversa' => true,
    ])->assertRedirectToRoute('estudante.pesquisas.show', $participacao);

    expect($participacao->fresh()->status)->toBe(StatusParticipacao::EmAndamento);
    expect($participacao->respostas()->sole()->valor_numerico)->toBe(2);
    expect($participacao->comentarioGeral()->sole()->comentario)->toBe('Gostaria de mais espaços de estudo.');
    expect($participacao->solicitacaoAcompanhamento()->exists())->toBeFalse();

    $this->actingAs($estudante->usuario)->post(route('estudante.pesquisas.finish', $participacao), [
        'respostas' => [$segunda->id => 5],
        'comentario' => 'Gostaria de mais espaços de estudo.',
        'solicitar_conversa' => true,
    ])->assertRedirectToRoute('estudante.dashboard');

    expect($participacao->fresh()->status)->toBe(StatusParticipacao::Finalizada);
    expect($participacao->respostas()->count())->toBe(2);
    expect($participacao->solicitacaoAcompanhamento()->sole()->status->value)->toBe('pendente');

    $this->actingAs($estudante->usuario)->post(route('estudante.pesquisas.save', $participacao), [
        'respostas' => [$primeira->id => 5],
    ])->assertForbidden();

    expect($participacao->respostas()->where('aplicacao_pergunta_id', $primeira->id)->sole()->valor_numerico)->toBe(2);
});

it('rejects answers from another application and values outside the snapshot scale', function () {
    [$estudante, $aplicacao, $participacao, $primeira] = pesquisaAbertaParaEstudante();
    $outraAplicacao = AplicacaoPesquisa::factory()->for($estudante->turma)->create();
    $perguntaExterna = $outraAplicacao->perguntas()->create([
        'pergunta_id' => Pergunta::factory()->create()->id,
        'origem' => 'temporaria',
        'ordem' => 1,
        'texto_snapshot' => 'Outra pesquisa?',
        'orientacao_escala_snapshot' => 'menor_maior_atencao',
        'valor_minimo_snapshot' => 1,
        'valor_maximo_snapshot' => 5,
    ]);

    $this->actingAs($estudante->usuario)->post(route('estudante.pesquisas.save', $participacao), [
        'respostas' => [$perguntaExterna->id => 3],
    ])->assertSessionHasErrors('respostas.'.$perguntaExterna->id);

    $this->actingAs($estudante->usuario)->post(route('estudante.pesquisas.save', $participacao), [
        'respostas' => [$primeira->id => 6],
    ])->assertSessionHasErrors('respostas.'.$primeira->id);

    expect($participacao->respostas()->count())->toBe(0);
    expect($aplicacao->perguntas()->count())->toBe(2);
});

it('blocks responses after the deadline or when the application is closed', function () {
    [$estudante, $aplicacao, $participacao, $primeira] = pesquisaAbertaParaEstudante();
    $aplicacao->update(['data_encerramento' => now()->subMinute()]);

    $this->actingAs($estudante->usuario)->get(route('estudante.pesquisas.show', $participacao))
        ->assertOk()->assertSee('não está disponível');
    $this->actingAs($estudante->usuario)->post(route('estudante.pesquisas.save', $participacao), [
        'respostas' => [$primeira->id => 3],
    ])->assertForbidden();

    $aplicacao->update(['data_encerramento' => now()->addDay(), 'status' => 'encerrada']);
    $this->actingAs($estudante->usuario)->post(route('estudante.pesquisas.finish', $participacao), [
        'respostas' => [$primeira->id => 3],
    ])->assertForbidden();

    expect($participacao->respostas()->count())->toBe(0);
});

it('allows finalization without optional answers or a conversation request', function () {
    [$estudante, , $participacao, $primeira] = pesquisaAbertaParaEstudante();

    $this->actingAs($estudante->usuario)->post(route('estudante.pesquisas.finish', $participacao), [
        'respostas' => [$primeira->id => 4],
        'comentario' => '',
    ])->assertRedirectToRoute('estudante.dashboard');

    expect($participacao->fresh()->status)->toBe(StatusParticipacao::Finalizada);
    expect($participacao->respostas()->count())->toBe(1);
    expect($participacao->comentarioGeral()->exists())->toBeFalse();
    expect($participacao->solicitacaoAcompanhamento()->exists())->toBeFalse();
});

it('blocks an academically inactive student from submitting answers', function () {
    [$estudante, , $participacao, $primeira] = pesquisaAbertaParaEstudante();
    $estudante->update(['situacao_academica' => 'inativo']);

    $this->actingAs($estudante->usuario)->post(route('estudante.pesquisas.save', $participacao), [
        'respostas' => [$primeira->id => 3],
    ])->assertForbidden();

    expect($participacao->respostas()->count())->toBe(0);
});
