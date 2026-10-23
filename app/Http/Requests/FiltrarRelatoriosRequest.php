<?php

namespace App\Http\Requests;

use App\Models\AplicacaoPesquisa;
use App\Models\Campus;
use App\Models\Curso;
use App\Models\Turma;
use App\StatusAplicacao;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class FiltrarRelatoriosRequest extends FiltrarIndicadoresRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [...parent::rules(),
            'relatorio' => ['nullable', Rule::in(['resultado', 'historico', 'participacao', 'solicitacoes'])],
            'modo' => ['nullable', Rule::in(['individual', 'comparativo', 'consolidado'])],
            'gerar' => ['nullable', 'boolean'],
            'aplicacoes' => [Rule::requiredIf($this->boolean('gerar')), 'array', 'min:1', 'max:100'],
            'aplicacoes.*' => ['required', 'integer', 'distinct', Rule::exists(AplicacaoPesquisa::class, 'id')],
        ];
    }

    /** @return list<callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->any()) {
                return;
            }
            foreach (['campus_id' => Campus::class, 'curso_id' => Curso::class, 'turma_id' => Turma::class] as $campo => $modelo) {
                if ($this->filled($campo)) {
                    abort_unless($modelo::query()->visiveisPara($this->user())->whereKey($this->integer($campo))->exists(), 403);
                }
            }
            $aplicacoes = AplicacaoPesquisa::query()->with('turma.curso')->whereKey($this->input('aplicacoes', []))->get();
            foreach ($aplicacoes as $aplicacao) {
                abort_unless($this->user()->can('view', $aplicacao), 403);
                if ($aplicacao->status === StatusAplicacao::Planejada) {
                    $validator->errors()->add('aplicacoes', 'Selecione apenas aplicações abertas ou encerradas.');
                }
            }
            if ($this->input('modo') === 'individual' && $aplicacoes->count() > 1) {
                $validator->errors()->add('modo', 'No modo individual, selecione uma única aplicação.');
            }
            if ($this->input('relatorio') === 'historico' && ($aplicacoes->pluck('turma_id')->unique()->count() > 1 || $aplicacoes->pluck('tipo_aplicacao')->unique()->count() > 1)) {
                $validator->errors()->add('aplicacoes', 'O histórico deve reunir aplicações da mesma turma e tipo. Para cruzar turmas ou tipos, use Resultado de aplicações.');
            }
        }];
    }
}
