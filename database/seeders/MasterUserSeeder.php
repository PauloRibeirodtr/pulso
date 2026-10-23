<?php

namespace Database\Seeders;

use App\Models\User;
use App\NivelEscopo;
use App\TipoUsuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MasterUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $credentials = config('auth.master');

        if (blank($credentials['username']) && blank($credentials['password'])) {
            $this->command?->warn('Administrador inicial não criado: configure MASTER_USERNAME e MASTER_PASSWORD.');

            return;
        }

        $credentials = Validator::make($credentials, [
            'username' => ['required', 'string', 'max:50', 'regex:/^\S+$/u'],
            'password' => ['required', 'string', 'min:8'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
        ])->validate();

        DB::transaction(function () use ($credentials): void {
            $user = User::query()->firstOrCreate([
                'identificador_institucional' => $credentials['username'],
            ], [
                'name' => $credentials['name'],
                'email' => $credentials['email'] ?: null,
                'password' => $credentials['password'],
                'tipo_usuario' => TipoUsuario::Admin,
                'ativo' => true,
            ]);

            if (! $user->wasRecentlyCreated) {
                $this->command?->warn('O identificador MASTER_USERNAME já existe. A conta e suas permissões foram preservadas.');

                return;
            }

            $user->permissoes()->create([
                'nivel_escopo' => NivelEscopo::Instituicao,
                'campus_id' => null,
                'curso_id' => null,
                'papel' => 'administrador_institucional',
                'ativo' => true,
            ]);

            $this->command?->info('Administrador inicial criado com permissão institucional.');
        });
    }
}
