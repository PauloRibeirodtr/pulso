<?php

namespace Database\Factories;

use App\Models\AplicacaoPesquisa;
use App\Models\AplicacaoPergunta;
use App\Models\Pergunta;
use App\OrientacaoEscala;
use App\OrigemPerguntaAplicacao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AplicacaoPergunta>
 */
class AplicacaoPerguntaFactory extends Factory
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
            'pergunta_id' => Pergunta::factory(),
            'modelo_questionario_id' => null,
            'origem' => OrigemPerguntaAplicacao::Temporaria,
            'ordem' => fake()->numberBetween(1, 1000),
            'obrigatoria' => true,
            'texto_snapshot' => fake()->sentence().'?',
            'orientacao_escala_snapshot' => OrientacaoEscala::MenorMaiorAtencao,
            'valor_minimo_snapshot' => 1,
            'valor_maximo_snapshot' => 5,
        ];
    }
}
