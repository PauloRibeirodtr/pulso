<?php

namespace App\Policies;

use App\Models\User;
use App\TipoUsuario;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tipo_usuario !== TipoUsuario::Estudante
            && $user->possuiPermissaoInstitucional();
    }

    public function view(User $user, User $model): bool
    {
        return $this->viewAny($user) && $model->tipo_usuario === TipoUsuario::Servidor;
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, User $model): bool
    {
        return $this->view($user, $model) && $user->id !== $model->id;
    }
}
