<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreModeloQuestionarioRequest;
use App\Http\Requests\UpdateModeloQuestionarioRequest;
use App\Models\Curso;
use App\Models\ModeloQuestionario;
use App\Models\Pergunta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ModeloQuestionarioController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ModeloQuestionario::class);

        $modelos = ModeloQuestionario::query()
            ->where(function (Builder $query) use ($request): void {
                $query->whereNull('curso_id')
                    ->orWhereHas('curso', fn (Builder $curso): Builder => $curso->visiveisPara($request->user()));
            })
            ->with('curso:id,nome,sigla')->withCount('itens')->latest()->paginate(20);

        return view('modelos-questionario.index', compact('modelos'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('viewAny', ModeloQuestionario::class);

        $cursos = Curso::query()->visiveisPara($request->user())->where('ativo', true)->orderBy('nome')->get();

        return view('modelos-questionario.form', compact('cursos'));
    }

    public function store(StoreModeloQuestionarioRequest $request): RedirectResponse
    {
        $modeloQuestionario = ModeloQuestionario::query()->create($request->validated() + [
            'criado_por_usuario_id' => $request->user()->id,
        ]);

        return redirect()->route('modelos-questionario.show', $modeloQuestionario)
            ->with('status', 'Modelo criado em rascunho.');
    }

    public function show(Request $request, ModeloQuestionario $modeloQuestionario): View
    {
        Gate::authorize('view', $modeloQuestionario);

        $modeloQuestionario->load(['curso:id,nome,sigla', 'itens' => fn ($query) => $query->orderBy('ordem'), 'itens.pergunta.dimensao']);
        $perguntasDisponiveis = collect();

        if ($request->user()->can('update', $modeloQuestionario)) {
            $perguntasDisponiveis = Pergunta::query()
                ->where('ativo', true)
                ->where('curso_origem_id', $modeloQuestionario->curso_id)
                ->whereNotIn('id', $modeloQuestionario->itens->pluck('pergunta_id'))
                ->with('dimensao:id,nome')->orderBy('texto')->get();
        }

        return view('modelos-questionario.show', compact('modeloQuestionario', 'perguntasDisponiveis'));
    }

    public function edit(Request $request, ModeloQuestionario $modeloQuestionario): View
    {
        Gate::authorize('update', $modeloQuestionario);

        $cursos = Curso::query()->visiveisPara($request->user())->whereKey($modeloQuestionario->curso_id)->get();

        return view('modelos-questionario.form', compact('modeloQuestionario', 'cursos'));
    }

    public function update(UpdateModeloQuestionarioRequest $request, ModeloQuestionario $modeloQuestionario): RedirectResponse
    {
        $modeloQuestionario->update($request->validated());

        return redirect()->route('modelos-questionario.show', $modeloQuestionario)
            ->with('status', 'Rascunho atualizado.');
    }

    public function publish(ModeloQuestionario $modeloQuestionario): RedirectResponse
    {
        Gate::authorize('publish', $modeloQuestionario);

        DB::transaction(function () use ($modeloQuestionario): void {
            $modelo = ModeloQuestionario::query()->lockForUpdate()->findOrFail($modeloQuestionario->id);

            if ($modelo->publicado_em !== null || ! $modelo->ativo || ! $modelo->itens()->exists()) {
                throw ValidationException::withMessages(['modelo' => 'O modelo precisa estar ativo, em rascunho e conter perguntas.']);
            }

            if ($modelo->itens()->whereHas('pergunta', fn (Builder $query): Builder => $query->where('ativo', false))->exists()) {
                throw ValidationException::withMessages(['modelo' => 'Ative todas as perguntas antes de publicar.']);
            }

            $modelo->update(['publicado_em' => now()]);
        });

        return redirect()->route('modelos-questionario.show', $modeloQuestionario)
            ->with('status', 'Modelo publicado e congelado.');
    }

    public function duplicate(Request $request, ModeloQuestionario $modeloQuestionario): RedirectResponse
    {
        Gate::authorize('duplicate', $modeloQuestionario);

        $data = $request->validate(['nome' => [
            'required',
            'string',
            'max:150',
            Rule::unique('modelos_questionario')->where('curso_id', $modeloQuestionario->curso_id),
        ]]);

        $copia = DB::transaction(function () use ($request, $modeloQuestionario, $data): ModeloQuestionario {
            $copia = $modeloQuestionario->replicate(['publicado_em']);
            $copia->nome = $data['nome'];
            $copia->criado_por_usuario_id = $request->user()->id;
            $copia->save();

            foreach ($modeloQuestionario->itens()->orderBy('ordem')->get() as $item) {
                $copia->itens()->create($item->only(['pergunta_id', 'ordem', 'obrigatoria', 'ativo']));
            }

            return $copia;
        });

        return redirect()->route('modelos-questionario.show', $copia)
            ->with('status', 'Cópia criada em rascunho.');
    }

    public function changeStatus(Request $request, ModeloQuestionario $modeloQuestionario): RedirectResponse
    {
        Gate::authorize('changeStatus', $modeloQuestionario);

        $data = $request->validate(['ativo' => ['required', 'boolean']]);
        $modeloQuestionario->update($data);

        return back()->with('status', $modeloQuestionario->ativo ? 'Modelo ativado.' : 'Modelo retirado de uso.');
    }
}
