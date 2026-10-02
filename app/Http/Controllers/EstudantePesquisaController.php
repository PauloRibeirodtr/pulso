<?php

namespace App\Http\Controllers;

use App\Actions\RegistrarParticipacaoPesquisa;
use App\Http\Requests\SalvarRespostasPesquisaRequest;
use App\Models\ParticipacaoPesquisa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class EstudantePesquisaController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ParticipacaoPesquisa::class);

        $estudante = $request->user()->estudante;
        $participacoes = $estudante
            ? ParticipacaoPesquisa::query()
                ->where('estudante_id', $estudante->id)
                ->with('aplicacao:id,turma_id,nome,tipo_aplicacao,data_abertura,data_encerramento,status')
                ->withCount('respostas')
                ->latest()->paginate(20)
            : new LengthAwarePaginator([], 0, 20);

        return view('estudante.pesquisas.index', compact('participacoes'));
    }

    public function show(Request $request, ParticipacaoPesquisa $participacao): View
    {
        Gate::authorize('view', $participacao);

        $participacao->load([
            'aplicacao.perguntas' => fn ($query) => $query->orderBy('ordem'),
            'respostas',
            'comentarioGeral',
            'solicitacaoAcompanhamento',
        ]);
        $respostas = $participacao->respostas->pluck('valor_numerico', 'aplicacao_pergunta_id');
        $podeResponder = $request->user()->can('update', $participacao);

        return view('estudante.pesquisas.show', compact('participacao', 'respostas', 'podeResponder'));
    }

    public function save(
        SalvarRespostasPesquisaRequest $request,
        ParticipacaoPesquisa $participacao,
        RegistrarParticipacaoPesquisa $registrar,
    ): RedirectResponse {
        $registrar->handle($participacao, $request->validated(), false);

        return redirect()->route('estudante.pesquisas.show', $participacao)
            ->with('status', 'Seu progresso foi salvo. Você pode continuar depois.');
    }

    public function finish(
        SalvarRespostasPesquisaRequest $request,
        ParticipacaoPesquisa $participacao,
        RegistrarParticipacaoPesquisa $registrar,
    ): RedirectResponse {
        $registrar->handle($participacao, $request->validated(), true);

        return redirect()->route('estudante.dashboard')
            ->with('status', 'Pesquisa finalizada. Obrigado por participar!');
    }
}
