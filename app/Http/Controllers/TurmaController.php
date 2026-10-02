<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTurmaRequest;
use App\Http\Requests\UpdateTurmaRequest;
use App\Models\Curso;
use App\Models\Turma;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TurmaController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Turma::class);

        $turmas = Turma::query()
            ->visiveisPara($request->user())
            ->select([
                'id',
                'curso_id',
                'nome_amigavel',
                'codigo_academico',
                'ano_ingresso',
                'periodo_ingresso',
                'ativo',
                'created_at',
            ])
            ->with([
                'curso:id,campus_id,nome,sigla',
                'curso.campus:id,nome,sigla',
            ])
            ->withCount('estudantes')
            ->when($request->string('busca')->trim()->isNotEmpty(), function (Builder $query) use ($request): void {
                $busca = $request->string('busca')->trim()->toString();

                $query->where(function (Builder $query) use ($busca): void {
                    $query->where('nome_amigavel', 'like', "%{$busca}%")
                        ->orWhere('codigo_academico', 'like', "%{$busca}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('turmas.index', compact('turmas'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Turma::class);

        $cursos = $this->cursosDisponiveis($request, somenteAtivos: true);

        return view('turmas.create', compact('cursos'));
    }

    public function store(StoreTurmaRequest $request): RedirectResponse
    {
        Turma::query()->create($request->validated());

        return redirect()->route('turmas.index')
            ->with('status', 'Turma cadastrada com sucesso.');
    }

    public function edit(Request $request, Turma $turma): View
    {
        Gate::authorize('update', $turma);

        $cursos = $this->cursosDisponiveis($request);

        return view('turmas.edit', compact('cursos', 'turma'));
    }

    public function update(UpdateTurmaRequest $request, Turma $turma): RedirectResponse
    {
        $turma->update($request->validated());

        return redirect()->route('turmas.index')
            ->with('status', 'Turma atualizada com sucesso.');
    }

    /**
     * @return Collection<int, Curso>
     */
    private function cursosDisponiveis(Request $request, bool $somenteAtivos = false): Collection
    {
        return Curso::query()
            ->visiveisPara($request->user())
            ->when($somenteAtivos, fn (Builder $query) => $query->where('ativo', true))
            ->with('campus:id,nome,sigla')
            ->orderBy('nome')
            ->get(['id', 'campus_id', 'nome', 'sigla']);
    }
}
