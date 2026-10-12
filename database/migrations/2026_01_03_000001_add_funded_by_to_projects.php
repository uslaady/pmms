<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('funded_by')->default('Borno State Govt.')->after('priority');
        });

        DB::table('projects')->whereNull('funded_by')->update(['funded_by' => 'Borno State Govt.']);
    }

    public function down(): void
    {
        Schema::table('projects', fn (Blueprint $t) => $t->dropColumn('funded_by'));
    }
};
