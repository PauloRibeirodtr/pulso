<?php

namespace App\Http\Requests;

use App\Models\Campus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateCampusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('campus'));
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'nome' => [
                'required',
                'string',
                'max:150',
                Rule::unique(Campus::class)->ignore($this->route('campus')),
            ],
            'sigla' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique(Campus::class)->ignore($this->route('campus')),
            ],
            'ativo' => ['required', 'boolean'],
        ];
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
