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
        Schema::create('folders', function (Blueprint $table) {
            $table->id();
            $table->string('nama_folder');

            //parent id null = folder di halaman utama (root)
            $table->foreignId('parent_id')->nullable()->constrained('folders')->cascadeOnDelete();

            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folders');
    }
};
