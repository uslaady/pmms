<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Roads, Housing, Bridges, Water, Public Buildings...
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_code')->unique(); // MWH/2026/RD/001
            $table->string('name');
            // Every project belongs to exactly one directorate: civil | public_building | electrical | mechanical
            $table->string('directorate', 30)->index();
            $table->string('department')->nullable();
            $table->foreignId('project_category_id')->nullable()->constrained();
            $table->string('sector')->nullable();
            $table->text('description')->nullable();

            $table->foreignId('district_id')->nullable()->constrained();
            $table->foreignId('lga_id')->nullable()->constrained();
            $table->foreignId('ward_id')->nullable()->constrained();
            $table->foreignId('community_id')->nullable()->constrained();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->foreignId('contractor_id')->nullable()->constrained();
            $table->foreignId('consultant_id')->nullable()->constrained();
            $table->foreignId('supervising_engineer_id')->nullable()->constrained('users');

            $table->date('award_date')->nullable();
            $table->date('commencement_date')->nullable();
            $table->date('expected_completion_date')->nullable();
            $table->unsignedInteger('contract_duration_days')->nullable();

            // pending = awaiting Perm Sec approval; rejected = declined, can be corrected and resubmitted
            $table->enum('status', [
                'pending', 'rejected', 'planned', 'in_progress', 'suspended', 'delayed', 'completed', 'cancelled', 'deactivated',
            ])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');

            $table->decimal('planned_progress_percent', 5, 2)->default(0);
            $table->decimal('actual_progress_percent', 5, 2)->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
        Schema::dropIfExists('project_categories');
    }
};
