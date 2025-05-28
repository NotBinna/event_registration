<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'Member'],
            ['id' => 2, 'name' => 'Administrator'],
            ['id' => 3, 'name' => 'Keuangan'],
            ['id' => 4, 'name' => 'Panitia'],
        ]);
    }
}
