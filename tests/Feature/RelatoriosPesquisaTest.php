<?php

use App\Models\AplicacaoPesquisa;
use App\Models\Estudante;
use App\Models\Pergunta;
use App\Models\RegistroAuditoria;
use App\Models\Turma;
use App\Models\User;
use App\Models\UsuarioPermissao;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/** @return array{User, Turma, Pergunta, array<int, AplicacaoPesquisa>} */
function cenarioRelatorios(array $primeira = [1, 2, 3], array $segunda = [4, 5, 5]): array
{
    $turma = Turma::factory()->create();
    $servidor = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($servidor, 'usuario')->paraCurso($turma->curso)->create();
    $pergunta = Pergunta::factory()->create(['texto' => 'Tenho apoio para aprender.']);
    $estudantes = Estudante::factory()->count(max(count($primeira), count($segunda)))->for($turma)->create();
    $aplicacoes = [];
    foreach ([$primeira, $segunda] as $indice => $valores) {
        $aplicacao = AplicacaoPesquisa::factory()->for($turma)->create([
            'nome' => 'Rodada '.($indice + 1), 'status' => $indice === 0 ? 'encerrada' : 'aberta',
            'data_abertura' => now()->subDays($indice === 0 ? 45 : 2),
            'data_encerramento' => $indice === 0 ? now()->subDays(40) : now()->addDays(5),
        ]);
        $item = $aplicacao->perguntas()->create([
            'pergunta_id' => $pergunta->id, 'origem' => 'institucional', 'ordem' => 1,
            'texto_snapshot' => $pergunta->texto, 'orientacao_escala_snapshot' => 'menor_maior_atencao',
            'valor_minimo_snapshot' => 1, 'valor_maximo_snapshot' => 5,
        ]);
        foreach ($valores as $posicao => $valor) {
            $participacao = $aplicacao->participacoes()->create([
                'estudante_id' => $estudantes[$posicao]->id, 'status' => 'finalizada', 'finalizado_em' => now()->subDay(),
            ]);
            $participacao->respostas()->create(['aplicacao_pergunta_id' => $item->id, 'valor_numerico' => $valor, 'respondido_em' => now()->subDay()]);
        }
        $aplicacoes[] = $aplicacao;
    }

    return [$servidor, $turma, $pergunta, $aplicacoes];
}

function filtrosRelatorios(array $aplicacoes, string $relatorio = 'resultado', string $modo = 'comparativo'): array
{
    return ['gerar' => 1, 'relatorio' => $relatorio, 'modo' => $modo, 'aplicacoes' => array_map(fn ($app) => $app->id, $aplicacoes)];
}

it('renders all four reports and exports the selected applications', function (string $relatorio) {
    [$servidor, , , $aplicacoes] = cenarioRelatorios();
    $filtros = filtrosRelatorios($aplicacoes, $relatorio);
    $this->actingAs($servidor)->get(route('relatorios.index', $filtros))
        ->assertOk()->assertSee('Imprimir / salvar PDF')->assertSee('Exportar CSV')
        ->assertSee('Rodada 1')->assertSee('Rodada 2')->assertSee('parciais')
        ->assertViewHas('dados', fn ($dados) => $dados['resumo']['estudantes_unicos'] === 3 && $dados['resumo']['participacoes'] === 6);
    $exportacao = $this->get(route('relatorios.export', $filtros))->assertOk()->assertDownload('relatorio-'.$relatorio.'.csv');
    expect($exportacao->streamedContent())->toContain('Rodada 1', 'Rodada 2', 'comparativo');
    expect(RegistroAuditoria::query()->where('acao', 'relatorios.exportados')->count())->toBe(2);
})->with(['resultado', 'historico', 'participacao', 'solicitacoes']);

