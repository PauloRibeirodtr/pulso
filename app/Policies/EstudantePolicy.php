<?php

namespace App\Policies;

use App\Models\Estudante;
use App\Models\User;
use App\Models\Turma;
use App\TipoUsuario;

class EstudantePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tipo_usuario !== TipoUsuario::Estudante
            && $user->possuiAlgumEscopoAcademico();
    }

    public function view(User $user, Estudante $estudante): bool
    {
        $estudante->loadMissing('turma.curso');

        return $user->tipo_usuario !== TipoUsuario::Estudante
            && $user->podeAcessarCurso($estudante->turma->curso);
    }

    public function create(User $user, ?Turma $turma = null): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        if ($turma === null) {
            return true;
        }

        $turma->loadMissing('curso');

        return $user->podeAcessarCurso($turma->curso);
    }

    public function update(User $user, Estudante $estudante): bool
    {
        return $this->view($user, $estudante);
    }

    public function resetPassword(User $user, Estudante $estudante): bool
    {
        return $this->update($user, $estudante);
    }
}
