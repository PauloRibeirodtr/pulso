<?php

namespace App\Services;

use App\Models\AplicacaoPergunta;
use App\Models\AplicacaoPesquisa;
use App\Models\Curso;
use App\Models\Dimensao;
use App\Models\ParticipacaoPesquisa;
use App\Models\Pergunta;
use App\Models\Resposta;
use App\Models\Turma;
use App\StatusParticipacao;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IndicadoresPesquisa
{
    public const MIN_PARTICIPANTES = 3;

    /**
     * @param  iterable<int>|Builder|EloquentBuilder  $aplicacaoIds
     * @return Collection<int, object>
     */
    public function mediasInstitucionaisPorAplicacao(iterable|Builder|EloquentBuilder $aplicacaoIds): Collection
    {
        return $this->respostas()
            ->whereIn('aq.aplicacao_id', $aplicacaoIds)
            ->where('aq.origem', 'institucional')
            ->selectRaw('aq.aplicacao_id, COUNT(*) as respostas, COUNT(DISTINCT pp.estudante_id) as estudantes, AVG(r.valor_numerico) as media')
            ->groupBy('aq.aplicacao_id')
            ->get()->keyBy('aplicacao_id');
    }

    /**
     * @return Collection<int, object>
     */
    public function mediasPorDimensao(AplicacaoPesquisa $aplicacao): Collection
    {
        return $this->respostas()
            ->join((new Pergunta)->getTable().' as q', 'q.id', '=', 'aq.pergunta_id')
            ->join((new Dimensao)->getTable().' as d', 'd.id', '=', 'q.dimensao_id')
            ->where('aq.aplicacao_id', $aplicacao->id)
            ->selectRaw('aq.origem, d.id as dimensao_id, d.nome as dimensao, COUNT(*) as respostas, COUNT(DISTINCT pp.estudante_id) as estudantes, AVG(r.valor_numerico) as media')
            ->groupBy('aq.origem', 'd.id', 'd.nome')
            ->orderBy('aq.origem')->orderBy('d.nome')
            ->get();
    }

    /**
     * @param  iterable<int>|Builder|EloquentBuilder  $aplicacaoIds
     * @return Collection<int, object>
     */
    public function mediasInstitucionaisPorCurso(iterable|Builder|EloquentBuilder $aplicacaoIds): Collection
    {
        return $this->respostas()
            ->join((new AplicacaoPesquisa)->getTable().' as a', 'a.id', '=', 'aq.aplicacao_id')
            ->join((new Turma)->getTable().' as t', 't.id', '=', 'a.turma_id')
            ->join((new Curso)->getTable().' as c', 'c.id', '=', 't.curso_id')
            ->whereIn('a.id', $aplicacaoIds)
            ->where('aq.origem', 'institucional')
            ->selectRaw('c.id as curso_id, c.nome as curso_nome, c.sigla as curso_sigla, COUNT(*) as respostas, COUNT(DISTINCT pp.estudante_id) as estudantes, AVG(r.valor_numerico) as media')
            ->groupBy('c.id', 'c.nome', 'c.sigla')
            ->orderBy('c.nome')
            ->get();
    }

    /**
     * @param  iterable<int>|Builder|EloquentBuilder  $aplicacaoIds
     */
    public function resumoInstitucional(iterable|Builder|EloquentBuilder $aplicacaoIds): object
    {
        return $this->respostas()
            ->whereIn('aq.aplicacao_id', $aplicacaoIds)
            ->where('aq.origem', 'institucional')
            ->selectRaw('COUNT(*) as respostas, COUNT(DISTINCT pp.estudante_id) as estudantes, AVG(r.valor_numerico) as media')
            ->first();
    }

    public function mediaPublicavel(?object $resumo): ?float
    {
        return $resumo && (int) $resumo->estudantes >= self::MIN_PARTICIPANTES
            ? round((float) $resumo->media, 2)
            : null;
    }

    private function respostas(): Builder
    {
        return DB::table((new Resposta)->getTable().' as r')
            ->join((new ParticipacaoPesquisa)->getTable().' as pp', 'pp.id', '=', 'r.participacao_id')
            ->join((new AplicacaoPergunta)->getTable().' as aq', 'aq.id', '=', 'r.aplicacao_pergunta_id')
            ->where('pp.status', StatusParticipacao::Finalizada->value);
    }
}
