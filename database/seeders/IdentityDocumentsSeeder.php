<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class IdentityDocumentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('identity_documents')->insert([
            [
                'name' => 'Cédula de ciudadanía',
                'abbreviation' => 'CC',
                'enterprise_id' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Tarjeta de identidad',
                'abbreviation' => 'TI',
                'enterprise_id' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Registro civil',
                'abbreviation' => 'RC',
                'enterprise_id' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Cédula de extranjería',
                'abbreviation' => 'CE',
                'enterprise_id' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Pasaporte',
                'abbreviation' => 'PA',
                'enterprise_id' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Permiso especial de permanencia',
                'abbreviation' => 'PEP',
                'enterprise_id' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Documento de identificación extranjero',
                'abbreviation' => 'DIE',
                'enterprise_id' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Número de identificación tributaria',
                'abbreviation' => 'NIT',
                'enterprise_id' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
