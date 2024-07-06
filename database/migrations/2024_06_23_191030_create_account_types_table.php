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
        Schema::create('account_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            
            $table->enum('type', ['assets', 'bank', 'credit_card', 'liabilities', 'revenues', 'expenses'])->default('assets');
            $table->string('name'); // Nome del conto
            $table->string('account_number')->nullable(); // Numero del conto (se Beni -> Banca)
            $table->string('currency')->nullable(); // Valuta (se Beni -> Banca oppure Passività -> Carta di Credito)
            $table->decimal('initial_balance', 10, 2)->default(0);
            $table->text('description')->nullable(); // Descrizione del conto
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_types');
    }
};
