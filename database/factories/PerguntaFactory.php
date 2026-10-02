<?php

namespace Database\Factories;

use App\Models\Dimensao;
use App\Models\Pergunta;
use App\OrientacaoEscala;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pergunta>
 */
class PerguntaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dimensao_id' => Dimensao::factory(),
            'curso_origem_id' => null,
            'texto' => fake()->sentence().'?',
            'sensivel' => false,
            'orientacao_escala' => OrientacaoEscala::MenorMaiorAtencao,
            'valor_minimo' => 1,
            'valor_maximo' => 5,
            'ativo' => true,
        ];
    }
}