it('weights consolidated averages by finalized answers and counts distinct students', function () {
    [$servidor, $turma, , $aplicacoes] = cenarioRelatorios([1, 1], [5, 5, 5]);
    $parcial = $aplicacoes[1]->participacoes()->create(['estudante_id' => Estudante::factory()->for($turma)->create()->id, 'status' => 'em_andamento']);
    $parcial->respostas()->create(['aplicacao_pergunta_id' => $aplicacoes[1]->perguntas()->sole()->id, 'valor_numerico' => 1, 'respondido_em' => now()]);
    $this->actingAs($servidor)->get(route('relatorios.index', filtrosRelatorios($aplicacoes, modo: 'consolidado')))
        ->assertOk()->assertViewHas('dados', function ($dados) {
            expect($dados['dimensoes'])->toHaveCount(1)
                ->and($dados['dimensoes'][0]['media'])->toBe(3.4)
                ->and($dados['dimensoes'][0]['respostas'])->toBe(5)
                ->and($dados['dimensoes'][0]['estudantes'])->toBe(3)
                ->and($dados['perguntas'][0]['distribuicao'])->toBe([1 => 2, 5 => 3])
                ->and($dados['resumo']['estudantes_unicos'])->toBe(4)
                ->and($dados['resumo']['respondentes_unicos'])->toBe(3)
                ->and($dados['resumo']['finalizadas'])->toBe(5);

            return true;
        });
});

it('suppresses averages and distributions when repeated rounds contain only two distinct respondents', function () {
    [$servidor, , , $aplicacoes] = cenarioRelatorios([1, 2], [4, 5]);
    $filtros = filtrosRelatorios($aplicacoes, modo: 'consolidado');
    $this->actingAs($servidor)->get(route('relatorios.index', $filtros))
        ->assertOk()->assertViewHas('dados', fn ($dados) => $dados['dimensoes'][0]['media'] === null && $dados['perguntas'][0]['distribuicao'] === null);
    $csv = $this->get(route('relatorios.export', $filtros))->assertOk()->streamedContent();
    expect($csv)->toContain('Restrita')->not->toContain('3,00', '"1":');
});

it('excludes questions whose snapshots are incompatible', function (string $campo, mixed $valor) {
    [$servidor, , , $aplicacoes] = cenarioRelatorios();
    $aplicacoes[1]->perguntas()->sole()->update([$campo => $valor]);
    $this->actingAs($servidor)->get(route('relatorios.index', filtrosRelatorios($aplicacoes)))
        ->assertOk()->assertSee('Não há perguntas compatíveis')
        ->assertViewHas('dados', fn ($dados) => $dados['dimensoes']->isEmpty() && $dados['perguntas']->isEmpty());
})->with([
    'changed wording' => ['texto_snapshot', 'Uma pergunta diferente.'],
    'changed scale' => ['valor_maximo_snapshot', 10],
    'changed block' => ['origem', 'curso'],
]);

it('uses only institutional questions across different courses and rejects mixed-class history', function () {
    [$servidor, $turma, $pergunta, $aplicacoes] = cenarioRelatorios();
    $outraTurma = Turma::factory()->create();
    $aplicacoes[1]->update(['turma_id' => $outraTurma->id]);
    UsuarioPermissao::factory()->for($servidor, 'usuario')->paraCurso($outraTurma->curso)->create();
    $perguntaCurso = Pergunta::factory()->create();
    foreach ($aplicacoes as $aplicacao) {
        $item = $aplicacao->perguntas()->sole()->replicate();
        $item->pergunta_id = $perguntaCurso->id;
        $item->origem = 'curso';
        $item->ordem = 2;
        $item->save();
        foreach ($aplicacao->participacoes()->get() as $participacao) {
            $participacao->respostas()->create(['aplicacao_pergunta_id' => $item->id, 'valor_numerico' => 1, 'respondido_em' => now()]);
        }
    }
    $this->actingAs($servidor)->get(route('relatorios.index', filtrosRelatorios($aplicacoes)))
        ->assertOk()->assertSee('somente o bloco institucional')
        ->assertViewHas('dados', fn ($dados) => $dados['dimensoes']->pluck('origem')->unique()->all() === ['institucional']);
    $this->get(route('relatorios.index', filtrosRelatorios($aplicacoes, 'historico')))->assertSessionHasErrors('aplicacoes');
});

