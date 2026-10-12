<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kept for databases created before the directorate column was part of the
 * projects table. On a fresh install (php artisan migrate:fresh --seed) the
 * projects table already has it, so only roles.is_active is added here.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('projects', 'directorate')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->string('directorate', 30)->nullable()->after('name')->index();
            });
        }

        if (! Schema::hasColumn('roles', 'is_active')) {
            Schema::table('roles', function (Blueprint $table) {
                // false = retired role (cannot be assigned to users any more)
                $table->boolean('is_active')->default(true)->after('description');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('roles', 'is_active')) {
            Schema::table('roles', fn (Blueprint $t) => $t->dropColumn('is_active'));
        }
    }
};
