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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('judul_dokumen');
            $table->string('file_path');
            $table->string('ekstensi', 10)->nullable();
            $table->integer('ukuran_file')->nullable();
            
            //letak dokumen di folder mana
            $table->foreignId('folder_id')->nullable()->constrained()->cascadeOnDelete();

            //metadata tambahan untuk multiyears
            $table->year('tahun_mulai')->nullable();
            $table->year('tahun_selesai')->nullable();

            $table->foreignId('uploader_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

           
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
