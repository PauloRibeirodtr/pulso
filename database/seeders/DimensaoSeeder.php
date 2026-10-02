<?php

namespace Database\Seeders;

use App\Models\Dimensao;
use Illuminate\Database\Seeder;

class DimensaoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dimensoes = [
            'Aprendizagem',
            'Permanência e intenção de continuidade',
            'Dificuldades acadêmicas',
            'Infraestrutura',
            'Ambiente e experiência no curso',
            'Apoio ao estudante',
            'Adaptação institucional',
            'Organização acadêmica',
            'Pertencimento e integração',
        ];

        foreach ($dimensoes as $nome) {
            Dimensao::query()->firstOrCreate(['nome' => $nome]);
        }
    }
}
