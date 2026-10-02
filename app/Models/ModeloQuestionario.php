<?php

namespace App\Models;

use App\EscopoModeloQuestionario;
use App\TipoAplicacao;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModeloQuestionario extends Model
{
    /** @use HasFactory<\Database\Factories\ModeloQuestionarioFactory> */
    use HasFactory;

    protected $table = 'modelos_questionario';

    protected $fillable = [
        'curso_id',
        'nome',
        'escopo_modelo',
        'tipo',
        'descricao',
        'ativo',
        'publicado_em',
        'criado_por_usuario_id',
    ];

    protected $attributes = ['ativo' => true];

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por_usuario_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ModeloQuestionarioPergunta::class);
    }

    public function perguntas(): BelongsToMany
    {
        return $this->belongsToMany(
            Pergunta::class,
            'modelo_questionario_perguntas',
        )->withPivot(['ordem', 'obrigatoria', 'ativo'])->withTimestamps();
    }

    public function aplicacaoModelos(): HasMany
    {
        return $this->hasMany(AplicacaoModelo::class);
    }

    public function aplicacoesPesquisa(): BelongsToMany
    {
        return $this->belongsToMany(
            AplicacaoPesquisa::class,
            'aplicacao_modelos',
            'modelo_questionario_id',
            'aplicacao_id',
        )->withTimestamps();
    }

    public function perguntasAplicacao(): HasMany
    {
        return $this->hasMany(AplicacaoPergunta::class);
    }

    protected function casts(): array
    {
        return [
            'escopo_modelo' => EscopoModeloQuestionario::class,
            'tipo' => TipoAplicacao::class,
            'ativo' => 'boolean',
            'publicado_em' => 'datetime',
        ];
    }
}
