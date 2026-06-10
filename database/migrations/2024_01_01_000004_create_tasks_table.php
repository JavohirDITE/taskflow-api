<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('creator_id')->constrained('users');
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('todo');
            $table->string('priority', 20)->default('medium');
            $table->timestamp('due_date')->nullable();
            $table->decimal('estimated_hours', 6, 2)->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Composite indexes for common filter queries
            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'priority']);
            $table->index(['assignee_id', 'status']);
            $table->index(['due_date', 'status']); // For overdue queries
            $table->index('is_archived');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
