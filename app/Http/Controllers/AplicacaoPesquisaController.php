<?php

namespace App\Http\Controllers;

use App\Actions\ComporAplicacaoPesquisa;
use App\Http\Requests\ProrrogarAplicacaoRequest;
use App\Http\Requests\StoreAplicacaoPesquisaRequest;
use App\Http\Requests\UpdateAplicacaoPesquisaRequest;
use App\Models\AplicacaoPesquisa;
use App\Models\ModeloQuestionario;
use App\Models\Pergunta;
use App\Models\Turma;
use App\StatusAplicacao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AplicacaoPesquisaController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', AplicacaoPesquisa::class);

        $aplicacoes = AplicacaoPesquisa::query()
            ->whereHas('turma', fn ($query) => $query->visiveisPara($request->user()))
            ->with(['turma:id,curso_id,nome_amigavel', 'turma.curso:id,nome,sigla'])
            ->withCount(['participacoes', 'participacoes as finalizadas_count' => fn ($query) => $query->where('status', 'finalizada')])
            ->latest()->paginate(20);

        return view('aplicacoes.index', compact('aplicacoes'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', AplicacaoPesquisa::class);

        return view('aplicacoes.form', $this->opcoes($request));
    }

    public function store(StoreAplicacaoPesquisaRequest $request, ComporAplicacaoPesquisa $compor): RedirectResponse
    {
        $data = $request->validated();

        $aplicacao = DB::transaction(function () use ($request, $data, $compor): AplicacaoPesquisa {
            $aplicacao = AplicacaoPesquisa::query()->create([
                'turma_id' => $data['turma_id'],
                'nome' => $data['nome'],
                'tipo_aplicacao' => $data['tipo_aplicacao'],
                'data_abertura' => $data['data_abertura'] ?? null,
                'data_encerramento' => $data['data_encerramento'] ?? null,
                'criado_por_usuario_id' => $request->user()->id,
            ]);

            $compor->handle(
                $aplicacao,
                ModeloQuestionario::query()->findOrFail($data['institucional_modelo_id']),
                ModeloQuestionario::query()->findOrFail($data['curso_modelo_id']),
                $data['temporarias'] ?? [],
            );

            return $aplicacao;
        });

        return redirect()->route('aplicacoes.show', $aplicacao)->with('status', 'Aplicação planejada.');
    }

    public function show(AplicacaoPesquisa $aplicacao): View
    {
        Gate::authorize('view', $aplicacao);

        $aplicacao->load([
            'turma.curso.campus',
            'modelosQuestionario:id,nome,curso_id',
            'perguntas' => fn ($query) => $query->orderBy('ordem'),
            'perguntas.pergunta.dimensao',
        ])->loadCount(['participacoes', 'participacoes as finalizadas_count' => fn ($query) => $query->where('status', 'finalizada')]);

        $participacoes = $aplicacao->participacoes()->with('estudante.usuario:id,name,identificador_institucional')
            ->latest()->paginate(20);

        return view('aplicacoes.show', compact('aplicacao', 'participacoes'));
    }

    public function edit(Request $request, AplicacaoPesquisa $aplicacao): View
    {
        Gate::authorize('update', $aplicacao);

        $aplicacao->load(['modelosQuestionario', 'perguntas']);

        return view('aplicacoes.form', ['aplicacao' => $aplicacao, ...$this->opcoes($request)]);
    }

    public function update(UpdateAplicacaoPesquisaRequest $request, AplicacaoPesquisa $aplicacao, ComporAplicacaoPesquisa $compor): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $aplicacao, $compor): void {
            $atual = AplicacaoPesquisa::query()->lockForUpdate()->findOrFail($aplicacao->id);
            Gate::authorize('update', $atual);
            $atual->update([
                'nome' => $data['nome'],
                'tipo_aplicacao' => $data['tipo_aplicacao'],
                'data_abertura' => $data['data_abertura'] ?? null,
                'data_encerramento' => $data['data_encerramento'] ?? null,
            ]);

            $compor->handle(
                $atual,
                ModeloQuestionario::query()->findOrFail($data['institucional_modelo_id']),
                ModeloQuestionario::query()->findOrFail($data['curso_modelo_id']),
                $data['temporarias'] ?? [],
            );
        });

        return redirect()->route('aplicacoes.show', $aplicacao)->with('status', 'Planejamento atualizado.');
    }

    public function open(AplicacaoPesquisa $aplicacao): RedirectResponse
    {
        Gate::authorize('open', $aplicacao);

        DB::transaction(function () use ($aplicacao): void {
            $atual = AplicacaoPesquisa::query()->lockForUpdate()->findOrFail($aplicacao->id);
            Gate::authorize('open', $atual);

            if (($atual->data_abertura && $atual->data_abertura->isFuture())
                || ($atual->data_encerramento && $atual->data_encerramento->isPast())) {
                throw ValidationException::withMessages(['data_abertura' => 'O período configurado não permite abrir a aplicação agora.']);
            }

            if (! $atual->perguntas()->exists()) {
                throw ValidationException::withMessages(['perguntas' => 'A aplicação precisa conter perguntas.']);
            }

            $modelos = $atual->modelosQuestionario()->get();

            if ($modelos->count() !== 2 || $modelos->contains(fn (ModeloQuestionario $modelo): bool => ! $modelo->ativo || $modelo->publicado_em === null)) {
                throw ValidationException::withMessages(['modelos' => 'Os dois modelos precisam estar publicados e ativos para abrir a aplicação.']);
            }

            $atual->update(['status' => StatusAplicacao::Aberta, 'data_abertura' => now()]);

            $atual->turma->estudantes()->where('situacao_academica', 'ativo')
                ->whereHas('usuario', fn ($query) => $query->where('ativo', true))
                ->select('id')->chunkById(200, function ($estudantes) use ($atual): void {
                    foreach ($estudantes as $estudante) {
                        $atual->participacoes()->create(['estudante_id' => $estudante->id]);
                    }
                });
        });

        return back()->with('status', 'Aplicação aberta para a turma.');
    }

    public function close(AplicacaoPesquisa $aplicacao): RedirectResponse
    {
        Gate::authorize('close', $aplicacao);

        DB::transaction(function () use ($aplicacao): void {
            $atual = AplicacaoPesquisa::query()->lockForUpdate()->findOrFail($aplicacao->id);
            Gate::authorize('close', $atual);
            $atual->update(['status' => StatusAplicacao::Encerrada, 'data_encerramento' => now()]);
        });

        return back()->with('status', 'Aplicação encerrada.');
    }

    public function extend(ProrrogarAplicacaoRequest $request, AplicacaoPesquisa $aplicacao): RedirectResponse
    {
        DB::transaction(function () use ($request, $aplicacao): void {
            $atual = AplicacaoPesquisa::query()->lockForUpdate()->findOrFail($aplicacao->id);
            Gate::authorize('extend', $atual);
            $atual->update(['data_encerramento' => $request->validated('data_encerramento')]);
        });

        return back()->with('status', 'Prazo prorrogado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function opcoes(Request $request): array
    {
        return [
            'turmas' => Turma::query()->visiveisPara($request->user())->where('ativo', true)
                ->with('curso:id,nome,sigla')->orderBy('nome_amigavel')->get(),
            'modelos' => ModeloQuestionario::query()->where('ativo', true)->whereNotNull('publicado_em')
                ->where(function ($query) use ($request): void {
                    $query->whereNull('curso_id')->orWhereHas('curso', fn ($curso) => $curso->visiveisPara($request->user()));
                })->with('curso:id,nome,sigla')->orderBy('nome')->get(),
            'temporariasDisponiveis' => Pergunta::query()->where('ativo', true)->whereHas('cursoOrigem', fn ($curso) => $curso->visiveisPara($request->user()))
                ->with('cursoOrigem:id,nome,sigla')->orderBy('texto')->get(),
        ];
    }
}
