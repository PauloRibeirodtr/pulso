<?php

namespace App\Http\Requests;

use App\Models\Estudante;
use App\Models\Turma;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ImportEstudantesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Estudante::class);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'turma_id' => ['required', 'integer', Rule::exists(Turma::class, 'id')],
            'arquivo' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
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
                $validator->errors()->add('turma_id', 'Você não possui permissão para importar estudantes nesta turma.');
            }
        }];
    }
}
