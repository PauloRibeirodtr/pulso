<?php

namespace Database\Factories;

use App\Models\AplicacaoPesquisa;
use App\Models\Estudante;
use App\Models\ParticipacaoPesquisa;
use App\StatusParticipacao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ParticipacaoPesquisa>
 */
class ParticipacaoPesquisaFactory extends Factory
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
            'estudante_id' => Estudante::factory(),
            'status' => StatusParticipacao::NaoIniciada,
            'iniciado_em' => null,
            'finalizado_em' => null,
        ];
    }
}
