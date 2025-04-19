<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bancos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 10)->comment('Código do banco no sistema bancário');
            $table->string('nome', 100)->comment('Nome do banco');
            $table->timestamps();
        });

        // Inserir apenas os bancos suportados pelo sistema
        DB::table('bancos')->insert([
            ['codigo' => 1, 'nome' => 'Banco do Brasil', 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 2, 'nome' => 'Bradesco', 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 3, 'nome' => 'SICOOB', 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 4, 'nome' => 'C6', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bancos');
    }
};
