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
        Schema::create('aplicacao_perguntas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aplicacao_id')->constrained('aplicacoes_pesquisa')->restrictOnDelete();
            $table->foreignId('pergunta_id')->constrained('perguntas')->restrictOnDelete();
            $table->foreignId('modelo_questionario_id')->nullable()->constrained('modelos_questionario')->restrictOnDelete();
            $table->string('origem', 20)->index();
            $table->unsignedSmallInteger('ordem');
            $table->boolean('obrigatoria')->default(true);
            $table->text('texto_snapshot');
            $table->string('orientacao_escala_snapshot', 30);
            $table->unsignedTinyInteger('valor_minimo_snapshot');
            $table->unsignedTinyInteger('valor_maximo_snapshot');
            $table->timestamps();

            $table->unique(['aplicacao_id', 'pergunta_id'], 'aplicacao_pergunta_unique');
            $table->unique(['aplicacao_id', 'ordem'], 'aplicacao_pergunta_ordem_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aplicacao_perguntas');
    }
};
