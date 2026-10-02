<?php

namespace App\Actions;

use App\Models\Estudante;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RedefinirSenhaEstudante
{
    public function handle(Estudante $estudante): void
    {
        $estudante->loadMissing(['turma', 'usuario']);

        DB::transaction(function () use ($estudante): void {
            $estudante->usuario->forceFill([
                'password' => Hash::make($estudante->turma->nome_amigavel),
                'remember_token' => Str::random(60),
            ])->save();
        });
    }
}
