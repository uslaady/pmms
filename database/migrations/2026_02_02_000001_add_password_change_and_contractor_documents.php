<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // true = user must set a personal password before using the system
            $table->boolean('must_change_password')->default(true)->after('status');
        });

        Schema::table('contractors', function (Blueprint $table) {
            // Contractors are soft-deleted so their name stays on the projects they worked on
            $table->softDeletes();
            // Why the Permanent Secretary rejected the registration (status 'rejected')
            $table->text('rejection_reason')->nullable();
        });

        Schema::create('contractor_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contractor_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            // registration certificate, tax clearance, etc.
            $table->string('type', 50)->default('other');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contractor_documents');
        Schema::table('contractors', function (Blueprint $t) {
            $t->dropSoftDeletes();
            $t->dropColumn('rejection_reason');
        });
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('must_change_password'));
    }
};
