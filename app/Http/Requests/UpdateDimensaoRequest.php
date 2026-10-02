<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateDimensaoRequest extends StoreDimensaoRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('dimensao'));
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $rules['nome'][3] = Rule::unique('dimensoes')->ignore($this->route('dimensao'));

        return $rules;
    }
}
