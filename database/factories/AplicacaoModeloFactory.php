<?php

namespace Database\Factories;

use App\Models\AplicacaoPesquisa;
use App\Models\AplicacaoModelo;
use App\Models\ModeloQuestionario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AplicacaoModelo>
 */
class AplicacaoModeloFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'aplicacao_id' => AplicacaoPesquisa::factory(),
            'modelo_questionario_id' => ModeloQuestionario::factory(),
        ];
    }
}
