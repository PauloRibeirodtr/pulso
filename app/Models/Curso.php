<?php

namespace App\Models;

use App\NivelEscopo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Curso extends Model
{
    /** @use HasFactory<\Database\Factories\CursoFactory> */
    use HasFactory;

    protected $fillable = ['campus_id', 'nome', 'sigla', 'ativo'];

    protected $attributes = ['ativo' => true];

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function turmas(): HasMany
    {
        return $this->hasMany(Turma::class);
    }

    public function permissoes(): HasMany
    {
        return $this->hasMany(UsuarioPermissao::class);
    }

    public function perguntas(): HasMany
    {
        return $this->hasMany(Pergunta::class, 'curso_origem_id');
    }

    public function modelosQuestionario(): HasMany
    {
        return $this->hasMany(ModeloQuestionario::class);
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
            $query->whereIn('campus_id', $campusIds)
                ->orWhereIn('id', $cursoIds);
        });
    }

    protected function casts(): array
    {
        return ['ativo' => 'boolean'];
    }
}
