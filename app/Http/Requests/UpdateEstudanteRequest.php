<?php

namespace App\Http\Requests;

use App\Models\Estudante;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateEstudanteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('estudante'));
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        /** @var Estudante $estudante */
        $estudante = $this->route('estudante');

        return [
            'matricula' => [
                'required', 'string', 'max:50',
                Rule::unique(User::class, 'identificador_institucional')->ignore($estudante->usuario_id),
            ],
            'nome' => ['required', 'string', 'max:255'],
            'turma_id' => ['required', 'integer', Rule::exists(Turma::class, 'id')],
            'situacao_academica' => ['required', Rule::in(['ativo', 'inativo'])],
        ];
    }

    /**
     * @return list<callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $turma = Turma::query()->find($this->integer('turma_id'));

            if ($turma && ! $this->user()->can('create', [Estudante::class, $turma])) {
                $validator->errors()->add('turma_id', 'Você não possui permissão para mover o estudante para esta turma.');
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'matricula' => Str::squish($this->string('matricula')->toString()),
            'nome' => Str::squish($this->string('nome')->toString()),
        ]);
    }
}
