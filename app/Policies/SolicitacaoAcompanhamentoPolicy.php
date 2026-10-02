<?php

namespace App\Policies;

use App\Models\SolicitacaoAcompanhamento;
use App\Models\User;
use App\StatusSolicitacaoAcompanhamento;

class SolicitacaoAcompanhamentoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->possuiAlgumEscopoAcademico();
    }

    public function view(User $user, SolicitacaoAcompanhamento $solicitacaoAcompanhamento): bool
    {
        return $user->podeAcessarCurso($solicitacaoAcompanhamento->participacao->aplicacao->turma->curso);
    }

    public function attend(User $user, SolicitacaoAcompanhamento $solicitacaoAcompanhamento): bool
    {
        return $solicitacaoAcompanhamento->status === StatusSolicitacaoAcompanhamento::Pendente
            && $this->view($user, $solicitacaoAcompanhamento);
    }
}
