<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreModeloQuestionarioPerguntaRequest;
use App\Models\ModeloQuestionario;
use App\Models\ModeloQuestionarioPergunta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ModeloQuestionarioPerguntaController extends Controller
{
    public function store(StoreModeloQuestionarioPerguntaRequest $request, ModeloQuestionario $modeloQuestionario): RedirectResponse
    {
        DB::transaction(function () use ($request, $modeloQuestionario): void {
            $modelo = ModeloQuestionario::query()->lockForUpdate()->findOrFail($modeloQuestionario->id);
            Gate::authorize('update', $modelo);
            $ordem = (int) $modelo->itens()->max('ordem') + 1;
            $modelo->itens()->create($request->validated() + ['ordem' => $ordem]);
        });

        return back()->with('status', 'Pergunta adicionada ao modelo.');
    }

    public function destroy(ModeloQuestionario $modeloQuestionario, ModeloQuestionarioPergunta $item): RedirectResponse
    {
        Gate::authorize('update', $modeloQuestionario);
        abort_unless($item->modelo_questionario_id === $modeloQuestionario->id, 404);

        DB::transaction(function () use ($modeloQuestionario, $item): void {
            $modelo = ModeloQuestionario::query()->lockForUpdate()->findOrFail($modeloQuestionario->id);
            Gate::authorize('update', $modelo);
            $item->delete();

            foreach ($modelo->itens()->orderBy('ordem')->get() as $index => $pergunta) {
                $pergunta->update(['ordem' => $index + 1]);
            }
        });

        return back()->with('status', 'Pergunta removida do modelo.');
    }

    public function move(ModeloQuestionario $modeloQuestionario, ModeloQuestionarioPergunta $item, string $direcao): RedirectResponse
    {
        Gate::authorize('update', $modeloQuestionario);
        abort_unless($item->modelo_questionario_id === $modeloQuestionario->id, 404);
        abort_unless(in_array($direcao, ['subir', 'descer'], true), 404);

        DB::transaction(function () use ($modeloQuestionario, $item, $direcao): void {
            $modelo = ModeloQuestionario::query()->lockForUpdate()->findOrFail($modeloQuestionario->id);
            Gate::authorize('update', $modelo);
            $item->refresh();
            $vizinho = $modelo->itens()->where('ordem', $item->ordem + ($direcao === 'subir' ? -1 : 1))->first();

            if ($vizinho) {
                $ordem = $item->ordem;
                $item->update(['ordem' => 0]);
                $vizinho->update(['ordem' => $ordem]);
                $item->update(['ordem' => $direcao === 'subir' ? $ordem - 1 : $ordem + 1]);
            }
        });

        return back()->with('status', 'Ordem atualizada.');
    }
}
