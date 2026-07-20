@extends('layouts.app')

@section('content')
@php
    // Compile all linked documents for this specific evaluation, grouped by document ID
    $groupedEvalLinkedDocs = [];
    foreach ($evaluation->indicators as $ind) {
        foreach ($ind->documents as $doc) {
            if (!isset($groupedEvalLinkedDocs[$doc->id])) {
                $groupedEvalLinkedDocs[$doc->id] = [
                    'id' => $doc->id,
                    'judul_dokumen' => $doc->judul_dokumen,
                    'ekstensi' => strtolower($doc->ekstensi),
                    'ukuran_file' => number_format($doc->ukuran_file / 1024, 0) . ' KB',
                    'file_path' => asset('storage/' . $doc->file_path),
                    'links' => [],
                ];
            }
            $groupedEvalLinkedDocs[$doc->id]['links'][] = [
                'indikator_nama' => $ind->nama_indikator,
                'indikator_id' => $ind->id,
                'pic_user_id' => $ind->pic_user_id,
            ];
        }
    }
    $evalLinkedDocs = array_values($groupedEvalLinkedDocs);

    // Map all documents in database for autocompletion
    $allDocsJson = $allDocuments->map(fn($d) => [
        'id' => $d->id,
        'judul_dokumen' => $d->judul_dokumen,
        'ekstensi' => strtolower($d->ekstensi),
        'ukuran_file' => number_format($d->ukuran_file / 1024, 0) . ' KB',
        'file_path' => asset('storage/' . $d->file_path),
    ]);
@endphp

