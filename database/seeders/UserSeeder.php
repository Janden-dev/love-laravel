<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'janden'],
            [
                'password' => Hash::make('20030103'),
                'my_name' => '闫麟飞',
                'my_english_name' => 'janden',
                'partner_name' => '徐立冉',
                'partner_english_name' => 'Larry',
            ]
        );

        User::updateOrCreate(
            ['username' => 'larry'],
            [
                'password' => Hash::make('20030415'),
                'my_name' => '徐立冉',
                'my_english_name' => 'larry',
                'partner_name' => '闫麟飞',
                'partner_english_name' => 'Janden',
            ]
        );
    }
}
