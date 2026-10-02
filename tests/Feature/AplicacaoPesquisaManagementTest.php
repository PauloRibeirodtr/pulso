<?php

use App\Models\AplicacaoPesquisa;
use App\Models\Curso;
use App\Models\Estudante;
use App\Models\ModeloQuestionario;
use App\Models\Pergunta;
use App\Models\Turma;
use App\Models\User;
use App\Models\UsuarioPermissao;
use App\StatusAplicacao;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function modelosParaAplicacao(Curso $curso): array
{
    $institucional = ModeloQuestionario::factory()->create(['publicado_em' => now()]);
    $perguntaInstitucional = Pergunta::factory()->create(['texto' => 'Pergunta institucional?']);
    $institucional->itens()->create(['pergunta_id' => $perguntaInstitucional->id, 'ordem' => 1, 'obrigatoria' => true]);

    $modeloCurso = ModeloQuestionario::factory()->create([
        'curso_id' => $curso->id,
        'escopo_modelo' => 'curso',
        'publicado_em' => now(),
    ]);
    $perguntaCurso = Pergunta::factory()->create(['curso_origem_id' => $curso->id, 'texto' => 'Pergunta do curso?']);
    $modeloCurso->itens()->create(['pergunta_id' => $perguntaCurso->id, 'ordem' => 1, 'obrigatoria' => false]);

    return [$institucional, $modeloCurso];
}

it('plans an application with three blocks and snapshots the published questions', function () {
    $curso = Curso::factory()->create();
    $turma = Turma::factory()->for($curso)->create();
    $user = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($user, 'usuario')->paraCurso($curso)->create();
    [$institucional, $modeloCurso] = modelosParaAplicacao($curso);
    $temporaria = Pergunta::factory()->create(['curso_origem_id' => $curso->id, 'texto' => 'Pergunta temporária?']);

    $this->actingAs($user)->post(route('aplicacoes.store'), [
        'turma_id' => $turma->id,
        'nome' => '  Rodada   de outubro ',
        'tipo_aplicacao' => 'monitoramento',
        'institucional_modelo_id' => $institucional->id,
        'curso_modelo_id' => $modeloCurso->id,
        'temporarias' => [$temporaria->id],
    ])->assertRedirect();

    $aplicacao = AplicacaoPesquisa::query()->sole();
    expect($aplicacao->nome)->toBe('Rodada de outubro');
    expect($aplicacao->status)->toBe(StatusAplicacao::Planejada);
    expect($aplicacao->perguntas()->orderBy('ordem')->get()->pluck('origem')->map->value->all())
        ->toBe(['institucional', 'curso', 'temporaria']);
    expect($aplicacao->perguntas()->orderBy('ordem')->pluck('texto_snapshot')->all())
        ->toBe(['Pergunta institucional?', 'Pergunta do curso?', 'Pergunta temporária?']);
    expect($aplicacao->modelosQuestionario()->count())->toBe(2);

    $this->actingAs($user)->get(route('aplicacoes.show', $aplicacao))
        ->assertOk()->assertSee('Pergunta temporária?');
});

it('opens the application for active students and freezes planning', function () {
    $curso = Curso::factory()->create();
    $turma = Turma::factory()->for($curso)->create();
    $user = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($user, 'usuario')->paraCurso($curso)->create();
    [$institucional, $modeloCurso] = modelosParaAplicacao($curso);
    $estudante = Estudante::factory()->for($turma)->create();
    Estudante::factory()->for($turma)->create(['situacao_academica' => 'inativo']);
    $aplicacao = AplicacaoPesquisa::factory()->for($turma)->create(['criado_por_usuario_id' => $user->id]);
    $aplicacao->modelosQuestionario()->attach([$institucional->id, $modeloCurso->id]);
    $aplicacao->perguntas()->create([
        'pergunta_id' => $institucional->itens()->first()->pergunta_id,
        'modelo_questionario_id' => $institucional->id,
        'origem' => 'institucional',
        'ordem' => 1,
        'texto_snapshot' => 'Pergunta institucional?',
        'orientacao_escala_snapshot' => 'menor_maior_atencao',
        'valor_minimo_snapshot' => 1,
        'valor_maximo_snapshot' => 5,
    ]);

    $this->actingAs($user)->post(route('aplicacoes.open', $aplicacao))->assertRedirect();

    expect($aplicacao->fresh()->status)->toBe(StatusAplicacao::Aberta);
    expect($aplicacao->participacoes()->pluck('estudante_id')->all())->toBe([$estudante->id]);

    $this->actingAs($user)->get(route('aplicacoes.edit', $aplicacao))->assertForbidden();
    $this->actingAs($user)->post(route('aplicacoes.open', $aplicacao))->assertForbidden();

    $this->actingAs($user)->patch(route('aplicacoes.extend', $aplicacao), [
        'data_encerramento' => now()->addDays(10)->format('Y-m-d H:i:s'),
    ])->assertRedirect();

    $this->actingAs($user)->post(route('aplicacoes.close', $aplicacao))->assertRedirect();
    expect($aplicacao->fresh()->status)->toBe(StatusAplicacao::Encerrada);
});

