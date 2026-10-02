<?php

namespace App\Models;

use App\NivelEscopo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Turma extends Model
{
    /** @use HasFactory<\Database\Factories\TurmaFactory> */
    use HasFactory;

    protected $fillable = [
        'curso_id',
        'nome_amigavel',
        'codigo_academico',
        'ano_ingresso',
        'periodo_ingresso',
        'ativo',
    ];

    protected $attributes = ['ativo' => true];

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function estudantes(): HasMany
    {
        return $this->hasMany(Estudante::class);
    }

    public function aplicacoesPesquisa(): HasMany
    {
        return $this->hasMany(AplicacaoPesquisa::class);
    }

    public function scopeVisiveisPara(Builder $query, User $user): Builder
    {
        if ($user->possuiPermissaoInstitucional()) {
            return $query;
        }

        $campusIds = $user->permissoesAtivas
            ->where('nivel_escopo', NivelEscopo::Campus)
            ->pluck('campus_id');
        $cursoIds = $user->permissoesAtivas
            ->where('nivel_escopo', NivelEscopo::Curso)
            ->pluck('curso_id');

        return $query->where(function (Builder $query) use ($campusIds, $cursoIds): void {
            $query->whereIn('curso_id', $cursoIds)
                ->orWhereHas('curso', function (Builder $query) use ($campusIds): void {
                    $query->whereIn('campus_id', $campusIds);
                });
        });
    }

    protected function casts(): array
    {
        return [
            'ano_ingresso' => 'integer',
            'ativo' => 'boolean',
        ];
    }
}
