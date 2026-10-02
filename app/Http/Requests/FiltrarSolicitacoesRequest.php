<?php

namespace App\Http\Requests;

use App\Models\SolicitacaoAcompanhamento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FiltrarSolicitacoesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', SolicitacaoAcompanhamento::class);
    }

    public function rules(): array
    {
        return ['status' => ['nullable', Rule::in(['pendente', 'atendida', 'todas'])]];
    }
}
