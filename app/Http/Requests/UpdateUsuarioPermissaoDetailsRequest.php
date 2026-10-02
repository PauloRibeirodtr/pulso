<?php

namespace App\Http\Requests;

use Illuminate\Validation\Validator;

class UpdateUsuarioPermissaoDetailsRequest extends StoreUsuarioPermissaoRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('usuarioPermissao'));
    }

    /**
     * @return list<callable>
     */
    public function after(): array
    {
        return [
            ...parent::after(),
            function (Validator $validator): void {
                if ($validator->errors()->any()) {
                    return;
                }

                $permissao = $this->route('usuarioPermissao');
                $duplicada = $permissao->usuario->permissoes()
                    ->whereKeyNot($permissao->id)
                    ->where('nivel_escopo', $this->input('nivel_escopo'))
                    ->where('campus_id', $this->input('campus_id'))
                    ->where('curso_id', $this->input('curso_id'))
                    ->where('papel', $this->input('papel'))
                    ->exists();

                if ($duplicada) {
                    $validator->errors()->add('papel', 'Essa permissão já está cadastrada para o servidor.');
                }
            },
        ];
    }
}
