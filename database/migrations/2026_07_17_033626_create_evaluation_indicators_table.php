<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up():void
    {
        Schema::create('evaluation_indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained()->cascadeOnDelete();
            $table->string('nama_indikator');
            $table->string('deskripsi')->nullable();
            
            // PIC yang ditugaskan (opsional, nyambung ke tabel users bawaan Laravel)
        $table->foreignId('pic_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_indicators');
    }
};
