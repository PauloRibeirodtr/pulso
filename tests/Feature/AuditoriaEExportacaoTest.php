<?php

use App\Models\AplicacaoPesquisa;
use App\Models\Curso;
use App\Models\Dimensao;
use App\Models\Estudante;
use App\Models\Pergunta;
use App\Models\RegistroAuditoria;
use App\Models\Turma;
use App\Models\User;
use App\Models\UsuarioPermissao;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function aplicacaoExportavel(Curso $curso, array $valores, string $dimensao = 'Permanência'): AplicacaoPesquisa
{
    $turma = Turma::factory()->for($curso)->create();
    $aplicacao = AplicacaoPesquisa::factory()->for($turma)->create(['status' => 'encerrada']);
    $pergunta = Pergunta::factory()->for(Dimensao::factory()->create(['nome' => $dimensao]))->create();
    $item = $aplicacao->perguntas()->create([
        'pergunta_id' => $pergunta->id,
        'origem' => 'institucional',
        'ordem' => 1,
        'texto_snapshot' => 'Como você avalia a experiência?',
        'orientacao_escala_snapshot' => 'menor_maior_atencao',
        'valor_minimo_snapshot' => 1,
        'valor_maximo_snapshot' => 5,
    ]);

    foreach ($valores as $valor) {
        $estudante = Estudante::factory()->for($turma)->create();
        $participacao = $aplicacao->participacoes()->create([
            'estudante_id' => $estudante->id,
            'status' => 'finalizada',
            'finalizado_em' => now(),
        ]);
        $participacao->respostas()->create([
            'aplicacao_pergunta_id' => $item->id,
            'valor_numerico' => $valor,
            'respondido_em' => now(),
        ]);
    }

    return $aplicacao;
}

it('exports only aggregated data within the authorized course and audits the download', function () {
    $curso = Curso::factory()->create();
    $outroCurso = Curso::factory()->create();
    $aplicacao = aplicacaoExportavel($curso, [3, 4, 5], '=2+2');
    $outraAplicacao = aplicacaoExportavel($outroCurso, [5, 5, 5]);
    $servidor = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($servidor, 'usuario')->paraCurso($curso)->create();

    $this->actingAs($servidor)->get(route('indicadores.export', $outraAplicacao))->assertForbidden();
    $this->actingAs(Estudante::factory()->create()->usuario)
        ->get(route('indicadores.export', $aplicacao))->assertForbidden();
    $resposta = $this->actingAs($servidor)->get(route('indicadores.export', $aplicacao))->assertOk();
    $conteudo = $resposta->streamedContent();

    expect($resposta->headers->get('content-type'))->toContain('text/csv')
        ->and($conteudo)->toContain("'\u{003D}2+2", '4,00')
        ->not->toContain('identificador_institucional', 'comentario');
    expect(RegistroAuditoria::query()->where('usuario_id', $servidor->id)
        ->where('acao', 'indicadores.exportados')->where('entidade_id', $aplicacao->id)->exists())->toBeTrue();
});

it('hides the average in an export with fewer than three students', function () {
    $curso = Curso::factory()->create();
    $aplicacao = aplicacaoExportavel($curso, [3, 5]);
    $servidor = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($servidor, 'usuario')->paraCurso($curso)->create();

    $conteudo = $this->actingAs($servidor)->get(route('indicadores.export', $aplicacao))
        ->assertOk()->streamedContent();

    expect($conteudo)->toContain('Permanência;2;2;')->not->toContain('4,00');
});

it('records relevant changes without storing their values and restricts the audit page', function () {
    $administrador = User::factory()->administrador()->create();
    UsuarioPermissao::factory()->for($administrador, 'usuario')->create();
    $curso = Curso::factory()->create();
    $servidor = User::factory()->servidor()->create();
    $permissao = UsuarioPermissao::factory()->for($servidor, 'usuario')->paraCurso($curso)->create();

    $this->actingAs($administrador);
    $permissao->update(['ativo' => false]);

    $registro = RegistroAuditoria::query()->where('entidade_tipo', 'UsuarioPermissao')
        ->where('entidade_id', $permissao->id)->sole();
    expect($registro->acao)->toBe('alterado')
        ->and($registro->usuario_id)->toBe($administrador->id)
        ->and($registro->getAttributes())->not->toHaveKey('detalhes');

    $aplicacao = AplicacaoPesquisa::factory()->create();
    $aplicacao->update(['status' => 'aberta']);
    expect(RegistroAuditoria::query()->where('entidade_tipo', 'AplicacaoPesquisa')
        ->where('entidade_id', $aplicacao->id)->where('acao', 'alterado')->exists())->toBeTrue();

    $this->get(route('auditoria.index'))->assertOk()->assertSee('Alteração');
    $outroServidor = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($outroServidor, 'usuario')->paraCurso($curso)->create();
    $this->actingAs($outroServidor)->get(route('auditoria.index'))->assertForbidden();
    $this->actingAs(Estudante::factory()->create()->usuario)->get(route('auditoria.index'))->assertForbidden();
});
