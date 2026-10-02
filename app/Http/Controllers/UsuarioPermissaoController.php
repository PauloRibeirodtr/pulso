<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUsuarioPermissaoRequest;
use App\Http\Requests\UpdateUsuarioPermissaoRequest;
use App\Http\Requests\UpdateUsuarioPermissaoDetailsRequest;
use App\Models\Campus;
use App\Models\Curso;
use App\Models\User;
use App\Models\UsuarioPermissao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UsuarioPermissaoController extends Controller
{
    public function edit(UsuarioPermissao $usuarioPermissao): View
    {
        Gate::authorize('update', $usuarioPermissao);

        $campi = Campus::query()->orderBy('nome')->get(['id', 'nome', 'sigla']);
        $cursos = Curso::query()->with('campus:id,sigla')->orderBy('nome')
            ->get(['id', 'campus_id', 'nome', 'sigla']);

        return view('servidores.edit-permissao', compact('usuarioPermissao', 'campi', 'cursos'));
    }

    public function store(StoreUsuarioPermissaoRequest $request, User $servidor): RedirectResponse
    {
        $servidor->permissoes()->create($request->validated() + ['ativo' => true]);

        return redirect()->route('servidores.edit', $servidor)
            ->with('status', 'Permissão cadastrada com sucesso.');
    }

    public function update(UpdateUsuarioPermissaoRequest $request, UsuarioPermissao $usuarioPermissao): RedirectResponse
    {
        $usuarioPermissao->update($request->validated());

        return redirect()->route('servidores.edit', $usuarioPermissao->usuario_id)
            ->with('status', 'Permissão atualizada com sucesso.');
    }

    public function updateDetails(UpdateUsuarioPermissaoDetailsRequest $request, UsuarioPermissao $usuarioPermissao): RedirectResponse
    {
        $usuarioPermissao->update($request->validated());

        return redirect()->route('servidores.edit', $usuarioPermissao->usuario_id)
            ->with('status', 'Permissão alterada com sucesso.');
    }
}
