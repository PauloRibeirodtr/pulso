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
        Schema::create('solicitacoes_acompanhamento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participacao_id')->unique()->constrained('participacoes_pesquisa')->restrictOnDelete();
            $table->string('status', 20)->default('pendente')->index();
            $table->timestamp('solicitado_em');
            $table->foreignId('responsavel_usuario_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('atendido_em')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitacoes_acompanhamento');
    }
};
