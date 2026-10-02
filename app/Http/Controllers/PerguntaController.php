<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePerguntaRequest;
use App\Http\Requests\UpdatePerguntaRequest;
use App\Models\Curso;
use App\Models\Dimensao;
use App\Models\Pergunta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PerguntaController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Pergunta::class);

        $perguntas = Pergunta::query()
            ->where(function (Builder $query) use ($request): void {
                $query->whereNull('curso_origem_id')
                    ->orWhereHas('cursoOrigem', fn (Builder $curso): Builder => $curso->visiveisPara($request->user()));
            })
            ->with(['dimensao:id,nome', 'cursoOrigem:id,nome,sigla'])
            ->latest()->paginate(20);

        return view('perguntas.index', compact('perguntas'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('viewAny', Pergunta::class);

        $dimensoes = Dimensao::query()->where('ativo', true)->orderBy('nome')->get();
        $cursos = Curso::query()->visiveisPara($request->user())->where('ativo', true)->orderBy('nome')->get();

        return view('perguntas.form', compact('dimensoes', 'cursos'));
    }

    public function store(StorePerguntaRequest $request): RedirectResponse
    {
        Pergunta::query()->create($request->validated() + [
            'orientacao_escala' => 'menor_maior_atencao',
            'valor_minimo' => 1,
            'valor_maximo' => 5,
        ]);

        return redirect()->route('perguntas.index')->with('status', 'Pergunta cadastrada.');
    }

    public function edit(Request $request, Pergunta $pergunta): View
    {
        Gate::authorize('update', $pergunta);

        $dimensoes = Dimensao::query()
            ->where(fn (Builder $query) => $query->where('ativo', true)->orWhere('id', $pergunta->dimensao_id))
            ->orderBy('nome')->get();
        $cursos = Curso::query()->visiveisPara($request->user())->whereKey($pergunta->curso_origem_id)->get();

        return view('perguntas.form', compact('pergunta', 'dimensoes', 'cursos'));
    }

    public function update(UpdatePerguntaRequest $request, Pergunta $pergunta): RedirectResponse
    {
        $pergunta->update($request->validated());

        return redirect()->route('perguntas.index')->with('status', 'Pergunta atualizada.');
    }
}
