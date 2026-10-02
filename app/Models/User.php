<?php

namespace App\Models;

use App\TipoUsuario;
use App\NivelEscopo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'identificador_institucional',
        'name',
        'email',
        'password',
        'tipo_usuario',
        'ativo',
        'ultimo_acesso_em',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $attributes = [
        'ativo' => true,
    ];

    public function estudante(): HasOne
    {
        return $this->hasOne(Estudante::class, 'usuario_id');
    }

    public function permissoes(): HasMany
    {
        return $this->hasMany(UsuarioPermissao::class, 'usuario_id');
    }

    public function permissoesAtivas(): HasMany
    {
        return $this->permissoes()->where('ativo', true);
    }

    public function modelosQuestionarioCriados(): HasMany
    {
        return $this->hasMany(ModeloQuestionario::class, 'criado_por_usuario_id');
    }

    public function aplicacoesPesquisaCriadas(): HasMany
    {
        return $this->hasMany(AplicacaoPesquisa::class, 'criado_por_usuario_id');
    }

    public function solicitacoesAcompanhamentoAtendidas(): HasMany
    {
        return $this->hasMany(SolicitacaoAcompanhamento::class, 'responsavel_usuario_id');
    }

    public function podeAcessarSistema(): bool
    {
        if (! $this->ativo) {
            return false;
        }

        return $this->tipo_usuario !== TipoUsuario::Servidor
            || $this->permissoes()->where('ativo', true)->exists();
    }

    public function possuiAlgumEscopoAcademico(): bool
    {
        return $this->permissoesAcademicasAtivas()->isNotEmpty();
    }

    public function possuiPermissaoInstitucional(): bool
    {
        return $this->permissoesAcademicasAtivas()
            ->contains('nivel_escopo', NivelEscopo::Instituicao);
    }

    public function podeAcessarCampus(Campus $campus): bool
    {
        return $this->permissoesAcademicasAtivas()->contains(function (
            UsuarioPermissao $permissao,
        ) use ($campus): bool {
            return $permissao->nivel_escopo === NivelEscopo::Instituicao
                || ($permissao->nivel_escopo === NivelEscopo::Campus
                    && $permissao->campus_id === $campus->id)
                || ($permissao->nivel_escopo === NivelEscopo::Curso
                    && $permissao->curso?->campus_id === $campus->id);
        });
    }

    public function podeGerenciarCursosNoCampus(Campus $campus): bool
    {
        return $this->permissoesAcademicasAtivas()->contains(function (
            UsuarioPermissao $permissao,
        ) use ($campus): bool {
            return $permissao->nivel_escopo === NivelEscopo::Instituicao
                || ($permissao->nivel_escopo === NivelEscopo::Campus
                    && $permissao->campus_id === $campus->id);
        });
    }

    public function podeAcessarCurso(Curso $curso): bool
    {
        return $this->permissoesAcademicasAtivas()->contains(function (
            UsuarioPermissao $permissao,
        ) use ($curso): bool {
            return $permissao->nivel_escopo === NivelEscopo::Instituicao
                || ($permissao->nivel_escopo === NivelEscopo::Campus
                    && $permissao->campus_id === $curso->campus_id)
                || ($permissao->nivel_escopo === NivelEscopo::Curso
                    && $permissao->curso_id === $curso->id);
        });
    }

    /**
     * @return Collection<int, UsuarioPermissao>
     */
    private function permissoesAcademicasAtivas(): Collection
    {
        if (! $this->exists) {
            return new Collection;
        }

        $this->loadMissing('permissoesAtivas.curso:id,campus_id');

        return $this->permissoesAtivas;
    }

    public function adminlte_desc(): string
    {
        return $this->email ?? $this->identificador_institucional;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'tipo_usuario' => TipoUsuario::class,
            'ativo' => 'boolean',
            'ultimo_acesso_em' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
