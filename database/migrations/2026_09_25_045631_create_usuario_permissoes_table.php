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
        Schema::create('usuario_permissoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->string('nivel_escopo', 20);
            $table->foreignId('campus_id')->nullable()->constrained('campi')->restrictOnDelete();
            $table->foreignId('curso_id')->nullable()->constrained('cursos')->restrictOnDelete();
            $table->string('papel', 50);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['nivel_escopo', 'ativo']);
            $table->index(['usuario_id', 'ativo']);
            $table->unique(
                ['usuario_id', 'nivel_escopo', 'campus_id', 'curso_id', 'papel'],
                'usuario_permissao_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuario_permissoes');
    }
};
