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
        Schema::create('recurrent_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            
            $table->string('name');
            $table->enum('repeat_interval', [
                'every_day',
                'every_week',
                'every_2_weeks',
                'every_month',
                'every_2_months',
                'every_3_months',
                'every_6_months',
                'every_year',
                'every_2_years',
                'every_3_years',
            ])->default('every_month');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('amount', 10, 2);
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurrent_expenses');
    }
};
