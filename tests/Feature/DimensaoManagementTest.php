<?php

use App\Models\Dimensao;
use App\Models\User;
use App\Models\UsuarioPermissao;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('allows institutional staff to manage dimensions', function () {
    $user = User::factory()->administrador()->create();
    UsuarioPermissao::factory()->for($user, 'usuario')->create();

    $this->actingAs($user)->post(route('dimensoes.store'), [
        'nome' => '  Condições   de estudo ',
        'descricao' => 'Ambiente e recursos',
        'ativo' => true,
    ])->assertRedirectToRoute('dimensoes.index');

    expect(Dimensao::query()->sole()->nome)->toBe('Condições de estudo');

    $this->actingAs($user)->put(route('dimensoes.update', Dimensao::query()->sole()), [
        'nome' => 'Condições de estudo',
        'descricao' => 'Atualizada',
        'ativo' => false,
    ])->assertRedirectToRoute('dimensoes.index');

    expect(Dimensao::query()->sole()->ativo)->toBeFalse();
});

it('does not allow a student to list dimensions', function () {
    $user = User::factory()->estudante()->create();

    $this->actingAs($user)->get(route('dimensoes.index'))->assertForbidden();
});
