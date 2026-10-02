<?php

use App\Models\Campus;
use App\Models\Curso;
use App\Models\UsuarioPermissao;
use App\Models\User;
use App\NivelEscopo;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('assigns multiple campus and course permissions to one server', function () {
    $gestor = User::factory()->administrador()->create();
    UsuarioPermissao::factory()->for($gestor, 'usuario')->create();
    $servidor = User::factory()->servidor()->create();
    $campus = Campus::factory()->create();
    $curso = Curso::factory()->for($campus)->create();

    $this->actingAs($gestor)->post(route('servidores.permissoes.store', $servidor), [
        'nivel_escopo' => 'campus', 'campus_id' => $campus->id,
        'papel' => 'direção de ensino',
    ])->assertRedirectToRoute('servidores.edit', $servidor);

    $this->actingAs($gestor)->post(route('servidores.permissoes.store', $servidor), [
        'nivel_escopo' => 'curso', 'curso_id' => $curso->id,
        'papel' => 'coordenador',
    ])->assertRedirectToRoute('servidores.edit', $servidor);

    expect($servidor->permissoes()->count())->toBe(2);
    expect($servidor->permissoes()->where('nivel_escopo', NivelEscopo::Curso->value)->sole()->curso_id)->toBe($curso->id);
    expect($servidor->podeAcessarCurso($curso))->toBeTrue();
});

it('rejects invalid and duplicate permission scopes', function () {
    $gestor = User::factory()->administrador()->create();
    UsuarioPermissao::factory()->for($gestor, 'usuario')->create();
    $servidor = User::factory()->servidor()->create();
    $campus = Campus::factory()->create();
    $curso = Curso::factory()->for($campus)->create();

    $this->actingAs($gestor)->post(route('servidores.permissoes.store', $servidor), [
        'nivel_escopo' => 'curso', 'campus_id' => $campus->id,
        'curso_id' => $curso->id, 'papel' => 'coordenador',
    ])->assertSessionHasErrors('curso_id');

    $this->actingAs($gestor)->post(route('servidores.permissoes.store', $servidor), [
        'nivel_escopo' => 'campus', 'campus_id' => $campus->id,
        'papel' => 'direcao',
    ])->assertRedirectToRoute('servidores.edit', $servidor);

    $this->actingAs($gestor)->post(route('servidores.permissoes.store', $servidor), [
        'nivel_escopo' => 'campus', 'campus_id' => $campus->id,
        'papel' => 'direcao',
    ])->assertSessionHasErrors('papel');

    expect($servidor->permissoes()->count())->toBe(1);
});

it('deactivates and reactivates permissions without removing their history', function () {
    $gestor = User::factory()->administrador()->create();
    UsuarioPermissao::factory()->for($gestor, 'usuario')->create();
    $servidor = User::factory()->servidor()->create();
    $permissao = UsuarioPermissao::factory()->for($servidor, 'usuario')->create();

    $this->actingAs($gestor)->patch(route('permissoes.update', $permissao), ['ativo' => false])
        ->assertRedirectToRoute('servidores.edit', $servidor);

    expect($permissao->fresh()->ativo)->toBeFalse();
    expect($servidor->fresh()->podeAcessarSistema())->toBeFalse();

    $this->actingAs($gestor)->patch(route('permissoes.update', $permissao), ['ativo' => true])
        ->assertRedirectToRoute('servidores.edit', $servidor);

    expect($permissao->fresh()->ativo)->toBeTrue();
    expect($servidor->fresh()->podeAcessarSistema())->toBeTrue();
});

it('does not allow a campus manager to grant institutional access', function () {
    $campus = Campus::factory()->create();
    $gestorCampus = User::factory()->servidor()->create();
    UsuarioPermissao::factory()->for($gestorCampus, 'usuario')->paraCampus($campus)->create();
    $servidor = User::factory()->servidor()->create();

    $this->actingAs($gestorCampus)->post(route('servidores.permissoes.store', $servidor), [
        'nivel_escopo' => 'instituicao', 'papel' => 'gestao',
    ])->assertForbidden();

    expect($servidor->permissoes()->count())->toBe(0);
});

it('prevents institutional servers from altering their own permissions', function () {
    $gestor = User::factory()->servidor()->create();
    $permissao = UsuarioPermissao::factory()->for($gestor, 'usuario')->create();

    $this->actingAs($gestor)->patch(route('permissoes.update', $permissao), ['ativo' => false])
        ->assertForbidden();

    expect($permissao->fresh()->ativo)->toBeTrue();
});

it('changes permission scope and role while preserving the existing permission record', function () {
    $gestor = User::factory()->administrador()->create();
    UsuarioPermissao::factory()->for($gestor, 'usuario')->create();
    $servidor = User::factory()->servidor()->create();
    $campus = Campus::factory()->create();
    $curso = Curso::factory()->for($campus)->create();
    $permissao = UsuarioPermissao::factory()->for($servidor, 'usuario')->paraCampus($campus)->create();

    $this->actingAs($gestor)->get(route('permissoes.edit', $permissao))
        ->assertOk()->assertSee($servidor->name);

    $this->actingAs($gestor)->put(route('permissoes.details.update', $permissao), [
        'nivel_escopo' => 'curso',
        'curso_id' => $curso->id,
        'papel' => 'vice-coordenador',
    ])->assertRedirectToRoute('servidores.edit', $servidor);

    $permissao->refresh();
    expect($permissao->nivel_escopo)->toBe(NivelEscopo::Curso);
    expect($permissao->campus_id)->toBeNull();
    expect($permissao->curso_id)->toBe($curso->id);
    expect($permissao->papel)->toBe('vice-coordenador');
    expect($servidor->permissoes()->count())->toBe(1);
});

it('rejects changing a permission into a duplicate of another record', function () {
    $gestor = User::factory()->administrador()->create();
    UsuarioPermissao::factory()->for($gestor, 'usuario')->create();
    $servidor = User::factory()->servidor()->create();
    $campus = Campus::factory()->create();
    $primeira = UsuarioPermissao::factory()->for($servidor, 'usuario')->paraCampus($campus)->create();
    $segunda = UsuarioPermissao::factory()->for($servidor, 'usuario')->create();

    $this->actingAs($gestor)->put(route('permissoes.details.update', $segunda), [
        'nivel_escopo' => 'campus',
        'campus_id' => $campus->id,
        'papel' => $primeira->papel,
    ])->assertSessionHasErrors('papel');

    expect($segunda->fresh()->nivel_escopo)->toBe(NivelEscopo::Instituicao);
});
