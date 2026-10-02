<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreServidorRequest extends StoreUsuarioPermissaoRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'siape' => ['required', 'string', 'max:50', 'regex:/^[0-9]+$/', Rule::unique(User::class, 'identificador_institucional')],
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'password' => ['required', 'string', Password::default(), 'confirmed'],
            'ativo' => ['required', 'boolean'],
        ]);
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge([
            'siape' => Str::squish($this->string('siape')->toString()),
            'nome' => Str::squish($this->string('nome')->toString()),
            'email' => $this->filled('email') ? Str::lower(Str::squish($this->string('email')->toString())) : null,
        ]);
    }
}
