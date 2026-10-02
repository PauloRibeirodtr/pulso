<?php

namespace App\Policies;

use App\Models\Dimensao;
use App\Models\User;

class DimensaoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->possuiAlgumEscopoAcademico();
    }

    public function view(User $user, Dimensao $dimensao): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->possuiPermissaoInstitucional();
    }

    public function update(User $user, Dimensao $dimensao): bool
    {
        return $this->create($user)
            && ! $dimensao->perguntas()->where(function ($query): void {
                $query->whereHas('modelosQuestionario', function ($modelos): void {
                    $modelos->whereNotNull('publicado_em');
                })->orWhereHas('perguntasAplicacao');
            })->exists();
    }
}
