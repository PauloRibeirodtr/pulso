<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComentarioGeral extends Model
{
    /** @use HasFactory<\Database\Factories\ComentarioGeralFactory> */
    use HasFactory;

    protected $table = 'comentarios_gerais';

    protected $fillable = ['participacao_id', 'comentario'];

    public function participacao(): BelongsTo
    {
        return $this->belongsTo(ParticipacaoPesquisa::class, 'participacao_id');
    }
}