it('rejects models and temporary questions outside the course', function () {
    $curso = Curso::factory()->create();
    $outroCurso = Curso::factory()->create();
    $turma = Turma::factory()->for($curso)->create();
    $user = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($user, 'usuario')->paraCurso($curso)->create();
    [$institucional] = modelosParaAplicacao($curso);
    [, $modeloOutroCurso] = modelosParaAplicacao($outroCurso);
    $temporaria = Pergunta::factory()->create(['curso_origem_id' => $outroCurso->id]);

    $this->actingAs($user)->post(route('aplicacoes.store'), [
        'turma_id' => $turma->id,
        'nome' => 'Fora do escopo',
        'tipo_aplicacao' => 'monitoramento',
        'institucional_modelo_id' => $institucional->id,
        'curso_modelo_id' => $modeloOutroCurso->id,
        'temporarias' => [$temporaria->id],
    ])->assertSessionHasErrors(['curso_modelo_id', 'temporarias.0']);

    expect(AplicacaoPesquisa::query()->count())->toBe(0);
});

it('does not let another course manage an application', function () {
    $curso = Curso::factory()->create();
    $outroCurso = Curso::factory()->create();
    $turma = Turma::factory()->for($curso)->create();
    $user = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($user, 'usuario')->paraCurso($outroCurso)->create();
    $aplicacao = AplicacaoPesquisa::factory()->for($turma)->create();

    $this->actingAs($user)->get(route('aplicacoes.show', $aplicacao))->assertForbidden();
    $this->actingAs($user)->post(route('aplicacoes.open', $aplicacao))->assertForbidden();
    $this->actingAs($user)->get(route('aplicacoes.index'))->assertOk()->assertDontSee($aplicacao->nome);
});

it('renders planning and detail pages for an authorized server', function () {
    $curso = Curso::factory()->create();
    $turma = Turma::factory()->for($curso)->create();
    $user = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($user, 'usuario')->paraCurso($curso)->create();
    [$institucional, $modeloCurso] = modelosParaAplicacao($curso);
    $aplicacao = AplicacaoPesquisa::factory()->for($turma)->create();
    $aplicacao->modelosQuestionario()->attach([$institucional->id, $modeloCurso->id]);

    $this->actingAs($user)->get(route('aplicacoes.create'))->assertOk();
    $this->actingAs($user)->get(route('aplicacoes.edit', $aplicacao))->assertOk();
    $this->actingAs($user)->get(route('aplicacoes.show', $aplicacao))->assertOk();
});

it('does not open a planned application after a selected model is retired', function () {
    $curso = Curso::factory()->create();
    $turma = Turma::factory()->for($curso)->create();
    $user = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($user, 'usuario')->paraCurso($curso)->create();
    [$institucional, $modeloCurso] = modelosParaAplicacao($curso);
    $aplicacao = AplicacaoPesquisa::factory()->for($turma)->create();
    $aplicacao->modelosQuestionario()->attach([$institucional->id, $modeloCurso->id]);
    $aplicacao->perguntas()->create([
        'pergunta_id' => $institucional->itens()->first()->pergunta_id,
        'origem' => 'institucional',
        'ordem' => 1,
        'texto_snapshot' => 'Pergunta institucional?',
        'orientacao_escala_snapshot' => 'menor_maior_atencao',
        'valor_minimo_snapshot' => 1,
        'valor_maximo_snapshot' => 5,
    ]);
    $modeloCurso->update(['ativo' => false]);

    $this->actingAs($user)->post(route('aplicacoes.open', $aplicacao))
        ->assertSessionHasErrors('modelos');

    expect($aplicacao->fresh()->status)->toBe(StatusAplicacao::Planejada);
});

it('rebuilds only a planned application when its temporary selection changes', function () {
    $curso = Curso::factory()->create();
    $turma = Turma::factory()->for($curso)->create();
    $user = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($user, 'usuario')->paraCurso($curso)->create();
    [$institucional, $modeloCurso] = modelosParaAplicacao($curso);
    $temporaria = Pergunta::factory()->create(['curso_origem_id' => $curso->id]);
    $aplicacao = AplicacaoPesquisa::factory()->for($turma)->create();

    $this->actingAs($user)->put(route('aplicacoes.update', $aplicacao), [
        'turma_id' => $turma->id,
        'nome' => 'Planejamento revisado',
        'tipo_aplicacao' => 'monitoramento',
        'institucional_modelo_id' => $institucional->id,
        'curso_modelo_id' => $modeloCurso->id,
        'temporarias' => [$temporaria->id],
    ])->assertRedirect();

    expect($aplicacao->fresh()->nome)->toBe('Planejamento revisado');
    expect($aplicacao->perguntas()->orderBy('ordem')->pluck('pergunta_id')->all())
        ->toBe([
            $institucional->itens()->first()->pergunta_id,
            $modeloCurso->itens()->first()->pergunta_id,
            $temporaria->id,
        ]);
});
