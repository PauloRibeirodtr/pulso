<?php

namespace App\Http\Requests;

use App\Models\AplicacaoPesquisa;
use App\Models\Campus;
use App\Models\Curso;
use App\Models\Turma;
use App\TipoAplicacao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FiltrarIndicadoresRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', AplicacaoPesquisa::class);
    }

    public function rules(): array
    {
        return [
            'campus_id' => ['nullable', 'integer', Rule::exists(Campus::class, 'id')],
            'curso_id' => ['nullable', 'integer', Rule::exists(Curso::class, 'id')],
            'turma_id' => ['nullable', 'integer', Rule::exists(Turma::class, 'id')],
            'tipo_aplicacao' => ['nullable', Rule::enum(TipoAplicacao::class)],
            'data_inicio' => ['nullable', 'date_format:Y-m-d'],
            'data_fim' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:data_inicio'],
        ];
    }
}
