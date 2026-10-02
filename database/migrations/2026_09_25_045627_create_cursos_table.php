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
        Schema::create('cursos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campus_id')->constrained('campi')->restrictOnDelete();
            $table->string('nome', 150);
            $table->string('sigla', 30)->nullable();
            $table->boolean('ativo')->default(true)->index();
            $table->timestamps();

            $table->unique(['campus_id', 'nome']);
            $table->unique(['campus_id', 'sigla']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cursos');
    }
};
