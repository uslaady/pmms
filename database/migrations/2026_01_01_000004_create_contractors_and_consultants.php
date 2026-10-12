<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contractors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('rc_number')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('address')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('director')->nullable();
            $table->decimal('performance_score', 5, 2)->default(0);
            $table->unsignedInteger('active_contracts_count')->default(0);
            $table->timestamps();
        });

        Schema::create('consultants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        // Contractor Representative accounts linked to a contractor company
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('contractor_id')->nullable()->after('role_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contractor_id');
        });
        Schema::dropIfExists('consultants');
        Schema::dropIfExists('contractors');
    }
};
