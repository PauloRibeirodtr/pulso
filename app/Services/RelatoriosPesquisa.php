<?php

namespace App\Services;

use App\Models\AplicacaoPergunta;
use App\Models\AplicacaoPesquisa;
use App\Models\Dimensao;
use App\Models\ParticipacaoPesquisa;
use App\Models\Pergunta;
use App\Models\Resposta;
use App\Models\SolicitacaoAcompanhamento;
use App\StatusAplicacao;
use App\StatusParticipacao;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RelatoriosPesquisa
{
    public const TITULOS = [
        'resultado' => 'Resultado de aplicações',
        'historico' => 'Evolução histórica da turma',
        'participacao' => 'Participação nas pesquisas',
        'solicitacoes' => 'Solicitações de conversa',
    ];

    /** @param array<string, mixed> $filtros */
    public function filtrar(Builder $query, array $filtros): Builder
    {
        return $query->where('status', '!=', StatusAplicacao::Planejada->value)
            ->when($filtros['campus_id'] ?? null, fn (Builder $q, int $id) => $q->whereHas('turma.curso', fn (Builder $curso) => $curso->where('campus_id', $id)))
            ->when($filtros['curso_id'] ?? null, fn (Builder $q, int $id) => $q->whereHas('turma', fn (Builder $turma) => $turma->where('curso_id', $id)))
            ->when($filtros['turma_id'] ?? null, fn (Builder $q, int $id) => $q->where('turma_id', $id))
            ->when($filtros['tipo_aplicacao'] ?? null, fn (Builder $q, string $tipo) => $q->where('tipo_aplicacao', $tipo))
            ->when($filtros['data_inicio'] ?? null, fn (Builder $q, string $data) => $q->whereDate('data_abertura', '>=', $data))
            ->when($filtros['data_fim'] ?? null, fn (Builder $q, string $data) => $q->whereDate('data_abertura', '<=', $data));
    }

    /**
     * @param  Collection<int, AplicacaoPesquisa>  $aplicacoes
     * @return array{resumo: array<string, int>, participacao: Collection, solicitacoes: Collection, dimensoes: Collection, perguntas: Collection, avisos: list<string>}
     */
    public function gerar(Collection $aplicacoes, string $modo, string $relatorio): array
    {
        $ids = $aplicacoes->modelKeys();
        $resumo = ParticipacaoPesquisa::query()->whereIn('aplicacao_id', $ids)
            ->selectRaw('COUNT(*) as participacoes, COUNT(DISTINCT estudante_id) as estudantes_unicos')
            ->selectRaw('COUNT(DISTINCT CASE WHEN status = ? THEN estudante_id END) as respondentes_unicos', [StatusParticipacao::Finalizada->value])->first();
        $participacao = $this->participacao($aplicacoes, $modo);
        $dados = [
            'resumo' => [
                'participacoes' => (int) $resumo->participacoes,
                'estudantes_unicos' => (int) $resumo->estudantes_unicos,
                'respondentes_unicos' => (int) $resumo->respondentes_unicos,
                'finalizadas' => $participacao->sum('finalizadas'),
            ],
            'participacao' => $participacao,
            'solicitacoes' => collect(), 'dimensoes' => collect(), 'perguntas' => collect(), 'avisos' => [],
        ];
        if ($aplicacoes->contains('status', StatusAplicacao::Aberta)) {
            $dados['avisos'][] = 'Há aplicações abertas na seleção: seus resultados são parciais.';
        }
        if ($relatorio === 'solicitacoes') {
            $dados['solicitacoes'] = $this->solicitacoes($aplicacoes, $modo);
        }
        if (in_array($relatorio, ['resultado', 'historico'], true)) {
            $dados = array_replace($dados, $this->resultados($aplicacoes, $modo, $dados['avisos']));
        }

        return $dados;
    }

    /**
     * @param  Collection<int, AplicacaoPesquisa>  $aplicacoes
     * @return Collection<int, array<string, mixed>>
     */
    private function participacao(Collection $aplicacoes, string $modo): Collection
    {
        $consulta = ParticipacaoPesquisa::query()->whereIn('aplicacao_id', $aplicacoes->modelKeys())
            ->selectRaw('COUNT(*) as previstas')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as finalizadas', ['finalizada'])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as andamento', ['em_andamento'])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as nao_iniciadas', ['nao_iniciada']);
        if ($modo !== 'consolidado') {
            $consulta->addSelect('aplicacao_id')->groupBy('aplicacao_id');
        }
        $totais = $consulta->get()->keyBy('aplicacao_id');
        $grupos = $modo === 'consolidado' ? collect([0 => null]) : $aplicacoes->keyBy('id');

        return $grupos->map(function (?AplicacaoPesquisa $aplicacao, int $id) use ($totais, $modo): array {
            $total = $modo === 'consolidado' ? $totais->first() : $totais->get($id);
            $previstas = (int) ($total->previstas ?? 0);
            $finalizadas = (int) ($total->finalizadas ?? 0);

            return [
                'grupo' => $aplicacao ? $this->rotulo($aplicacao) : 'Consolidado da seleção',
                'previstas' => $previstas, 'finalizadas' => $finalizadas,
                'andamento' => (int) ($total->andamento ?? 0), 'nao_iniciadas' => (int) ($total->nao_iniciadas ?? 0),
                'taxa' => $previstas ? round($finalizadas * 100 / $previstas, 1) : 0,
            ];
        })->values();
    }

    /**
     * @param  Collection<int, AplicacaoPesquisa>  $aplicacoes
     * @return Collection<int, array<string, mixed>>
     */
    private function solicitacoes(Collection $aplicacoes, string $modo): Collection
    {
        $grupos = $modo === 'consolidado' ? collect([0 => null]) : $aplicacoes->keyBy('id');
        $totais = $grupos->map(fn (?AplicacaoPesquisa $aplicacao): array => [
            'grupo' => $aplicacao ? $this->rotulo($aplicacao) : 'Consolidado da seleção',
            'pendentes' => 0, 'atendidas' => 0,
            'ate_7' => 0, 'de_8_a_14' => 0, 'acima_14' => 0,
            'soma_espera' => 0.0, 'soma_atendimento' => 0.0,
        ])->all();
        $agora = CarbonImmutable::now();
        $consulta = SolicitacaoAcompanhamento::query()
            ->join((new ParticipacaoPesquisa)->getTable().' as pp', 'pp.id', '=', 'participacao_id')
            ->whereIn('pp.aplicacao_id', $aplicacoes->modelKeys())
            ->where('pp.status', StatusParticipacao::Finalizada->value)
            ->select([(new SolicitacaoAcompanhamento)->getTable().'.*', 'pp.aplicacao_id']);
        foreach ($consulta->cursor() as $solicitacao) {
            $id = $modo === 'consolidado' ? 0 : $solicitacao->aplicacao_id;
            if ($solicitacao->status->value === 'pendente') {
                $dias = max(0, $solicitacao->solicitado_em->diffInDays($agora));
                $totais[$id]['pendentes']++;
                $totais[$id]['soma_espera'] += $dias;
                $faixa = $dias < 8 ? 'ate_7' : ($dias < 15 ? 'de_8_a_14' : 'acima_14');
                $totais[$id][$faixa]++;
            } else {
                $totais[$id]['atendidas']++;
                if ($solicitacao->atendido_em) {
                    $totais[$id]['soma_atendimento'] += max(0, $solicitacao->solicitado_em->diffInDays($solicitacao->atendido_em));
                }
            }
        }

        return collect($totais)->map(function (array $linha): array {
            $linha['espera_media'] = $linha['pendentes'] ? round($linha['soma_espera'] / $linha['pendentes'], 1) : null;
            $linha['atendimento_medio'] = $linha['atendidas'] ? round($linha['soma_atendimento'] / $linha['atendidas'], 1) : null;
            unset($linha['soma_espera'], $linha['soma_atendimento']);

            return $linha;
        })->values();
    }

    /**
     * @param  Collection<int, AplicacaoPesquisa>  $aplicacoes
     * @param  list<string>  $avisos
     * @return array{dimensoes: Collection, perguntas: Collection, avisos: list<string>}
     */
    private function resultados(Collection $aplicacoes, string $modo, array $avisos): array
    {
        $itens = AplicacaoPergunta::query()->with('pergunta.dimensao')
            ->whereIn('aplicacao_id', $aplicacoes->modelKeys())->orderBy('ordem')->get();
        $totalOriginal = $itens->count();
        if ($aplicacoes->count() > 1) {
            if ($aplicacoes->pluck('turma.curso_id')->unique()->count() > 1) {
                $itens = $itens->filter(fn (AplicacaoPergunta $item): bool => $item->origem->value === 'institucional');
                $avisos[] = 'Entre cursos diferentes, a comparação e a consolidação usam somente o bloco institucional.';
            }
            $compativeis = $itens->groupBy(fn (AplicacaoPergunta $item): string => $this->assinatura($item))
                ->filter(fn (Collection $grupo): bool => $grupo->pluck('aplicacao_id')->unique()->count() === $aplicacoes->count());
            $itens = $compativeis->flatten(1);
            $excluidas = $totalOriginal - $itens->count();
            if ($excluidas > 0) {
                $avisos[] = $excluidas.' item(ns) fora do conjunto comparável. Só entram perguntas presentes em todas as aplicações, com a mesma identidade, texto, bloco, escala e orientação.';
            }
            if ($aplicacoes->pluck('tipo_aplicacao')->unique()->count() > 1) {
                $avisos[] = 'A seleção contém tipos de aplicação diferentes. Considere essa diferença ao interpretar os resultados.';
            }
        }
        if ($itens->isEmpty()) {
            $avisos[] = 'Não há perguntas compatíveis para esta seleção. Ajuste as aplicações ou consulte cada uma individualmente.';

            return ['dimensoes' => collect(), 'perguntas' => collect(), 'avisos' => $avisos];
        }
        $base = DB::table((new Resposta)->getTable().' as r')
            ->join((new ParticipacaoPesquisa)->getTable().' as pp', 'pp.id', '=', 'r.participacao_id')
            ->join((new AplicacaoPergunta)->getTable().' as aq', 'aq.id', '=', 'r.aplicacao_pergunta_id')
            ->whereColumn('pp.aplicacao_id', 'aq.aplicacao_id')
            ->where('pp.status', StatusParticipacao::Finalizada->value)
            ->whereIn('aq.id', $itens->pluck('id'));
        $campos = ['aq.origem', 'aq.valor_minimo_snapshot', 'aq.valor_maximo_snapshot', 'aq.orientacao_escala_snapshot'];
        if ($modo !== 'consolidado') {
            $campos[] = 'aq.aplicacao_id';
        }
        $medidas = 'COUNT(*) as respostas, COUNT(DISTINCT pp.estudante_id) as estudantes, AVG(r.valor_numerico) as media';
        $dimensoes = (clone $base)
            ->join((new Pergunta)->getTable().' as q', 'q.id', '=', 'aq.pergunta_id')
            ->join((new Dimensao)->getTable().' as d', 'd.id', '=', 'q.dimensao_id')
            ->select([...$campos, 'd.id as dimensao_id', 'd.nome as dimensao'])->selectRaw($medidas)
            ->groupBy([...$campos, 'd.id', 'd.nome'])->orderBy('d.nome')->get();
        $mapaAplicacoes = $aplicacoes->keyBy('id');
        $indicadores = app(IndicadoresPesquisa::class);
        $formatar = function (object $linha) use ($mapaAplicacoes, $modo, $indicadores): array {
            $aplicacao = $modo === 'consolidado' ? null : $mapaAplicacoes->get($linha->aplicacao_id);

            return [
                'grupo' => $aplicacao ? $this->rotulo($aplicacao) : 'Consolidado da seleção',
                'aplicacao_id' => $aplicacao?->id,
                'origem' => $linha->origem,
                'minimo' => (int) $linha->valor_minimo_snapshot, 'maximo' => (int) $linha->valor_maximo_snapshot,
                'estudantes' => (int) $linha->estudantes, 'respostas' => (int) $linha->respostas,
                'media' => $indicadores->mediaPublicavel($linha),
            ];
        };
        $dimensoes = $dimensoes->map(fn (object $linha): array => [...$formatar($linha), 'dimensao' => $linha->dimensao, 'dimensao_id' => $linha->dimensao_id]);
        $camposPergunta = [...$campos, 'aq.pergunta_id', 'aq.texto_snapshot'];
        $perguntas = (clone $base)->select($camposPergunta)->selectRaw($medidas)->groupBy($camposPergunta)->get();
        $distribuicoes = (clone $base)->select([...$camposPergunta, 'r.valor_numerico'])->selectRaw('COUNT(*) as quantidade')
            ->groupBy([...$camposPergunta, 'r.valor_numerico'])->get()->groupBy(fn (object $linha): string => $this->chaveDistribuicao($linha, $modo));
        $perguntas = $perguntas->map(function (object $linha) use ($formatar, $distribuicoes, $modo): array {
            $resultado = [...$formatar($linha), 'texto' => $linha->texto_snapshot];
            $resultado['distribuicao'] = $resultado['media'] === null ? null : $distribuicoes->get($this->chaveDistribuicao($linha, $modo), collect())->pluck('quantidade', 'valor_numerico')->map(fn ($n): int => (int) $n)->all();

            return $resultado;
        });

        return compact('dimensoes', 'perguntas', 'avisos');
    }

    private function assinatura(AplicacaoPergunta $item): string
    {
        return hash('sha256', json_encode([$item->pergunta_id, $item->texto_snapshot, $item->origem->value, $item->valor_minimo_snapshot, $item->valor_maximo_snapshot, $item->orientacao_escala_snapshot->value], JSON_THROW_ON_ERROR));
    }

    private function chaveDistribuicao(object $linha, string $modo): string
    {
        return ($modo === 'consolidado' ? 'total' : $linha->aplicacao_id).':'.$linha->pergunta_id;
    }

    public function rotulo(AplicacaoPesquisa $aplicacao): string
    {
        return $aplicacao->nome.' · '.$aplicacao->turma->nome_amigavel
            .' · '.($aplicacao->data_abertura?->format('d/m/Y') ?? 'Sem data')
            .($aplicacao->status === StatusAplicacao::Aberta ? ' (parcial)' : '');
    }
}
