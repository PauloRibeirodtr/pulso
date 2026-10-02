<?php

namespace App\Actions;

use App\Models\AplicacaoPesquisa;
use App\Models\ParticipacaoPesquisa;
use App\StatusParticipacao;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RegistrarParticipacaoPesquisa
{
    /**
     * @param  array<string, mixed>  $dados
     */
    public function handle(ParticipacaoPesquisa $participacao, array $dados, bool $finalizar): void
    {
        DB::transaction(function () use ($participacao, $dados, $finalizar): void {
            $aplicacao = AplicacaoPesquisa::query()->lockForUpdate()->findOrFail($participacao->aplicacao_id);
            $atual = ParticipacaoPesquisa::query()->lockForUpdate()->findOrFail($participacao->id);
            $atual->setRelation('aplicacao', $aplicacao);
            Gate::authorize('update', $atual);

            $perguntas = $aplicacao->perguntas()
                ->get(['id', 'obrigatoria', 'valor_minimo_snapshot', 'valor_maximo_snapshot'])
                ->keyBy('id');
            $respostas = $dados['respostas'] ?? [];

            foreach ($respostas as $id => $valor) {
                $pergunta = ctype_digit((string) $id) ? $perguntas->get((int) $id) : null;

                if (! $pergunta || $valor < $pergunta->valor_minimo_snapshot || $valor > $pergunta->valor_maximo_snapshot) {
                    throw ValidationException::withMessages([
                        "respostas.{$id}" => 'A resposta não pertence a esta pesquisa ou está fora da escala permitida.',
                    ]);
                }
            }

            if ($finalizar) {
                $respondidas = $atual->respostas()->pluck('aplicacao_pergunta_id')
                    ->merge(array_map('intval', array_keys($respostas)))->unique();

                if ($perguntas->where('obrigatoria', true)->pluck('id')->diff($respondidas)->isNotEmpty()) {
                    throw ValidationException::withMessages([
                        'respostas' => 'Responda todas as perguntas obrigatórias antes de finalizar.',
                    ]);
                }
            }

            foreach ($respostas as $id => $valor) {
                $atual->respostas()->updateOrCreate(
                    ['aplicacao_pergunta_id' => (int) $id],
                    ['valor_numerico' => (int) $valor, 'respondido_em' => now()],
                );
            }

            if (array_key_exists('comentario', $dados)) {
                $comentario = trim((string) ($dados['comentario'] ?? ''));

                if ($comentario === '') {
                    $atual->comentarioGeral()->delete();
                } else {
                    $atual->comentarioGeral()->updateOrCreate([], ['comentario' => $comentario]);
                }
            }

            if ($finalizar && (bool) ($dados['solicitar_conversa'] ?? false)) {
                $atual->solicitacaoAcompanhamento()->create(['solicitado_em' => now()]);
            }

            $atual->update([
                'status' => $finalizar ? StatusParticipacao::Finalizada : StatusParticipacao::EmAndamento,
                'iniciado_em' => $atual->iniciado_em ?? now(),
                'finalizado_em' => $finalizar ? now() : null,
            ]);
        });
    }
}
