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
        Schema::create('modelos_questionario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->nullable()->constrained('cursos')->restrictOnDelete();
            $table->string('nome', 150);
            $table->string('escopo_modelo', 20)->index();
            $table->string('tipo', 20)->index();
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true)->index();
            $table->foreignId('criado_por_usuario_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['curso_id', 'nome']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modelos_questionario');
    }
};
