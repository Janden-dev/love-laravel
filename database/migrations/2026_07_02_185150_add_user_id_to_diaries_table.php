<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop unique on entry_date, add user_id with composite unique (user_id, entry_date)
        Schema::table('diaries', function (Blueprint $table) {
            // Drop the existing unique index on entry_date
            $table->dropUnique(['entry_date']);
        });

        Schema::table('diaries', function (Blueprint $table) {
            // Add user_id - nullable first so we can backfill existing rows
            $table->foreignId('user_id')->nullable()->constrained()->after('id');
        });

        // Assign existing diaries to the first user (janden) as default
        $firstUser = DB::table('users')->orderBy('id')->first();
        if ($firstUser) {
            DB::table('diaries')->whereNull('user_id')->update(['user_id' => $firstUser->id]);
        }

        // Now make user_id not nullable and add composite unique
        Schema::table('diaries', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
            $table->unique(['user_id', 'entry_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('diaries', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id', 'entry_date']);
            $table->dropColumn('user_id');
            $table->unique(['entry_date']);
        });
    }
};
