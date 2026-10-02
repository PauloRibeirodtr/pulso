<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServidorRequest;
use App\Http\Requests\UpdateServidorRequest;
use App\Models\Campus;
use App\Models\Curso;
use App\Models\User;
use App\TipoUsuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ServidorController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        $busca = $request->string('busca')->trim()->toString();
        $servidores = User::query()
            ->where('tipo_usuario', TipoUsuario::Servidor)
            ->when($busca !== '', function (Builder $query) use ($busca): void {
                $query->where(function (Builder $filtro) use ($busca): void {
                    $filtro->where('name', 'like', "%{$busca}%")
                        ->orWhere('identificador_institucional', 'like', "%{$busca}%")
                        ->orWhere('email', 'like', "%{$busca}%");
                });
            })
            ->withCount(['permissoesAtivas'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('servidores.index', compact('servidores'));
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        $campi = Campus::query()->orderBy('nome')->get(['id', 'nome', 'sigla']);
        $cursos = Curso::query()->with('campus:id,sigla')->orderBy('nome')
            ->get(['id', 'campus_id', 'nome', 'sigla']);

        return view('servidores.create', compact('campi', 'cursos'));
    }

    public function store(StoreServidorRequest $request): RedirectResponse
    {
        $dados = $request->validated();

        DB::transaction(function () use ($dados): void {
            $servidor = User::query()->create([
                'identificador_institucional' => $dados['siape'],
                'name' => $dados['nome'],
                'email' => $dados['email'] ?? null,
                'password' => $dados['password'],
                'tipo_usuario' => TipoUsuario::Servidor,
                'ativo' => $dados['ativo'],
            ]);

            $servidor->permissoes()->create([
                'nivel_escopo' => $dados['nivel_escopo'],
                'campus_id' => $dados['campus_id'] ?? null,
                'curso_id' => $dados['curso_id'] ?? null,
                'papel' => $dados['papel'],
                'ativo' => true,
            ]);
        });

        return redirect()->route('servidores.index')->with('status', 'Servidor cadastrado com sucesso.');
    }

    public function edit(User $servidor): View
    {
        Gate::authorize('update', $servidor);

        $servidor->load(['permissoes.campus:id,nome,sigla', 'permissoes.curso:id,nome,sigla']);
        $campi = Campus::query()->orderBy('nome')->get(['id', 'nome', 'sigla']);
        $cursos = Curso::query()->with('campus:id,sigla')->orderBy('nome')
            ->get(['id', 'campus_id', 'nome', 'sigla']);

        return view('servidores.edit', compact('servidor', 'campi', 'cursos'));
    }

    public function update(UpdateServidorRequest $request, User $servidor): RedirectResponse
    {
        $dados = $request->validated();
        $servidor->fill([
            'identificador_institucional' => $dados['siape'],
            'name' => $dados['nome'],
            'email' => $dados['email'] ?? null,
            'ativo' => $dados['ativo'],
        ]);

        if (! empty($dados['password'])) {
            $servidor->password = $dados['password'];
            $servidor->remember_token = Str::random(60);
        }

        $servidor->save();

        return redirect()->route('servidores.edit', $servidor)
            ->with('status', 'Servidor atualizado com sucesso.');
    }
}
