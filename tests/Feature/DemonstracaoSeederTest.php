<?php

use App\Models\AplicacaoPesquisa;
use App\Models\Campus;
use App\Models\Curso;
use App\Models\Estudante;
use App\Models\ModeloQuestionario;
use App\Models\ParticipacaoPesquisa;
use App\Models\Resposta;
use App\Models\SolicitacaoAcompanhamento;
use App\Models\Turma;
use App\Models\User;
use App\Services\IndicadoresPesquisa;
use App\StatusAplicacao;
use Carbon\CarbonImmutable;
use Database\Seeders\DemonstracaoSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-22 12:00:00', 'UTC'));
});

it('creates three complete historical rounds and an open fourth round per class', function () {
    $this->seed(DemonstracaoSeeder::class);

    expect(Curso::query()->count())->toBe(2)
        ->and(Estudante::query()->count())->toBe(80)
        ->and(User::query()->count())->toBe(84)
        ->and(ModeloQuestionario::query()->whereNotNull('publicado_em')->count())->toBe(3)
        ->and(AplicacaoPesquisa::query()->count())->toBe(16)
        ->and(ParticipacaoPesquisa::query()->count())->toBe(320)
        ->and(ParticipacaoPesquisa::query()->where('status', 'finalizada')->count())->toBe(288)
        ->and(ParticipacaoPesquisa::query()->where('status', 'em_andamento')->count())->toBe(12)
        ->and(SolicitacaoAcompanhamento::query()->where('status', 'pendente')->count())->toBe(8)
        ->and(SolicitacaoAcompanhamento::query()->where('status', 'atendida')->count())->toBe(8);

    expect(Turma::query()->count())->toBe(4);
    foreach (Turma::query()->with('aplicacoesPesquisa')->get() as $turma) {
        expect($turma->estudantes()->count())->toBe(20)
            ->and($turma->aplicacoesPesquisa->where('status', StatusAplicacao::Encerrada))->toHaveCount(3)
            ->and($turma->aplicacoesPesquisa->where('status', StatusAplicacao::Aberta))->toHaveCount(1);
        foreach ($turma->aplicacoesPesquisa as $aplicacao) {
            expect($aplicacao->perguntas()->count())->toBe(12)
                ->and($aplicacao->modelosQuestionario()->count())->toBe(2)
                ->and($aplicacao->perguntas()->pluck('origem')->map(fn ($origem): string => $origem->value)->unique()->sort()->values()->all())->toBe(['curso', 'institucional', 'temporaria']);
            if ($aplicacao->status === StatusAplicacao::Encerrada) {
                expect($aplicacao->data_encerramento->isPast())->toBeTrue()
                    ->and($aplicacao->participacoes()->where('status', 'finalizada')->count())->toBe(20);
            } else {
                expect($aplicacao->estaDisponivelParaResposta())->toBeTrue()
                    ->and($aplicacao->data_encerramento->copy()->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s'))
                    ->toBe('07/11/2026 23:59:59');
            }
            foreach ($aplicacao->participacoes()->with('respostas')->get() as $participacao) {
                expect($participacao->respostas)->toHaveCount(match ($participacao->status->value) {
                    'finalizada' => 12, 'em_andamento' => 4, 'nao_iniciada' => 0,
                });
                foreach ($participacao->respostas as $resposta) {
                    expect($resposta->valor_numerico)->toBeGreaterThanOrEqual(1)->toBeLessThanOrEqual(5)
                        ->and($resposta->respondido_em->between($aplicacao->data_abertura, $aplicacao->data_encerramento))->toBeTrue()
                        ->and($resposta->respondido_em->isPast())->toBeTrue();
                }
            }
        }
    }
    $historico = AplicacaoPesquisa::query()->where('nome', 'like', '%DEMO-INFO-2025')->orderBy('data_abertura')->get();
    $indicadores = app(IndicadoresPesquisa::class);
    $primeira = $indicadores->mediasPorDimensao($historico->first())->firstWhere('dimensao', 'Infraestrutura');
    $ultima = $indicadores->mediasPorDimensao($historico->last())->firstWhere('dimensao', 'Infraestrutura');
    expect((float) $ultima->media)->toBeGreaterThan((float) $primeira->media);
});

it('allows the reserved student to submit the live survey and restricts the coordinator scope', function () {
    $this->seed(DemonstracaoSeeder::class);
    $usuario = User::query()->where('identificador_institucional', 'DEMO-INFO-2026-01')->sole();
    expect(Hash::check('DEMO-INFO-2026', $usuario->password))->toBeTrue();
    $participacao = ParticipacaoPesquisa::query()->where('estudante_id', $usuario->estudante->id)
        ->whereHas('aplicacao', fn ($query) => $query->where('status', 'aberta'))->sole();
    $respostas = $participacao->aplicacao->perguntas()->pluck('id')->mapWithKeys(fn (int $id): array => [$id => 4])->all();
    $this->actingAs($usuario)->post(route('estudante.pesquisas.finish', $participacao), [
        'respostas' => $respostas, 'solicitar_conversa' => true,
    ])->assertSessionHasNoErrors()->assertRedirect();
    expect($participacao->fresh()->status->value)->toBe('finalizada')
        ->and($participacao->solicitacaoAcompanhamento()->exists())->toBeTrue();

    $coordenador = User::query()->where('identificador_institucional', 'demo.info')->sole();
    $info = Curso::query()->where('sigla', 'DEMO-INFO')->sole();
    $ads = Curso::query()->where('sigla', 'DEMO-ADS')->sole();
    expect($coordenador->podeAcessarCurso($info))->toBeTrue()
        ->and($coordenador->podeAcessarCurso($ads))->toBeFalse();
    $aplicacaoAds = AplicacaoPesquisa::query()->whereHas('turma', fn ($query) => $query->where('curso_id', $ads->id))->firstOrFail();
    $this->actingAs($coordenador)->get(route('indicadores.show', $aplicacaoAds))->assertForbidden();
});

it('preserves existing accounts and demo changes when seeded again', function () {
    $existente = User::factory()->create();
    $atributos = $existente->fresh()->getAttributes();
    $this->seed(DemonstracaoSeeder::class);
    $usuario = User::query()->where('identificador_institucional', 'DEMO-INFO-2026-01')->sole();
    $usuario->update(['password' => 'senha-alterada']);
    $hash = $usuario->fresh()->password;
    $respostas = Resposta::query()->count();
    $aplicacao = AplicacaoPesquisa::query()->where('status', 'aberta')->firstOrFail();
    $aplicacao->update(['status' => 'encerrada', 'data_encerramento' => now()->addDays(30)]);
    $historica = AplicacaoPesquisa::query()->where('nome', 'like', '[DEMO] Rodada 1%')->firstOrFail();
    $prazoHistorico = $historica->data_encerramento->toDateTimeString();
    $this->travel(40)->days();
    $this->seed(DemonstracaoSeeder::class);

    expect(User::query()->count())->toBe(85)
        ->and(Resposta::query()->count())->toBe($respostas)
        ->and(AplicacaoPesquisa::query()->count())->toBe(16)
        ->and($aplicacao->fresh()->status)->toBe(StatusAplicacao::Encerrada)
        ->and($aplicacao->fresh()->data_encerramento->copy()->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s'))->toBe('07/11/2026 23:59:59')
        ->and($historica->fresh()->data_encerramento->toDateTimeString())->toBe($prazoHistorico)
        ->and($usuario->fresh()->password)->toBe($hash)
        ->and($existente->fresh()->getAttributes())->toBe($atributos);
});

it('rolls back the entire load when an existing account uses a demo identifier', function () {
    $existente = User::factory()->create(['identificador_institucional' => 'demo.info']);
    expect(fn () => $this->seed(DemonstracaoSeeder::class))->toThrow(RuntimeException::class);
    expect(User::query()->sole()->id)->toBe($existente->id)
        ->and(Campus::query()->count())->toBe(0)
        ->and(ModeloQuestionario::query()->count())->toBe(0);
});
