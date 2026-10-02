<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resposta extends Model
{
    /** @use HasFactory<\Database\Factories\RespostaFactory> */
    use HasFactory;

    protected $fillable = [
        'participacao_id',
        'aplicacao_pergunta_id',
        'valor_numerico',
        'respondido_em',
    ];

    public function participacao(): BelongsTo
    {
        return $this->belongsTo(ParticipacaoPesquisa::class, 'participacao_id');
    }

    public function aplicacaoPergunta(): BelongsTo
    {
        return $this->belongsTo(AplicacaoPergunta::class);
    }

    protected function casts(): array
    {
        return [
            'valor_numerico' => 'integer',
            'respondido_em' => 'datetime',
        ];
    }
}
