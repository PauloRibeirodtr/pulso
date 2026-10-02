<?php

namespace App\Services;

use App\Models\RegistroAuditoria;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Auditoria
{
    public function registrar(User $ator, string $acao, Model $entidade): void
    {
        RegistroAuditoria::query()->create([
            'usuario_id' => $ator->id,
            'acao' => $acao,
            'entidade_tipo' => class_basename($entidade),
            'entidade_id' => $entidade->getKey(),
        ]);
    }
}
