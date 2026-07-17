<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Menambahkan status share dan token unik acak untuk tautan publik
            $table->boolean('is_shared')->default(false)->after('uploader_id');
            $table->string('share_token', 64)->nullable()->unique()->after('is_shared');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['is_shared', 'share_token']);
        });
    }
};
