<?php

namespace App\Observers;

use App\Models\User;
use App\Services\Auditoria;
use App\TipoUsuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class RegistrarAuditoria
{
    public function __construct(private Auditoria $auditoria) {}

    public function created(Model $entidade): void
    {
        $this->registrar('criado', $entidade);
    }

    public function updated(Model $entidade): void
    {
        $this->registrar('alterado', $entidade);
    }

    public function deleted(Model $entidade): void
    {
        $this->registrar('excluido', $entidade);
    }

    private function registrar(string $acao, Model $entidade): void
    {
        $ator = Auth::user();

        if ($ator instanceof User && $ator->tipo_usuario !== TipoUsuario::Estudante) {
            $this->auditoria->registrar($ator, $acao, $entidade);
        }
    }
}
