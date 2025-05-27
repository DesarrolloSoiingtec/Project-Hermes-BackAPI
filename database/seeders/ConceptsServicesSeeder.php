<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ConceptsServicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $concepts = [
            ['code' => '01', 'name' => 'Consultas', 'is_active' => true],
            ['code' => '02', 'name' => 'Procedimientos de diagnósticos', 'is_active' => true],
            ['code' => '03', 'name' => 'Procedimientos terapéuticos no quirúrgicos', 'is_active' => true],
            ['code' => '04', 'name' => 'Procedimientos terapéuticos quirúrgicos', 'is_active' => true],
            ['code' => '05', 'name' => 'Procedimientos de promoción y prevención', 'is_active' => true],
            ['code' => '06', 'name' => 'Estancias', 'is_active' => true],
            ['code' => '07', 'name' => 'Honorarios', 'is_active' => true],
            ['code' => '08', 'name' => 'Derechos de sala', 'is_active' => true],
            ['code' => '09', 'name' => 'Materiales e insumos', 'is_active' => true],
            ['code' => '10', 'name' => 'Banco de sangre', 'is_active' => true],
            ['code' => '11', 'name' => 'Prótesis y órtesis', 'is_active' => true],
            ['code' => '12', 'name' => 'Medicamentos POS', 'is_active' => true],
            ['code' => '13', 'name' => 'Medicamentos no POS', 'is_active' => true],
            ['code' => '14', 'name' => 'Traslado de pacientes', 'is_active' => true],
        ];

        foreach ($concepts as $concept) {
            DB::table('concepts_services')->insert(array_merge($concept, [
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]));
        }


    }
}
