<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Evaluation;
use App\Models\EvaluationIndicator;
use App\Models\Folder;
use App\Models\Document;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat User Contoh
        $user1 = User::create([
            'name' => 'Didik Yogo Suro Prasojo, S.Kom',
            'email' => 'didikysp619@gmail.com',
            'password' => Hash::make('dpmptsp'),
        ]);

        $user2 = User::create([
            'name' => 'Pegawai Dua',
            'email' => 'pegawai2@eviden.com',
            'password' => Hash::make('dpmptsp'),
        ]);

        // 2. Buat Data Evaluasi & Indikator
        $evaluasi = Evaluation::create([
            'nama_evaluasi' => 'Evaluasi Kinerja Zona Integritas',
            'instansi_penilai' => 'Kementerian PANRB',
            'tahun' => 2026,
        ]);

        EvaluationIndicator::create([
            'evaluation_id' => $evaluasi->id,
            'nama_indikator' => 'Peningkatan Kualitas Pelayanan Publik',
            'deskripsi' => 'Bukti berupa maklumat pelayanan, survei kepuasan pelanggan, dan standar prosedur pelayanan.',
            'pic_user_id' => $user1->id,
        ]);

        EvaluationIndicator::create([
            'evaluation_id' => $evaluasi->id,
            'nama_indikator' => 'Penerapan Sistem Pemerintahan Berbasis Elektronik (SPBE)',
            'deskripsi' => 'Bukti berupa dokumentasi penggunaan aplikasi e-office, sertifikasi server, dan SOP keamanan IT.',
            'pic_user_id' => $user2->id,
        ]);

        // 3. Buat Struktur Folder Awal
        $rootFolder = Folder::create([
            'nama_folder' => 'Dokumen Utama',
            'parent_id' => null,
            'dibuat_oleh' => $user1->id,
        ]);

        $subFolder = Folder::create([
            'nama_folder' => 'Laporan Pelayanan 2026',
            'parent_id' => $rootFolder->id,
            'dibuat_oleh' => $user1->id,
        ]);

        // 4. Buat Contoh Dokumen Pendukung
        Document::create([
            'judul_dokumen' => 'Standar Pelayanan Publik Terpadu',
            'file_path' => 'documents/contoh_standar_pelayanan.pdf',
            'ekstensi' => 'pdf',
            'ukuran_file' => 1542000, // Sekitar 1.5 MB
            'folder_id' => $subFolder->id,
            'tahun_mulai' => 2026,
            'tahun_selesai' => 2026,
            'uploader_id' => $user1->id,
        ]);
    }
}
