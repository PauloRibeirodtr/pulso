<?php

namespace Database\Factories;

use App\Models\Turma;
use App\Models\User;
use App\Models\AplicacaoPesquisa;
use App\StatusAplicacao;
use App\TipoAplicacao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AplicacaoPesquisa>
 */
class AplicacaoPesquisaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'turma_id' => Turma::factory(),
            'nome' => fake()->sentence(3),
            'tipo_aplicacao' => TipoAplicacao::Monitoramento,
            'data_abertura' => now(),
            'data_encerramento' => now()->addDays(5),
            'status' => StatusAplicacao::Planejada,
            'criado_por_usuario_id' => User::factory()->servidor(),
        ];
    }
}
