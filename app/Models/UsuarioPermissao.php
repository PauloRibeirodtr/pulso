<?php

namespace App\Models;

use App\NivelEscopo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsuarioPermissao extends Model
{
    /** @use HasFactory<\Database\Factories\UsuarioPermissaoFactory> */
    use HasFactory;

    protected $table = 'usuario_permissoes';

    protected $fillable = [
        'usuario_id',
        'nivel_escopo',
        'campus_id',
        'curso_id',
        'papel',
        'ativo',
    ];

    protected $attributes = ['ativo' => true];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    protected function casts(): array
    {
        return [
            'nivel_escopo' => NivelEscopo::class,
            'ativo' => 'boolean',
        ];
    }
}
