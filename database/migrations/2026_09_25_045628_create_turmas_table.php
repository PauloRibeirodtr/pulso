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
        Schema::create('turmas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->constrained('cursos')->restrictOnDelete();
            $table->string('nome_amigavel', 100);
            $table->string('codigo_academico', 100)->nullable();
            $table->year('ano_ingresso')->nullable();
            $table->string('periodo_ingresso', 30)->nullable();
            $table->boolean('ativo')->default(true)->index();
            $table->timestamps();

            $table->unique(['curso_id', 'nome_amigavel']);
            $table->unique(['curso_id', 'codigo_academico']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('turmas');
    }
};
