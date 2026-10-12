<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('funding_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // State Budget, World Bank, Federal Grant...
            $table->timestamps();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('contract_number')->unique();
            $table->date('award_date');
            $table->decimal('contract_value', 18, 2);
            $table->decimal('revised_value', 18, 2)->nullable();
            $table->foreignId('funding_source_id')->nullable()->constrained();
            $table->decimal('retention_percent', 5, 2)->default(5);
            $table->timestamps();
        });

        Schema::create('contract_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // variation_order, extension_of_time
            $table->decimal('amount', 18, 2)->nullable();
            $table->unsignedInteger('extra_days')->nullable();
            $table->text('reason')->nullable();
            $table->date('date');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->string('certificate_number')->nullable();
            $table->decimal('amount', 18, 2);
            $table->decimal('retention_held', 18, 2)->default(0);
            $table->date('payment_date');
            $table->enum('status', ['pending', 'approved', 'paid', 'rejected'])->default('pending');
            $table->foreignId('recorded_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('contract_variations');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('funding_sources');
    }
};
