@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto" x-data="{ openLinkModal: false, selectedIndicatorId: null, selectedIndicatorName: '' }">
    
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-slate-900">Pemenuhan Indikator Evaluasi</h2>
        <p class="text-slate-500 mt-1">Tautkan berkas bukti fisik (eviden) yang sudah Anda unggah di Gudang Eviden ke dalam indikator evaluasi.</p>
    </div>

    <!-- Loop Evaluasi -->
    @foreach($evaluations as $evaluasi)
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden mb-8">
            <div class="bg-slate-900 text-white p-6">
                <span class="text-xs bg-indigo-600 text-white font-mono font-semibold px-2.5 py-1 rounded">{{ $evaluasi->tahun }}</span>
                <h3 class="text-xl font-bold mt-2">{{ $evaluasi->nama_evaluasi }}</h3>
                <p class="text-xs text-slate-400 mt-1">Instansi Penilai: {{ $evaluasi->instansi_penilai ?? 'Belum Ditentukan' }}</p>
            </div>

            <!-- List Indikator di Bawah Evaluasi -->
            <div class="divide-y divide-slate-100">
                @foreach($evaluasi->indicators as $ind)
                    <div class="p-6">
                        <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-4">
                            <div class="max-w-2xl">
                                <h4 class="font-bold text-slate-900 text-md">{{ $ind->nama_indikator }}</h4>
                                <p class="text-xs text-slate-500 mt-1 leading-relaxed">{{ $ind->deskripsi }}</p>
                                <span class="text-xs text-slate-400 font-medium block mt-2">
                                    PIC: <strong class="text-slate-700">{{ $ind->user->name ?? 'Belum Ditunjuk' }}</strong>
                                </span>
                            </div>
                            <!-- Tombol Tautkan -->
                            <div>
                                <button 
                                    @click="openLinkModal = true; selectedIndicatorId = '{{ $ind->id }}'; selectedIndicatorName = '{{ $ind->nama_indikator }}'"
                                    class="px-3 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-lg text-xs font-semibold shadow-sm transition"
                                >
                                    🔗 Tautkan Eviden
                                </button>
                            </div>
                        </div>

                        <!-- Daftar Dokumen Terhubung -->
                        <div>
                            <h5 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Dokumen Eviden Terhubung ({{ $ind->documents->count() }})</h5>
                            @if($ind->documents->isEmpty())
                                <p class="text-xs text-slate-400 italic">Belum ada berkas eviden yang ditautkan ke indikator ini.</p>
                            @else
                                <div class="flex flex-wrap gap-2">
                                    @foreach($ind->documents as $doc)
                                        <div class="flex items-center gap-2 pl-3 pr-2 py-1.5 bg-slate-100 text-slate-800 rounded-lg text-xs font-medium border border-slate-200">
                                            <span>📄</span>
                                            <span class="truncate max-w-xs">{{ $doc->judul_dokumen }}</span>
                                            
                                            <!-- Aksi Putus Tautan -->
                                            <form action="{{ route('evaluations.unlink-document', [$ind->id, $doc->id]) }}" method="POST" class="inline ml-1">
                                                @csrf
                                                <button type="submit" class="text-slate-400 hover:text-rose-600 transition" title="Lepas Tautan">
                                                    ❌
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    <!-- MODAL: Pilih Dokumen dari Gudang Eviden -->
    <div x-show="openLinkModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-xl max-w-lg w-full p-6 shadow-xl border border-slate-200" @click.away="openLinkModal = false">
            <h3 class="text-lg font-bold text-slate-900 mb-2">Tautkan Dokumen Eviden</h3>
            <p class="text-xs text-slate-500 mb-4">
                Pilih dokumen dari Gudang Eviden untuk indikator:<br>
                <strong class="text-indigo-600 font-semibold" x-text="selectedIndicatorName"></strong>
            </p>

            <form :action="`/evaluations/indicators/${selectedIndicatorId}/link-document`" method="POST">
                @csrf
                <div class="mb-6 max-h-60 overflow-y-auto border border-slate-200 rounded-lg p-2 divide-y divide-slate-100">
                    @if($allDocuments->isEmpty())
                        <p class="text-xs text-slate-400 italic p-4 text-center">Gudang Eviden kosong. Silakan unggah dokumen di menu Gudang Eviden terlebih dahulu.</p>
                    @else
                        @foreach($allDocuments as $doc)
                            <label class="flex items-center gap-3 p-3 hover:bg-slate-50 rounded-md cursor-pointer transition">
                                <input type="radio" name="document_id" value="{{ $doc->id }}" required class="text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $doc->judul_dokumen }}</p>
                                    <span class="text-[10px] text-slate-500 font-mono">{{ strtoupper($doc->ekstensi) }} &bull; {{ $doc->folder->nama_folder ?? 'Gudang Utama' }}</span>
                                </div>
                            </label>
                        @endforeach
                    @endif
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" @click="openLinkModal = false; selectedIndicatorId = null" class="px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-700 hover:bg-slate-50">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 font-semibold shadow-sm" :disabled="!selectedIndicatorId">Tautkan</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
