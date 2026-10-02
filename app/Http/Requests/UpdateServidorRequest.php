<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateServidorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('servidor'));
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'siape' => [
                'required', 'string', 'max:50', 'regex:/^[0-9]+$/',
                Rule::unique(User::class, 'identificador_institucional')->ignore($this->route('servidor')),
            ],
            'nome' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable', 'email', 'max:255',
                Rule::unique(User::class, 'email')->ignore($this->route('servidor')),
            ],
            'password' => ['nullable', 'string', Password::default(), 'confirmed'],
            'ativo' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'siape' => Str::squish($this->string('siape')->toString()),
            'nome' => Str::squish($this->string('nome')->toString()),
            'email' => $this->filled('email') ? Str::lower(Str::squish($this->string('email')->toString())) : null,
        ]);
    }
}
