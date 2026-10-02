<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UsuarioPermissao;

class UsuarioPermissaoPolicy
{
    public function create(User $user, User $servidor): bool
    {
        return $user->can('update', $servidor);
    }

    public function update(User $user, UsuarioPermissao $usuarioPermissao): bool
    {
        $usuarioPermissao->loadMissing('usuario');

        return $this->create($user, $usuarioPermissao->usuario);
    }
}
