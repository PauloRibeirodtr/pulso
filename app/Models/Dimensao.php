<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dimensao extends Model
{
    /** @use HasFactory<\Database\Factories\DimensaoFactory> */
    use HasFactory;

    protected $table = 'dimensoes';

    protected $fillable = ['nome', 'descricao', 'ativo'];

    protected $attributes = ['ativo' => true];

    public function perguntas(): HasMany
    {
        return $this->hasMany(Pergunta::class);
    }

    protected function casts(): array
    {
        return ['ativo' => 'boolean'];
    }
}
