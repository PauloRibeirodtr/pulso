<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCursoRequest;
use App\Http\Requests\UpdateCursoRequest;
use App\Models\Campus;
use App\Models\Curso;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CursoController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Curso::class);

        $cursos = Curso::query()
            ->visiveisPara($request->user())
            ->select(['id', 'campus_id', 'nome', 'sigla', 'ativo', 'created_at'])
            ->with('campus:id,nome,sigla')
            ->withCount('turmas')
            ->when($request->string('busca')->trim()->isNotEmpty(), function (Builder $query) use ($request): void {
                $busca = $request->string('busca')->trim()->toString();

                $query->where(function (Builder $query) use ($busca): void {
                    $query->where('nome', 'like', "%{$busca}%")
                        ->orWhere('sigla', 'like', "%{$busca}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('cursos.index', compact('cursos'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Curso::class);

        $campi = Campus::query()
            ->visiveisPara($request->user())
            ->where('ativo', true)
            ->orderBy('nome')
            ->get(['id', 'nome', 'sigla']);

        return view('cursos.create', compact('campi'));
    }

    public function store(StoreCursoRequest $request): RedirectResponse
    {
        Curso::query()->create($request->validated());

        return redirect()->route('cursos.index')
            ->with('status', 'Curso cadastrado com sucesso.');
    }

    public function edit(Request $request, Curso $curso): View
    {
        Gate::authorize('update', $curso);

        $campi = Campus::query()
            ->visiveisPara($request->user())
            ->orderBy('nome')
            ->get(['id', 'nome', 'sigla']);

        return view('cursos.edit', compact('campi', 'curso'));
    }

    public function update(UpdateCursoRequest $request, Curso $curso): RedirectResponse
    {
        $curso->update($request->validated());

        return redirect()->route('cursos.index')
            ->with('status', 'Curso atualizado com sucesso.');
    }
}
