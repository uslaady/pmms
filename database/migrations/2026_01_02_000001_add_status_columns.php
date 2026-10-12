<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contractors', function (Blueprint $table) {
            // pending (new, awaiting admin approval) | active | deactivated | suspended | blacklisted
            $table->string('status')->default('pending')->after('performance_score');
        });

        Schema::table('users', function (Blueprint $table) {
            // active | deactivated | suspended | blacklisted — only 'active' can sign in
            $table->string('status')->default('active')->after('is_active');
        });

        // Existing contractors keep working: mark them active
        DB::table('contractors')->update(['status' => 'active']);
        DB::table('users')->update(['status' => 'active']);
    }

    public function down(): void
    {
        Schema::table('contractors', fn (Blueprint $t) => $t->dropColumn('status'));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('status'));
    }
};
