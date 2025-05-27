<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();

            // Datos generales
            $table->string('name', 255)->unique(); // Nombre comercial de la empresa
            $table->string('legal_name', 255);// Razón social de la empresa
            $table->string('id_number', 16)->unique();// Número de Identificación Tributaria (NIT)
            $table->string('verification_digit', 2)->nullable(); // Dígito de verificación del NIT (0 a 9)
            $table->unsignedBigInteger('company_type_id'); // Tipo de empresa -> (SAS, SA, LTDA, etc.)  -----> debe venir de la tabla "legal_documents_types"
            $table->foreign('company_type_id')->references('id')->on('legal_documents_types');

            $table->string('legal_representative', 128)->nullable(); // Representante legal de la empresa
            $table->date('incorporation_date')->nullable(); // Fecha de constitución de la empresa

            // Datos de dirección
            $table->string('street', 100)->nullable(); // Calle o carrera de la dirección
            $table->string('exterior_number', 10)->nullable(); // Número exterior (# 31-39)
            $table->string('interior_number', 10)->nullable(); // Número interior (oficina 226, apto, etc.)
            $table->string('neighborhood', 128)->nullable(); // Barrio o colonia
            $table->string('city', 100)->nullable(); // Ciudad de la dirección
            $table->string('municipality', 100)->nullable(); // Municipio de la dirección
            $table->string('department', 100)->nullable(); // Departamento de la dirección (en Colombia, se usa "departamento" en lugar de "estado")
            $table->string('postal_code', 20)->nullable(); // Código postal de la dirección

            // Información de contacto
            $table->string('phone', 18)->nullable(); // Teléfono de contacto
            $table->string('prefix_phone', 5)->nullable(); // Prefijo del teléfono de contacto  ----->   debe venir de la tabla "global_prefixes"
            $table->string('email', 128)->nullable(); // Correo electrónico de contacto
            $table->string('website', 255)->nullable(); // Sitio web de la empresa
            // Información de contacto para usuarios
            $table->string('user_contact_phone', 18)->nullable(); // Teléfono de contacto para usuarios
            $table->string('user_prefix_phone', 5)->nullable(); // Prefijo del teléfono de contacto  -----> debe venir de la tabla "global_prefixes"
            $table->string('user_contact_email', 128)->nullable(); // Correo de contacto para usuarios

            // Información legal adicional
            $table->string('registration_number', 40)->nullable(); // Número de registro en la Cámara de Comercio
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
