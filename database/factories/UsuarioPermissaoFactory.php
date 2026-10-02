<?php

namespace Database\Factories;

use App\Models\Campus;
use App\Models\Curso;
use App\Models\UsuarioPermissao;
use App\Models\User;
use App\NivelEscopo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UsuarioPermissao>
 */
class UsuarioPermissaoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usuario_id' => User::factory()->servidor(),
            'nivel_escopo' => NivelEscopo::Instituicao,
            'campus_id' => null,
            'curso_id' => null,
            'papel' => 'administrador_institucional',
            'ativo' => true,
        ];
    }

    public function paraCampus(?Campus $campus = null): static
    {
        return $this->state(fn (array $attributes) => [
            'nivel_escopo' => NivelEscopo::Campus,
            'campus_id' => $campus ?? Campus::factory(),
            'curso_id' => null,
            'papel' => 'direcao_ensino',
        ]);
    }

    public function paraCurso(?Curso $curso = null): static
    {
        return $this->state(fn (array $attributes) => [
            'nivel_escopo' => NivelEscopo::Curso,
            'campus_id' => null,
            'curso_id' => $curso ?? Curso::factory(),
            'papel' => 'coordenador',
        ]);
    }
}
