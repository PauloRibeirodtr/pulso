<?php

namespace App\Auth;

use App\TipoUsuario;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Database\Eloquent\Builder;

class ServidorUserProvider extends EloquentUserProvider
{
    protected function newModelQuery($model = null): Builder
    {
        return parent::newModelQuery($model)
            ->where('ativo', true)
            ->where(function (Builder $query): void {
                $query->where('tipo_usuario', TipoUsuario::Admin->value)
                    ->orWhere(function (Builder $query): void {
                        $query->where('tipo_usuario', TipoUsuario::Servidor->value)
                            ->whereHas('permissoes', function (Builder $query): void {
                                $query->where('ativo', true);
                            });
                    });
            });
    }
}
