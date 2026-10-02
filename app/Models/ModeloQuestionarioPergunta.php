<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModeloQuestionarioPergunta extends Model
{
    /** @use HasFactory<\Database\Factories\ModeloQuestionarioPerguntaFactory> */
    use HasFactory;

    protected $fillable = [
        'modelo_questionario_id',
        'pergunta_id',
        'ordem',
        'obrigatoria',
        'ativo',
    ];

    protected $attributes = [
        'obrigatoria' => true,
        'ativo' => true,
    ];

    public function modeloQuestionario(): BelongsTo
    {
        return $this->belongsTo(ModeloQuestionario::class);
    }

    public function pergunta(): BelongsTo
    {
        return $this->belongsTo(Pergunta::class);
    }

    protected function casts(): array
    {
        return [
            'ordem' => 'integer',
            'obrigatoria' => 'boolean',
            'ativo' => 'boolean',
        ];
    }
}
