<?php

use App\Models\Curso;
use App\Models\Estudante;
use App\Models\Turma;
use App\Models\UsuarioPermissao;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

it('registers a student with hashed initial password from the selected class', function () {
    $turma = Turma::factory()->create(['nome_amigavel' => 'INFO2026A']);
    $servidor = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($servidor, 'usuario')->paraCurso($turma->curso)->create();

    $this->actingAs($servidor)->post(route('estudantes.store'), [
        'matricula' => '20260001',
        'nome' => '  Ana   Silva ',
        'turma_id' => $turma->id,
        'situacao_academica' => 'ativo',
    ])->assertRedirectToRoute('estudantes.index');

    $estudante = Estudante::query()->with('usuario')->sole();
    expect($estudante->turma_id)->toBe($turma->id);
    expect($estudante->usuario->name)->toBe('Ana Silva');
    expect($estudante->usuario->email)->toBeNull();
    expect($estudante->usuario->password)->not->toBe('INFO2026A');
    expect(Hash::check('INFO2026A', $estudante->usuario->password))->toBeTrue();
});

it('lists only students within the server academic scope', function () {
    $curso = Curso::factory()->create(['nome' => 'Curso Completo', 'sigla' => 'CC']);
    $curso->campus->update(['nome' => 'Campus Completo', 'sigla' => 'CP']);
    $permitido = Estudante::factory()->for(Turma::factory()->for($curso))->create();
    $oculto = Estudante::factory()->create();
    $servidor = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($servidor, 'usuario')->paraCurso($curso)->create();

    $this->actingAs($servidor)->get(route('estudantes.index'))
        ->assertOk()
        ->assertSee($permitido->usuario->identificador_institucional)
        ->assertSee('CC')
        ->assertSee('CP')
        ->assertDontSee('Curso Completo')
        ->assertDontSee('Campus Completo')
        ->assertDontSee($oculto->usuario->identificador_institucional);
});

it('searches students by name, registration, friendly class name and academic class code within scope', function () {
    $curso = Curso::factory()->create();
    $turma = Turma::factory()->for($curso)->create([
        'nome_amigavel' => 'ADM2026A',
        'codigo_academico' => 'TUR-9843',
    ]);
    $permitido = Estudante::factory()->for($turma)->create();
    $permitido->usuario->update([
        'name' => 'Marina Costa',
        'identificador_institucional' => '202600876',
    ]);
    $outraTurma = Turma::factory()->for($curso)->create(['nome_amigavel' => 'INFO2026B']);
    $outroEstudante = Estudante::factory()->for($outraTurma)->create();
    $turmaForaDoEscopo = Turma::factory()->create(['nome_amigavel' => 'ADM2026A']);
    $oculto = Estudante::factory()->for($turmaForaDoEscopo)->create();
    $servidor = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($servidor, 'usuario')->paraCurso($curso)->create();

    foreach (['Marina', '202600876', 'ADM2026A', 'TUR-9843'] as $busca) {
        $this->actingAs($servidor)->get(route('estudantes.index', ['busca' => $busca]))
            ->assertOk()
            ->assertSee('202600876')
            ->assertDontSee($outroEstudante->usuario->identificador_institucional)
            ->assertDontSee($oculto->usuario->identificador_institucional);
    }
});

it('rejects registering or moving a student into a class outside the scope', function () {
    $turmaPermitida = Turma::factory()->create();
    $turmaOculta = Turma::factory()->create();
    $estudante = Estudante::factory()->for($turmaPermitida)->create();
    $servidor = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($servidor, 'usuario')->paraCurso($turmaPermitida->curso)->create();

    $this->actingAs($servidor)->post(route('estudantes.store'), [
        'matricula' => '20260002', 'nome' => 'Outro Estudante',
        'turma_id' => $turmaOculta->id, 'situacao_academica' => 'ativo',
    ])->assertSessionHasErrors('turma_id');

    $this->actingAs($servidor)->put(route('estudantes.update', $estudante), [
        'matricula' => $estudante->usuario->identificador_institucional,
        'nome' => $estudante->usuario->name,
        'turma_id' => $turmaOculta->id, 'situacao_academica' => 'ativo',
    ])->assertSessionHasErrors('turma_id');

    expect($estudante->fresh()->turma_id)->toBe($turmaPermitida->id);
    expect(Estudante::query()->count())->toBe(1);
});

