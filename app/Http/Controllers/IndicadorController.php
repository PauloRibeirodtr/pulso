<?php

namespace App\Http\Controllers;

use App\Http\Requests\FiltrarIndicadoresRequest;
use App\Models\AplicacaoPesquisa;
use App\Models\Campus;
use App\Models\Curso;
use App\Models\ParticipacaoPesquisa;
use App\Models\Turma;
use App\Services\IndicadoresPesquisa;
use App\StatusAplicacao;
use App\StatusParticipacao;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class IndicadorController extends Controller
{
    public function index(FiltrarIndicadoresRequest $request, IndicadoresPesquisa $indicadores): View
    {
        $filtros = $request->validated();
        $consulta = AplicacaoPesquisa::query()
            ->whereIn('turma_id', Turma::query()->visiveisPara($request->user())->select('id'))
            ->where('status', '!=', StatusAplicacao::Planejada->value)
            ->when($filtros['turma_id'] ?? null, fn (Builder $query, int $id): Builder => $query->where('turma_id', $id))
            ->when($filtros['curso_id'] ?? null, fn (Builder $query, int $id): Builder => $query->whereHas('turma', fn (Builder $turma): Builder => $turma->where('curso_id', $id)))
            ->when($filtros['campus_id'] ?? null, fn (Builder $query, int $id): Builder => $query->whereHas('turma.curso', fn (Builder $curso): Builder => $curso->where('campus_id', $id)))
            ->when($filtros['tipo_aplicacao'] ?? null, fn (Builder $query, string $tipo): Builder => $query->where('tipo_aplicacao', $tipo))
            ->when($filtros['data_inicio'] ?? null, fn (Builder $query, string $data): Builder => $query->whereDate('data_abertura', '>=', $data))
            ->when($filtros['data_fim'] ?? null, fn (Builder $query, string $data): Builder => $query->whereDate('data_abertura', '<=', $data));

        $idsFiltrados = (clone $consulta)->select((new AplicacaoPesquisa)->getTable().'.id');
        $aplicacoes = (clone $consulta)
            ->with(['turma:id,curso_id,nome_amigavel', 'turma.curso:id,nome,sigla'])
            ->withCount(['participacoes', 'participacoes as finalizadas_count' => fn (Builder $query): Builder => $query->where('status', StatusParticipacao::Finalizada->value)])
            ->orderByDesc('data_abertura')->orderByDesc('id')->paginate(20)->withQueryString();

        $mediasAplicacao = $indicadores->mediasInstitucionaisPorAplicacao($aplicacoes->getCollection()->pluck('id'));
        $resumoGeral = $indicadores->resumoInstitucional(clone $idsFiltrados);
        $mediasCurso = $indicadores->mediasInstitucionaisPorCurso(clone $idsFiltrados);
        $participacao = ParticipacaoPesquisa::query()
            ->whereIn('aplicacao_id', clone $idsFiltrados)
            ->selectRaw('COUNT(*) as convidados, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as finalizadas', [StatusParticipacao::Finalizada->value])
            ->first();

        $campi = Campus::query()->visiveisPara($request->user())->orderBy('nome')->get(['id', 'nome', 'sigla']);
        $cursos = Curso::query()->visiveisPara($request->user())->orderBy('nome')->get(['id', 'campus_id', 'nome', 'sigla']);
        $turmas = Turma::query()->visiveisPara($request->user())->with('curso:id,nome,sigla')->orderBy('nome_amigavel')->get(['id', 'curso_id', 'nome_amigavel']);

        return view('indicadores.index', compact(
            'aplicacoes', 'mediasAplicacao', 'resumoGeral', 'mediasCurso', 'participacao',
            'campi', 'cursos', 'turmas', 'filtros', 'indicadores',
        ));
    }

    public function show(AplicacaoPesquisa $aplicacao, IndicadoresPesquisa $indicadores): View
    {
        Gate::authorize('view', $aplicacao);

        $aplicacao->load('turma.curso.campus')
            ->loadCount(['participacoes', 'participacoes as finalizadas_count' => fn (Builder $query): Builder => $query->where('status', StatusParticipacao::Finalizada->value)]);

        $dimensoes = $indicadores->mediasPorDimensao($aplicacao);
        $historico = AplicacaoPesquisa::query()
            ->where('turma_id', $aplicacao->turma_id)
            ->where('tipo_aplicacao', $aplicacao->tipo_aplicacao->value)
            ->where('status', '!=', StatusAplicacao::Planejada->value)
            ->withCount(['participacoes', 'participacoes as finalizadas_count' => fn (Builder $query): Builder => $query->where('status', StatusParticipacao::Finalizada->value)])
            ->orderByDesc('data_abertura')->limit(20)->get();
        $mediasHistoricas = $indicadores->mediasInstitucionaisPorAplicacao($historico->pluck('id'));

        return view('indicadores.show', compact('aplicacao', 'dimensoes', 'historico', 'mediasHistoricas', 'indicadores'));
    }
}
