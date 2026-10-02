<?php

namespace App\Actions;

use App\Models\AplicacaoPesquisa;
use App\Models\ModeloQuestionario;
use App\Models\Pergunta;
use App\OrigemPerguntaAplicacao;

class ComporAplicacaoPesquisa
{
    /**
     * @param  list<int>  $temporarias
     */
    public function handle(
        AplicacaoPesquisa $aplicacao,
        ModeloQuestionario $institucional,
        ModeloQuestionario $curso,
        array $temporarias,
    ): void {
        $aplicacao->perguntas()->delete();
        $aplicacao->modelosQuestionario()->sync([$institucional->id, $curso->id]);

        $ordem = 0;
        $this->adicionarModelo($aplicacao, $institucional, OrigemPerguntaAplicacao::Institucional, $ordem);
        $this->adicionarModelo($aplicacao, $curso, OrigemPerguntaAplicacao::Curso, $ordem);

        $perguntas = Pergunta::query()->whereKey($temporarias)->get()->keyBy('id');

        foreach ($temporarias as $id) {
            $pergunta = $perguntas->get($id);
            $aplicacao->perguntas()->create($this->snapshot($pergunta, OrigemPerguntaAplicacao::Temporaria, ++$ordem, false));
        }
    }

    private function adicionarModelo(
        AplicacaoPesquisa $aplicacao,
        ModeloQuestionario $modelo,
        OrigemPerguntaAplicacao $origem,
        int &$ordem,
    ): void {
        $itens = $modelo->itens()->with('pergunta')->orderBy('ordem')->get();

        foreach ($itens as $item) {
            $aplicacao->perguntas()->create($this->snapshot($item->pergunta, $origem, ++$ordem, $item->obrigatoria) + [
                'modelo_questionario_id' => $modelo->id,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(Pergunta $pergunta, OrigemPerguntaAplicacao $origem, int $ordem, bool $obrigatoria): array
    {
        return [
            'pergunta_id' => $pergunta->id,
            'origem' => $origem,
            'ordem' => $ordem,
            'obrigatoria' => $obrigatoria,
            'texto_snapshot' => $pergunta->texto,
            'orientacao_escala_snapshot' => $pergunta->orientacao_escala,
            'valor_minimo_snapshot' => $pergunta->valor_minimo,
            'valor_maximo_snapshot' => $pergunta->valor_maximo,
        ];
    }
}
