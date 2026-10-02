<?php

namespace App\Http\Controllers;

use App\Actions\RedefinirSenhaEstudante;
use App\Models\Estudante;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ResetEstudantePasswordController extends Controller
{
    public function __invoke(
        Estudante $estudante,
        RedefinirSenhaEstudante $redefinirSenha,
    ): RedirectResponse
    {
        Gate::authorize('resetPassword', $estudante);

        $redefinirSenha->handle($estudante);

        return back()->with(
            'status',
            'Senha do estudante redefinida para o nome amigável da turma atual.',
        );
    }
}
