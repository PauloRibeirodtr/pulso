<?php

namespace App\Http\Requests;

use App\Models\Curso;
use App\Models\Dimensao;
use App\Models\Pergunta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePerguntaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Pergunta::class);
    }

    public function rules(): array
    {
        return [
            'dimensao_id' => ['required', 'integer', Rule::exists(Dimensao::class, 'id')->where('ativo', true)],
            'curso_origem_id' => ['nullable', 'integer', Rule::exists(Curso::class, 'id')->where('ativo', true)],
            'texto' => ['required', 'string', 'max:2000'],
            'sensivel' => ['required', 'boolean'],
            'ativo' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $curso = Curso::query()->find($this->integer('curso_origem_id'));

            if (! $this->user()->can('create', [Pergunta::class, $curso])) {
                $validator->errors()->add('curso_origem_id', 'Você não possui permissão para criar perguntas neste escopo.');
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['texto' => Str::squish($this->string('texto')->toString())]);
    }
}
