@extends('layouts.app')

@section('content')
@php
    // Compile all linked documents across all evaluations & indicators, grouped by document ID
    $groupedLinkedDocs = [];
    foreach ($evaluations as $eval) {
        foreach ($eval->indicators as $ind) {
            foreach ($ind->documents as $doc) {
                if (!isset($groupedLinkedDocs[$doc->id])) {
                    $groupedLinkedDocs[$doc->id] = [
                        'id' => $doc->id,
                        'judul_dokumen' => $doc->judul_dokumen,
                        'ekstensi' => strtolower($doc->ekstensi),
                        'ukuran_file' => number_format($doc->ukuran_file / 1024, 0) . ' KB',
                        'file_path' => asset('storage/' . $doc->file_path),
                        'links' => [],
                    ];
                }
                $groupedLinkedDocs[$doc->id]['links'][] = [
                    'evaluasi_nama' => $eval->nama_evaluasi,
                    'indikator_nama' => $ind->nama_indikator,
                    'evaluasi_id' => $eval->id,
                    'indikator_id' => $ind->id,
                ];
            }
        }
    }
    // Re-index to make it a list for JSON conversion
    $allLinkedDocs = array_values($groupedLinkedDocs);
@endphp

<div class="max-w-6xl mx-auto" x-data="{ 
    openNewEval: false, 
    openEditEval: false,
    editEvalId: null,
    editEvalName: '',
    editEvalDesc: '',
    editEvalAgency: '',
    editEvalYear: '',
    
    // Search
    searchQuery: '',
    allLinkedDocs: {{ json_encode($allLinkedDocs) }},
    
    // Preview
    previewDoc: null,
    
    initEdit(evalData) {
        this.editEvalId = evalData.id;
        this.editEvalName = evalData.nama_evaluasi;
        this.editEvalDesc = evalData.deskripsi || '';
        this.editEvalAgency = evalData.instansi_penilai || '';
        this.editEvalYear = evalData.tahun;
        this.openEditEval = true;
    },
    
    init() {
        this.$watch('searchQuery', value => {
            setTimeout(() => lucide.createIcons(), 50);
        });
    }
}">
    
    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm animate-in fade-in duration-200">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                <i data-lucide="clipboard-list" class="w-6 h-6 text-indigo-600"></i>
                Daftar Penilaian & Evaluasi
            </h2>
            <p class="text-slate-500 mt-1">Kelola penilaian instansi seperti ZI, SAKIP, SPBE, dll., beserta indikatornya.</p>
        </div>
        
        <button @click="openNewEval = true" class="flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold shadow-sm transition shrink-0">
            <i data-lucide="plus-circle" class="w-4 h-4"></i>
            Tambah Penilaian
        </button>
    </div>

    <!-- Search Input Bar -->
    <div class="relative w-full mb-8">
        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
            <i data-lucide="search" class="w-4 h-4"></i>
        </span>
        <input 
            type="text" 
            x-model="searchQuery" 
            placeholder="Cari berkas eviden yang sudah ditautkan..." 
            class="w-full pl-10 pr-4 py-3 border border-slate-200 rounded-2xl text-sm bg-white text-slate-700 focus:outline-none focus:border-indigo-500 transition shadow-sm animate-in fade-in duration-200"
        >
    </div>

    <!-- VIEW: HASIL PENCARIAN BERKAS TERTAUT (File tunggal dengan daftar penilaian di bawahnya) -->
    <div x-show="searchQuery.trim() !== ''" class="space-y-4 mb-8" x-cloak>
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Hasil Pencarian Berkas Tertaut</h3>
        
        <div class="grid grid-cols-1 gap-3">
            <template x-for="doc in allLinkedDocs.filter(d => d.judul_dokumen.toLowerCase().includes(searchQuery.toLowerCase()))" :key="doc.id">
                <div class="bg-white border border-slate-200 hover:border-indigo-500 rounded-2xl p-5 shadow-sm hover:shadow-md transition duration-150 flex items-start justify-between gap-4 animate-in fade-in duration-150">
                    <div class="flex items-start gap-3 min-w-0">
                        <div class="p-2.5 rounded-xl bg-indigo-50 text-indigo-700 shrink-0 mt-0.5">
                            <i class="w-5 h-5" :data-lucide="doc.ekstensi === 'pdf' ? 'file-text' : (['png','jpg','jpeg','svg','webp'].includes(doc.ekstensi) ? 'image' : 'file')"></i>
                        </div>
                        <div class="min-w-0 text-left">
                            <p class="text-sm font-bold text-slate-800 leading-snug truncate" x-text="doc.judul_dokumen"></p>
                            <!-- List Tautan Tag -->
                            <div class="mt-3.5 space-y-2">
                                <template x-for="(link, lIdx) in doc.links" :key="lIdx">
                                    <a :href="`/evaluations/${link.evaluasi_id}`" class="flex items-center gap-1.5 hover:opacity-85 transition flex-wrap w-fit">
                                        <span class="text-[9px] bg-slate-100 text-slate-650 font-medium px-2 py-0.5 rounded border border-slate-200/50" x-text="link.evaluasi_nama"></span>
                                        <span class="text-[9px] text-slate-400">›</span>
                                        <span class="text-[9px] bg-indigo-50 text-indigo-700 font-semibold px-2 py-0.5 rounded border border-indigo-100/50" x-text="link.indikator_nama"></span>
                                    </a>
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
            <div x-show="allLinkedDocs.filter(d => d.judul_dokumen.toLowerCase().includes(searchQuery.toLowerCase())).length === 0" 
                 class="bg-white border border-slate-200 rounded-2xl p-8 text-center text-slate-400 shadow-sm">
                <i data-lucide="search" class="w-8 h-8 mx-auto text-slate-300 mb-2"></i>
                <p class="text-xs font-semibold text-slate-650">Berkas tidak ditemukan</p>
                <p class="text-[10px] text-slate-400 mt-0.5">Tidak ada berkas tertaut yang cocok dengan kata kunci tersebut.</p>
            </div>
        </div>
    </div>

    <!-- VIEW: DAFTAR EVALUASI UTAMA (Tampil jika kolom search kosong) -->
    <div x-show="searchQuery.trim() === ''">
        @if($evaluations->isEmpty())
            <div class="bg-white border-2 border-dashed border-slate-200 rounded-2xl p-16 text-center text-slate-400">
                <i data-lucide="award" class="w-16 h-16 mx-auto text-slate-300 mb-4 animate-pulse"></i>
                <p class="text-base font-semibold text-slate-600">Belum ada daftar penilaian</p>
                <p class="text-xs text-slate-400 mt-1">Mulai dengan menambahkan penilaian baru seperti ZI atau SAKIP menggunakan tombol di atas.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 animate-in fade-in duration-200">
                @foreach($evaluations as $eval)
                    @php
                        $evalData = [
                            'id' => $eval->id,
                            'nama_evaluasi' => $eval->nama_evaluasi,
                            'deskripsi' => $eval->deskripsi,
                            'instansi_penilai' => $eval->instansi_penilai,
                            'tahun' => $eval->tahun
                        ];
                    @endphp
                    <div class="bg-white border border-slate-200 hover:border-indigo-500 rounded-2xl p-6 shadow-sm hover:shadow-md transition duration-200 relative group flex flex-col justify-between cursor-pointer"
                         @click="window.location = '{{ route('evaluations.show', $eval->id) }}'">
                        
                        <div>
                            <!-- Header Card -->
                            <div class="flex items-start justify-between gap-4 mb-3">
                                <span class="text-[10px] bg-indigo-50 text-indigo-700 font-bold px-2.5 py-1 rounded-md shrink-0">
                                    Tahun {{ $eval->tahun }}
                                </span>
                                <div class="flex items-center gap-1.5 opacity-0 group-hover:opacity-100 transition duration-150">
                                    <button @click.prevent.stop="initEdit({{ json_encode($evalData) }})" 
                                            class="p-1.5 text-slate-400 hover:text-indigo-650 hover:bg-indigo-50 rounded-lg transition"
                                            title="Ubah Evaluasi">
                                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                                    </button>
                                    <form action="{{ route('evaluations.destroy', $eval->id) }}" method="POST" 
                                          onsubmit="confirmDelete(event, 'Hapus Evaluasi?', 'Evaluasi ini beserta seluruh indikator dan tautan berkas di dalamnya akan terhapus permanen!')" 
                                          class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" @click.stop 
                                                class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
                                                title="Hapus Evaluasi">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Title & Desc -->
                            <h3 class="text-lg font-bold text-slate-800 group-hover:text-indigo-650 transition leading-snug">
                                {{ $eval->nama_evaluasi }}
                            </h3>
                            <p class="text-xs text-slate-400 mt-1 flex items-center gap-1">
                                <i data-lucide="building" class="w-3.5 h-3.5"></i>
                                Penilai: {{ $eval->instansi_penilai ?? 'Internal' }}
                            </p>
                            <p class="text-xs text-slate-500 mt-3 line-clamp-3 leading-relaxed">
                                {{ $eval->deskripsi ?? 'Tidak ada deskripsi penjelasan.' }}
                            </p>
                        </div>

                        <!-- Footer Card -->
                        <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400">
                            <span class="flex items-center gap-1.5 text-slate-500 font-medium bg-slate-50 px-2.5 py-1 rounded-lg border border-slate-200/50">
                                <i data-lucide="check-square" class="w-4 h-4 text-indigo-500"></i>
                                {{ $eval->indicators->count() }} Komponen Penilaian
                            </span>
                            <span class="text-indigo-600 font-semibold group-hover:translate-x-1 transition duration-150 flex items-center gap-0.5">
                                Buka Detail <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- MODAL: Tambah Penilaian Baru -->
    <div x-show="openNewEval" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 animate-in fade-in zoom-in-95 duration-150" @click.away="openNewEval = false">
            <h3 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                <i data-lucide="plus-circle" class="w-5 h-5 text-indigo-600"></i> Tambah Penilaian Baru
            </h3>
            <form action="{{ route('evaluations.store') }}" method="POST">
                @csrf
                <div class="space-y-4 mb-6">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Nama Evaluasi/Penilaian</label>
                        <input type="text" name="nama_evaluasi" required placeholder="Contoh: SAKIP Kabupaten" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Instansi Penilai</label>
                        <input type="text" name="instansi_penilai" placeholder="Contoh: Kementerian PANRB" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Tahun Evaluasi</label>
                        <input type="number" name="tahun" required value="{{ date('Y') }}" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Deskripsi Penjelasan</label>
                        <textarea name="deskripsi" rows="3" placeholder="Jelaskan secara singkat mengenai evaluasi ini..." class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-2.5">
                    <button type="button" @click="openNewEval = false" class="px-4 py-2.5 border border-slate-300 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">Batal</button>
                    <button type="submit" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold transition">Tambah</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Ubah Penilaian (Edit) -->
    <div x-show="openEditEval" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 animate-in fade-in zoom-in-95 duration-150" @click.away="openEditEval = false">
            <h3 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                <i data-lucide="edit-3" class="w-5 h-5 text-indigo-600"></i> Ubah Penilaian
            </h3>
            <form :action="`/evaluations/${editEvalId}`" method="POST">
                @csrf
                @method('PUT')
                <div class="space-y-4 mb-6">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Nama Evaluasi/Penilaian</label>
                        <input type="text" name="nama_evaluasi" x-model="editEvalName" required class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Instansi Penilai</label>
                        <input type="text" name="instansi_penilai" x-model="editEvalAgency" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Tahun Evaluasi</label>
                        <input type="number" name="tahun" x-model="editEvalYear" required class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Deskripsi Penjelasan</label>
                        <textarea name="deskripsi" rows="3" x-model="editEvalDesc" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-2.5">
                    <button type="button" @click="openEditEval = false" class="px-4 py-2.5 border border-slate-300 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">Batal</button>
                    <button type="submit" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold transition">Simpan Perubahan</button>
                </div>
            </form>
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
