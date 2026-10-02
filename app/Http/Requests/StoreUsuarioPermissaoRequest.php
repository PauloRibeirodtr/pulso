<?php

namespace App\Http\Requests;

use App\Models\Campus;
use App\Models\Curso;
use App\Models\UsuarioPermissao;
use App\NivelEscopo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreUsuarioPermissaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [UsuarioPermissao::class, $this->route('servidor')]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'nivel_escopo' => ['required', Rule::in(array_column(NivelEscopo::cases(), 'value'))],
            'campus_id' => ['nullable', 'integer', Rule::exists(Campus::class, 'id')],
            'curso_id' => ['nullable', 'integer', Rule::exists(Curso::class, 'id')],
            'papel' => ['required', 'string', 'max:50'],
        ];
    }

    /**
     * @return list<callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $nivel = $this->input('nivel_escopo');
            $campusId = $this->input('campus_id');
            $cursoId = $this->input('curso_id');

            if ($nivel === NivelEscopo::Instituicao->value && ($campusId !== null || $cursoId !== null)) {
                $validator->errors()->add('nivel_escopo', 'O escopo institucional não deve indicar campus ou curso.');
            }

            if ($nivel === NivelEscopo::Campus->value && ($campusId === null || $cursoId !== null)) {
                $validator->errors()->add('campus_id', 'Selecione apenas um campus para esse escopo.');
            }

            if ($nivel === NivelEscopo::Curso->value && ($cursoId === null || $campusId !== null)) {
                $validator->errors()->add('curso_id', 'Selecione apenas um curso para esse escopo.');
            }

            $servidor = $this->route('servidor');

            if ($servidor && ! $validator->errors()->any()) {
                $duplicada = $servidor->permissoes()
                    ->where('nivel_escopo', $nivel)
                    ->where('campus_id', $campusId)
                    ->where('curso_id', $cursoId)
                    ->where('papel', $this->input('papel'))
                    ->exists();

                if ($duplicada) {
                    $validator->errors()->add('papel', 'Essa permissão já está cadastrada para o servidor.');
                }
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'papel' => Str::squish($this->string('papel')->toString()),
            'campus_id' => $this->filled('campus_id') ? $this->input('campus_id') : null,
            'curso_id' => $this->filled('curso_id') ? $this->input('curso_id') : null,
        ]);
    }
}
