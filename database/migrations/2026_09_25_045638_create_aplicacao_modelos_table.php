<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('aplicacao_modelos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aplicacao_id')->constrained('aplicacoes_pesquisa')->restrictOnDelete();
            $table->foreignId('modelo_questionario_id')->constrained('modelos_questionario')->restrictOnDelete();
            $table->timestamps();

            $table->unique(
                ['aplicacao_id', 'modelo_questionario_id'],
                'aplicacao_modelo_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aplicacao_modelos');
    }
};
