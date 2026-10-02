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
        Schema::create('perguntas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dimensao_id')->constrained('dimensoes')->restrictOnDelete();
            $table->foreignId('curso_origem_id')->nullable()->constrained('cursos')->restrictOnDelete();
            $table->text('texto');
            $table->boolean('sensivel')->default(false)->index();
            $table->string('orientacao_escala', 30)->default('menor_maior_atencao');
            $table->unsignedTinyInteger('valor_minimo')->default(1);
            $table->unsignedTinyInteger('valor_maximo')->default(5);
            $table->boolean('ativo')->default(true)->index();
            $table->timestamps();

            $table->index(['dimensao_id', 'ativo']);
            $table->index(['curso_origem_id', 'ativo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perguntas');
    }
};
