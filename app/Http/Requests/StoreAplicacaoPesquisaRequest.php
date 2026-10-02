<?php

namespace App\Http\Requests;

use App\EscopoModeloQuestionario;
use App\Models\AplicacaoPesquisa;
use App\Models\ModeloQuestionario;
use App\Models\Pergunta;
use App\Models\Turma;
use App\TipoAplicacao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAplicacaoPesquisaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', AplicacaoPesquisa::class);
    }

    public function rules(): array
    {
        return [
            'turma_id' => ['required', 'integer', Rule::exists(Turma::class, 'id')->where('ativo', true)],
            'nome' => ['required', 'string', 'max:150'],
            'tipo_aplicacao' => ['required', Rule::enum(TipoAplicacao::class)],
            'institucional_modelo_id' => ['required', 'integer', Rule::exists(ModeloQuestionario::class, 'id')],
            'curso_modelo_id' => ['required', 'integer', Rule::exists(ModeloQuestionario::class, 'id')],
            'temporarias' => ['sometimes', 'array', 'max:100'],
            'temporarias.*' => ['integer', 'distinct', Rule::exists(Pergunta::class, 'id')],
            'data_abertura' => ['nullable', 'date'],
            'data_encerramento' => ['nullable', 'date', 'after:data_abertura'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $turma = Turma::query()->with('curso')->find($this->integer('turma_id'));
            $institucional = ModeloQuestionario::query()->find($this->integer('institucional_modelo_id'));
            $curso = ModeloQuestionario::query()->find($this->integer('curso_modelo_id'));

            if ($turma && (! $turma->ativo || ! $turma->curso->ativo || ! $this->user()->can('create', [AplicacaoPesquisa::class, $turma]))) {
                $validator->errors()->add('turma_id', 'Turma indisponível ou fora do seu escopo.');
            }

            if (! $this->modeloValido($institucional, EscopoModeloQuestionario::Institucional, null)) {
                $validator->errors()->add('institucional_modelo_id', 'Selecione um modelo institucional publicado, ativo e do mesmo tipo.');
            }

            if (! $this->modeloValido($curso, EscopoModeloQuestionario::Curso, $turma?->curso_id)) {
                $validator->errors()->add('curso_modelo_id', 'Selecione um modelo publicado e ativo deste curso e do mesmo tipo.');
            }

            $usadas = collect();
            if ($institucional && $curso) {
                $usadas = $institucional->itens()->pluck('pergunta_id')
                    ->merge($curso->itens()->pluck('pergunta_id'));
            }

            $temporarias = $this->input('temporarias', []);

            foreach (is_array($temporarias) ? $temporarias : [] as $indice => $id) {
                if (! is_scalar($id)) {
                    continue;
                }

                $pergunta = Pergunta::query()->find($id);

                if ($pergunta && (! $pergunta->ativo || $pergunta->curso_origem_id !== $turma?->curso_id || $usadas->contains($pergunta->id))) {
                    $validator->errors()->add("temporarias.{$indice}", 'A pergunta temporária deve estar ativa, pertencer ao curso e não repetir outra pergunta.');
                }
            }
        }];
    }

    private function modeloValido(?ModeloQuestionario $modelo, EscopoModeloQuestionario $escopo, ?int $cursoId): bool
    {
        return $modelo !== null
            && $modelo->ativo
            && $modelo->publicado_em !== null
            && $modelo->escopo_modelo === $escopo
            && $modelo->curso_id === $cursoId
            && $modelo->tipo->value === $this->input('tipo_aplicacao')
            && $modelo->itens()->exists();
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['nome' => Str::squish($this->string('nome')->toString())]);
    }
}
