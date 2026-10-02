<?php

namespace App\Models;

use App\StatusAplicacao;
use App\TipoAplicacao;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AplicacaoPesquisa extends Model
{
    /** @use HasFactory<\Database\Factories\AplicacaoPesquisaFactory> */
    use HasFactory;

    protected $table = 'aplicacoes_pesquisa';

    protected $fillable = [
        'turma_id',
        'nome',
        'tipo_aplicacao',
        'data_abertura',
        'data_encerramento',
        'status',
        'criado_por_usuario_id',
    ];

    protected $attributes = ['status' => 'planejada'];

    public function turma(): BelongsTo
    {
        return $this->belongsTo(Turma::class);
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por_usuario_id');
    }

    public function aplicacaoModelos(): HasMany
    {
        return $this->hasMany(AplicacaoModelo::class, 'aplicacao_id');
    }

    public function modelosQuestionario(): BelongsToMany
    {
        return $this->belongsToMany(
            ModeloQuestionario::class,
            'aplicacao_modelos',
            'aplicacao_id',
            'modelo_questionario_id',
        )->withTimestamps();
    }

    public function perguntas(): HasMany
    {
        return $this->hasMany(AplicacaoPergunta::class, 'aplicacao_id');
    }

    public function participacoes(): HasMany
    {
        return $this->hasMany(ParticipacaoPesquisa::class, 'aplicacao_id');
    }

    public function estaDisponivelParaResposta(): bool
    {
        $agora = now();

        return $this->status === StatusAplicacao::Aberta
            && ($this->data_abertura === null || $this->data_abertura->lessThanOrEqualTo($agora))
            && ($this->data_encerramento === null || $this->data_encerramento->greaterThan($agora));
    }

    protected function casts(): array
    {
        return [
            'tipo_aplicacao' => TipoAplicacao::class,
            'data_abertura' => 'datetime',
            'data_encerramento' => 'datetime',
            'status' => StatusAplicacao::class,
        ];
    }
}