<div class="max-w-6xl mx-auto" x-data="{ 
    openNewInd: false, 
    openEditInd: false,
    editIndId: null,
    editIndName: '',
    editIndDesc: '',
    editIndPic: '',
    
    // Modal Dokumen
    openDocModal: false,
    selectedInd: null,
    
    // Active User
    activeUser: {{ json_encode(['id' => $activeUser->id, 'name' => $activeUser->name]) }},
    
    // Search
    searchQuery: '',
    linkedDocs: {{ json_encode($evalLinkedDocs) }},
    
    // Autocomplete untuk penautan berkas
    allDatabaseDocs: {{ json_encode($allDocsJson) }},
    docSearchQuery: '',
    
    // Preview
    previewDoc: null,
    
    initEditInd(indData) {
        this.editIndId = indData.id;
        this.editIndName = indData.nama_indikator;
        this.editIndDesc = indData.deskripsi || '';
        this.editIndPic = indData.pic_user_id || '';
        this.openEditInd = true;
    },
    
    showDocModal(indData) {
        this.selectedInd = indData;
        this.openDocModal = true;
        this.docSearchQuery = '';
        setTimeout(() => lucide.createIcons(), 50);
    },
    
    init() {
        this.$watch('searchQuery', value => {
            setTimeout(() => lucide.createIcons(), 50);
        });
    }
}">

    <!-- Breadcrumbs & Navigation -->
    <div class="mb-6 flex items-center justify-between animate-in fade-in duration-200">
        <a href="{{ route('evaluations.index') }}" class="inline-flex items-center gap-1.5 text-xs text-slate-500 hover:text-indigo-600 transition font-medium">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
            Kembali ke Daftar Penilaian
        </a>
        <span class="text-[10px] bg-slate-200 text-slate-700 font-mono font-bold px-2 py-0.5 rounded-md">ID: EVAL-{{ $evaluation->id }}</span>
    </div>

    <!-- Header Detail Penilaian -->
    <div class="bg-white border border-slate-200 rounded-2xl p-6 xl:p-8 shadow-sm mb-8 animate-in fade-in duration-200">
        <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-4">
            <div class="space-y-1">
                <span class="text-xs bg-indigo-50 text-indigo-700 border border-indigo-100 font-bold px-3 py-1 rounded-full">
                    Tahun {{ $evaluation->tahun }}
                </span>
                <h1 class="text-2xl xl:text-3xl font-extrabold text-slate-900 mt-2">{{ $evaluation->nama_evaluasi }}</h1>
                <p class="text-xs text-slate-400 font-medium flex items-center gap-1.5">
                    <i data-lucide="building" class="w-4 h-4 text-slate-400"></i>
                    Instansi Penilai: <strong class="text-slate-700">{{ $evaluation->instansi_penilai ?? 'Belum Ditentukan' }}</strong>
                </p>
            </div>
            
            <div class="flex items-center gap-2">
                <!-- Tambah Indikator Button -->
                <button @click="openNewInd = true" class="flex items-center gap-1.5 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-sm transition">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i>
                    Tambah Komponen Penilaian
                </button>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-100">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Deskripsi Penilaian</span>
            <p class="text-xs xl:text-sm text-slate-600 leading-relaxed">{{ $evaluation->deskripsi ?? 'Tidak ada deskripsi penjelasan evaluasi.' }}</p>
        </div>
    </div>

    <!-- Search Input Bar -->
    <div class="relative w-full mb-8 animate-in fade-in duration-200">
        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
            <i data-lucide="search" class="w-4 h-4"></i>
        </span>
        <input 
            type="text" 
            x-model="searchQuery" 
            placeholder="Cari berkas eviden yang tertaut pada penilaian ini..." 
            class="w-full pl-10 pr-4 py-3 border border-slate-200 rounded-2xl text-sm bg-white text-slate-700 focus:outline-none focus:border-indigo-500 transition shadow-sm"
        >
    </div>

    <!-- VIEW: HASIL PENCARIAN BERKAS TERTAUT (Hanya menampilkan berkas yang ditautkan di evaluasi ini) -->
    <div x-show="searchQuery.trim() !== ''" class="space-y-4 mb-8" x-cloak>
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Hasil Pencarian Berkas Tertaut</h3>
        
        <div class="grid grid-cols-1 gap-3">
            <template x-for="doc in linkedDocs.filter(d => d.judul_dokumen.toLowerCase().includes(searchQuery.toLowerCase()))" :key="doc.id">
                <div class="bg-white border border-slate-200 hover:border-indigo-500 rounded-2xl p-5 shadow-sm hover:shadow-md transition duration-150 flex items-start justify-between gap-4 animate-in fade-in duration-150">
                    <div class="flex items-start gap-3 min-w-0">
                        <div class="p-2.5 rounded-xl bg-indigo-50 text-indigo-700 shrink-0 mt-0.5">
                            <i class="w-5 h-5" :data-lucide="doc.ekstensi === 'pdf' ? 'file-text' : (['png','jpg','jpeg','svg','webp'].includes(doc.ekstensi) ? 'image' : 'file')"></i>
                        </div>
                        <div class="min-w-0 text-left">
                            <p class="text-sm font-bold text-slate-800 leading-snug truncate" x-text="doc.judul_dokumen"></p>
                            <!-- Tautan Tag Indikator -->
                            <div class="mt-3.5 space-y-2">
                                <template x-for="(link, lIdx) in doc.links" :key="lIdx">
                                    <div class="flex items-center gap-1.5 flex-wrap w-fit">
                                        <span class="text-[9px] bg-indigo-50 text-indigo-700 font-semibold px-2 py-0.5 rounded border border-indigo-100/50" x-text="link.indikator_nama"></span>
                                        
                                        <!-- Aksi Lepas Tautan khusus untuk indikator ini jika user adalah PIC -->
                                        <template x-if="activeUser.id == link.pic_user_id">
                                            <form :action="`/evaluations/indicators/${link.indikator_id}/unlink-document/${doc.id}`" method="POST" 
                                                  onsubmit="confirmDelete(event, 'Lepas Tautan?', 'Dokumen ini akan dilepas kaitannya dari komponen penilaian ini!')"
                                                  class="inline-block">
                                                @csrf
                                                <button type="submit" class="text-slate-400 hover:text-rose-650 font-bold text-[9px] ml-1 transition" title="Lepas Kaitan">
                                                    ✕
                                                </button>
                                            </form>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-1.5 shrink-0 mt-0.5">
                        <button @click="previewDoc = doc" class="p-2 text-slate-400 hover:text-indigo-650 hover:bg-slate-50 rounded-xl transition" title="Pratinjau Berkas">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                        <a :href="doc.file_path" download class="p-2 text-slate-400 hover:text-indigo-650 hover:bg-slate-50 rounded-xl transition" title="Unduh Berkas">
                            <i data-lucide="download" class="w-4 h-4"></i>
                        </a>
                    </div>
                </div>
            </template>
            
            <!-- Jika tidak ditemukan -->
            <div x-show="linkedDocs.filter(d => d.judul_dokumen.toLowerCase().includes(searchQuery.toLowerCase())).length === 0" 
                 class="bg-white border border-slate-200 rounded-2xl p-8 text-center text-slate-400 shadow-sm">
                <i data-lucide="search" class="w-8 h-8 mx-auto text-slate-300 mb-2"></i>
                <p class="text-xs font-semibold text-slate-655">Berkas tidak ditemukan</p>
                <p class="text-[10px] text-slate-400 mt-0.5">Tidak ada berkas tertaut di penilaian ini yang cocok dengan kata kunci tersebut.</p>
            </div>
        </div>
    </div>

    <!-- VIEW: DAFTAR INDIKATOR / KOMPONEN UTAMA (Tampil jika kolom search kosong) -->
    <div x-show="searchQuery.trim() === ''" class="space-y-4 animate-in fade-in duration-200">
        <div class="flex items-center justify-between">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Komponen & Indikator Penilaian ({{ $evaluation->indicators->count() }})</h3>
        </div>

        @if($evaluation->indicators->isEmpty())
            <div class="bg-white border-2 border-dashed border-slate-200 rounded-2xl p-12 text-center text-slate-400 shadow-sm">
                <i data-lucide="check-square" class="w-12 h-12 mx-auto text-slate-300 mb-3 animate-bounce"></i>
                <p class="text-sm font-semibold text-slate-655">Belum ada komponen penilaian</p>
                <p class="text-xs text-slate-400 mt-1">Gunakan tombol "Tambah Komponen Penilaian" di atas untuk menambahkan indikator pemenuhan.</p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4">
                @foreach($evaluation->indicators as $ind)
                    @php
                        // Memetakan berkas yang tertaut untuk mempermudah operasional Javascript
                        $linkedDocs = $ind->documents->map(fn($d) => [
                            'id' => $d->id,
                            'judul_dokumen' => $d->judul_dokumen,
                            'file_path' => asset('storage/' . $d->file_path),
                            'ekstensi' => strtolower($d->ekstensi),
                            'ukuran_file' => number_format($d->ukuran_file / 1024, 0) . ' KB',
                            'uploader_name' => $d->uploader->name ?? 'System',
                        ]);
                        
                        $indData = [
                            'id' => $ind->id,
                            'nama_indikator' => $ind->nama_indikator,
                            'deskripsi' => $ind->deskripsi,
                            'pic_user_id' => $ind->pic_user_id,
                            'pic_name' => $ind->user->name ?? 'Belum Ditunjuk',
                            'documents' => $linkedDocs
                        ];
                    @endphp
                    <div class="bg-white border border-slate-200 hover:border-slate-300 rounded-2xl p-6 shadow-sm hover:shadow-md transition duration-150 relative group flex flex-col sm:flex-row sm:items-start justify-between gap-4 cursor-pointer"
                         @click="showDocModal({{ json_encode($indData) }})">
                        
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-2 flex-wrap">
                                <span class="text-[9px] bg-slate-100 text-slate-600 font-mono font-medium px-2 py-0.5 rounded border border-slate-200">
                                    IND-{{ $ind->id }}
                                </span>
                                <span class="text-[9px] bg-indigo-50 text-indigo-700 font-semibold px-2 py-0.5 rounded flex items-center gap-1">
                                    <i data-lucide="user" class="w-3 h-3"></i>
                                    PIC: {{ $ind->user->name ?? 'Belum Ditunjuk' }}
                                </span>
                            </div>
                            <h4 class="text-base font-bold text-slate-850 group-hover:text-indigo-650 transition">{{ $ind->nama_indikator }}</h4>
                            <p class="text-xs text-slate-500 mt-1.5 leading-relaxed line-clamp-2">{{ $ind->deskripsi ?? 'Tidak ada deskripsi komponen.' }}</p>
                            
                            <!-- Badges berkas -->
                            <div class="mt-4 flex items-center gap-2">
                                <span class="text-[10px] text-slate-400 font-medium flex items-center gap-1 bg-slate-50 px-2 py-1 rounded-md border border-slate-200/50">
                                    <i data-lucide="file-text" class="w-3.5 h-3.5 text-indigo-500"></i>
                                    {{ $ind->documents->count() }} Berkas Eviden Tertaut
                                </span>
                            </div>
                        </div>

                        <!-- Action Buttons (Indicators CRUD) -->
                        <div class="flex sm:flex-col items-center justify-end gap-1.5 shrink-0 opacity-0 group-hover:opacity-100 transition duration-150" @click.stop>
                            <button @click.prevent="initEditInd({{ json_encode($indData) }})" 
                                    class="p-2 text-slate-450 hover:text-indigo-650 hover:bg-indigo-50 rounded-xl transition"
                                    title="Edit Komponen">
                                <i data-lucide="edit-2" class="w-4 h-4"></i>
                            </button>
                            <form action="{{ route('evaluations.indicators.destroy', $ind->id) }}" method="POST" 
                                  onsubmit="confirmDelete(event, 'Hapus Komponen Penilaian?', 'Semua kaitan berkas dengan indikator ini akan terputus!')" 
                                  class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="p-2 text-slate-450 hover:text-rose-650 hover:bg-rose-50 rounded-xl transition"
                                        title="Hapus Komponen">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- MODAL: Tambah Indikator Baru -->
    <div x-show="openNewInd" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 animate-in fade-in zoom-in-95 duration-150" @click.away="openNewInd = false">
            <h3 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                <i data-lucide="plus-circle" class="w-5 h-5 text-indigo-600"></i> Tambah Komponen Baru
            </h3>
            <form action="{{ route('evaluations.indicators.store', $evaluation->id) }}" method="POST">
                @csrf
                <div class="space-y-4 mb-6">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Nama Komponen / Indikator</label>
                        <input type="text" name="nama_indikator" required placeholder="Contoh: Maklumat Pelayanan Publik" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Petugas PIC Penanggung Jawab</label>
                        <select name="pic_user_id" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:border-indigo-500 transition">
                            <option value="">-- Pilih PIC --</option>
                            @foreach($allUsers as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Deskripsi Indikator / Kriteria Bukti</label>
                        <textarea name="deskripsi" rows="3" placeholder="Sebutkan berkas bukti fisik yang harus diunggah di indikator ini..." class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-2.5">
                    <button type="button" @click="openNewInd = false" class="px-4 py-2.5 border border-slate-300 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">Batal</button>
                    <button type="submit" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold transition">Tambah</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Ubah Indikator (Edit) -->
    <div x-show="openEditInd" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 animate-in fade-in zoom-in-95 duration-150" @click.away="openEditInd = false">
            <h3 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                <i data-lucide="edit-3" class="w-5 h-5 text-indigo-600"></i> Ubah Komponen Penilaian
            </h3>
            <form :action="`/evaluations/indicators/${editIndId}`" method="POST">
                @csrf
                @method('PUT')
                <div class="space-y-4 mb-6">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Nama Komponen / Indikator</label>
                        <input type="text" name="nama_indikator" x-model="editIndName" required class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Petugas PIC Penanggung Jawab</label>
                        <select name="pic_user_id" x-model="editIndPic" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:border-indigo-500 transition">
                            <option value="">-- Pilih PIC --</option>
                            @foreach($allUsers as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Deskripsi Indikator / Kriteria Bukti</label>
                        <textarea name="deskripsi" rows="3" x-model="editIndDesc" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-2.5">
                    <button type="button" @click="openEditInd = false" class="px-4 py-2.5 border border-slate-300 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">Batal</button>
                    <button type="submit" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold transition">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL DETAIL & DOKUMEN TERTAUT (Klik Indikator) - DIPERBESAR (max-w-4xl) & TABEL -->
    <div x-show="openDocModal" class="fixed inset-0 z-40 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-2xl max-w-4xl w-full p-6 shadow-2xl border border-slate-100 animate-in fade-in zoom-in-95 duration-150 flex flex-col max-h-[90vh]" 
             @click.away="openDocModal = false; window.location.reload();">
            
            <!-- Header Modal -->
            <div class="pb-4 border-b border-slate-100 mb-4 shrink-0 flex items-start justify-between gap-4">
                <div class="min-w-0" x-data>
                    <template x-if="selectedInd">
                        <div>
                            <span class="text-[9px] bg-indigo-50 text-indigo-700 font-bold px-2 py-0.5 rounded" x-text="`IND-${selectedInd.id}`"></span>
                            <h3 class="text-base font-bold text-slate-900 mt-1 leading-snug truncate" x-text="selectedInd.nama_indikator"></h3>
                            <span class="text-[10px] text-slate-400 mt-1 block">PIC: <strong class="text-slate-650" x-text="selectedInd.pic_name"></strong></span>
                        </div>
                    </template>
                </div>
                <button @click="openDocModal = false; window.location.reload();" class="text-slate-400 hover:text-slate-600 text-xs p-1">✕</button>
            </div>
            
            <!-- Body Modal (Scrollable) -->
            <div class="flex-1 overflow-y-auto min-h-0 space-y-5 pr-1">
                <!-- Deskripsi Indikator -->
                <template x-if="selectedInd && selectedInd.deskripsi">
                    <div class="bg-slate-50 border border-slate-150 p-3.5 rounded-xl">
                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Bukti yang Diperlukan:</span>
                        <p class="text-xs text-slate-600 leading-relaxed" x-text="selectedInd.deskripsi"></p>
                    </div>
                </template>

                <!-- Daftar Dokumen Terhubung (Tampilan Tabel) -->
                <div>
                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block mb-2">Berkas Pendukung Terhubung</span>
                    
                    <template x-if="selectedInd && selectedInd.documents.length === 0">
                        <div class="border border-dashed border-slate-200 rounded-xl p-8 text-center text-slate-400">
                            <i data-lucide="file" class="w-8 h-8 mx-auto text-slate-350 mb-2"></i>
                            <p class="text-xs font-medium text-slate-550">Belum ada eviden yang tertaut</p>
                        </div>
                    </template>

                    <template x-if="selectedInd && selectedInd.documents.length > 0">
                        <div class="overflow-x-auto border border-slate-200 rounded-xl">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-200 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                                        <th class="px-4 py-3">Nama Berkas</th>
                                        <th class="px-4 py-3">Ukuran & Tipe</th>
                                        <th class="px-4 py-3">Pengunggah</th>
                                        <th class="px-4 py-3 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-slate-700">
                                    <template x-for="(doc, idx) in selectedInd.documents" :key="doc.id + '-' + selectedInd.id">
                                        <tr class="hover:bg-slate-50/50 transition">
                                            <td class="px-4 py-3 font-semibold text-slate-800">
                                                <div class="flex items-center gap-2">
                                                    <!-- Icon berdasarkan tipe file -->
                                                    <div class="p-1 rounded bg-indigo-50 text-indigo-700 shrink-0">
                                                        <i class="w-4 h-4" :data-lucide="doc.ekstensi === 'pdf' ? 'file-text' : (['png','jpg','jpeg','svg','webp'].includes(doc.ekstensi) ? 'image' : 'file')"></i>
                                                    </div>
                                                    <span class="truncate max-w-xs" x-text="doc.judul_dokumen"></span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 font-mono text-[10px]">
                                                <span class="uppercase font-bold" x-text="doc.ekstensi"></span>
                                                <span class="text-slate-400" x-text="` (${doc.ukuran_file})`"></span>
                                            </td>
                                            <td class="px-4 py-3 text-slate-500" x-text="doc.uploader_name"></td>
                                            <td class="px-4 py-3 text-right">
                                                <div class="flex items-center justify-end gap-1.5">
                                                    <!-- Preview -->
                                                    <button @click="previewDoc = doc" class="p-1.5 text-slate-400 hover:text-indigo-650 hover:bg-slate-100 rounded-lg transition" title="Pratinjau Dokumen">
                                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                                    </button>
                                                    <!-- Download -->
                                                    <a :href="doc.file_path" download class="p-1.5 text-slate-400 hover:text-indigo-650 hover:bg-slate-100 rounded-lg transition" title="Unduh Dokumen">
                                                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                                    </a>
                                                    <!-- Unlink (Hanya untuk PIC) -->
                                                    <template x-if="activeUser.id == selectedInd.pic_user_id">
                                                        <form :action="`/evaluations/indicators/${selectedInd.id}/unlink-document/${doc.id}`" method="POST" 
                                                              onsubmit="confirmDelete(event, 'Lepas Tautan?', 'Dokumen ini akan dilepas kaitannya dari komponen penilaian ini!')">
                                                            @csrf
                                                            <button type="submit" class="p-1.5 text-slate-450 hover:text-rose-650 hover:bg-rose-50 rounded-lg transition" title="Lepas Kaitan">
                                                                <i data-lucide="unlink" class="w-3.5 h-3.5"></i>
                                                            </button>
                                                        </form>
                                                    </template>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </div>

                <!-- Formulir Tambah Tautan Baru (Mode Pencarian Autocomplete) - KHUSUS PIC -->
                <template x-if="selectedInd && activeUser.id == selectedInd.pic_user_id">
                    <div class="pt-4 border-t border-slate-100 bg-slate-50/50 p-4 rounded-xl border border-slate-200">
                        <span class="text-[9px] font-bold text-indigo-650 uppercase tracking-wider block mb-2">Tautkan Berkas Baru dari Gudang (Pencarian)</span>
                        <div class="relative" x-data="{ openResults: false }">
                            <input 
                                type="text" 
                                x-model="docSearchQuery" 
                                @focus="openResults = true"
                                @click.away="setTimeout(() => openResults = false, 200)"
                                placeholder="Ketik untuk mencari berkas di Gudang Eviden..." 
                                class="w-full text-xs px-3.5 py-2.5 border border-slate-300 rounded-xl focus:outline-none focus:border-indigo-500 bg-white transition"
                            >
                            
                            <!-- Search Results Dropdown List -->
                            <div x-show="openResults && docSearchQuery.trim() !== ''" 
                                 class="absolute left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-48 overflow-y-auto z-50 divide-y divide-slate-100" 
                                 x-cloak>
                                
                                <template x-for="dbDoc in allDatabaseDocs.filter(d => d.judul_dokumen.toLowerCase().includes(docSearchQuery.toLowerCase()) && !selectedInd.documents.some(sd => sd.id === d.id))" :key="dbDoc.id">
                                    <form :action="`/evaluations/indicators/${selectedInd.id}/link-document`" method="POST" class="w-full">
                                        @csrf
                                        <input type="hidden" name="document_id" :value="dbDoc.id">
                                        <button 
                                            type="submit" 
                                            class="w-full flex items-center justify-between p-3 hover:bg-slate-50 text-left text-xs transition font-medium"
                                        >
                                            <div class="min-w-0">
                                                <p class="font-bold text-slate-800 truncate" x-text="dbDoc.judul_dokumen"></p>
                                                <span class="text-[9px] text-slate-450 font-mono uppercase" x-text="`${dbDoc.ekstensi} • ${dbDoc.ukuran_file}`"></span>
                                            </div>
                                            <span class="text-[10px] text-indigo-600 font-bold shrink-0 flex items-center gap-1">
                                                🔗 Kaitkan
                                            </span>
                                        </button>
                                    </form>
                                </template>
                                
                                <!-- Fallback jika kosong -->
                                <div x-show="allDatabaseDocs.filter(d => d.judul_dokumen.toLowerCase().includes(docSearchQuery.toLowerCase()) && !selectedInd.documents.some(sd => sd.id === d.id)).length === 0"
                                     class="p-4 text-center text-slate-400 text-xs">
                                    Berkas tidak ditemukan atau sudah tertaut.
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Formulir Unggah Berkas Baru Langsung (KHUSUS PIC) -->
                <template x-if="selectedInd && activeUser.id == selectedInd.pic_user_id">
                    <div class="pt-4 border-t border-slate-100 bg-slate-50/50 p-4 rounded-xl border border-slate-200">
                        <span class="text-[9px] font-bold text-indigo-650 uppercase tracking-wider block mb-2">Unggah Berkas Baru langsung sebagai PIC</span>
                        <form :action="`/evaluations/indicators/${selectedInd.id}/upload-document`" method="POST" enctype="multipart/form-data" class="space-y-3">
                            @csrf
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[8px] font-bold text-slate-400 uppercase mb-1">Judul Dokumen</label>
                                    <input type="text" name="judul_dokumen" required placeholder="Contoh: Laporan Publik 2026" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:border-indigo-500 bg-white transition">
                                </div>
                                <div>
                                    <label class="block text-[8px] font-bold text-slate-400 uppercase mb-1">Pilih Berkas</label>
                                    <input type="file" name="file" required class="w-full text-xs px-2 py-1.5 border border-slate-300 rounded-lg focus:outline-none focus:border-indigo-500 bg-white transition">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[8px] font-bold text-slate-400 uppercase mb-1">Tahun Mulai (Opsional)</label>
                                    <input type="number" name="tahun_mulai" value="{{ date('Y') }}" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:border-indigo-500 bg-white transition">
                                </div>
                                <div>
                                    <label class="block text-[8px] font-bold text-slate-400 uppercase mb-1">Tahun Selesai (Opsional)</label>
                                    <input type="number" name="tahun_selesai" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:border-indigo-500 bg-white transition">
                                </div>
                            </div>
                            <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl shadow-sm transition">
                                🚀 Unggah & Tautkan Berkas
                            </button>
                        </form>
                    </div>
                </template>
            </div>

            <!-- Footer Modal -->
            <div class="pt-4 border-t border-slate-100 shrink-0 flex justify-end">
                <button @click="openDocModal = false; window.location.reload();" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition shadow-sm">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL: Pratinjau Dokumen (Preview Modal) -->
    <div x-show="previewDoc" class="fixed inset-0 z-50 flex flex-col justify-between p-6 bg-slate-950/95 backdrop-blur-md" x-cloak x-transition>
        <!-- Header Modal -->
        <div class="flex items-center justify-between text-white pb-4 border-b border-slate-800/80">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-slate-800 rounded-lg text-slate-300">
                    <i data-lucide="file-text" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold" x-text="previewDoc ? previewDoc.judul_dokumen : ''"></h3>
                    <span class="text-[10px] text-slate-400 font-mono uppercase" x-text="previewDoc ? previewDoc.ekstensi : ''"></span>
                </div>
            </div>
            <button @click="previewDoc = null" class="text-slate-400 hover:text-white text-xs font-bold px-4 py-2 border border-slate-800 hover:border-slate-700 rounded-xl transition">
                ✕ Tutup Pratinjau
            </button>
        </div>

        <!-- Area Preview Utama -->
        <div class="flex-1 my-6 flex items-center justify-center overflow-hidden">
            <template x-if="previewDoc && previewDoc.ekstensi === 'pdf'">
                <iframe :src="previewDoc.file_path" class="w-full h-full max-w-5xl bg-white rounded-2xl shadow-2xl" frameborder="0"></iframe>
            </template>
            
            <template x-if="previewDoc && ['png', 'jpg', 'jpeg', 'svg', 'webp'].includes(previewDoc.ekstensi)">
                <img :src="previewDoc.file_path" class="max-w-full max-h-full object-contain rounded-2xl shadow-2xl bg-slate-900 border border-slate-800">
            </template>

            <!-- Fallback format file yang tidak bisa dipreview langsung -->
            <template x-if="previewDoc && previewDoc.ekstensi !== 'pdf' && !['png', 'jpg', 'jpeg', 'svg', 'webp'].includes(previewDoc.ekstensi)">
                <div class="bg-slate-900 text-slate-300 p-8 rounded-2xl max-w-md text-center space-y-4 shadow-2xl border border-slate-800">
                    <i data-lucide="folder-archive" class="w-12 h-12 text-slate-500 mx-auto"></i>
                    <h4 class="font-bold text-white text-md">Pratinjau Tidak Didukung</h4>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Dokumen dengan format <span class="uppercase font-mono font-bold text-white" x-text="previewDoc.ekstensi"></span> tidak mendukung pratinjau langsung di web browser.<br>Silakan unduh berkas untuk membukanya secara lokal.
                    </p>
                    <a :href="previewDoc.file_path" download class="inline-flex items-center gap-2 px-5 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold transition shadow-md shadow-indigo-900/40">
                        <i data-lucide="download" class="w-4 h-4"></i> Unduh Berkas Sekarang
                    </a>
                </div>
            </template>
        </div>

        <!-- Footer Modal -->
        <div class="text-center text-slate-650 text-[10px] tracking-wider uppercase font-semibold">
            EVIDEN Digital Secure Viewer
        </div>
    </div>

</div>
@endsection
