<?php

namespace App\Http\Requests;

use App\Models\Curso;
use App\Models\Turma;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTurmaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('turma'));
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'curso_id' => ['required', 'integer', Rule::exists(Curso::class, 'id')],
            'nome_amigavel' => [
                'required',
                'string',
                'max:100',
                Rule::unique(Turma::class)
                    ->where('curso_id', $this->integer('curso_id'))
                    ->ignore($this->route('turma')),
            ],
            'codigo_academico' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique(Turma::class)
                    ->where('curso_id', $this->integer('curso_id'))
                    ->ignore($this->route('turma')),
            ],
            'ano_ingresso' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'periodo_ingresso' => ['nullable', 'string', 'max:30'],
            'ativo' => ['required', 'boolean'],
        ];
    }

    /**
     * @return list<callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $curso = Curso::query()->find($this->integer('curso_id'));

            if ($curso && ! $this->user()->can('create', [Turma::class, $curso])) {
                $validator->errors()->add('curso_id', 'Você não possui permissão para mover a turma para este curso.');
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nome_amigavel' => Str::squish($this->string('nome_amigavel')->toString()),
            'codigo_academico' => $this->filled('codigo_academico')
                ? Str::squish($this->string('codigo_academico')->toString())
                : null,
            'periodo_ingresso' => $this->filled('periodo_ingresso')
                ? Str::squish($this->string('periodo_ingresso')->toString())
                : null,
        ]);
    }
}
