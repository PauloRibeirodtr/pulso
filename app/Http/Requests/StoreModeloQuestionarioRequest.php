<?php

namespace App\Http\Requests;

use App\EscopoModeloQuestionario;
use App\Models\Curso;
use App\Models\ModeloQuestionario;
use App\TipoAplicacao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreModeloQuestionarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', ModeloQuestionario::class);
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:150', Rule::unique('modelos_questionario')->where('curso_id', $this->integer('curso_id') ?: null)],
            'escopo_modelo' => ['required', Rule::enum(EscopoModeloQuestionario::class)],
            'curso_id' => ['nullable', 'integer', Rule::exists(Curso::class, 'id')->where('ativo', true)],
            'tipo' => ['required', Rule::enum(TipoAplicacao::class)],
            'descricao' => ['nullable', 'string'],
            'ativo' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $curso = Curso::query()->find($this->integer('curso_id'));
            $institucional = $this->input('escopo_modelo') === EscopoModeloQuestionario::Institucional->value;

            if ($institucional === ($curso !== null)) {
                $validator->errors()->add('curso_id', 'Informe um curso apenas para modelos permanentes de curso.');
            } elseif (! $this->user()->can('create', [ModeloQuestionario::class, $curso])) {
                $validator->errors()->add('curso_id', 'Você não possui permissão para criar modelos neste escopo.');
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['nome' => Str::squish($this->string('nome')->toString())]);
    }
}
