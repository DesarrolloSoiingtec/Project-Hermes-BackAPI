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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('external_event_id')->unique(); // ID del evento (Maileroo)
            $table->string('event_type'); // opened, rejected, etc.
            $table->dateTime('timestamp')->nullable(); // evento real
            $table->dateTime('inserted_at')->nullable(); // cuando llegó al sistema

            $table->string('reject_reason')->nullable();
            $table->string('domain')->nullable();
            $table->string('user_id')->nullable();

            $table->foreignId('message_id')->constrained('messages')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
