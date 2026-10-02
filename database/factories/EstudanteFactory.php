<?php

namespace Database\Factories;

use App\Models\Estudante;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Estudante>
 */
class EstudanteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usuario_id' => User::factory()->estudante(),
            'turma_id' => Turma::factory(),
            'situacao_academica' => 'ativo',
        ];
    }
}
