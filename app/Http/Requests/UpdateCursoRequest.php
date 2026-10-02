<?php

namespace App\Http\Requests;

use App\Models\Campus;
use App\Models\Curso;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCursoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('curso'));
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'campus_id' => ['required', 'integer', Rule::exists(Campus::class, 'id')],
            'nome' => [
                'required',
                'string',
                'max:150',
                Rule::unique(Curso::class)
                    ->where('campus_id', $this->integer('campus_id'))
                    ->ignore($this->route('curso')),
            ],
            'sigla' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique(Curso::class)
                    ->where('campus_id', $this->integer('campus_id'))
                    ->ignore($this->route('curso')),
            ],
            'ativo' => ['required', 'boolean'],
        ];
    }

    /**
     * @return list<callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $campus = Campus::query()->find($this->integer('campus_id'));
            $curso = $this->route('curso');

            if (! $campus) {
                return;
            }

            if ($campus->id !== $curso->campus_id
                && ! $this->user()->podeGerenciarCursosNoCampus($campus)) {
                $validator->errors()->add('campus_id', 'Você não possui permissão para mover o curso para este campus.');
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nome' => Str::squish($this->string('nome')->toString()),
            'sigla' => $this->filled('sigla')
                ? Str::upper(Str::squish($this->string('sigla')->toString()))
                : null,
        ]);
    }
}
