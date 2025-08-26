<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Usar SQL directo para PostgreSQL con USING
        DB::statement('ALTER TABLE emails ALTER COLUMN scheduled_time TYPE timestamp USING scheduled_time::timestamp, ALTER COLUMN scheduled_time DROP NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE emails ALTER COLUMN scheduled_time TYPE varchar(255) USING scheduled_time::varchar, ALTER COLUMN scheduled_time SET NOT NULL');
    }
};