it('updates academic status and leaves password unchanged when moving within a course', function () {
    $curso = Curso::factory()->create();
    $turmaOrigem = Turma::factory()->for($curso)->create();
    $turmaDestino = Turma::factory()->for($curso)->create();
    $estudante = Estudante::factory()->for($turmaOrigem)->create();
    $senhaAnterior = $estudante->usuario->password;
    $servidor = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($servidor, 'usuario')->paraCurso($curso)->create();

    $this->actingAs($servidor)->put(route('estudantes.update', $estudante), [
        'matricula' => $estudante->usuario->identificador_institucional,
        'nome' => 'Nome Atualizado',
        'turma_id' => $turmaDestino->id,
        'situacao_academica' => 'inativo',
    ])->assertRedirectToRoute('estudantes.index');

    expect($estudante->fresh()->turma_id)->toBe($turmaDestino->id);
    expect($estudante->fresh()->situacao_academica)->toBe('inativo');
    expect($estudante->usuario->fresh()->ativo)->toBeFalse();
    expect($estudante->usuario->password)->toBe($senhaAnterior);
});

it('imports a class CSV atomically with initial student passwords', function () {
    $turma = Turma::factory()->create(['nome_amigavel' => 'ADM2026B']);
    $servidor = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($servidor, 'usuario')->paraCurso($turma->curso)->create();
    $csv = UploadedFile::fake()->createWithContent('estudantes.csv', "\xEF\xBB\xBFmatricula;nome\n20260011;Ana Silva\n20260012;João Souza\n");

    $this->actingAs($servidor)->post(route('estudantes.import'), [
        'turma_id' => $turma->id,
        'arquivo' => $csv,
    ])->assertRedirectToRoute('estudantes.index');

    expect(Estudante::query()->count())->toBe(2);
    expect(Hash::check('ADM2026B', User::query()->where('identificador_institucional', '20260011')->sole()->password))->toBeTrue();
});

it('rejects duplicate registrations in a CSV without partial imports', function () {
    $turma = Turma::factory()->create();
    $servidor = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($servidor, 'usuario')->paraCurso($turma->curso)->create();
    $csv = UploadedFile::fake()->createWithContent('estudantes.csv', "matricula;nome\n20260013;Ana\n20260013;Bia\n");

    $this->actingAs($servidor)->post(route('estudantes.import'), [
        'turma_id' => $turma->id,
        'arquivo' => $csv,
    ])->assertSessionHasErrors('arquivo');

    expect(Estudante::query()->count())->toBe(0);
});

it('rejects a CSV containing a registration already present in the system', function () {
    $turma = Turma::factory()->create();
    $existente = Estudante::factory()->for($turma)->create();
    $matricula = $existente->usuario->identificador_institucional;
    $servidor = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($servidor, 'usuario')->paraCurso($turma->curso)->create();
    $csv = UploadedFile::fake()->createWithContent('estudantes.csv', "matricula;nome\n20260014;Novo\n{$matricula};Repetido\n");

    $this->actingAs($servidor)->post(route('estudantes.import'), [
        'turma_id' => $turma->id,
        'arquivo' => $csv,
    ])->assertSessionHasErrors('arquivo');

    expect(Estudante::query()->count())->toBe(1);
    expect(User::query()->where('identificador_institucional', '20260014')->exists())->toBeFalse();
});

it('renders the student registration, editing and import forms for an authorized server', function () {
    $turma = Turma::factory()->create();
    $estudante = Estudante::factory()->for($turma)->create();
    $servidor = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($servidor, 'usuario')->paraCurso($turma->curso)->create();

    $this->actingAs($servidor)->get(route('estudantes.create'))
        ->assertOk()->assertSee($turma->nome_amigavel);
    $this->actingAs($servidor)->get(route('estudantes.edit', $estudante))
        ->assertOk()->assertSee($estudante->usuario->name);
    $this->actingAs($servidor)->get(route('estudantes.import.form'))
        ->assertOk()->assertSee('matricula;nome');
});

it('denies student management to a student account', function () {
    $usuario = User::factory()->estudante()->create();

    $this->actingAs($usuario)->get(route('estudantes.index'))->assertForbidden();
    $this->actingAs($usuario)->get(route('estudantes.import.form'))->assertForbidden();
});
