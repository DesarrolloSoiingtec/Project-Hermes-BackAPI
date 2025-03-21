<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            EconomicActivitiesSeeder::class,
        ]);
        $this->call([
            LegalDocumentsTypesSeeder::class,
        ]);
        $this->call([
            CiuuCodesSeeder::class,
        ]);
        $this->call([
            PermissionsDemoSeeder::class,
        ]);
        $this->call([
            CountriesSeeder::class,
        ]);
    }
}
