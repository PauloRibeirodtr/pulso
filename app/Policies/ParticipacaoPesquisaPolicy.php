<?php

namespace App\Policies;

use App\Models\ParticipacaoPesquisa;
use App\Models\User;
use App\StatusParticipacao;
use App\TipoUsuario;

class ParticipacaoPesquisaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tipo_usuario === TipoUsuario::Estudante;
    }

    public function view(User $user, ParticipacaoPesquisa $participacaoPesquisa): bool
    {
        return $user->tipo_usuario === TipoUsuario::Estudante
            && $user->estudante?->id === $participacaoPesquisa->estudante_id;
    }

    public function update(User $user, ParticipacaoPesquisa $participacaoPesquisa): bool
    {
        return $this->view($user, $participacaoPesquisa)
            && $user->estudante->situacao_academica === 'ativo'
            && $user->estudante->turma_id === $participacaoPesquisa->aplicacao->turma_id
            && $participacaoPesquisa->status !== StatusParticipacao::Finalizada
            && $participacaoPesquisa->aplicacao->estaDisponivelParaResposta();
    }
}
