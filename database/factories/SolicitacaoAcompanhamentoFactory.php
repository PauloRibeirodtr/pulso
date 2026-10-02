<?php

namespace Database\Factories;

use App\Models\ParticipacaoPesquisa;
use App\Models\SolicitacaoAcompanhamento;
use App\StatusSolicitacaoAcompanhamento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SolicitacaoAcompanhamento>
 */
class SolicitacaoAcompanhamentoFactory extends Factory
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
            'status' => StatusSolicitacaoAcompanhamento::Pendente,
            'solicitado_em' => now(),
            'responsavel_usuario_id' => null,
            'atendido_em' => null,
        ];
    }
}
