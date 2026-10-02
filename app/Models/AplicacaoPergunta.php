<?php

namespace App\Models;

use App\OrientacaoEscala;
use App\OrigemPerguntaAplicacao;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AplicacaoPergunta extends Model
{
    /** @use HasFactory<\Database\Factories\AplicacaoPerguntaFactory> */
    use HasFactory;

    protected $fillable = [
        'aplicacao_id',
        'pergunta_id',
        'modelo_questionario_id',
        'origem',
        'ordem',
        'obrigatoria',
        'texto_snapshot',
        'orientacao_escala_snapshot',
        'valor_minimo_snapshot',
        'valor_maximo_snapshot',
    ];

    protected $attributes = ['obrigatoria' => true];

    public function aplicacao(): BelongsTo
    {
        return $this->belongsTo(AplicacaoPesquisa::class, 'aplicacao_id');
    }

    public function pergunta(): BelongsTo
    {
        return $this->belongsTo(Pergunta::class);
    }

    public function modeloQuestionario(): BelongsTo
    {
        return $this->belongsTo(ModeloQuestionario::class);
    }

    public function respostas(): HasMany
    {
        return $this->hasMany(Resposta::class);
    }

    protected function casts(): array
    {
        return [
            'origem' => OrigemPerguntaAplicacao::class,
            'ordem' => 'integer',
            'obrigatoria' => 'boolean',
            'orientacao_escala_snapshot' => OrientacaoEscala::class,
            'valor_minimo_snapshot' => 'integer',
            'valor_maximo_snapshot' => 'integer',
        ];
    }
}
