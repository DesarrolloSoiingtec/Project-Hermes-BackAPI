<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SpecialtiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        {
            $specialties = [
                // Alergología
                ['name' => 'Alergología clínica', 'description' => 'Alergología clínica', 'is_active' => false],
                ['name' => 'Alergología', 'description' => 'Alergología', 'is_active' => false],
                // Anestesiología
                ['name' => 'Anestesiología y reanimación', 'description' => 'Anestesiología y reanimación', 'is_active' => false],
                ['name' => 'Anestesiología y medicina perioperatoria', 'description' => 'Anestesiología y medicina perioperatoria', 'is_active' => false],
                ['name' => 'Anestesiología', 'description' => 'Anestesiología', 'is_active' => false],
                // Cardiología
                ['name' => 'Cardiología', 'description' => 'Cardiología', 'is_active' => false],
                ['name' => 'Cardiología clínica', 'description' => 'Cardiología clínica', 'is_active' => false],
                ['name' => 'Cardiología de adultos', 'description' => 'Cardiología de adultos', 'is_active' => false],
                ['name' => 'Cardiología pediátrica', 'description' => 'Cardiología pediátrica', 'is_active' => false],
                ['name' => 'Cardiología hemodinámica y cardiología intervencionista y vascular periférico', 'description' => 'Cardiología hemodinámica y cardiología intervencionista y vascular periférico', 'is_active' => false],
                ['name' => 'Hemodinamia y cardiología', 'description' => 'Hemodinamia y cardiología', 'is_active' => false],
                ['name' => 'Cardiología intervencionista', 'description' => 'Cardiología intervencionista', 'is_active' => false],
                ['name' => 'Cardiología intervencionista y hemodinámica', 'description' => 'Cardiología intervencionista y hemodinámica', 'is_active' => false],
                ['name' => 'Cardiología intervencionista y hemodinamia', 'description' => 'Cardiología intervencionista y hemodinamia', 'is_active' => false],
                // Cirugía
                ['name' => 'Cirugía cardiovascular y torácica', 'description' => 'Cirugía cardiovascular y torácica', 'is_active' => false],
                ['name' => 'Cirugía cardiovascular', 'description' => 'Cirugía cardiovascular', 'is_active' => false],
                ['name' => 'Cirugía de mano', 'description' => 'Cirugía de mano', 'is_active' => false],
                ['name' => 'Cirugía de trasplantes de órganos abdominales', 'description' => 'Cirugía de trasplantes de órganos abdominales', 'is_active' => false],
                ['name' => 'Cirugía plástica', 'description' => 'Cirugía plástica', 'is_active' => false],
                ['name' => 'Cirugía de cabeza y cuello', 'description' => 'Cirugía de cabeza y cuello', 'is_active' => false],
                ['name' => 'Mastología', 'description' => 'Mastología', 'is_active' => false],
                ['name' => 'Cirugía de tórax', 'description' => 'Cirugía de tórax', 'is_active' => false],
                ['name' => 'Cirugía de trasplantes', 'description' => 'Cirugía de trasplantes', 'is_active' => false],
                ['name' => 'Cirugía general', 'description' => 'Cirugía general', 'is_active' => false],
                ['name' => 'Cirugía pediátrica', 'description' => 'Cirugía pediátrica', 'is_active' => false],
                ['name' => 'Cirugía oncológica', 'description' => 'Cirugía oncológica', 'is_active' => false],
                ['name' => 'Cirugía plástica, reconstructiva y estética', 'description' => 'Cirugía plástica, reconstructiva y estética', 'is_active' => false],
                ['name' => 'Cirugía plástica, estética y reconstructiva', 'description' => 'Cirugía plástica, estética y reconstructiva', 'is_active' => false],
                ['name' => 'Cirugía plástica, estética, maxilofacial y de la mano', 'description' => 'Cirugía plástica, estética, maxilofacial y de la mano', 'is_active' => false],
                ['name' => 'Cirugía plástica, maxilofacial y de la mano', 'description' => 'Cirugía plástica, maxilofacial y de la mano', 'is_active' => false],
                ['name' => 'Cirugía vascular y angiología', 'description' => 'Cirugía vascular y angiología', 'is_active' => false],
                ['name' => 'Cirugía vascular periférica y angiología', 'description' => 'Cirugía vascular periférica y angiología', 'is_active' => false],
                ['name' => 'Cirugía vascular periférica', 'description' => 'Cirugía vascular periférica', 'is_active' => false],
                ['name' => 'Cirugía vascular', 'description' => 'Cirugía vascular', 'is_active' => false],
                // Otras especialidades quirúrgicas
                ['name' => 'Coloproctologia', 'description' => 'Coloproctologia', 'is_active' => false],
                // Dermatología
                ['name' => 'Dermatología', 'description' => 'Dermatología', 'is_active' => false],
                ['name' => 'Dermatología y cirugía dermatológica', 'description' => 'Dermatología y cirugía dermatológica', 'is_active' => false],
                // Electrofisiología
                ['name' => 'Electrofisiología cardiovascular', 'description' => 'Electrofisiología cardiovascular', 'is_active' => false],
                ['name' => 'Electrofisiología cardíaca', 'description' => 'Electrofisiología cardíaca', 'is_active' => false],
                ['name' => 'Electrofisiología clínica, marcapasos y arritmias cardíacas', 'description' => 'Electrofisiología clínica, marcapasos y arritmias cardíacas', 'is_active' => false],
                // Endocrinología
                ['name' => 'Endocrinología', 'description' => 'Endocrinología', 'is_active' => false],
                ['name' => 'Endocrinología clínica y metabolismo', 'description' => 'Endocrinología clínica y metabolismo', 'is_active' => false],
                ['name' => 'Endocrinología, diabetes y metabolismo del adulto', 'description' => 'Endocrinología, diabetes y metabolismo del adulto', 'is_active' => false],
                // Gastroenterología
                ['name' => 'Gastroenterología', 'description' => 'Gastroenterología', 'is_active' => false],
                ['name' => 'Gastroenterología clínico quirúrgica', 'description' => 'Gastroenterología clínico quirúrgica', 'is_active' => false],
                ['name' => 'Gastroenterología y endoscopia digestiva', 'description' => 'Gastroenterología y endoscopia digestiva', 'is_active' => false],
                // Obstetricia y ginecología
                ['name' => 'Obstetricia y ginecología', 'description' => 'Obstetricia y ginecología', 'is_active' => false],
                // Infectología
                ['name' => 'Infectología', 'description' => 'Infectología', 'is_active' => false],
                ['name' => 'Enfermedades infecciosas', 'description' => 'Enfermedades infecciosas', 'is_active' => false],
                ['name' => 'Infectología pediátrica', 'description' => 'Infectología pediátrica', 'is_active' => false],
                // Hepatología
                ['name' => 'Hepatología', 'description' => 'Hepatología', 'is_active' => false],
                // Especialidades pediátricas y de metabolismo
                ['name' => 'Endocrinología pediátrica', 'description' => 'Endocrinología pediátrica', 'is_active' => false],
                ['name' => 'Gastroenterología pediátrica', 'description' => 'Gastroenterología pediátrica', 'is_active' => false],
                // Otras
                ['name' => 'Genética médica', 'description' => 'Genética médica', 'is_active' => false],
                ['name' => 'Geriatría', 'description' => 'Geriatría', 'is_active' => false],
                ['name' => 'Hematología', 'description' => 'Hematología', 'is_active' => false],
                ['name' => 'Oncología clínica', 'description' => 'Oncología clínica', 'is_active' => false],
                ['name' => 'Enfermedades infecciosas en pediatría', 'description' => 'Enfermedades infecciosas en pediatría', 'is_active' => false],
                ['name' => 'Medicina del dolor y cuidado paliativo', 'description' => 'Medicina del dolor y cuidado paliativo', 'is_active' => false],
                ['name' => 'Hemato-oncología pediátrica', 'description' => 'Hemato-oncología pediátrica', 'is_active' => false],
                ['name' => 'Onco-hematología pediátrica', 'description' => 'Onco-hematología pediátrica', 'is_active' => false],
                // Medicina crítica y cuidados
                ['name' => 'Medicina crítica y cuidado intensivo', 'description' => 'Medicina crítica y cuidado intensivo', 'is_active' => false],
                ['name' => 'Medicina crítica y cuidado intensivo del adulto', 'description' => 'Medicina crítica y cuidado intensivo del adulto', 'is_active' => false],
                // Deporte y urgencias
                ['name' => 'Medicina del deporte y de la actividad física', 'description' => 'Medicina del deporte y de la actividad física', 'is_active' => false],
                ['name' => 'Medicina de urgencias', 'description' => 'Medicina de urgencias', 'is_active' => false],
                ['name' => 'Medicina de emergencias', 'description' => 'Medicina de emergencias', 'is_active' => false],
                ['name' => 'Medicina de urgencias y domiciliaria', 'description' => 'Medicina de urgencias y domiciliaria', 'is_active' => false],
                // Medicina familiar e interna
                ['name' => 'Medicina familiar', 'description' => 'Medicina familiar', 'is_active' => false],
                ['name' => 'Medicina familiar y comunitaria', 'description' => 'Medicina familiar y comunitaria', 'is_active' => false],
                ['name' => 'Medicina familiar integral', 'description' => 'Medicina familiar integral', 'is_active' => false],
                ['name' => 'Medicina interna', 'description' => 'Medicina interna', 'is_active' => false],
                ['name' => 'Medicina interna geriatría', 'description' => 'Medicina interna geriatría', 'is_active' => false],
                // Materno, perinatal y neonatología
                ['name' => 'Medicina maternofetal', 'description' => 'Medicina maternofetal', 'is_active' => false],
                ['name' => 'Perinatología y neonatología', 'description' => 'Perinatología y neonatología', 'is_active' => false],
                // Neumología y neurocirugía
                ['name' => 'Neumología', 'description' => 'Neumología', 'is_active' => false],
                ['name' => 'Neumología clínica', 'description' => 'Neumología clínica', 'is_active' => false],
                ['name' => 'Neonatología', 'description' => 'Neonatología', 'is_active' => false],
                ['name' => 'Neumología pediátrica', 'description' => 'Neumología pediátrica', 'is_active' => false],
                ['name' => 'Neurocirugía', 'description' => 'Neurocirugía', 'is_active' => false],
                // Otras especialidades
                ['name' => 'Medicina nuclear', 'description' => 'Medicina nuclear', 'is_active' => false],
                ['name' => 'Nefrología', 'description' => 'Nefrología', 'is_active' => false],
                ['name' => 'Nefrología pediátrica', 'description' => 'Nefrología pediátrica', 'is_active' => false],
                ['name' => 'Medicina aeroespacial', 'description' => 'Medicina aeroespacial', 'is_active' => false],
                ['name' => 'Medicina crítica y cuidado intensivo pediátrico', 'description' => 'Medicina crítica y cuidado intensivo pediátrico', 'is_active' => false],
                ['name' => 'Medicina física y rehabilitación', 'description' => 'Medicina física y rehabilitación', 'is_active' => false],
                ['name' => 'Medicina forense', 'description' => 'Medicina forense', 'is_active' => false],
                // Neurología
                ['name' => 'Neurología', 'description' => 'Neurología', 'is_active' => false],
                ['name' => 'Neurología clínica', 'description' => 'Neurología clínica', 'is_active' => false],
                ['name' => 'Neurología pediátrica', 'description' => 'Neurología pediátrica', 'is_active' => false],
                ['name' => 'Neuropediatría', 'description' => 'Neuropediatría', 'is_active' => false],
                ['name' => 'Neurología pediátrica para especialistas en pediatría', 'description' => 'Neurología pediátrica para especialistas en pediatría', 'is_active' => false],
                ['name' => 'Neurología infantil', 'description' => 'Neurología infantil', 'is_active' => false],
                // Ortopedia y Otorrinolaringología
                ['name' => 'Ortopedia infantil', 'description' => 'Ortopedia infantil', 'is_active' => false],
                ['name' => 'Ortopedia y traumatología pediátrica', 'description' => 'Ortopedia y traumatología pediátrica', 'is_active' => false],
                ['name' => 'Otología', 'description' => 'Otología', 'is_active' => false],
                ['name' => 'Otología y neurotología', 'description' => 'Otología y neurotología', 'is_active' => false],
                ['name' => 'Otología y otoneurología', 'description' => 'Otología y otoneurología', 'is_active' => false],
                ['name' => 'Otorrinolaringología', 'description' => 'Otorrinolaringología', 'is_active' => false],
                ['name' => 'Otorrinolaringología y cirugía de cabeza y cuello', 'description' => 'Otorrinolaringología y cirugía de cabeza y cuello', 'is_active' => false],
                ['name' => 'Otorrinolaringología pediátrica', 'description' => 'Otorrinolaringología pediátrica', 'is_active' => false],
                // Patología
                ['name' => 'Patología', 'description' => 'Patología', 'is_active' => false],
                ['name' => 'Patología anatómica y clínica', 'description' => 'Patología anatómica y clínica', 'is_active' => false],
                ['name' => 'Anatomía patológica', 'description' => 'Anatomía patológica', 'is_active' => false],
                ['name' => 'Anatomía patológica y patología clínica', 'description' => 'Anatomía patológica y patología clínica', 'is_active' => false],
                // Psiquiatría
                ['name' => 'Psiquiatría', 'description' => 'Psiquiatría', 'is_active' => false],
                ['name' => 'Psiquiatría general', 'description' => 'Psiquiatría general', 'is_active' => false],
                ['name' => 'Psiquiatría y salud mental', 'description' => 'Psiquiatría y salud mental', 'is_active' => false],
                ['name' => 'Psiquiatría de enlace', 'description' => 'Psiquiatría de enlace', 'is_active' => false],
                ['name' => 'Psiquiatría de enlace e interconsultas', 'description' => 'Psiquiatría de enlace e interconsultas', 'is_active' => false],
                ['name' => 'Psiquiatría pediátrica', 'description' => 'Psiquiatría pediátrica', 'is_active' => false],
                ['name' => 'Psiquiatría de niños y adolescentes', 'description' => 'Psiquiatría de niños y adolescentes', 'is_active' => false],
                ['name' => 'Psiquiatría infantil y del adolescente', 'description' => 'Psiquiatría infantil y del adolescente', 'is_active' => false],
                // Radiología y Radioterapia
                ['name' => 'Radiología', 'description' => 'Radiología', 'is_active' => false],
                ['name' => 'Radiología e imágenes diagnósticas', 'description' => 'Radiología e imágenes diagnósticas', 'is_active' => false],
                ['name' => 'Radiodiagnóstico radiología e imágenes', 'description' => 'Radiodiagnóstico radiología e imágenes', 'is_active' => false],
                ['name' => 'Radioterapia Oncológica', 'description' => 'Radioterapia Oncológica', 'is_active' => false],
                // Otras especialidades
                ['name' => 'Oftalmología', 'description' => 'Oftalmología', 'is_active' => true],
                ['name' => 'Ortopedia y traumatología', 'description' => 'Ortopedia y traumatología', 'is_active' => false],
                ['name' => 'Pediatría', 'description' => 'Pediatría', 'is_active' => false],
                ['name' => 'Sexología clínica', 'description' => 'Sexología clínica', 'is_active' => false],
                ['name' => 'Radiología intervencionista', 'description' => 'Radiología intervencionista', 'is_active' => false],
                ['name' => 'Reumatología', 'description' => 'Reumatología', 'is_active' => false],
                // Medicina alternativa
                ['name' => 'Medicina Homeopática', 'description' => 'Medicina Homeopática', 'is_active' => false],
                ['name' => 'Medicina Neuralterapéutica', 'description' => 'Medicina Neuralterapéutica', 'is_active' => false],
                ['name' => 'Medicina Osteopática', 'description' => 'Medicina Osteopática', 'is_active' => false],
                ['name' => 'Medicina tradicional china', 'description' => 'Medicina tradicional china', 'is_active' => false],
                ['name' => 'Toxicología clínica', 'description' => 'Toxicología clínica', 'is_active' => false],
                // Urología y reumatología pediátrica
                ['name' => 'Urología', 'description' => 'Urología', 'is_active' => false],
                ['name' => 'Reumatología pediátrica', 'description' => 'Reumatología pediátrica', 'is_active' => false],
                ['name' => 'Urología Pediátrica', 'description' => 'Urología Pediátrica', 'is_active' => false],
                // Medicina complementaria
                ['name' => 'Medicina Ayurveda', 'description' => 'Medicina Ayurveda', 'is_active' => false],
                ['name' => 'Medicina Naturopática', 'description' => 'Medicina Naturopática', 'is_active' => false],
            ];

            foreach ($specialties as $specialty) {
                DB::table('specialties')->insert(array_merge($specialty, [
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]));
            }
        }
    }
}
