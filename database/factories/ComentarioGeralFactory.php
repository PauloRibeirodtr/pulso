<?php

namespace Database\Factories;

use App\Models\ComentarioGeral;
use App\Models\ParticipacaoPesquisa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComentarioGeral>
 */
class ComentarioGeralFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'participacao_id' => ParticipacaoPesquisa::factory(),
            'comentario' => fake()->paragraph(),
        ];
    }
}
