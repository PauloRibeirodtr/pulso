<?php

namespace App\Actions;

use App\Models\Estudante;
use App\Models\Turma;
use App\Models\User;
use App\TipoUsuario;
use Illuminate\Support\Facades\DB;

class CadastrarEstudante
{
    /**
     * @param  array{matricula: string, nome: string, situacao_academica?: string}  $dados
     */
    public function handle(array $dados, Turma $turma): Estudante
    {
        return DB::transaction(function () use ($dados, $turma): Estudante {
            $situacao = $dados['situacao_academica'] ?? 'ativo';
            $usuario = User::query()->create([
                'identificador_institucional' => $dados['matricula'],
                'name' => $dados['nome'],
                'email' => null,
                'password' => $turma->nome_amigavel,
                'tipo_usuario' => TipoUsuario::Estudante,
                'ativo' => $situacao === 'ativo',
            ]);

            return $usuario->estudante()->create([
                'turma_id' => $turma->id,
                'situacao_academica' => $situacao,
            ]);
        });
    }
}
