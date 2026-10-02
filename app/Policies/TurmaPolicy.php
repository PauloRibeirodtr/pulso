<?php

namespace App\Policies;

use App\Models\Curso;
use App\Models\Turma;
use App\Models\User;

class TurmaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->possuiAlgumEscopoAcademico();
    }

    public function view(User $user, Turma $turma): bool
    {
        $turma->loadMissing('curso');

        return $user->podeAcessarCurso($turma->curso);
    }

    public function create(User $user, ?Curso $curso = null): bool
    {
        if ($curso) {
            return $user->podeAcessarCurso($curso);
        }

        return $user->possuiAlgumEscopoAcademico();
    }

    public function update(User $user, Turma $turma): bool
    {
        return $this->view($user, $turma);
    }
}
