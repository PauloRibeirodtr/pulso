<?php

namespace App\Models;

use App\OrientacaoEscala;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pergunta extends Model
{
    /** @use HasFactory<\Database\Factories\PerguntaFactory> */
    use HasFactory;

    protected $fillable = [
        'dimensao_id',
        'curso_origem_id',
        'texto',
        'sensivel',
        'orientacao_escala',
        'valor_minimo',
        'valor_maximo',
        'ativo',
    ];

    protected $attributes = [
        'sensivel' => false,
        'orientacao_escala' => 'menor_maior_atencao',
        'valor_minimo' => 1,
        'valor_maximo' => 5,
        'ativo' => true,
    ];

    public function dimensao(): BelongsTo
    {
        return $this->belongsTo(Dimensao::class);
    }

    public function cursoOrigem(): BelongsTo
    {
        return $this->belongsTo(Curso::class, 'curso_origem_id');
    }

    public function itensModelo(): HasMany
    {
        return $this->hasMany(ModeloQuestionarioPergunta::class);
    }

    public function modelosQuestionario(): BelongsToMany
    {
        return $this->belongsToMany(
            ModeloQuestionario::class,
            'modelo_questionario_perguntas',
        )->withPivot(['ordem', 'obrigatoria', 'ativo'])->withTimestamps();
    }

    public function perguntasAplicacao(): HasMany
    {
        return $this->hasMany(AplicacaoPergunta::class);
    }

    protected function casts(): array
    {
        return [
            'sensivel' => 'boolean',
            'orientacao_escala' => OrientacaoEscala::class,
            'valor_minimo' => 'integer',
            'valor_maximo' => 'integer',
            'ativo' => 'boolean',
        ];
    }
}
