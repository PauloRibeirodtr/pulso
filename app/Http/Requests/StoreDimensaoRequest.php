<?php

namespace App\Http\Requests;

use App\Models\Dimensao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreDimensaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Dimensao::class);
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:150', Rule::unique(Dimensao::class)],
            'descricao' => ['nullable', 'string'],
            'ativo' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['nome' => Str::squish($this->string('nome')->toString())]);
    }
}
