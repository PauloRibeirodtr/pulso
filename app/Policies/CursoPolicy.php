<?php

namespace App\Policies;

use App\Models\Campus;
use App\Models\Curso;
use App\Models\User;
use App\NivelEscopo;

class CursoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->possuiAlgumEscopoAcademico();
    }

    public function view(User $user, Curso $curso): bool
    {
        return $user->podeAcessarCurso($curso);
    }

    public function create(User $user, ?Campus $campus = null): bool
    {
        if ($campus) {
            return $user->podeGerenciarCursosNoCampus($campus);
        }

        return $user->possuiPermissaoInstitucional()
            || $user->permissoesAtivas
                ->contains('nivel_escopo', NivelEscopo::Campus);
    }

    public function update(User $user, Curso $curso): bool
    {
        return $user->podeAcessarCurso($curso);
    }
}
