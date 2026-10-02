<?php

namespace App\Policies;

use App\Models\Curso;
use App\Models\Pergunta;
use App\Models\User;

class PerguntaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->possuiAlgumEscopoAcademico();
    }

    public function view(User $user, Pergunta $pergunta): bool
    {
        return $pergunta->curso_origem_id === null
            ? $this->viewAny($user)
            : $user->podeAcessarCurso($pergunta->cursoOrigem);
    }

    public function create(User $user, ?Curso $curso = null): bool
    {
        return $curso ? $user->podeAcessarCurso($curso) : $user->possuiPermissaoInstitucional();
    }

    public function update(User $user, Pergunta $pergunta): bool
    {
        return $this->create($user, $pergunta->cursoOrigem)
            && ! $pergunta->modelosQuestionario()->whereNotNull('publicado_em')->exists()
            && ! $pergunta->perguntasAplicacao()->exists();
    }
}
