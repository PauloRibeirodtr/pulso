<?php

namespace App\Models;

use App\NivelEscopo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campus extends Model
{
    /** @use HasFactory<\Database\Factories\CampusFactory> */
    use HasFactory;

    protected $table = 'campi';

    protected $fillable = ['nome', 'sigla', 'ativo'];

    protected $attributes = ['ativo' => true];

    public function cursos(): HasMany
    {
        return $this->hasMany(Curso::class);
    }

    public function permissoes(): HasMany
    {
        return $this->hasMany(UsuarioPermissao::class);
    }

    public function scopeVisiveisPara(Builder $query, User $user): Builder
    {
        if ($user->possuiPermissaoInstitucional()) {
            return $query;
        }

        $campusIds = $user->permissoesAtivas
            ->where('nivel_escopo', NivelEscopo::Campus)
            ->pluck('campus_id');
        $campusIdsDosCursos = $user->permissoesAtivas
            ->where('nivel_escopo', NivelEscopo::Curso)
            ->pluck('curso.campus_id');

        return $query->whereIn('id', $campusIds->merge($campusIdsDosCursos)->filter()->unique());
    }

    protected function casts(): array
    {
        return ['ativo' => 'boolean'];
    }
}
