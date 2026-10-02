<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estudante extends Model
{
    /** @use HasFactory<\Database\Factories\EstudanteFactory> */
    use HasFactory;

    protected $fillable = ['usuario_id', 'turma_id', 'situacao_academica'];

    protected $attributes = ['situacao_academica' => 'ativo'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function turma(): BelongsTo
    {
        return $this->belongsTo(Turma::class);
    }

    public function participacoesPesquisa(): HasMany
    {
        return $this->hasMany(ParticipacaoPesquisa::class);
    }

    public function scopeVisiveisPara(Builder $query, User $user): Builder
    {
        return $query->whereHas('turma', function (Builder $turmas) use ($user): void {
            $turmas->visiveisPara($user);
        });
    }
}
