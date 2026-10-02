<?php

namespace Database\Factories;

use App\Models\Campus;
use App\Models\Curso;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Curso>
 */
class CursoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'campus_id' => Campus::factory(),
            'nome' => fake()->unique()->words(3, true),
            'sigla' => fake()->unique()->bothify('CUR-###'),
            'ativo' => true,
        ];
    }
}
