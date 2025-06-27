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
            'password' => str_replace('$2y$', '$2b$', Hash::make('user123')),
            'role_id' => 2,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'name' => 'Panitia',
            'email' => 'panitia@example.com',
            'password' => str_replace('$2y$', '$2b$', Hash::make('user123')),
            'role_id' => 4,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'name' => 'Member',
            'email' => 'member@example.com',
            'password' => str_replace('$2y$', '$2b$', Hash::make('user123')),
            'role_id' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'name' => 'Keuangan',
            'email' => 'keuangan@example.com',
            'password' => str_replace('$2y$', '$2b$', Hash::make('user123')),
            'role_id' => 3,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);
    }
}