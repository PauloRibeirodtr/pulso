<?php

namespace Database\Factories;

use App\Models\AplicacaoPergunta;
use App\Models\ParticipacaoPesquisa;
use App\Models\Resposta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Resposta>
 */
class RespostaFactory extends Factory
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
            'aplicacao_pergunta_id' => AplicacaoPergunta::factory(),
            'valor_numerico' => fake()->numberBetween(1, 5),
            'respondido_em' => now(),
        ];
    }
}
