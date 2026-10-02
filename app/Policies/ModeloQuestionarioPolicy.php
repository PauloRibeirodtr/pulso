<?php

namespace App\Policies;

use App\Models\Curso;
use App\Models\ModeloQuestionario;
use App\Models\User;

class ModeloQuestionarioPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->possuiAlgumEscopoAcademico();
    }

    public function view(User $user, ModeloQuestionario $modeloQuestionario): bool
    {
        return $modeloQuestionario->curso_id === null
            ? $this->viewAny($user)
            : $user->podeAcessarCurso($modeloQuestionario->curso);
    }

    public function create(User $user, ?Curso $curso = null): bool
    {
        return $curso ? $user->podeAcessarCurso($curso) : $user->possuiPermissaoInstitucional();
    }

    public function update(User $user, ModeloQuestionario $modeloQuestionario): bool
    {
        return $modeloQuestionario->publicado_em === null
            && $this->create($user, $modeloQuestionario->curso);
    }

    public function publish(User $user, ModeloQuestionario $modeloQuestionario): bool
    {
        return $this->update($user, $modeloQuestionario);
    }

    public function duplicate(User $user, ModeloQuestionario $modeloQuestionario): bool
    {
        return $this->create($user, $modeloQuestionario->curso);
    }

    public function changeStatus(User $user, ModeloQuestionario $modeloQuestionario): bool
    {
        return $this->create($user, $modeloQuestionario->curso);
    }
}
