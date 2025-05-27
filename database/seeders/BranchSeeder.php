<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \DB::table('branch')->insert([
            'name' => 'Sucursal Central',
            'address' => 'Av. Principal #123',
            'city' => 'Bucaramanga',
            'state' => 'Santander',
            'country' => 'Colombia',
            'phone' => '555-123-4567',
            'email' => 'central@ejemplo.com',
            'manager_name' => 'Fernanda Cabal',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
