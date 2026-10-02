<?php

namespace Database\Factories;

use App\Models\ModeloQuestionarioPergunta;
use App\Models\ModeloQuestionario;
use App\Models\Pergunta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModeloQuestionarioPergunta>
 */
class ModeloQuestionarioPerguntaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'modelo_questionario_id' => ModeloQuestionario::factory(),
            'pergunta_id' => Pergunta::factory(),
            'ordem' => fake()->numberBetween(1, 1000),
            'obrigatoria' => true,
            'ativo' => true,
        ];
    }
}
