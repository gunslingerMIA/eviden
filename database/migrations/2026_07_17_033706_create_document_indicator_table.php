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
        Schema::create('document_indicator', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('indicator_id')->constrained('evaluation_indicators')->cascadeOnDelete();
            $table->foreignId('ditautkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            //Mencegah dokumen yang sama ditautkan 2x ke indikator yang sama
            $table->unique(['document_id', 'indicator_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_indicator');
    }
};
