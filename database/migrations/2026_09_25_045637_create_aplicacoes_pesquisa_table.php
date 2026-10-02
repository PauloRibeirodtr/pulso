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
        Schema::create('aplicacoes_pesquisa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turma_id')->constrained('turmas')->restrictOnDelete();
            $table->string('nome', 150);
            $table->string('tipo_aplicacao', 20)->index();
            $table->timestamp('data_abertura')->nullable()->index();
            $table->timestamp('data_encerramento')->nullable()->index();
            $table->string('status', 20)->default('planejada')->index();
            $table->foreignId('criado_por_usuario_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['turma_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aplicacoes_pesquisa');
    }
};
