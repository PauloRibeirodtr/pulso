<?php

namespace App\Http\Controllers;

use App\Http\Requests\FiltrarSolicitacoesRequest;
use App\Models\SolicitacaoAcompanhamento;
use App\StatusSolicitacaoAcompanhamento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SolicitacaoAcompanhamentoController extends Controller
{
    public function index(FiltrarSolicitacoesRequest $request): View
    {
        $status = $request->validated('status') ?? 'pendente';

        $solicitacoes = SolicitacaoAcompanhamento::query()
            ->whereHas('participacao.aplicacao.turma', fn (Builder $turma): Builder => $turma->visiveisPara($request->user()))
            ->when($status !== 'todas', fn (Builder $query): Builder => $query->where('status', $status))
            ->with([
                'participacao.estudante.usuario:id,name,identificador_institucional',
                'participacao.aplicacao.turma.curso.campus',
                'responsavel:id,name',
            ])
            ->orderByRaw("CASE WHEN status = 'pendente' THEN 0 ELSE 1 END")
            ->orderByDesc('solicitado_em')
            ->paginate(20)->withQueryString();

        return view('solicitacoes.index', compact('solicitacoes', 'status'));
    }

    public function attend(SolicitacaoAcompanhamento $solicitacao): RedirectResponse
    {
        Gate::authorize('attend', $solicitacao);

        DB::transaction(function () use ($solicitacao): void {
            $atual = SolicitacaoAcompanhamento::query()->lockForUpdate()->findOrFail($solicitacao->id);
            Gate::authorize('attend', $atual);
            $atual->update([
                'status' => StatusSolicitacaoAcompanhamento::Atendida,
                'responsavel_usuario_id' => auth()->id(),
                'atendido_em' => now(),
            ]);
        });

        return back()->with('status', 'Atendimento marcado como realizado.');
    }
}