it('rejects unauthorized applications and academic filters in screen and export', function (string $rota) {
    [$servidor, , , $aplicacoes] = cenarioRelatorios();
    $outra = AplicacaoPesquisa::factory()->create(['status' => 'encerrada', 'nome' => 'Aplicação fora do escopo']);
    $this->actingAs($servidor)->get(route('relatorios.index'))->assertOk()->assertDontSee('Aplicação fora do escopo');
    $this->getJson(route($rota, filtrosRelatorios([$aplicacoes[0], $outra])))->assertForbidden();
    $this->getJson(route($rota, [...filtrosRelatorios($aplicacoes), 'curso_id' => $outra->turma->curso_id]))->assertForbidden();
    $estudante = Estudante::factory()->create()->usuario;
    $this->actingAs($estudante)->get(route($rota))->assertForbidden();
    expect(RegistroAuditoria::query()->where('acao', 'relatorios.exportados')->count())->toBe(0);
})->with(['relatorios.index', 'relatorios.export']);

it('validates selection, dates, planned applications and individual mode', function () {
    [$servidor, , , $aplicacoes] = cenarioRelatorios();
    $this->actingAs($servidor)->get(route('relatorios.index', ['gerar' => 1]))->assertSessionHasErrors('aplicacoes');
    $this->get(route('relatorios.index', filtrosRelatorios($aplicacoes, modo: 'individual')))->assertSessionHasErrors('modo');
    $this->get(route('relatorios.index', [...filtrosRelatorios($aplicacoes), 'data_inicio' => '2026-11-01', 'data_fim' => '2026-10-01']))->assertSessionHasErrors('data_fim');
    $aplicacoes[0]->update(['status' => 'planejada']);
    $this->get(route('relatorios.export', filtrosRelatorios($aplicacoes)))->assertSessionHasErrors('aplicacoes');
});

it('renders an individual report, preserves distribution and neutralizes spreadsheet formulas', function () {
    [$servidor, , , $aplicacoes] = cenarioRelatorios();
    $aplicacoes[0]->update(['nome' => '=FORMULA()']);
    $filtros = filtrosRelatorios([$aplicacoes[0]]);
    $this->actingAs($servidor)->get(route('relatorios.index', $filtros))
        ->assertOk()->assertViewHas('modo', 'individual')
        ->assertViewHas('dados', fn ($dados) => $dados['perguntas'][0]['distribuicao'] === [1 => 1, 2 => 1, 3 => 1]);
    $csv = $this->get(route('relatorios.export', $filtros))->assertOk()->streamedContent();
    expect($csv)->toContain("'=FORMULA()", '2,00');
});

it('calculates pending age buckets and time until attendance without exposing student identities', function () {
    [$servidor, , , $aplicacoes] = cenarioRelatorios();
    $participacoes = $aplicacoes[0]->participacoes()->get();
    foreach ([3, 10, 20] as $i => $dias) {
        $participacoes[$i]->solicitacaoAcompanhamento()->create(['solicitado_em' => now()->subDays($dias)]);
    }
    $aplicacoes[1]->participacoes()->first()->solicitacaoAcompanhamento()->create([
        'status' => 'atendida', 'solicitado_em' => now()->subDays(10), 'atendido_em' => now()->subDays(7), 'responsavel_usuario_id' => $servidor->id,
    ]);
    $this->actingAs($servidor)->get(route('relatorios.index', filtrosRelatorios($aplicacoes, 'solicitacoes', 'consolidado')))
        ->assertOk()->assertDontSee($participacoes[0]->estudante->usuario->name)
        ->assertViewHas('dados', function ($dados) {
            $linha = $dados['solicitacoes'][0];
            expect($linha['pendentes'])->toBe(3)->and($linha['atendidas'])->toBe(1)
                ->and($linha['ate_7'])->toBe(1)->and($linha['de_8_a_14'])->toBe(1)->and($linha['acima_14'])->toBe(1)
                ->and($linha['espera_media'])->toBe(11.0)->and($linha['atendimento_medio'])->toBe(3.0);

            return true;
        });
});
