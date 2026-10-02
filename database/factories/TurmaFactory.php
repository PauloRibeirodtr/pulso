<?php

namespace Database\Factories;

use App\Models\Curso;
use App\Models\Turma;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Turma>
 */
class TurmaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'curso_id' => Curso::factory(),
            'nome_amigavel' => fake()->unique()->bothify('TURMA-####-?'),
            'codigo_academico' => fake()->unique()->numerify('########'),
            'ano_ingresso' => fake()->numberBetween(2020, (int) date('Y')),
            'periodo_ingresso' => fake()->randomElement(['1', '2']),
            'ativo' => true,
        ];
    }
}
