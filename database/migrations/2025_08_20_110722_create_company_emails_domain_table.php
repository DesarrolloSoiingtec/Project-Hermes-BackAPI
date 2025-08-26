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
        Schema::create('company_emails_domain', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_email_id')->constrained('company_emails')->onDelete('cascade');
            $table->foreignId('domain_id')->constrained('domain')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_emails_domain');
    }
};
