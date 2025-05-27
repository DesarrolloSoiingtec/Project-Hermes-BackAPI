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
                ['name' => 'Alergología clínica', 'description' => 'Alergología clínica', 'is_active' => true],
                ['name' => 'Alergología', 'description' => 'Alergología', 'is_active' => true],
                // Anestesiología
                ['name' => 'Anestesiología y reanimación', 'description' => 'Anestesiología y reanimación', 'is_active' => true],
                ['name' => 'Anestesiología y medicina perioperatoria', 'description' => 'Anestesiología y medicina perioperatoria', 'is_active' => true],
                ['name' => 'Anestesiología', 'description' => 'Anestesiología', 'is_active' => true],
                // Cardiología
                ['name' => 'Cardiología', 'description' => 'Cardiología', 'is_active' => true],
                ['name' => 'Cardiología clínica', 'description' => 'Cardiología clínica', 'is_active' => true],
                ['name' => 'Cardiología de adultos', 'description' => 'Cardiología de adultos', 'is_active' => true],
                ['name' => 'Cardiología pediátrica', 'description' => 'Cardiología pediátrica', 'is_active' => true],
                ['name' => 'Cardiología hemodinámica y cardiología intervencionista y vascular periférico', 'description' => 'Cardiología hemodinámica y cardiología intervencionista y vascular periférico', 'is_active' => true],
                ['name' => 'Hemodinamia y cardiología', 'description' => 'Hemodinamia y cardiología', 'is_active' => true],
                ['name' => 'Cardiología intervencionista', 'description' => 'Cardiología intervencionista', 'is_active' => true],
                ['name' => 'Cardiología intervencionista y hemodinámica', 'description' => 'Cardiología intervencionista y hemodinámica', 'is_active' => true],
                ['name' => 'Cardiología intervencionista y hemodinamia', 'description' => 'Cardiología intervencionista y hemodinamia', 'is_active' => true],
                // Cirugía
                ['name' => 'Cirugía cardiovascular y torácica', 'description' => 'Cirugía cardiovascular y torácica', 'is_active' => true],
                ['name' => 'Cirugía cardiovascular', 'description' => 'Cirugía cardiovascular', 'is_active' => true],
                ['name' => 'Cirugía de mano', 'description' => 'Cirugía de mano', 'is_active' => true],
                ['name' => 'Cirugía de trasplantes de órganos abdominales', 'description' => 'Cirugía de trasplantes de órganos abdominales', 'is_active' => true],
                ['name' => 'Cirugía plástica', 'description' => 'Cirugía plástica', 'is_active' => true],
                ['name' => 'Cirugía de cabeza y cuello', 'description' => 'Cirugía de cabeza y cuello', 'is_active' => true],
                ['name' => 'Mastología', 'description' => 'Mastología', 'is_active' => true],
                ['name' => 'Cirugía de tórax', 'description' => 'Cirugía de tórax', 'is_active' => true],
                ['name' => 'Cirugía de trasplantes', 'description' => 'Cirugía de trasplantes', 'is_active' => true],
                ['name' => 'Cirugía general', 'description' => 'Cirugía general', 'is_active' => true],
                ['name' => 'Cirugía pediátrica', 'description' => 'Cirugía pediátrica', 'is_active' => true],
                ['name' => 'Cirugía oncológica', 'description' => 'Cirugía oncológica', 'is_active' => true],
                ['name' => 'Cirugía plástica, reconstructiva y estética', 'description' => 'Cirugía plástica, reconstructiva y estética', 'is_active' => true],
                ['name' => 'Cirugía plástica, estética y reconstructiva', 'description' => 'Cirugía plástica, estética y reconstructiva', 'is_active' => true],
                ['name' => 'Cirugía plástica, estética, maxilofacial y de la mano', 'description' => 'Cirugía plástica, estética, maxilofacial y de la mano', 'is_active' => true],
                ['name' => 'Cirugía plástica, maxilofacial y de la mano', 'description' => 'Cirugía plástica, maxilofacial y de la mano', 'is_active' => true],
                ['name' => 'Cirugía vascular y angiología', 'description' => 'Cirugía vascular y angiología', 'is_active' => true],
                ['name' => 'Cirugía vascular periférica y angiología', 'description' => 'Cirugía vascular periférica y angiología', 'is_active' => true],
                ['name' => 'Cirugía vascular periférica', 'description' => 'Cirugía vascular periférica', 'is_active' => true],
                ['name' => 'Cirugía vascular', 'description' => 'Cirugía vascular', 'is_active' => true],
                // Otras especialidades quirúrgicas
                ['name' => 'Coloproctologia', 'description' => 'Coloproctologia', 'is_active' => true],
                // Dermatología
                ['name' => 'Dermatología', 'description' => 'Dermatología', 'is_active' => true],
                ['name' => 'Dermatología y cirugía dermatológica', 'description' => 'Dermatología y cirugía dermatológica', 'is_active' => true],
                // Electrofisiología
                ['name' => 'Electrofisiología cardiovascular', 'description' => 'Electrofisiología cardiovascular', 'is_active' => true],
                ['name' => 'Electrofisiología cardíaca', 'description' => 'Electrofisiología cardíaca', 'is_active' => true],
                ['name' => 'Electrofisiología clínica, marcapasos y arritmias cardíacas', 'description' => 'Electrofisiología clínica, marcapasos y arritmias cardíacas', 'is_active' => true],
                // Endocrinología
                ['name' => 'Endocrinología', 'description' => 'Endocrinología', 'is_active' => true],
                ['name' => 'Endocrinología clínica y metabolismo', 'description' => 'Endocrinología clínica y metabolismo', 'is_active' => true],
                ['name' => 'Endocrinología, diabetes y metabolismo del adulto', 'description' => 'Endocrinología, diabetes y metabolismo del adulto', 'is_active' => true],
                // Gastroenterología
                ['name' => 'Gastroenterología', 'description' => 'Gastroenterología', 'is_active' => true],
                ['name' => 'Gastroenterología clínico quirúrgica', 'description' => 'Gastroenterología clínico quirúrgica', 'is_active' => true],
                ['name' => 'Gastroenterología y endoscopia digestiva', 'description' => 'Gastroenterología y endoscopia digestiva', 'is_active' => true],
                // Obstetricia y ginecología
                ['name' => 'Obstetricia y ginecología', 'description' => 'Obstetricia y ginecología', 'is_active' => true],
                // Infectología
                ['name' => 'Infectología', 'description' => 'Infectología', 'is_active' => true],
                ['name' => 'Enfermedades infecciosas', 'description' => 'Enfermedades infecciosas', 'is_active' => true],
                ['name' => 'Infectología pediátrica', 'description' => 'Infectología pediátrica', 'is_active' => true],
                // Hepatología
                ['name' => 'Hepatología', 'description' => 'Hepatología', 'is_active' => true],
                // Especialidades pediátricas y de metabolismo
                ['name' => 'Endocrinología pediátrica', 'description' => 'Endocrinología pediátrica', 'is_active' => true],
                ['name' => 'Gastroenterología pediátrica', 'description' => 'Gastroenterología pediátrica', 'is_active' => true],
                // Otras
                ['name' => 'Genética médica', 'description' => 'Genética médica', 'is_active' => true],
                ['name' => 'Geriatría', 'description' => 'Geriatría', 'is_active' => true],
                ['name' => 'Hematología', 'description' => 'Hematología', 'is_active' => true],
                ['name' => 'Oncología clínica', 'description' => 'Oncología clínica', 'is_active' => true],
                ['name' => 'Enfermedades infecciosas en pediatría', 'description' => 'Enfermedades infecciosas en pediatría', 'is_active' => true],
                ['name' => 'Medicina del dolor y cuidado paliativo', 'description' => 'Medicina del dolor y cuidado paliativo', 'is_active' => true],
                ['name' => 'Hemato-oncología pediátrica', 'description' => 'Hemato-oncología pediátrica', 'is_active' => true],
                ['name' => 'Onco-hematología pediátrica', 'description' => 'Onco-hematología pediátrica', 'is_active' => true],
                // Medicina crítica y cuidados
                ['name' => 'Medicina crítica y cuidado intensivo', 'description' => 'Medicina crítica y cuidado intensivo', 'is_active' => true],
                ['name' => 'Medicina crítica y cuidado intensivo del adulto', 'description' => 'Medicina crítica y cuidado intensivo del adulto', 'is_active' => true],
                // Deporte y urgencias
                ['name' => 'Medicina del deporte y de la actividad física', 'description' => 'Medicina del deporte y de la actividad física', 'is_active' => true],
                ['name' => 'Medicina de urgencias', 'description' => 'Medicina de urgencias', 'is_active' => true],
                ['name' => 'Medicina de emergencias', 'description' => 'Medicina de emergencias', 'is_active' => true],
                ['name' => 'Medicina de urgencias y domiciliaria', 'description' => 'Medicina de urgencias y domiciliaria', 'is_active' => true],
                // Medicina familiar e interna
                ['name' => 'Medicina familiar', 'description' => 'Medicina familiar', 'is_active' => true],
                ['name' => 'Medicina familiar y comunitaria', 'description' => 'Medicina familiar y comunitaria', 'is_active' => true],
                ['name' => 'Medicina familiar integral', 'description' => 'Medicina familiar integral', 'is_active' => true],
                ['name' => 'Medicina interna', 'description' => 'Medicina interna', 'is_active' => true],
                ['name' => 'Medicina interna geriatría', 'description' => 'Medicina interna geriatría', 'is_active' => true],
                // Materno, perinatal y neonatología
                ['name' => 'Medicina maternofetal', 'description' => 'Medicina maternofetal', 'is_active' => true],
                ['name' => 'Perinatología y neonatología', 'description' => 'Perinatología y neonatología', 'is_active' => true],
                // Neumología y neurocirugía
                ['name' => 'Neumología', 'description' => 'Neumología', 'is_active' => true],
                ['name' => 'Neumología clínica', 'description' => 'Neumología clínica', 'is_active' => true],
                ['name' => 'Neonatología', 'description' => 'Neonatología', 'is_active' => true],
                ['name' => 'Neumología pediátrica', 'description' => 'Neumología pediátrica', 'is_active' => true],
                ['name' => 'Neurocirugía', 'description' => 'Neurocirugía', 'is_active' => true],
                // Otras especialidades
                ['name' => 'Medicina nuclear', 'description' => 'Medicina nuclear', 'is_active' => true],
                ['name' => 'Nefrología', 'description' => 'Nefrología', 'is_active' => true],
                ['name' => 'Nefrología pediátrica', 'description' => 'Nefrología pediátrica', 'is_active' => true],
                ['name' => 'Medicina aeroespacial', 'description' => 'Medicina aeroespacial', 'is_active' => true],
                ['name' => 'Medicina crítica y cuidado intensivo pediátrico', 'description' => 'Medicina crítica y cuidado intensivo pediátrico', 'is_active' => true],
                ['name' => 'Medicina física y rehabilitación', 'description' => 'Medicina física y rehabilitación', 'is_active' => true],
                ['name' => 'Medicina forense', 'description' => 'Medicina forense', 'is_active' => true],
                // Neurología
                ['name' => 'Neurología', 'description' => 'Neurología', 'is_active' => true],
                ['name' => 'Neurología clínica', 'description' => 'Neurología clínica', 'is_active' => true],
                ['name' => 'Neurología pediátrica', 'description' => 'Neurología pediátrica', 'is_active' => true],
                ['name' => 'Neuropediatría', 'description' => 'Neuropediatría', 'is_active' => true],
                ['name' => 'Neurología pediátrica para especialistas en pediatría', 'description' => 'Neurología pediátrica para especialistas en pediatría', 'is_active' => true],
                ['name' => 'Neurología infantil', 'description' => 'Neurología infantil', 'is_active' => true],
                // Ortopedia y Otorrinolaringología
                ['name' => 'Ortopedia infantil', 'description' => 'Ortopedia infantil', 'is_active' => true],
                ['name' => 'Ortopedia y traumatología pediátrica', 'description' => 'Ortopedia y traumatología pediátrica', 'is_active' => true],
                ['name' => 'Otología', 'description' => 'Otología', 'is_active' => true],
                ['name' => 'Otología y neurotología', 'description' => 'Otología y neurotología', 'is_active' => true],
                ['name' => 'Otología y otoneurología', 'description' => 'Otología y otoneurología', 'is_active' => true],
                ['name' => 'Otorrinolaringología', 'description' => 'Otorrinolaringología', 'is_active' => true],
                ['name' => 'Otorrinolaringología y cirugía de cabeza y cuello', 'description' => 'Otorrinolaringología y cirugía de cabeza y cuello', 'is_active' => true],
                ['name' => 'Otorrinolaringología pediátrica', 'description' => 'Otorrinolaringología pediátrica', 'is_active' => true],
                // Patología
                ['name' => 'Patología', 'description' => 'Patología', 'is_active' => true],
                ['name' => 'Patología anatómica y clínica', 'description' => 'Patología anatómica y clínica', 'is_active' => true],
                ['name' => 'Anatomía patológica', 'description' => 'Anatomía patológica', 'is_active' => true],
                ['name' => 'Anatomía patológica y patología clínica', 'description' => 'Anatomía patológica y patología clínica', 'is_active' => true],
                // Psiquiatría
                ['name' => 'Psiquiatría', 'description' => 'Psiquiatría', 'is_active' => true],
                ['name' => 'Psiquiatría general', 'description' => 'Psiquiatría general', 'is_active' => true],
                ['name' => 'Psiquiatría y salud mental', 'description' => 'Psiquiatría y salud mental', 'is_active' => true],
                ['name' => 'Psiquiatría de enlace', 'description' => 'Psiquiatría de enlace', 'is_active' => true],
                ['name' => 'Psiquiatría de enlace e interconsultas', 'description' => 'Psiquiatría de enlace e interconsultas', 'is_active' => true],
                ['name' => 'Psiquiatría pediátrica', 'description' => 'Psiquiatría pediátrica', 'is_active' => true],
                ['name' => 'Psiquiatría de niños y adolescentes', 'description' => 'Psiquiatría de niños y adolescentes', 'is_active' => true],
                ['name' => 'Psiquiatría infantil y del adolescente', 'description' => 'Psiquiatría infantil y del adolescente', 'is_active' => true],
                // Radiología y Radioterapia
                ['name' => 'Radiología', 'description' => 'Radiología', 'is_active' => true],
                ['name' => 'Radiología e imágenes diagnósticas', 'description' => 'Radiología e imágenes diagnósticas', 'is_active' => true],
                ['name' => 'Radiodiagnóstico radiología e imágenes', 'description' => 'Radiodiagnóstico radiología e imágenes', 'is_active' => true],
                ['name' => 'Radioterapia Oncológica', 'description' => 'Radioterapia Oncológica', 'is_active' => true],
                // Otras especialidades
                ['name' => 'Oftalmología', 'description' => 'Oftalmología', 'is_active' => true],
                ['name' => 'Ortopedia y traumatología', 'description' => 'Ortopedia y traumatología', 'is_active' => true],
                ['name' => 'Pediatría', 'description' => 'Pediatría', 'is_active' => true],
                ['name' => 'Sexología clínica', 'description' => 'Sexología clínica', 'is_active' => true],
                ['name' => 'Radiología intervencionista', 'description' => 'Radiología intervencionista', 'is_active' => true],
                ['name' => 'Reumatología', 'description' => 'Reumatología', 'is_active' => true],
                // Medicina alternativa
                ['name' => 'Medicina Homeopática', 'description' => 'Medicina Homeopática', 'is_active' => true],
                ['name' => 'Medicina Neuralterapéutica', 'description' => 'Medicina Neuralterapéutica', 'is_active' => true],
                ['name' => 'Medicina Osteopática', 'description' => 'Medicina Osteopática', 'is_active' => true],
                ['name' => 'Medicina tradicional china', 'description' => 'Medicina tradicional china', 'is_active' => true],
                ['name' => 'Toxicología clínica', 'description' => 'Toxicología clínica', 'is_active' => true],
                // Urología y reumatología pediátrica
                ['name' => 'Urología', 'description' => 'Urología', 'is_active' => true],
                ['name' => 'Reumatología pediátrica', 'description' => 'Reumatología pediátrica', 'is_active' => true],
                ['name' => 'Urología Pediátrica', 'description' => 'Urología Pediátrica', 'is_active' => true],
                // Medicina complementaria
                ['name' => 'Medicina Ayurveda', 'description' => 'Medicina Ayurveda', 'is_active' => true],
                ['name' => 'Medicina Naturopática', 'description' => 'Medicina Naturopática', 'is_active' => true],
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
