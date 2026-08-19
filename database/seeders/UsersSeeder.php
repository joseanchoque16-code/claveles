<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->updateOrInsert(
            ['email' => 'admin@local'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('Admin12345!'),
                'role' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('users')->updateOrInsert(
            ['email' => 'operador@local'],
            [
                'name' => 'Operador',
                'password' => Hash::make('Operador12345!'),
                'role' => 'operador',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
