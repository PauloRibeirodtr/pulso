<?php

use App\EscopoModeloQuestionario;
use App\Models\AplicacaoModelo;
use App\Models\AplicacaoPergunta;
use App\Models\AplicacaoPesquisa;
use App\Models\Campus;
use App\Models\ComentarioGeral;
use App\Models\Curso;
use App\Models\Dimensao;
use App\Models\Estudante;
use App\Models\ModeloQuestionario;
use App\Models\ModeloQuestionarioPergunta;
use App\Models\ParticipacaoPesquisa;
use App\Models\Pergunta;
use App\Models\Resposta;
use App\Models\SolicitacaoAcompanhamento;
use App\Models\Turma;
use App\Models\UsuarioPermissao;
use App\Models\User;
use App\NivelEscopo;
use App\OrigemPerguntaAplicacao;
use App\StatusAplicacao;
use App\StatusParticipacao;
use App\StatusSolicitacaoAcompanhamento;
use App\TipoAplicacao;
use App\TipoUsuario;
use Database\Seeders\DimensaoSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('represents the academic hierarchy without duplicating course or campus on the student', function () {
    $campus = Campus::factory()->create();
    $curso = Curso::factory()->for($campus)->create();
    $turma = Turma::factory()->for($curso)->create();
    $usuario = User::factory()->estudante()->create();
    $estudante = Estudante::factory()->for($usuario, 'usuario')->for($turma)->create();

    expect($estudante->usuario->tipo_usuario)->toBe(TipoUsuario::Estudante)
        ->and($estudante->turma->is($turma))->toBeTrue()
        ->and($estudante->turma->curso->is($curso))->toBeTrue()
        ->and($estudante->turma->curso->campus->is($campus))->toBeTrue()
        ->and($campus->cursos)->toHaveCount(1)
        ->and($curso->turmas)->toHaveCount(1)
        ->and($turma->estudantes)->toHaveCount(1);
});

it('casts and relates permissions to their authorized scope', function () {
    $servidor = User::factory()->servidor()->create();
    $curso = Curso::factory()->create();
    $permissao = UsuarioPermissao::factory()
        ->for($servidor, 'usuario')
        ->paraCurso($curso)
        ->create();

    expect($permissao->nivel_escopo)->toBe(NivelEscopo::Curso)
        ->and($permissao->usuario->is($servidor))->toBeTrue()
        ->and($permissao->curso->is($curso))->toBeTrue()
        ->and($permissao->campus)->toBeNull()
        ->and($servidor->permissoes)->toHaveCount(1);
});

it('preserves a questionnaire snapshot and its student response', function () {
    $criador = User::factory()->servidor()->create();
    $turma = Turma::factory()->create();
    $dimensao = Dimensao::factory()->create();
    $pergunta = Pergunta::factory()->for($dimensao)->create();
    $modelo = ModeloQuestionario::factory()->for($criador, 'criadoPor')->create();

    ModeloQuestionarioPergunta::factory()->create([
        'modelo_questionario_id' => $modelo,
        'pergunta_id' => $pergunta,
        'ordem' => 1,
    ]);

    $aplicacao = AplicacaoPesquisa::factory()
        ->for($turma)
        ->for($criador, 'criadoPor')
        ->create();

    AplicacaoModelo::factory()->create([
        'aplicacao_id' => $aplicacao,
        'modelo_questionario_id' => $modelo,
    ]);

    $perguntaAplicada = AplicacaoPergunta::factory()->create([
        'aplicacao_id' => $aplicacao,
        'pergunta_id' => $pergunta,
        'modelo_questionario_id' => $modelo,
        'origem' => OrigemPerguntaAplicacao::Institucional,
        'ordem' => 1,
        'texto_snapshot' => $pergunta->texto,
    ]);

    $estudante = Estudante::factory()->for($turma)->create();
    $participacao = ParticipacaoPesquisa::factory()->create([
        'aplicacao_id' => $aplicacao,
        'estudante_id' => $estudante,
        'status' => StatusParticipacao::Finalizada,
        'finalizado_em' => now(),
    ]);
    $resposta = Resposta::factory()->create([
        'participacao_id' => $participacao,
        'aplicacao_pergunta_id' => $perguntaAplicada,
        'valor_numerico' => 4,
    ]);
    ComentarioGeral::factory()->create(['participacao_id' => $participacao]);
    $solicitacao = SolicitacaoAcompanhamento::factory()->create([
        'participacao_id' => $participacao,
    ]);

    expect($modelo->escopo_modelo)->toBe(EscopoModeloQuestionario::Institucional)
        ->and($modelo->tipo)->toBe(TipoAplicacao::Monitoramento)
        ->and($aplicacao->status)->toBe(StatusAplicacao::Planejada)
        ->and($aplicacao->modelosQuestionario->first()->is($modelo))->toBeTrue()
        ->and($aplicacao->perguntas->first()->texto_snapshot)->toBe($pergunta->texto)
        ->and($participacao->respostas->first()->is($resposta))->toBeTrue()
        ->and($participacao->comentarioGeral)->not->toBeNull()
        ->and($participacao->solicitacaoAcompanhamento->is($solicitacao))->toBeTrue()
        ->and($solicitacao->status)->toBe(StatusSolicitacaoAcompanhamento::Pendente);
});

it('seeds the initial dimensions idempotently', function () {
    $this->seed(DimensaoSeeder::class);
    $this->seed(DimensaoSeeder::class);

    expect(Dimensao::query()->count())->toBe(9)
        ->and(Dimensao::query()->where('nome', 'Aprendizagem')->exists())->toBeTrue();
});
