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
        Schema::create('modelo_questionario_perguntas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modelo_questionario_id')->constrained('modelos_questionario')->restrictOnDelete();
            $table->foreignId('pergunta_id')->constrained('perguntas')->restrictOnDelete();
            $table->unsignedSmallInteger('ordem');
            $table->boolean('obrigatoria')->default(true);
            $table->boolean('ativo')->default(true)->index();
            $table->timestamps();

            $table->unique(
                ['modelo_questionario_id', 'pergunta_id'],
                'modelo_pergunta_unique',
            );
            $table->unique(
                ['modelo_questionario_id', 'ordem'],
                'modelo_pergunta_ordem_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modelo_questionario_perguntas');
    }
};
