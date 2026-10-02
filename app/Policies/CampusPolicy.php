<?php

namespace App\Policies;

use App\Models\Campus;
use App\Models\User;

class CampusPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->possuiAlgumEscopoAcademico();
    }

    public function view(User $user, Campus $campus): bool
    {
        return $user->podeAcessarCampus($campus);
    }

    public function create(User $user): bool
    {
        return $user->possuiPermissaoInstitucional();
    }

    public function update(User $user, Campus $campus): bool
    {
        return $user->possuiPermissaoInstitucional();
    }
}
