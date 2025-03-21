<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LegalDocumentsTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('legal_documents_types')->insert([
            // Documentos para empresas ------->>
            [
                'name' => 'Sociedad por Acciones Simplificada',
                'code' => 'S.A.S.',
                'for_company' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sociedad Anónima',
                'code' => 'S.A.',
                'for_company' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sociedad de Responsabilidad Limitada',
                'code' => 'Ltda.',
                'for_company' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sociedad Colectiva',
                'code' => 'S.C.',
                'for_company' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sociedad en Comandita Simple',
                'code' => 'S. en C.',
                'for_company' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sociedad en Comandita por Acciones',
                'code' => 'S.C.A.',
                'for_company' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sociedades de Economía Mixta',
                'code' => 'SEM',
                'for_company' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Empresas de Beneficio e Interés Colectivo',
                'code' => 'B Corp',
                'for_company' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Empresa Unipersonal',
                'code' => 'E.U.',
                'for_company' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Empresa Individual de Responsabilidad Limitada',
                'code' => 'EIRL',
                'for_company' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Documentos para usuarios ------->>
            [
                'name' => 'Registro Civil',
                'code' => 'R.C.',
                'for_company' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Tarjeta de Identidad',
                'code' => 'T.I.',
                'for_company' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cédula de Ciudadanía',
                'code' => 'C.C.',
                'for_company' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cédula de Extranjería',
                'code' => 'C.E.',
                'for_company' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pasaporte',
                'code' => 'P.',
                'for_company' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Permiso por Protección Temporal',
                'code' => 'P.P.T.',
                'for_company' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
