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
        Schema::create('participacoes_pesquisa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aplicacao_id')->constrained('aplicacoes_pesquisa')->restrictOnDelete();
            $table->foreignId('estudante_id')->constrained('estudantes')->restrictOnDelete();
            $table->string('status', 20)->default('nao_iniciada')->index();
            $table->timestamp('iniciado_em')->nullable();
            $table->timestamp('finalizado_em')->nullable();
            $table->timestamps();

            $table->unique(
                ['aplicacao_id', 'estudante_id'],
                'aplicacao_estudante_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participacoes_pesquisa');
    }
};
