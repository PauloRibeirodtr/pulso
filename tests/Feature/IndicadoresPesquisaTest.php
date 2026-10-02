<?php

use App\Models\AplicacaoPesquisa;
use App\Models\Curso;
use App\Models\Estudante;
use App\Models\Pergunta;
use App\Models\Turma;
use App\Models\User;
use App\Models\UsuarioPermissao;
use App\Services\IndicadoresPesquisa;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function aplicacaoComRespostas(Turma $turma, array $valores, string $nome): AplicacaoPesquisa
{
    $aplicacao = AplicacaoPesquisa::factory()->for($turma)->create([
        'nome' => $nome,
        'status' => 'encerrada',
    ]);
    $pergunta = Pergunta::factory()->create();
    $item = $aplicacao->perguntas()->create([
        'pergunta_id' => $pergunta->id,
        'origem' => 'institucional',
        'ordem' => 1,
        'texto_snapshot' => 'Como você avalia a experiência?',
        'orientacao_escala_snapshot' => 'menor_maior_atencao',
        'valor_minimo_snapshot' => 1,
        'valor_maximo_snapshot' => 5,
    ]);

    foreach ($valores as $valor) {
        $estudante = Estudante::factory()->for($turma)->create();
        $participacao = $aplicacao->participacoes()->create([
            'estudante_id' => $estudante->id,
            'status' => 'finalizada',
            'finalizado_em' => now(),
        ]);
        $participacao->respostas()->create([
            'aplicacao_pergunta_id' => $item->id,
            'valor_numerico' => $valor,
            'respondido_em' => now(),
        ]);
    }

    return $aplicacao;
}

it('calculates institutional averages only from finalized answers', function () {
    $curso = Curso::factory()->create(['sigla' => 'CURSO-A']);
    $turma = Turma::factory()->for($curso)->create();
    $aplicacao = aplicacaoComRespostas($turma, [3, 4, 5], 'Rodada de outubro');
    $item = $aplicacao->perguntas()->sole();
    $naoFinalizada = $aplicacao->participacoes()->create([
        'estudante_id' => Estudante::factory()->for($turma)->create()->id,
        'status' => 'em_andamento',
    ]);
    $naoFinalizada->respostas()->create([
        'aplicacao_pergunta_id' => $item->id,
        'valor_numerico' => 1,
        'respondido_em' => now(),
    ]);
    $indicadores = app(IndicadoresPesquisa::class);

    $resumo = $indicadores->resumoInstitucional([$aplicacao->id]);
    expect((int) $resumo->estudantes)->toBe(3)
        ->and((int) $resumo->respostas)->toBe(3)
        ->and($indicadores->mediaPublicavel($resumo))->toBe(4.0);

    $servidor = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($servidor, 'usuario')->paraCurso($curso)->create();
    $this->actingAs($servidor)->get(route('indicadores.index'))
        ->assertOk()->assertSee('Rodada de outubro')->assertSee('4,00');
    $this->actingAs($servidor)->get(route('indicadores.show', $aplicacao))
        ->assertOk()->assertSee('4,00');
});

it('suppresses small-group averages and limits access by course', function () {
    $curso = Curso::factory()->create(['sigla' => 'CURSO-A']);
    $outroCurso = Curso::factory()->create(['sigla' => 'CURSO-B']);
    $aplicacao = aplicacaoComRespostas(Turma::factory()->for($curso)->create(), [2, 4], 'Aplicação autorizada');
    $outraAplicacao = aplicacaoComRespostas(Turma::factory()->for($outroCurso)->create(), [5, 5, 5], 'Aplicação reservada');
    $servidor = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($servidor, 'usuario')->paraCurso($curso)->create();

    expect(app(IndicadoresPesquisa::class)->mediaPublicavel(
        app(IndicadoresPesquisa::class)->resumoInstitucional([$aplicacao->id])
    ))->toBeNull();

    $this->actingAs($servidor)->get(route('indicadores.index'))
        ->assertOk()->assertSee('Aplicação autorizada')
        ->assertDontSee('Aplicação reservada')->assertSee('Restrita');
    $this->actingAs($servidor)->get(route('indicadores.show', $outraAplicacao))->assertForbidden();
    $this->actingAs($servidor)->get(route('indicadores.index', ['curso_id' => $outroCurso->id]))
        ->assertOk()->assertDontSee('Aplicação reservada');
    $this->actingAs(Estudante::factory()->create()->usuario)->get(route('indicadores.index'))->assertForbidden();
});

it('shows historical applications of the same class and type', function () {
    $curso = Curso::factory()->create();
    $turma = Turma::factory()->for($curso)->create();
    $anterior = aplicacaoComRespostas($turma, [2, 3, 4], 'Rodada anterior');
    $anterior->update(['data_abertura' => now()->subDays(45)]);
    $atual = aplicacaoComRespostas($turma, [3, 4, 5], 'Rodada atual');
    $servidor = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($servidor, 'usuario')->paraCurso($curso)->create();

    $this->actingAs($servidor)->get(route('indicadores.show', $atual))
        ->assertOk()->assertSee('Rodada anterior')->assertSee('Rodada atual')
        ->assertSee('3,00')->assertSee('4,00');
});

it('keeps the historical dimension label stable once a question is applied', function () {
    $curso = Curso::factory()->create();
    $turma = Turma::factory()->for($curso)->create();
    $aplicacao = aplicacaoComRespostas($turma, [3, 4, 5], 'Rodada histórica');
    $dimensao = $aplicacao->perguntas()->sole()->pergunta->dimensao;
    $administrador = User::factory()->administrador()->create();
    UsuarioPermissao::factory()->for($administrador, 'usuario')->create();

    $this->actingAs($administrador)->put(route('dimensoes.update', $dimensao), [
        'nome' => 'Nova classificação',
        'descricao' => 'Mudança retrospectiva',
        'ativo' => true,
    ])->assertForbidden();

    expect($dimensao->fresh()->nome)->not->toBe('Nova classificação');
});
