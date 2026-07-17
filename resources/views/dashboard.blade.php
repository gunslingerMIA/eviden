@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-slate-900">Dashboard Ringkasan</h2>
        <p class="text-slate-500 mt-1">Status pengumpulan eviden dan evaluasi kinerja saat ini.</p>
    </div>

    <!-- Statistik Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Evaluasi</span>
            <h3 class="text-3xl font-bold text-slate-900 mt-1">{{ $total_evaluasi }}</h3>
        </div>
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Folder</span>
            <h3 class="text-3xl font-bold text-slate-900 mt-1">{{ $total_folder }}</h3>
        </div>
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total File Eviden</span>
            <h3 class="text-3xl font-bold text-slate-900 mt-1">{{ $total_dokumen }}</h3>
        </div>
    </div>

    <!-- Dev Info -->
    <div class="bg-gradient-to-r from-slate-900 to-indigo-950 text-white rounded-xl p-8 shadow-sm">
        <h3 class="text-xl font-bold">Portal Pengunggahan Eviden Digital</h3>
        <p class="text-slate-300 mt-2 max-w-2xl text-sm leading-relaxed">
            Anda saat ini mengakses aplikasi dalam mode <strong>Development/Bypass Auth</strong>. Semua file yang diunggah dan folder yang dibuat sementara akan diatribusikan ke user demo utama Anda.
        </p>
        <div class="mt-6 flex gap-4">
            <a href="{{ route('folders.index') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-lg text-sm font-semibold transition">
                Buka File Manajer
            </a>
            <a href="{{ route('evaluations.index') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-200 px-5 py-2.5 rounded-lg text-sm font-semibold transition">
                Lihat Indikator Evaluasi
            </a>
        </div>
    </div>
</div>
@endsection
