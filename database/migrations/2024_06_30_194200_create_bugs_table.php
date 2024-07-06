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
        Schema::create('bugs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            
            $table->string('code');
            $table->string('title');
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->enum('priority', ['low', 'medium', 'high'])->default('low');
            $table->text('problem_notes');
            $table->boolean('is_solved')->default(false);
            $table->text('resolution_notes')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('bug_type', ['functional', 'visual', 'security', 'other']);
            $table->string('software_version')->nullable();
            $table->enum('environment', ['production', 'development']);
            $table->text('steps_to_reproduce')->nullable();
            $table->json('attachments')->nullable();
            $table->json('labels')->nullable();
            $table->date('deadline')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        Schema::dropIfExists('bugs');
    }
};
