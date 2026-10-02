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
        Schema::create('respostas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participacao_id')->constrained('participacoes_pesquisa')->restrictOnDelete();
            $table->foreignId('aplicacao_pergunta_id')->constrained('aplicacao_perguntas')->restrictOnDelete();
            $table->unsignedTinyInteger('valor_numerico');
            $table->timestamp('respondido_em')->nullable();
            $table->timestamps();

            $table->unique(
                ['participacao_id', 'aplicacao_pergunta_id'],
                'participacao_pergunta_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('respostas');
    }
};
