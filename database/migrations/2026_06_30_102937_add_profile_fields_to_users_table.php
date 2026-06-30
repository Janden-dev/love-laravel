<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('my_name')->default('闫麟飞')->after('password');
            $table->string('my_english_name')->default('janden')->after('my_name');
            $table->string('partner_name')->default('徐立冉')->after('my_english_name');
            $table->string('partner_english_name')->default('Larry')->after('partner_name');
            $table->text('bio')->nullable()->after('partner_english_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['my_name', 'my_english_name', 'partner_name', 'partner_english_name', 'bio']);
        });
    }
};
