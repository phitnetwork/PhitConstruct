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
         Schema::create('projects', function (Blueprint $table) {
             $table->id();
             $table->foreignId('organization_id')->constrained()->onDelete('cascade');
             
             $table->string('name');
             $table->text('description')->nullable();

             $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();

             // Gestione Deadlines
             $table->date('deadline')->nullable();
             $table->string('estimated_hours_client', 50)->nullable();
 
             // Stato e tipo
            $table->enum('status', [
                    'planned', 
                    'waiting_for_quote', 
                    'waiting_for_advance', 
                    'in_progress', 
                    'on_hold', 
                    'invoiced',
                    'paid',
                    'canceled_by_client', 
                    'quote_refused'
                ])->default('planned');

             $table->string('project_type')->nullable();
             $table->decimal('budget', 10, 2)->nullable();
             $table->integer('prepayment_percentage', 2)->default(50);
             $table->decimal('prepayment_amount', 10, 2)
                 ->virtualAs('budget * (prepayment_percentage / 100.0)')->nullable();

            $table->string('hours_worked', 50)->nullable();
            $table->enum('priority', ['low', 'medium', 'high'])->default('low');
            $table->json('milestones')->nullable();
            $table->json('attachments')->nullable();
            $table->text('notes')->nullable();
            $table->string('color', 7)->nullable();

            $table->timestamps();
         });
             
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
