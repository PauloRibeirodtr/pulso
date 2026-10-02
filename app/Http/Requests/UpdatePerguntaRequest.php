<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdatePerguntaRequest extends StorePerguntaRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('pergunta'));
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $rules['curso_origem_id'] = ['nullable', Rule::in([$this->route('pergunta')->curso_origem_id])];

        return $rules;
    }
}
