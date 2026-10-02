<?php

namespace Database\Factories;

use App\EscopoModeloQuestionario;
use App\Models\ModeloQuestionario;
use App\Models\User;
use App\TipoAplicacao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModeloQuestionario>
 */
class ModeloQuestionarioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'curso_id' => null,
            'nome' => fake()->unique()->sentence(3),
            'escopo_modelo' => EscopoModeloQuestionario::Institucional,
            'tipo' => TipoAplicacao::Monitoramento,
            'descricao' => fake()->optional()->sentence(),
            'ativo' => true,
            'criado_por_usuario_id' => User::factory()->administrador(),
        ];
    }
}
