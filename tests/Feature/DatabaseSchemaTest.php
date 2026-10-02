<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

it('creates the core Pulso IFMS tables', function () {
    $tables = [
        'campi',
        'cursos',
        'turmas',
        'users',
        'estudantes',
        'usuario_permissoes',
        'dimensoes',
        'perguntas',
        'modelos_questionario',
        'modelo_questionario_perguntas',
        'aplicacoes_pesquisa',
        'aplicacao_modelos',
        'aplicacao_perguntas',
        'participacoes_pesquisa',
        'respostas',
        'comentarios_gerais',
        'solicitacoes_acompanhamento',
    ];

    foreach ($tables as $table) {
        expect(Schema::hasTable($table))->toBeTrue();
    }
});

it('creates the authentication and academic hierarchy columns', function () {
    expect(Schema::hasColumns('users', [
        'identificador_institucional',
        'name',
        'email',
        'password',
        'tipo_usuario',
        'ativo',
        'ultimo_acesso_em',
    ]))->toBeTrue()
        ->and(Schema::hasColumns('turmas', [
            'curso_id',
            'nome_amigavel',
            'codigo_academico',
            'ano_ingresso',
            'periodo_ingresso',
            'ativo',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('estudantes', [
            'usuario_id',
            'turma_id',
            'situacao_academica',
        ]))->toBeTrue();
});

it('creates immutable application snapshots and accompaniment requests', function () {
    expect(Schema::hasColumns('aplicacao_perguntas', [
        'aplicacao_id',
        'pergunta_id',
        'origem',
        'ordem',
        'texto_snapshot',
        'orientacao_escala_snapshot',
        'valor_minimo_snapshot',
        'valor_maximo_snapshot',
    ]))->toBeTrue()
        ->and(Schema::hasColumns('solicitacoes_acompanhamento', [
            'participacao_id',
            'status',
            'solicitado_em',
            'responsavel_usuario_id',
            'atendido_em',
        ]))->toBeTrue();
});

it('persists users with an institutional identifier', function () {
    $user = User::factory()->create();

    $this->assertModelExists($user);

    expect($user->identificador_institucional)->not->toBeEmpty()
        ->and($user->ativo)->toBeTrue();
});
