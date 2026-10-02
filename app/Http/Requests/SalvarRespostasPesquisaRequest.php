<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SalvarRespostasPesquisaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('participacao'));
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'respostas' => ['sometimes', 'array'],
            'respostas.*' => ['required', 'integer', 'between:1,5'],
            'comentario' => ['nullable', 'string', 'max:5000'],
            'solicitar_conversa' => ['sometimes', 'boolean'],
        ];
    }
}
