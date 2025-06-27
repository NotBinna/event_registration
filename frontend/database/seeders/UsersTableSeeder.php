<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
        [
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('user123'),
            'role_id' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'name' => 'Panitia',
            'email' => 'panitia@example.com',
            'password' => Hash::make('user123'),
            'role_id' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'name' => 'Member',
            'email' => 'member@example.com',
            'password' => Hash::make('user123'),
            'role_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'name' => 'Keuangan',
            'email' => 'keuangan@example.com',
            'password' => Hash::make('user123'),
            'role_id' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);
    }
}