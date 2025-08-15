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
        Schema::create('messages_recipients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('messages_id');
            $table->foreign('messages_id')->references('id')->on('messages')->onDelete('cascade');
            $table->unsignedBigInteger('recipient_id');
            $table->foreign('recipient_id')->references('id')->on('recipients')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages_recipients');
    }
};
