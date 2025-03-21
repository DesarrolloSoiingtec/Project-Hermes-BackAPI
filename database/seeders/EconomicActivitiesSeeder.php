<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EconomicActivitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $activities = [
            [
                'section' => 'A',
                'activity_name' => 'Agricultura, ganadería, caza, silvicultura y pesca',
                'description' => 'Incluye actividades como el cultivo, la cría de animales, la pesca y la explotación forestal',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'section' => 'B',
                'activity_name' => 'Explotación de minas y canteras',
                'description' => 'Comprende la extracción de minerales, petróleo y gas, entre otros recursos naturales',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'section' => 'C',
                'activity_name' => 'Industrias manufactureras',
                'description' => 'Engloba la transformación de materias primas en productos elaborados',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'section' => 'D',
                'activity_name' => 'Suministro de electricidad, gas, vapor y aire acondicionado',
                'description' => 'Actividades relacionadas con la generación y distribución de energía',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'section' => 'E',
                'activity_name' => 'Abastecimiento de agua, saneamiento, gestión de residuos y descontaminación',
                'description' => 'Incluye la captación, tratamiento y distribución de agua, así como el manejo de residuos',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'section' => 'F',
                'activity_name' => 'Construcción',
                'description' => 'Actividades vinculadas a la edificación de infraestructuras, viviendas y obras civiles',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'section' => 'G',
                'activity_name' => 'Comercio al por mayor y al por menor; reparación de vehículos automotores y motocicletas',
                'description' => 'Se refiere a la comercialización de bienes y servicios, tanto mayoristas como minoristas, y la reparación de vehículos',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'section' => 'H',
                'activity_name' => 'Transporte y almacenamiento',
                'description' => 'Incluye el transporte de personas y mercancías, así como el almacenamiento y actividades conexas',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'section' => 'I',
                'activity_name' => 'Hostelería',
                'description' => 'Comprende servicios de alojamiento y alimentos, como hoteles y restaurantes',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'section' => 'J',
                'activity_name' => 'Información y comunicaciones',
                'description' => 'Engloba servicios relacionados con tecnologías de la información, telecomunicaciones, medios de comunicación, etc.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'section' => 'K',
                'activity_name' => 'Actividades financieras y de seguros',
                'description' => 'Incluye la prestación de servicios bancarios, seguros y otros servicios financieros',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'section' => 'L',
                'activity_name' => 'Actividades inmobiliarias',
                'description' => 'Actividades relacionadas con la compraventa, alquiler y administración de bienes inmuebles',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'section' => 'M',
                'activity_name' => 'Actividades profesionales, científicas y técnicas',
                'description' => 'Servicios de consultoría, investigación y desarrollo, asesorías especializadas y similares',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'section' => 'N',
                'activity_name' => 'Actividades administrativas y de servicios de apoyo',
                'description' => 'Comprende servicios de apoyo a empresas, como gestión de recursos humanos, limpieza, seguridad privada, etc.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'section' => 'O',
                'activity_name' => 'Administración pública y defensa; planes de seguridad social',
                'description' => 'Actividades propias del sector público y la administración del estado',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'section' => 'P',
                'activity_name' => 'Educación',
                'description' => 'Se refiere a servicios educativos, desde preescolar hasta educación superior',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'section' => 'Q',
                'activity_name' => 'Salud humana y servicios sociales',
                'description' => 'Engloba actividades de hospitales, clínicas, consultorios y otros servicios de salud',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'section' => 'R',
                'activity_name' => 'Artes, entretenimiento y recreación',
                'description' => 'Actividades relacionadas con la cultura, el arte, el deporte y el entretenimiento',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'section' => 'S',
                'activity_name' => 'Otras actividades de servicios',
                'description' => 'Incluye servicios no clasificados en las secciones anteriores, abarcando una amplia variedad de actividades',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'section' => 'T',
                'activity_name' => 'Actividades de los hogares como empleadores de personal doméstico',
                'description' => 'Se refiere a la contratación de personal doméstico y servicios asociados',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'section' => 'U',
                'activity_name' => 'Actividades de organizaciones y órganos extraterritoriales',
                'description' => 'Engloba actividades realizadas por entidades con alcance internacional o que no se rigen por la normativa interna',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        \DB::table('economic_activities')->insert($activities);
    }
}
