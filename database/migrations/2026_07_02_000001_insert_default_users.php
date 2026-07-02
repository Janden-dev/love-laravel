<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $users = [
            [
                'username' => 'janden',
                'password' => Hash::make('20030103'),
                'my_name' => '闫麟飞',
                'my_english_name' => 'janden',
                'partner_name' => '徐立冉',
                'partner_english_name' => 'Larry',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'username' => 'larry',
                'password' => Hash::make('20030415'),
                'my_name' => '徐立冉',
                'my_english_name' => 'larry',
                'partner_name' => '闫麟飞',
                'partner_english_name' => 'Janden',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(
                ['username' => $user['username']],
                $user
            );
        }
    }

    public function down(): void
    {
        DB::table('users')->whereIn('username', ['janden', 'larry'])->delete();
    }
};
