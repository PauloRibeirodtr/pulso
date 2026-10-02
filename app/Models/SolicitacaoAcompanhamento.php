<?php

namespace App\Models;

use App\StatusSolicitacaoAcompanhamento;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitacaoAcompanhamento extends Model
{
    /** @use HasFactory<\Database\Factories\SolicitacaoAcompanhamentoFactory> */
    use HasFactory;

    protected $table = 'solicitacoes_acompanhamento';

    protected $fillable = [
        'participacao_id',
        'status',
        'solicitado_em',
        'responsavel_usuario_id',
        'atendido_em',
    ];

    protected $attributes = ['status' => 'pendente'];

    public function participacao(): BelongsTo
    {
        return $this->belongsTo(ParticipacaoPesquisa::class, 'participacao_id');
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsavel_usuario_id');
    }

    protected function casts(): array
    {
        return [
            'status' => StatusSolicitacaoAcompanhamento::class,
            'solicitado_em' => 'datetime',
            'atendido_em' => 'datetime',
        ];
    }
}
