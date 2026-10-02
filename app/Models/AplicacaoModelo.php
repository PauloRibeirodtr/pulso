<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AplicacaoModelo extends Model
{
    /** @use HasFactory<\Database\Factories\AplicacaoModeloFactory> */
    use HasFactory;

    protected $fillable = ['aplicacao_id', 'modelo_questionario_id'];

    public function aplicacao(): BelongsTo
    {
        return $this->belongsTo(AplicacaoPesquisa::class, 'aplicacao_id');
    }

    public function modeloQuestionario(): BelongsTo
    {
        return $this->belongsTo(ModeloQuestionario::class);
    }
}
