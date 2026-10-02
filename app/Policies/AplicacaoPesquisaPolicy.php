<?php

namespace App\Policies;

use App\Models\AplicacaoPesquisa;
use App\Models\Turma;
use App\Models\User;
use App\StatusAplicacao;

class AplicacaoPesquisaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->possuiAlgumEscopoAcademico();
    }

    public function view(User $user, AplicacaoPesquisa $aplicacaoPesquisa): bool
    {
        return $user->podeAcessarCurso($aplicacaoPesquisa->turma->curso);
    }

    public function create(User $user, ?Turma $turma = null): bool
    {
        return $turma
            ? $user->podeAcessarCurso($turma->curso)
            : $this->viewAny($user);
    }

    public function update(User $user, AplicacaoPesquisa $aplicacaoPesquisa): bool
    {
        return $aplicacaoPesquisa->status === StatusAplicacao::Planejada
            && $this->view($user, $aplicacaoPesquisa);
    }

    public function open(User $user, AplicacaoPesquisa $aplicacaoPesquisa): bool
    {
        return $this->update($user, $aplicacaoPesquisa);
    }

    public function close(User $user, AplicacaoPesquisa $aplicacaoPesquisa): bool
    {
        return $aplicacaoPesquisa->status === StatusAplicacao::Aberta
            && $this->view($user, $aplicacaoPesquisa);
    }

    public function extend(User $user, AplicacaoPesquisa $aplicacaoPesquisa): bool
    {
        return $this->close($user, $aplicacaoPesquisa);
    }
}
