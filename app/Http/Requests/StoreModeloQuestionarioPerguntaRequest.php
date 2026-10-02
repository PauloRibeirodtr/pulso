<?php

namespace App\Http\Requests;

use App\Models\Pergunta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreModeloQuestionarioPerguntaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('modeloQuestionario'));
    }

    public function rules(): array
    {
        return [
            'pergunta_id' => ['required', 'integer', Rule::exists(Pergunta::class, 'id')->where('ativo', true)],
            'obrigatoria' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $modelo = $this->route('modeloQuestionario');
            $pergunta = Pergunta::query()->find($this->integer('pergunta_id'));

            if (! $pergunta) {
                return;
            }

            if ($pergunta->curso_origem_id !== $modelo->curso_id) {
                $validator->errors()->add('pergunta_id', 'A pergunta deve pertencer ao mesmo bloco do modelo.');
            }

            if ($modelo->itens()->where('pergunta_id', $pergunta->id)->exists()) {
                $validator->errors()->add('pergunta_id', 'Esta pergunta já está no modelo.');
            }
        }];
    }
}
