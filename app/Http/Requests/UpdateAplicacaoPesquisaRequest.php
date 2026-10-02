<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateAplicacaoPesquisaRequest extends StoreAplicacaoPesquisaRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('aplicacao'));
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $rules['turma_id'] = ['required', Rule::in([$this->route('aplicacao')->turma_id])];

        return $rules;
    }
}
