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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');

            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->enum('can_login', ['yes', 'no'])->default('no');

            // Dati fiscali
            $table->string('company_name', 80)->nullable(); // Denominazione (80 caratteri)
            $table->string('first_name', 60)->nullable(); // Nome (alfanumerico, max 60 caratteri)
            $table->string('last_name', 60)->nullable(); // Cognome (alfanumerico, max 60 caratteri)
            $table->string('full_name', 255)->nullable()->storedAs(
                "CASE
                     WHEN customer_type = 'individual' THEN 
                         CASE
                             WHEN first_name IS NOT NULL AND last_name IS NOT NULL THEN CONCAT(first_name, ' ', last_name)
                             ELSE CONCAT_WS(' ', COALESCE(first_name, ''), COALESCE(last_name, ''))
                         END
                     ELSE 
                         CASE
                             WHEN first_name IS NOT NULL AND last_name IS NOT NULL THEN CONCAT(company_name, ' (', first_name, ' ', last_name, ')')
                             ELSE CONCAT_WS(' ', company_name, COALESCE(CONCAT('(', first_name, ' ', last_name, ')'), ''))
                         END
                 END"
            );
            $table->string('country_id', 2)->nullable(); // ID Paese (alfanumerico, 2 caratteri)
            $table->string('vat_number', 28)->nullable()->unique(); // P.IVA (univoca, numerico, 11 caratteri)
            $table->string('fiscal_code', 16)->nullable()->unique(); // Codice Fiscale (univoco, alfanumerico tra 11 e 16 caratteri)
            $table->string('recipient_code', 7)->nullable(); // Codice Destinatario (alfanumerico, 7 caratteri)
            $table->string('pec', 255)->nullable(); // Casella Pec (alfanumerico, tra 7 e 255)
            $table->string('email_copy', 255)->nullable(); // Email per copia documenti (alfanumerico, tra 7 e 255)
            $table->string('pec_copy', 255)->nullable(); // Pec per copia documenti (alfanumerico, tra 7 e 255)
            $table->string('phone', 12)->nullable(); // Telefono (alfanumerico, tra 5 e 12)
            $table->string('admin_reference', 40)->nullable(); // Riferimento Amministrazione (alfanumerico, max 40 caratteri)
            

            // Indirizzo
            $table->string('country')->nullable(); // Nazione
            $table->string('postal_code', 10)->nullable(); // CAP (numerico, 5 caratteri)
            $table->string('province')->nullable(); // Provincia
            $table->string('city')->nullable(); // Comune
            $table->string('address')->nullable(); // Indirizzo (alfanumerico, 80 caratteri)

            // Altri dati
            $table->enum('supplier_type', ['individual', 'company'])->default('company');
            $table->string('currency')->nullable(); // Valuta
            $table->text('notes')->nullable(); // Note

            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};