<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateModeloQuestionarioRequest extends StoreModeloQuestionarioRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('modeloQuestionario'));
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $modelo = $this->route('modeloQuestionario');
        $rules['nome'][3] = Rule::unique('modelos_questionario')->where('curso_id', $modelo->curso_id)->ignore($modelo);
        $rules['escopo_modelo'] = ['required', Rule::in([$modelo->escopo_modelo->value])];
        $rules['curso_id'] = ['nullable', Rule::in([$modelo->curso_id])];
        $rules['tipo'] = ['required', Rule::in([$modelo->tipo->value])];

        return $rules;
    }
}
