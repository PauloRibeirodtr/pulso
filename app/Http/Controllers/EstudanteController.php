<?php

namespace App\Http\Controllers;

use App\Actions\CadastrarEstudante;
use App\Actions\ImportarEstudantes;
use App\Http\Requests\ImportEstudantesRequest;
use App\Http\Requests\StoreEstudanteRequest;
use App\Http\Requests\UpdateEstudanteRequest;
use App\Models\Estudante;
use App\Models\Turma;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EstudanteController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Estudante::class);

        $busca = $request->string('busca')->trim()->toString();
        $estudantes = Estudante::query()
            ->visiveisPara($request->user())
            ->select(['id', 'usuario_id', 'turma_id', 'situacao_academica', 'created_at'])
            ->with([
                'usuario:id,identificador_institucional,name,ativo',
                'turma:id,curso_id,nome_amigavel',
                'turma.curso:id,campus_id,sigla',
                'turma.curso.campus:id,sigla',
            ])
            ->when($busca !== '', function (Builder $query) use ($busca): void {
                $query->where(function (Builder $pesquisa) use ($busca): void {
                    $pesquisa->whereHas('usuario', function (Builder $usuarios) use ($busca): void {
                        $usuarios->where('name', 'like', "%{$busca}%")
                            ->orWhere('identificador_institucional', 'like', "%{$busca}%");
                    })->orWhereHas('turma', function (Builder $turmas) use ($busca): void {
                        $turmas->where('nome_amigavel', 'like', "%{$busca}%")
                            ->orWhere('codigo_academico', 'like', "%{$busca}%");
                    });
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('estudantes.index', compact('estudantes'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Estudante::class);

        $turmas = $this->turmasDisponiveis($request, true);

        return view('estudantes.create', compact('turmas'));
    }

    public function store(StoreEstudanteRequest $request, CadastrarEstudante $cadastrar): RedirectResponse
    {
        $dados = $request->validated();
        $turma = Turma::query()->findOrFail($dados['turma_id']);
        $cadastrar->handle($dados, $turma);

        return redirect()->route('estudantes.index')->with('status', 'Estudante cadastrado com sucesso.');
    }

    public function edit(Request $request, Estudante $estudante): View
    {
        Gate::authorize('update', $estudante);

        $estudante->load('usuario');
        $turmas = $this->turmasDisponiveis($request);

        return view('estudantes.edit', compact('estudante', 'turmas'));
    }

    public function update(UpdateEstudanteRequest $request, Estudante $estudante): RedirectResponse
    {
        $dados = $request->validated();

        DB::transaction(function () use ($estudante, $dados): void {
            $estudante->update([
                'turma_id' => $dados['turma_id'],
                'situacao_academica' => $dados['situacao_academica'],
            ]);
            $estudante->usuario()->update([
                'identificador_institucional' => $dados['matricula'],
                'name' => $dados['nome'],
                'ativo' => $dados['situacao_academica'] === 'ativo',
            ]);
        });

        return redirect()->route('estudantes.index')->with('status', 'Estudante atualizado com sucesso.');
    }

    public function importForm(Request $request): View
    {
        Gate::authorize('create', Estudante::class);

        $turmas = $this->turmasDisponiveis($request, true);

        return view('estudantes.import', compact('turmas'));
    }

    public function import(ImportEstudantesRequest $request, ImportarEstudantes $importar): RedirectResponse
    {
        $turma = Turma::query()->findOrFail($request->integer('turma_id'));
        $quantidade = $importar->handle($request->file('arquivo'), $turma);

        return redirect()->route('estudantes.index')
            ->with('status', "{$quantidade} estudantes importados com sucesso.");
    }

    /**
     * @return Collection<int, Turma>
     */
    private function turmasDisponiveis(Request $request, bool $somenteAtivas = false): Collection
    {
        return Turma::query()
            ->visiveisPara($request->user())
            ->when($somenteAtivas, fn (Builder $query) => $query->where('ativo', true))
            ->with('curso:id,campus_id,nome', 'curso.campus:id,nome')
            ->orderBy('nome_amigavel')
            ->get(['id', 'curso_id', 'nome_amigavel']);
    }
}
