<?php

namespace App\Models;

use App\StatusParticipacao;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ParticipacaoPesquisa extends Model
{
    /** @use HasFactory<\Database\Factories\ParticipacaoPesquisaFactory> */
    use HasFactory;

    protected $table = 'participacoes_pesquisa';

    protected $fillable = [
        'aplicacao_id',
        'estudante_id',
        'status',
        'iniciado_em',
        'finalizado_em',
    ];

    protected $attributes = ['status' => 'nao_iniciada'];

    public function aplicacao(): BelongsTo
    {
        return $this->belongsTo(AplicacaoPesquisa::class, 'aplicacao_id');
    }

    public function estudante(): BelongsTo
    {
        return $this->belongsTo(Estudante::class);
    }

    public function respostas(): HasMany
    {
        return $this->hasMany(Resposta::class, 'participacao_id');
    }

    public function comentarioGeral(): HasOne
    {
        return $this->hasOne(ComentarioGeral::class, 'participacao_id');
    }

    public function solicitacaoAcompanhamento(): HasOne
    {
        return $this->hasOne(SolicitacaoAcompanhamento::class, 'participacao_id');
    }

    protected function casts(): array
    {
        return [
            'status' => StatusParticipacao::class,
            'iniciado_em' => 'datetime',
            'finalizado_em' => 'datetime',
        ];
    }
}
