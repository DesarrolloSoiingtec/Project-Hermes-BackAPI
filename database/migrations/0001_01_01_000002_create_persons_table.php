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
        Schema::create('persons', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('legal_document_type_id'); // ID de la actividad económica
            $table->foreign('legal_document_type_id')->references('id')->on('legal_documents_types');

            $table->string('document_number', 30); // Número de documento
            $table->string('name', 80); // Primer nombre
            $table->string('second_name', 80)->nullable(); // Segundo nombre (opcional)
            $table->string('lastname', 80); // Primer apellido
            $table->string('second_lastname', 80)->nullable(); // Segundo apellido (opcional)
            $table->string('address', 128)->nullable(); // Dirección (Cra, calle, diagonal, etc)
            $table->string('prefix_phone', 5)->nullable(); // Prefijo del teléfono de contacto -----> debe venir de la tabla "global_prefixes"
            $table->string('phone', 18)->nullable(); // Teléfono de contacto (opcional)
            $table->string('photo')->nullable(); // Foto (opcional)
            $table->string('fingerprint')->nullable(); // Huella digital (opcional)
            $table->date('birthday')->nullable(); // Fecha de nacimiento
            $table->string('gender', 1); // Género
            $table->string('municipality', 100)->nullable(); // Municipio de la dirección (opcional)
            $table->string('department', 100)->nullable(); // Departamento de la dirección (opcional) (en Colombia, se usa "departamento" en lugar de "estado")
            $table->string('zone', 1)->comment('(R)ural - (U)rbano')->nullable(); // Zona (Rural o Urbano) (opcional)
            $table->integer('country_origin')->nullable(); // País de origen (opcional)
            $table->integer('country_residence')->nullable(); // País de residencia (opcional)

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('persons');
    }
};
