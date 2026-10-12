<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submitted_by')->constrained('users');
            $table->decimal('physical_progress_percent', 5, 2);
            $table->decimal('financial_progress_percent', 5, 2)->nullable();
            $table->text('narrative')->nullable();
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('project_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g. Site Clearance, Earthworks, Drainage...
            $table->unsignedInteger('sequence')->default(0);
            $table->date('planned_date')->nullable();
            $table->date('actual_date')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'delayed'])->default('pending');
            $table->text('comments')->nullable();
            $table->timestamps();
        });

        Schema::create('risk_register', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->text('mitigation')->nullable();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users');
            $table->date('due_date')->nullable();
            $table->enum('status', ['open', 'mitigated', 'closed'])->default('open');
            $table->timestamps();
        });

        Schema::create('project_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('raised_by')->nullable()->constrained('users');
            $table->enum('status', ['open', 'resolved'])->default('open');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_issues');
        Schema::dropIfExists('risk_register');
        Schema::dropIfExists('project_milestones');
        Schema::dropIfExists('project_updates');
    }
};
