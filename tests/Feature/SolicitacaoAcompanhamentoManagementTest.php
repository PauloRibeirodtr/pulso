<?php

use App\Models\AplicacaoPesquisa;
use App\Models\Curso;
use App\Models\Estudante;
use App\Models\SolicitacaoAcompanhamento;
use App\Models\Turma;
use App\Models\User;
use App\Models\UsuarioPermissao;
use App\StatusSolicitacaoAcompanhamento;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function pedidoDeConversa(Curso $curso, string $nome): SolicitacaoAcompanhamento
{
    $turma = Turma::factory()->for($curso)->create();
    $estudante = Estudante::factory()->for($turma)->create(['usuario_id' => User::factory()->estudante()->state(['name' => $nome])]);
    $aplicacao = AplicacaoPesquisa::factory()->for($turma)->create(['status' => 'encerrada']);
    $participacao = $aplicacao->participacoes()->create([
        'estudante_id' => $estudante->id,
        'status' => 'finalizada',
        'finalizado_em' => now(),
    ]);

    return $participacao->solicitacaoAcompanhamento()->create(['solicitado_em' => now()]);
}

it('lists only conversation requests within the server course and marks one as attended', function () {
    $curso = Curso::factory()->create();
    $outroCurso = Curso::factory()->create();
    $pedido = pedidoDeConversa($curso, 'Estudante Autorizado');
    $pedidoExterno = pedidoDeConversa($outroCurso, 'Estudante Externo');
    $servidor = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($servidor, 'usuario')->paraCurso($curso)->create();

    $this->actingAs($servidor)->get(route('solicitacoes.index'))
        ->assertOk()->assertSee('Estudante Autorizado')->assertDontSee('Estudante Externo');
    $this->actingAs($servidor)->patch(route('solicitacoes.attend', $pedidoExterno))->assertForbidden();
    $this->actingAs($servidor)->patch(route('solicitacoes.attend', $pedido))->assertRedirect();

    $pedido->refresh();
    expect($pedido->status)->toBe(StatusSolicitacaoAcompanhamento::Atendida)
        ->and($pedido->responsavel_usuario_id)->toBe($servidor->id)
        ->and($pedido->atendido_em)->not->toBeNull();

    $this->actingAs($servidor)->get(route('solicitacoes.index'))
        ->assertOk()->assertDontSee('Estudante Autorizado');
    $this->actingAs($servidor)->get(route('solicitacoes.index', ['status' => 'atendida']))
        ->assertOk()->assertSee('Estudante Autorizado');
    $this->actingAs($servidor)->patch(route('solicitacoes.attend', $pedido))->assertForbidden();
});

it('does not expose conversation requests to students', function () {
    $curso = Curso::factory()->create();
    $pedido = pedidoDeConversa($curso, 'Estudante Solicitante');
    $estudante = $pedido->participacao->estudante;

    $this->actingAs($estudante->usuario)->get(route('solicitacoes.index'))->assertForbidden();
    $this->actingAs($estudante->usuario)->patch(route('solicitacoes.attend', $pedido))->assertForbidden();
    expect($pedido->fresh()->status)->toBe(StatusSolicitacaoAcompanhamento::Pendente);
});
