<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ProrrogarAplicacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('extend', $this->route('aplicacao'));
    }

    public function rules(): array
    {
        return ['data_encerramento' => ['required', 'date', 'after:now']];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $atual = $this->route('aplicacao')->data_encerramento;

            if ($atual && $this->date('data_encerramento')?->lessThanOrEqualTo($atual)) {
                $validator->errors()->add('data_encerramento', 'O novo prazo precisa ser posterior ao atual.');
            }
        }];
    }
}
