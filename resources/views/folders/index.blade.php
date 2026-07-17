@extends('layouts.app')

@section('content')
<div class="w-full h-full flex flex-col relative" 
     x-data="{ 
         openNewFolder: false, 
         openUpload: false, 
         selectedDoc: null, 
         previewDoc: null,
         copyStatus: 'Salin Link',
         openEditFolder: false,
         editFolderId: null,
         editFolderName: '',
         openEditDoc: false,
         openLinkIndicator: false,
         openLinkFolderIndicator: false,
         selectedFolder: null,
         viewMode: localStorage.getItem('eviden_view_mode') || 'grid',
         openShareModal: false,
         copyShareStatus: 'Salin Link',
         isShared: false,
         shareUrl: '',
         linkedIndicatorIds: [],
         async toggleShareStatus() {
             if (!this.selectedDoc) return;
             let url = `/documents/${this.selectedDoc.id}/toggle-share`;
             
             let response = await fetch(url, {
                 method: 'POST',
                 headers: {
                     'X-CSRF-TOKEN': '{{ csrf_token() }}',
                     'Accept': 'application/json',
                     'X-Requested-With': 'XMLHttpRequest'
                 }
             });
             
             if (response.ok) {
                 let data = await response.json();
                 if (data.success) {
                     this.isShared = data.is_shared;
                     this.shareUrl = data.share_url;
                     
                     // Re-assign object to trigger deep reactivity in Alpine
                     this.selectedDoc = {
                         ...this.selectedDoc,
                         is_shared: data.is_shared,
                         share_url: data.share_url
                     };
                     this.copyShareStatus = 'Salin Link';
                     setTimeout(() => lucide.createIcons(), 50);
                 }
             }
         },
         
         // State Baru: Tab & Search
         currentTab: 'files',
         searchQuery: '',
         
         // State Baru: Pemindahan (Move)
         openMoveModal: false,
         moveTargetType: '',
         moveTargetId: null,
         moveTargetName: '',
         
         // State Drag & Drop
         isDragging: false,
         uploads: [],
         async handleDrop(e) {
             this.isDragging = false;
             let items = e.dataTransfer.items;
             if (!items) return;
             
             let filesList = [];
             
             const traverseFileTree = (item, path = '') => {
                 return new Promise((resolve) => {
                     if (item.isFile) {
                         item.file((file) => {
                             file.relativePath = path + file.name;
                             filesList.push(file);
                             resolve();
                         });
                     } else if (item.isDirectory) {
                         let dirReader = item.createReader();
                         const readAllEntries = () => {
                             dirReader.readEntries(async (entries) => {
                                 if (entries.length > 0) {
                                     for (let i = 0; i < entries.length; i++) {
                                         await traverseFileTree(entries[i], path + item.name + '/');
                                     }
                                     readAllEntries();
                                 } else {
                                     resolve();
                                 }
                             });
                         };
                         readAllEntries();
                     }
                 });
             };
             
             let promises = [];
             for (let i = 0; i < items.length; i++) {
                 if (typeof items[i].webkitGetAsEntry === 'function') {
                     let entry = items[i].webkitGetAsEntry();
                     if (entry) {
                         promises.push(traverseFileTree(entry));
                     }
                 }
             }
             
             await Promise.all(promises);
             this.uploadFiles(filesList);
         },
         uploadFiles(files) {
             if (files.length === 0) return;
             
             let uploadUrl = '{{ $currentFolder ? route('folders.store-document', $currentFolder->id) : route('folders.store-document-root') }}';
             
             Array.from(files).forEach(file => {
                 let uploadItem = {
                     name: file.name,
                     progress: 0,
                     status: 'Mengunggah...'
                 };
                 this.uploads.push(uploadItem);
                 
                 let formData = new FormData();
                 formData.append('file', file);
                 formData.append('judul_dokumen', file.name.split('.').slice(0, -1).join('.') || file.name);
                 
                 // Kirim relative_path ke server agar direkonstruksi folder-nya
                 let relativePath = file.relativePath || file.webkitRelativePath || '';
                 formData.append('relative_path', relativePath);
                 
                 formData.append('_token', '{{ csrf_token() }}');
                 
                 let xhr = new XMLHttpRequest();
                 xhr.open('POST', uploadUrl, true);
                 xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                 xhr.setRequestHeader('Accept', 'application/json');
                 
                 xhr.upload.addEventListener('progress', e => {
                     if (e.lengthComputable) {
                         let percent = Math.round((e.loaded / e.total) * 100);
                         uploadItem.progress = percent;
                     }
                 });
                 
                 xhr.addEventListener('load', () => {
                     if (xhr.status >= 200 && xhr.status < 300) {
                         uploadItem.progress = 100;
                         uploadItem.status = 'Selesai';
                         setTimeout(() => { window.location.reload(); }, 1200);
                     } else {
                         uploadItem.status = 'Gagal';
                     }
                     setTimeout(() => { lucide.createIcons(); }, 100);
                 });
                 
                 xhr.addEventListener('error', () => {
                     uploadItem.status = 'Gagal';
                 });
                 
                 xhr.send(formData);
             });
         },
         
         // State Context Menu (Klik Kanan)
         contextMenu: {
             show: false,
             x: 0,
             y: 0,
             type: '',
             targetId: null,
             targetName: '',
             targetIsShared: false
         },
         openContextMenu(e, type, target = null) {
             this.contextMenu.show = true;
             this.contextMenu.x = e.clientX;
             this.contextMenu.y = e.clientY;
             this.contextMenu.type = type;
             
             if (target) {
                 this.contextMenu.targetId = target.id;
                 this.contextMenu.targetName = target.judul_dokumen || target.nama_folder || '';
                 this.contextMenu.targetIsShared = target.is_shared || false;
                 
                 if (type === 'document' || type === 'trashed') {
                     this.selectedDoc = target;
                 }
             }
             setTimeout(() => { lucide.createIcons(); }, 50);
         }
     }"
     @dragover.prevent="isDragging = true"
     @dragleave.prevent="isDragging = false"
     @drop.prevent="handleDrop($event)"
     @click="contextMenu.show = false"
>

    <!-- Overlay Visual Drag & Drop -->
    <div x-show="isDragging" class="absolute inset-0 z-50 flex flex-col items-center justify-center p-8 bg-indigo-600/90 backdrop-blur-sm border-4 border-dashed border-white text-white rounded-2xl animate-in fade-in duration-150" x-cloak>
        <div class="p-6 bg-white/10 rounded-full mb-4 animate-bounce">
            <i data-lucide="upload-cloud" class="w-16 h-16"></i>
        </div>
        <h3 class="text-2xl font-bold">Lepaskan Berkas untuk Mengunggah</h3>
        <p class="text-indigo-200 mt-2 text-sm">Taruh berkas pendukung Anda di sini untuk mengunggah secara otomatis.</p>
    </div>

    <!-- Top Action Bar & Breadcrumbs -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm animate-in fade-in duration-200"
         @contextmenu.prevent="openContextMenu($event, 'blank')">
        <div>
            <!-- Breadcrumbs -->
            <div class="flex items-center gap-2 text-xs text-slate-400 font-medium">
                <a href="{{ route('folders.index') }}" class="hover:text-indigo-600 flex items-center gap-1 transition">
                    <i data-lucide="database" class="w-3.5 h-3.5"></i> Gudang Utama
                </a>
                @if($currentFolder)
                    <i data-lucide="chevron-right" class="w-3 h-3 text-slate-300"></i>
                    @if($currentFolder->parent)
                        <a href="{{ route('folders.show', $currentFolder->parent->id) }}" class="hover:text-indigo-600 transition">{{ $currentFolder->parent->nama_folder }}</a>
                        <i data-lucide="chevron-right" class="w-3 h-3 text-slate-300"></i>
                    @endif
                    <span class="text-slate-800 font-semibold">{{ $currentFolder->nama_folder }}</span>
                @endif
            </div>
            <h2 class="text-2xl font-bold text-slate-900 mt-2 flex items-center gap-2">
                <i data-lucide="folder-open" class="w-6 h-6 text-indigo-600"></i>
                {{ $currentFolder ? $currentFolder->nama_folder : 'Gudang Utama' }}
            </h2>
        </div>
        
        <!-- Action Buttons -->
        <div class="flex items-center gap-3">
            <button @click="openNewFolder = true" class="flex items-center gap-2 px-4 py-2.5 border border-slate-300 rounded-xl text-sm font-semibold bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-400 shadow-sm transition">
                <i data-lucide="folder-plus" class="w-4 h-4 text-slate-500"></i>
                Buat Folder
            </button>
            <button @click="openUpload = true" class="flex items-center gap-2 px-4 py-2.5 bg-indigo-600 rounded-xl text-sm font-semibold text-white hover:bg-indigo-700 shadow-sm transition">
                <i data-lucide="upload-cloud" class="w-4 h-4"></i>
                Unggah Dokumen
            </button>
            <button @click="document.getElementById('folderInputDirect').click()" class="flex items-center gap-2 px-4 py-2.5 bg-amber-500 hover:bg-amber-600 rounded-xl text-sm font-semibold text-white shadow-sm transition">
                <i data-lucide="folder-up" class="w-4 h-4"></i>
                Unggah Folder
            </button>
        </div>
    </div>

    <!-- Tab & Search Bar (Drive style subbar) -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8" @contextmenu.prevent="openContextMenu($event, 'blank')">
        <!-- Tab selector -->
        <div class="flex items-center bg-slate-200/60 p-1 rounded-xl">
            <button 
                @click="currentTab = 'files'; selectedDoc = null;" 
                class="px-4 py-2 rounded-lg text-xs font-bold transition-all duration-200 flex items-center gap-2"
                :class="currentTab === 'files' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
            >
                <i data-lucide="files" class="w-3.5 h-3.5"></i> Semua Berkas
            </button>
            <button 
                @click="currentTab = 'trash'; selectedDoc = null;" 
                class="px-4 py-2 rounded-lg text-xs font-bold transition-all duration-200 flex items-center gap-2"
                :class="currentTab === 'trash' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
            >
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Tempat Sampah
            </button>
        </div>

        <!-- Search Bar & Toggle View -->
        <div class="flex items-center gap-3 w-full sm:w-auto">
            <!-- Search Bar -->
            <div class="relative w-full sm:w-72">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i data-lucide="search" class="w-4 h-4"></i>
                </span>
                <input 
                    type="text" 
                    x-model="searchQuery" 
                    placeholder="Cari berkas atau folder..." 
                    class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-xl text-xs bg-white text-slate-700 focus:outline-none focus:border-indigo-500 transition shadow-sm"
                >
            </div>
            <!-- View Mode Toggle -->
            <div class="flex items-center bg-slate-200/60 p-1 rounded-xl shrink-0">
                <button 
                    @click="viewMode = 'grid'; localStorage.setItem('eviden_view_mode', 'grid'); setTimeout(() => lucide.createIcons(), 50);" 
                    class="p-2 rounded-lg text-xs font-bold transition flex items-center justify-center"
                    :class="viewMode === 'grid' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                    title="Tampilan Kisi (Grid)"
                >
                    <i data-lucide="layout-grid" class="w-4 h-4"></i>
                </button>
                <button 
                    @click="viewMode = 'list'; localStorage.setItem('eviden_view_mode', 'list'); setTimeout(() => lucide.createIcons(), 50);" 
                    class="p-2 rounded-lg text-xs font-bold transition flex items-center justify-center"
                    :class="viewMode === 'list' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                    title="Tampilan Daftar (List)"
                >
                    <i data-lucide="list" class="w-4 h-4"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Layout Utama: File Manager & Sidebar Detail -->
    <div class="w-full flex-1 flex flex-col lg:flex-row gap-6 items-start"
         @contextmenu.prevent="openContextMenu($event, 'blank')">
        
        <!-- Panel Kiri: Grid Folder & List Dokumen -->
        <div class="w-full lg:flex-1 space-y-8">
            
            <!-- VIEW: SEMUA BERKAS -->
            <div x-show="currentTab === 'files'" class="space-y-8">
                <!-- Bagian Folder -->
                <div>
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <i data-lucide="folders" class="w-4 h-4 text-slate-400"></i> Daftar Folder
                    </h3>
                    @if($folders->isEmpty())
                        <div class="p-6 bg-slate-50 border border-slate-200 rounded-2xl text-center text-slate-400 text-sm italic">
                            Tidak ada subfolder di direktori ini.
                        </div>
                    @else
                        <div :class="viewMode === 'grid' ? 'grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4' : 'flex flex-col gap-2'">
                            @foreach($folders as $folder)
                                <div class="flex items-center justify-between bg-white border border-slate-200 rounded-2xl hover:border-indigo-500 hover:shadow-md transition duration-200 group"
                                     :class="viewMode === 'grid' ? 'p-5' : 'p-3'"
                                     x-show="!searchQuery || {{ json_encode(strtolower($folder->nama_folder)) }}.includes(searchQuery.toLowerCase())"
                                     @contextmenu.prevent.stop="openContextMenu($event, 'folder', { id: '{{ $folder->id }}', nama_folder: '{{ addslashes($folder->nama_folder) }}' })">
                                    <a href="{{ route('folders.show', $folder->id) }}" class="flex items-center gap-4 min-w-0 flex-1">
                                        <div class="p-3 bg-amber-50 group-hover:bg-amber-100 rounded-xl text-amber-500 transition shrink-0">
                                            <i data-lucide="folder" class="w-6 h-6"></i>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-bold text-slate-800 truncate group-hover:text-indigo-600 transition leading-snug">{{ $folder->nama_folder }}</p>
                                            <div class="flex flex-wrap items-center gap-1.5 mt-1">
                                                <span class="text-[10px] text-slate-400 shrink-0">Oleh: {{ $folder->user->name ?? 'System' }}</span>
                                                @foreach($folder->indicators as $ind)
                                                    <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 bg-indigo-50 border border-indigo-100 text-indigo-700 rounded text-[9px] font-semibold">
                                                        {{ $ind->nama_indikator }}
                                                        <form action="{{ route('folders.unlink-indicator', [$folder->id, $ind->id]) }}" method="POST" class="inline" onsubmit="confirmDelete(event, 'Lepas Kaitan?', 'Lepas kaitan folder dengan penilaian ini?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-indigo-400 hover:text-rose-600 transition ml-0.5" title="Lepas Kaitan">✕</button>
                                                        </form>
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    </a>
                                    <!-- Tombol Aksi Rename / Hapus Folder -->
                                    <div class="flex items-center gap-1.5 ml-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button @click.prevent="openEditFolder = true; editFolderId = '{{ $folder->id }}'; editFolderName = '{{ addslashes($folder->nama_folder) }}'; setTimeout(() => lucide.createIcons(), 50);" class="p-1 text-slate-400 hover:text-indigo-600 transition" title="Ubah Nama">
                                            <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                        <form action="{{ route('folders.destroy', $folder->id) }}" method="POST" onsubmit="confirmDelete(event, 'Hapus Folder?', 'Folder ini beserta seluruh isi subfolder dan berkas di dalamnya akan terhapus permanen!')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1 text-slate-400 hover:text-rose-600 transition" title="Hapus Folder">
                                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Bagian Berkas Dokumen -->
                <div>
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <i data-lucide="files" class="w-4 h-4 text-slate-400"></i> Berkas Pendukung (Eviden)
                    </h3>
                    @if($documents->isEmpty())
                        <div class="bg-white border-2 border-dashed border-slate-200 rounded-2xl p-12 text-center text-slate-400">
                            <i data-lucide="file-text" class="w-12 h-12 mx-auto text-slate-300 mb-3"></i>
                            <p class="text-sm font-semibold text-slate-600">Belum ada dokumen eviden</p>
                            <p class="text-xs text-slate-400 mt-1">Gunakan tombol "Unggah Dokumen" atau seret berkas langsung ke layar ini.</p>
                        </div>
                    @else
                        <!-- Tampilan Daftar (List Table View) -->
                        <div x-show="viewMode === 'list'" class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm animate-in fade-in duration-200">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        <th class="px-6 py-4">Nama Berkas</th>
                                        <th class="px-6 py-4">Masa Berlaku</th>
                                        <th class="px-6 py-4">Tipe & Ukuran</th>
                                        <th class="px-6 py-4">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                                    @foreach($documents as $doc)
                                        @php
                                            $docObj = [
                                                'id' => $doc->id,
                                                'judul_dokumen' => $doc->judul_dokumen,
                                                'file_path' => asset('storage/' . $doc->file_path),
                                                'ekstensi' => strtolower($doc->ekstensi),
                                                'ukuran_file' => number_format($doc->ukuran_file / 1024, 0) . ' KB',
                                                'uploader_name' => $doc->uploader->name ?? 'System',
                                                'tahun_mulai' => $doc->tahun_mulai ?? 'Tanpa Tahun',
                                                'tahun_selesai' => $doc->tahun_selesai,
                                                'is_shared' => (bool)$doc->is_shared,
                                                'share_token' => $doc->share_token,
                                                'share_url' => $doc->share_token ? route('documents.shared', $doc->share_token) : '',
                                                'is_trashed' => false,
                                                'indicators' => $doc->indicators->map(fn($i) => ['id' => $i->id, 'nama_indikator' => $i->nama_indikator])
                                            ];
                                        @endphp
                                        <tr 
                                            @click.stop="selectedDoc = {{ json_encode($docObj) }}; isShared = selectedDoc.is_shared; shareUrl = selectedDoc.share_url; copyStatus = 'Salin Link'; setTimeout(() => lucide.createIcons(), 50);" 
                                            @dblclick.stop="previewDoc = selectedDoc; setTimeout(() => lucide.createIcons(), 50);"
                                            @contextmenu.prevent.stop="selectedDoc = {{ json_encode($docObj) }}; isShared = selectedDoc.is_shared; shareUrl = selectedDoc.share_url; openContextMenu($event, 'document', selectedDoc);"
                                            class="hover:bg-slate-50/70 transition cursor-pointer"
                                            :class="selectedDoc && selectedDoc.id == '{{ $doc->id }}' ? 'bg-indigo-50/50' : ''"
                                            x-show="!searchQuery || {{ json_encode(strtolower($doc->judul_dokumen)) }}.includes(searchQuery.toLowerCase())"
                                        >
                                            <td class="px-6 py-4 font-bold text-slate-900">
                                                <div class="flex items-center gap-3.5">
                                                    <div class="p-2 rounded-lg" :class="selectedDoc && selectedDoc.id == '{{ $doc->id }}' ? 'bg-indigo-100 text-indigo-600' : 'bg-slate-100 text-slate-500'">
                                                        <i data-lucide="file-text" class="w-5 h-5"></i>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p class="leading-snug truncate max-w-xs sm:max-w-md">{{ $doc->judul_dokumen }}</p>
                                                        <span class="text-[10px] text-slate-400 font-mono font-medium">{{ basename($doc->file_path) }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                @if($doc->tahun_mulai)
                                                    <span class="bg-indigo-50 text-indigo-700 px-2.5 py-1 rounded-lg text-xs font-mono font-semibold">
                                                        {{ $doc->tahun_mulai }} {{ $doc->tahun_selesai ? ' - ' . $doc->tahun_selesai : '' }}
                                                    </span>
                                                @else
                                                    <span class="text-slate-400 italic text-xs">Tanpa tahun</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 font-mono text-xs">
                                                <span class="uppercase font-bold text-slate-700">{{ $doc->ekstensi }}</span>
                                                @if($doc->ukuran_file)
                                                    <span class="text-slate-400"> ({{ number_format($doc->ukuran_file / 1024, 0) }} KB)</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4">
                                                @if($doc->is_shared)
                                                    <span class="bg-emerald-50 text-emerald-700 text-xs font-semibold px-2.5 py-1 rounded-lg flex items-center gap-1 w-fit">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Dibagikan
                                                    </span>
                                                @else
                                                    <span class="bg-slate-100 text-slate-500 text-xs font-semibold px-2.5 py-1 rounded-lg w-fit block">
                                                        Privat
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Tampilan Kisi (Grid Cards View) -->
                        <div x-show="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4 animate-in fade-in duration-200" x-cloak>
                            @foreach($documents as $doc)
                                @php
                                    $docObj = [
                                        'id' => $doc->id,
                                        'judul_dokumen' => $doc->judul_dokumen,
                                        'file_path' => asset('storage/' . $doc->file_path),
                                        'ekstensi' => strtolower($doc->ekstensi),
                                        'ukuran_file' => number_format($doc->ukuran_file / 1024, 0) . ' KB',
                                        'uploader_name' => $doc->uploader->name ?? 'System',
                                        'tahun_mulai' => $doc->tahun_mulai ?? 'Tanpa Tahun',
                                        'tahun_selesai' => $doc->tahun_selesai,
                                        'is_shared' => (bool)$doc->is_shared,
                                        'share_token' => $doc->share_token,
                                        'share_url' => $doc->share_token ? route('documents.shared', $doc->share_token) : '',
                                        'is_trashed' => false,
                                        'indicators' => $doc->indicators->map(fn($i) => ['id' => $i->id, 'nama_indikator' => $i->nama_indikator])
                                    ];
                                @endphp
                                <div 
                                    @click.stop="selectedDoc = {{ json_encode($docObj) }}; isShared = selectedDoc.is_shared; shareUrl = selectedDoc.share_url; copyStatus = 'Salin Link'; setTimeout(() => lucide.createIcons(), 50);" 
                                    @dblclick.stop="previewDoc = selectedDoc; setTimeout(() => lucide.createIcons(), 50);"
                                    @contextmenu.prevent.stop="selectedDoc = {{ json_encode($docObj) }}; isShared = selectedDoc.is_shared; shareUrl = selectedDoc.share_url; openContextMenu($event, 'document', selectedDoc);"
                                    class="bg-white border border-slate-200 rounded-2xl p-4 hover:border-indigo-500 hover:shadow-md transition duration-200 cursor-pointer flex flex-col justify-between group relative"
                                    :class="selectedDoc && selectedDoc.id == '{{ $doc->id }}' ? 'border-indigo-500 bg-indigo-50/10' : ''"
                                    x-show="!searchQuery || {{ json_encode(strtolower($doc->judul_dokumen)) }}.includes(searchQuery.toLowerCase())"
                                >
                                    <!-- File Preview/Icon Container -->
                                    <div class="h-28 bg-slate-50/80 border border-slate-100 rounded-xl flex items-center justify-center mb-3 text-slate-400 group-hover:bg-slate-100/50 transition">
                                        @if(in_array(strtolower($doc->ekstensi), ['png', 'jpg', 'jpeg', 'svg', 'webp']))
                                            <img src="{{ asset('storage/' . $doc->file_path) }}" class="w-full h-full object-cover rounded-xl">
                                        @elseif(strtolower($doc->ekstensi) === 'pdf')
                                            <i data-lucide="file-text" class="w-10 h-10 text-rose-500"></i>
                                        @else
                                            <i data-lucide="file" class="w-10 h-10 text-indigo-500"></i>
                                        @endif
                                    </div>
                                    
                                    <!-- File Info -->
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-slate-800 truncate leading-snug group-hover:text-indigo-600 transition">{{ $doc->judul_dokumen }}</p>
                                        <div class="flex items-center justify-between mt-1.5 text-[10px] text-slate-400">
                                            <span>{{ strtoupper($doc->ekstensi) }} • {{ number_format($doc->ukuran_file / 1024, 0) }} KB</span>
                                            <span>{{ $doc->uploader->name ?? 'System' }}</span>
                                        </div>
                                        
                                        <!-- Indicator badges inside Grid View card -->
                                        @if(!$doc->indicators->isEmpty())
                                            <div class="flex flex-wrap gap-1 mt-2.5">
                                                @foreach($doc->indicators as $ind)
                                                    <span class="inline-flex items-center px-1.5 py-0.5 bg-indigo-50/80 border border-indigo-150 text-indigo-700 rounded text-[9px] font-semibold">
                                                        {{ $ind->nama_indikator }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- VIEW: TEMPAT SAMPAH (Recycle Bin) -->
            <div x-show="currentTab === 'trash'" class="space-y-6" x-cloak>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center gap-2">
                        <i data-lucide="trash-2" class="w-4 h-4 text-slate-400"></i> Berkas Terhapus (Recycle Bin)
                    </h3>
                    @if(!$trashedDocuments->isEmpty())
                        <form action="{{ route('documents.empty-trash') }}" method="POST" onsubmit="confirmDelete(event, 'Kosongkan Tempat Sampah?', 'Semua berkas di dalam tempat sampah akan dihapus secara permanen!')" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm border border-rose-100">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Kosongkan Tempat Sampah
                            </button>
                        </form>
                    @endif
                </div>
                <div>
                    @if($trashedDocuments->isEmpty())
                        <div class="bg-white border-2 border-dashed border-slate-200 rounded-2xl p-12 text-center text-slate-400">
                            <i data-lucide="trash" class="w-12 h-12 mx-auto text-slate-300 mb-3"></i>
                            <p class="text-sm font-semibold text-slate-600">Tempat sampah kosong</p>
                            <p class="text-xs text-slate-400 mt-1">Dokumen yang Anda hapus sementara akan muncul di sini.</p>
                        </div>
                    @else
                        <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        <th class="px-6 py-4">Nama Berkas</th>
                                        <th class="px-6 py-4">Tipe & Ukuran</th>
                                        <th class="px-6 py-4">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                                    @foreach($trashedDocuments as $doc)
                                        @php
                                            $docObj = [
                                                'id' => $doc->id,
                                                'judul_dokumen' => $doc->judul_dokumen,
                                                'file_path' => asset('storage/' . $doc->file_path),
                                                'ekstensi' => strtolower($doc->ekstensi),
                                                'ukuran_file' => number_format($doc->ukuran_file / 1024, 0) . ' KB',
                                                'uploader_name' => $doc->uploader->name ?? 'System',
                                                'tahun_mulai' => $doc->tahun_mulai ?? 'Tanpa Tahun',
                                                'tahun_selesai' => $doc->tahun_selesai,
                                                'is_trashed' => true,
                                                'indicators' => $doc->indicators->map(fn($i) => ['id' => $i->id, 'nama_indikator' => $i->nama_indikator])
                                            ];
                                        @endphp
                                        <tr 
                                            @click.stop="selectedDoc = {{ json_encode($docObj) }}; isShared = false; shareUrl = ''; setTimeout(() => lucide.createIcons(), 50);"
                                            @contextmenu.prevent.stop="selectedDoc = {{ json_encode($docObj) }}; isShared = false; shareUrl = ''; openContextMenu($event, 'trashed', selectedDoc);"
                                            class="hover:bg-slate-50/70 transition cursor-pointer"
                                            :class="selectedDoc && selectedDoc.id == '{{ $doc->id }}' ? 'bg-indigo-50/50' : ''"
                                            x-show="!searchQuery || {{ json_encode(strtolower($doc->judul_dokumen)) }}.includes(searchQuery.toLowerCase())"
                                        >
                                            <td class="px-6 py-4 font-bold text-slate-900">
                                                <div class="flex items-center gap-3.5">
                                                    <div class="p-2 rounded-lg bg-slate-100 text-slate-400">
                                                        <i data-lucide="file-text" class="w-5 h-5"></i>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p class="leading-snug truncate max-w-xs sm:max-w-md">{{ $doc->judul_dokumen }}</p>
                                                        <span class="text-[10px] text-slate-400 font-mono font-medium">{{ basename($doc->file_path) }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 font-mono text-xs">
                                                <span class="uppercase font-bold text-slate-700">{{ $doc->ekstensi }}</span>
                                                @if($doc->ukuran_file)
                                                    <span class="text-slate-400"> ({{ number_format($doc->ukuran_file / 1024, 0) }} KB)</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-3">
                                                    <form action="{{ route('documents.restore', $doc->id) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-bold transition flex items-center gap-1">
                                                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i> Pulihkan
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('documents.force-delete', $doc->id) }}" method="POST" onsubmit="confirmDelete(event, 'Hapus Permanen?', 'Berkas akan dihapus selamanya dari server!')" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg text-xs font-bold transition flex items-center gap-1">
                                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Hapus Permanen
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

        </div>

        <!-- Panel Kanan: Sidebar Detail Berkas -->
        <div class="w-full lg:w-80 shrink-0">
            <!-- Tampilan Kosong Jika Belum Ada Berkas yang Dipilih -->
            <div x-show="!selectedDoc" class="bg-white border border-slate-200 rounded-2xl p-6 text-center text-slate-400 shadow-sm">
                <i data-lucide="info" class="w-8 h-8 text-slate-300 mx-auto mb-2"></i>
                <p class="text-xs font-semibold">Klik salah satu berkas di kiri untuk melihat detail & mengaktifkan link sharing.</p>
            </div>

            <template x-if="selectedDoc">
                <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-6 animate-in fade-in slide-in-from-right-4 duration-200" x-cloak>
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h4 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                            <i data-lucide="file-info" class="w-4 h-4 text-indigo-600"></i> Detail Berkas
                        </h4>
                        <button @click="selectedDoc = null" class="text-slate-400 hover:text-slate-600 text-xs p-1">✕</button>
                    </div>

                    <!-- Visual Tipe Berkas -->
                    <div class="h-32 bg-slate-50 rounded-xl flex flex-col items-center justify-center border border-slate-100">
                        <div class="p-3 bg-indigo-50 rounded-full text-indigo-600 mb-2">
                            <i class="w-8 h-8 animate-in zoom-in-50 duration-150" :data-lucide="selectedDoc.ekstensi === 'pdf' ? 'file-text' : (['png','jpg','jpeg','svg','webp'].includes(selectedDoc.ekstensi) ? 'image' : 'file')"></i>
                        </div>
                        <span class="text-xs font-bold text-slate-500 uppercase" x-text="selectedDoc.ekstensi"></span>
                    </div>

                    <!-- KONDISI DETAIL DOKUMEN BIASA -->
                    <template x-if="!selectedDoc.is_trashed">
                        <div class="space-y-6">
                            <!-- Metadata List -->
                            <div class="space-y-4 text-xs">
                                <div>
                                    <span class="text-slate-400 block mb-1">Nama Dokumen</span>
                                    <p class="font-bold text-slate-800 text-sm leading-snug" x-text="selectedDoc.judul_dokumen"></p>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <span class="text-slate-400 block mb-1">Ukuran File</span>
                                        <p class="font-semibold text-slate-800" x-text="selectedDoc.ukuran_file"></p>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block mb-1">Masa Berlaku</span>
                                        <p class="font-semibold text-slate-800" x-text="selectedDoc.tahun_mulai + (selectedDoc.tahun_selesai ? ' - ' + selectedDoc.tahun_selesai : '')"></p>
                                    </div>
                                </div>
                                <div class="pt-3 border-t border-slate-100">
                                    <span class="text-slate-400 block mb-1">Pengunggah</span>
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-slate-100 text-slate-600 font-bold flex items-center justify-center text-[10px]" x-text="selectedDoc.uploader_name[0]"></div>
                                        <p class="font-semibold text-slate-800" x-text="selectedDoc.uploader_name"></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Tags Kaitan Penilaian -->
                            <div class="pt-4 border-t border-slate-100 space-y-2">
                                <span class="text-xs font-bold text-slate-800 block">Kaitan Penilaian (Tags)</span>
                                <div class="flex flex-wrap gap-1.5" x-show="selectedDoc.indicators && selectedDoc.indicators.length > 0">
                                    <template x-for="ind in selectedDoc.indicators" :key="ind.id">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-indigo-50 border border-indigo-100 text-indigo-700 rounded-lg text-[10px] font-semibold">
                                            <span x-text="ind.nama_indikator"></span>
                                            <form :action="`/documents/${selectedDoc.id}/unlink-indicator/${ind.id}`" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-indigo-400 hover:text-rose-600 transition ml-1" title="Lepas Kaitan">
                                                    ✕
                                                </button>
                                            </form>
                                        </span>
                                    </template>
                                </div>
                                <p class="text-[10px] text-slate-400 italic" x-show="!selectedDoc.indicators || selectedDoc.indicators.length === 0">
                                    Belum dikaitkan ke penilaian mana pun.
                                </p>
                            </div>

                            <!-- Opsi Preview & Download & Link -->
                            <div class="space-y-2">
                                <div class="flex gap-2">
                                    <button 
                                        @click="previewDoc = selectedDoc; setTimeout(() => lucide.createIcons(), 50);" 
                                        class="flex-1 py-2 bg-indigo-600 text-white rounded-xl text-xs font-semibold text-center hover:bg-indigo-700 transition flex items-center justify-center gap-1.5 shadow-sm shadow-indigo-100"
                                    >
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i> Pratinjau
                                    </button>
                                    <a :href="selectedDoc.file_path" download class="flex-1 py-2 border border-slate-300 rounded-xl text-xs text-center font-semibold text-slate-700 hover:bg-slate-50 hover:border-slate-400 transition flex items-center justify-center gap-1.5">
                                        <i data-lucide="download" class="w-3.5 h-3.5 text-slate-400"></i> Unduh
                                    </a>
                                </div>
                                
                                <button 
                                    @click="openLinkIndicator = true; linkedIndicatorIds = selectedDoc.indicators ? selectedDoc.indicators.map(i => i.id) : []; setTimeout(() => lucide.createIcons(), 50);" 
                                    class="w-full py-2 border border-indigo-200 text-indigo-700 bg-indigo-50/50 rounded-xl text-xs font-semibold hover:bg-indigo-50 transition flex items-center justify-center gap-1.5"
                                >
                                    <i data-lucide="link" class="w-3.5 h-3.5"></i> Kaitkan ke Penilaian
                                </button>
                                
                                <button 
                                    @click="openMoveModal = true; moveTargetType = 'document'; moveTargetId = selectedDoc.id; moveTargetName = selectedDoc.judul_dokumen; setTimeout(() => lucide.createIcons(), 50);" 
                                    class="w-full py-2 border border-slate-300 text-slate-700 rounded-xl text-xs font-semibold hover:bg-slate-50 transition flex items-center justify-center gap-1.5"
                                >
                                    <i data-lucide="folder-symlink" class="w-3.5 h-3.5 text-slate-400"></i> Pindahkan Berkas
                                </button>
                            </div>

                            <!-- Panel Sharing Link -->
                            <div class="pt-4 border-t border-slate-100 space-y-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="text-xs font-bold text-slate-800 block">Bagikan Tautan</span>
                                        <span class="text-[10px]" :class="selectedDoc.is_shared ? 'text-emerald-600 font-semibold' : 'text-slate-400'" x-text="selectedDoc.is_shared ? '🌐 Berbagi Publik Aktif' : '🔒 Dibatasi'"></span>
                                    </div>
                                    <button @click="openShareModal = true; copyShareStatus = 'Salin Link'; setTimeout(() => lucide.createIcons(), 50);" class="text-xs font-semibold px-3.5 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition flex items-center gap-1">
                                        <i data-lucide="share-2" class="w-3 h-3"></i> Bagikan
                                    </button>
                                </div>
                            </div>

                            <!-- Panel Manipulasi CRUD Berkas -->
                            <div class="pt-4 border-t border-slate-100 flex gap-2.5">
                                <button @click="openEditDoc = true; setTimeout(() => lucide.createIcons(), 50);" class="flex-1 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold flex items-center justify-center gap-1.5 transition">
                                    <i data-lucide="edit" class="w-3.5 h-3.5 text-slate-500"></i> Edit Berkas
                                </button>
                                <form :action="`/documents/${selectedDoc.id}`" method="POST" class="flex-1 inline" onsubmit="confirmDelete(event, 'Hapus Berkas?', 'Berkas akan dipindahkan ke Tempat Sampah!')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-full py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-xl text-xs font-semibold flex items-center justify-center gap-1.5 transition">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5 text-rose-500"></i> Hapus Berkas
                                    </button>
                                </form>
                            </div>
                        </div>
                    </template>

                    <!-- KONDISI DETAIL DOKUMEN TERHAPUS (TRASHED) -->
                    <template x-if="selectedDoc.is_trashed">
                        <div class="space-y-6 pt-2">
                            <div class="p-4 bg-amber-50 border-l-4 border-amber-550 text-amber-800 rounded-r-lg text-xs leading-normal">
                                <strong class="block mb-1">Berkas Terhapus Sementara</strong>
                                Berkas ini berada di Tempat Sampah. Anda dapat memulihkannya ke folder asal atau menghapusnya permanen.
                            </div>
                            
                            <div class="space-y-4 text-xs">
                                <div>
                                    <span class="text-slate-400 block mb-1">Nama Dokumen</span>
                                    <p class="font-bold text-slate-800 text-sm leading-snug" x-text="selectedDoc.judul_dokumen"></p>
                                </div>
                                <div>
                                    <span class="text-slate-400 block mb-1">Ukuran File</span>
                                    <p class="font-semibold text-slate-800" x-text="selectedDoc.ukuran_file"></p>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <form :action="`/documents/${selectedDoc.id}/restore`" method="POST" class="w-full">
                                    @csrf
                                    <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold flex items-center justify-center gap-1.5 transition">
                                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i> Pulihkan Berkas
                                    </button>
                                </form>
                                <form :action="`/documents/${selectedDoc.id}/force-delete`" method="POST" onsubmit="confirmDelete(event, 'Hapus Permanen?', 'Berkas akan dihapus selamanya dari server!')" class="w-full">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-full py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-xl text-xs font-semibold flex items-center justify-center gap-1.5 transition">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5 text-rose-500"></i> Hapus Permanen
                                    </button>
                                </form>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>

    </div>

    <!-- FLOATING PROGRESS PANEL (Drag & Drop Uploader Status) -->
    <div x-show="uploads.length > 0" class="fixed bottom-6 right-6 w-80 bg-white border border-slate-200 rounded-2xl shadow-2xl overflow-hidden z-50 animate-in slide-in-from-bottom-5 duration-200" x-cloak>
        <div class="bg-slate-900 text-white p-4 flex items-center justify-between">
            <span class="text-xs font-bold flex items-center gap-1.5"><i data-lucide="upload" class="w-4 h-4"></i> Status Unggahan</span>
            <button @click="uploads = []" class="text-slate-400 hover:text-white text-xs">✕</button>
        </div>
        <div class="max-h-60 overflow-y-auto divide-y divide-slate-100 p-3 space-y-2">
            <template x-for="item in uploads" :key="item.name">
                <div class="py-2.5 space-y-1">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-medium text-slate-700 truncate max-w-[180px]" x-text="item.name"></span>
                        <span class="font-mono text-slate-400" x-text="item.status"></span>
                    </div>
                    <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                        <div class="bg-indigo-600 h-full transition-all duration-300" :style="`width: ${item.progress}%`"></div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- CUSTOM CONTEXT MENU (Klik Kanan Kustom ala Drive) -->
    <div x-show="contextMenu.show" 
         class="fixed bg-white border border-slate-200 rounded-xl shadow-xl py-2 w-48 z-50 text-xs text-slate-700 font-medium animate-in fade-in zoom-in-95 duration-100" 
         :style="`top: ${contextMenu.y}px; left: ${contextMenu.x}px;`"
         @click.away="contextMenu.show = false"
         x-cloak
    >
        <!-- Context Menu: FOLDER -->
        <template x-if="contextMenu.type === 'folder'">
            <div class="divide-y divide-slate-100">
                <div class="py-1">
                    <a :href="`/folders/${contextMenu.targetId}`" class="flex items-center gap-2.5 px-4 py-2 hover:bg-slate-50 text-slate-700 transition">
                        <i data-lucide="folder-open" class="w-3.5 h-3.5 text-slate-450"></i> Buka Folder
                    </a>
                    <a :href="`/folders/${contextMenu.targetId}/download-zip`" class="flex items-center gap-2.5 px-4 py-2 hover:bg-slate-50 text-slate-700 transition">
                        <i data-lucide="download" class="w-3.5 h-3.5 text-slate-450"></i> Unduh Folder (.zip)
                    </a>
                    <button @click="contextMenu.show = false; openLinkFolderIndicator = true; selectedFolder = { id: contextMenu.targetId, nama_folder: contextMenu.targetName }; setTimeout(() => lucide.createIcons(), 50);" class="w-full flex items-center gap-2.5 px-4 py-2 hover:bg-slate-50 text-slate-700 transition text-left">
                        <i data-lucide="link" class="w-3.5 h-3.5 text-slate-450"></i> Kaitkan Penilaian
                    </button>
                    <button @click="contextMenu.show = false; openMoveModal = true; moveTargetType = 'folder'; moveTargetId = contextMenu.targetId; moveTargetName = contextMenu.targetName; setTimeout(() => lucide.createIcons(), 50);" class="w-full flex items-center gap-2.5 px-4 py-2 hover:bg-slate-50 text-slate-700 transition text-left">
                        <i data-lucide="folder-symlink" class="w-3.5 h-3.5 text-slate-450"></i> Pindahkan Folder
                    </button>
                    <button @click="contextMenu.show = false; openEditFolder = true; editFolderId = contextMenu.targetId; editFolderName = contextMenu.targetName;" class="w-full flex items-center gap-2.5 px-4 py-2 hover:bg-slate-50 text-slate-700 transition text-left">
                        <i data-lucide="edit-2" class="w-3.5 h-3.5 text-slate-450"></i> Ubah Nama
                    </button>
                </div>
                <div class="py-1">
                    <form :action="`/folders/${contextMenu.targetId}`" method="POST" onsubmit="confirmDelete(event, 'Hapus Folder?', 'Folder ini beserta seluruh isi subfolder dan berkas di dalamnya akan terhapus permanen!')" class="w-full">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full flex items-center gap-2.5 px-4 py-2 hover:bg-rose-50 text-rose-600 transition text-left">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Hapus Folder
                        </button>
                    </form>
                </div>
            </div>
        </template>

        <!-- Context Menu: DOCUMENT -->
        <template x-if="contextMenu.type === 'document'">
            <div class="divide-y divide-slate-100">
                <div class="py-1">
                    <button @click="contextMenu.show = false; previewDoc = selectedDoc;" class="w-full flex items-center gap-2.5 px-4 py-2 hover:bg-slate-50 text-slate-700 transition text-left">
                        <i data-lucide="eye" class="w-3.5 h-3.5 text-slate-450"></i> Pratinjau
                    </button>
                    <a :href="selectedDoc ? selectedDoc.file_path : '#'" download class="flex items-center gap-2.5 px-4 py-2 hover:bg-slate-50 text-slate-700 transition">
                        <i data-lucide="download" class="w-3.5 h-3.5 text-slate-450"></i> Unduh Berkas
                    </a>
                </div>
                <div class="py-1">
                    <button @click="contextMenu.show = false; openLinkIndicator = true; linkedIndicatorIds = selectedDoc.indicators ? selectedDoc.indicators.map(i => i.id) : [];" class="w-full flex items-center gap-2.5 px-4 py-2 hover:bg-slate-50 text-slate-700 transition text-left">
                        <i data-lucide="link" class="w-3.5 h-3.5 text-slate-450"></i> Kaitkan Penilaian
                    </button>
                    <button @click="contextMenu.show = false; openMoveModal = true; moveTargetType = 'document'; moveTargetId = contextMenu.targetId; moveTargetName = contextMenu.targetName; setTimeout(() => lucide.createIcons(), 50);" class="w-full flex items-center gap-2.5 px-4 py-2 hover:bg-slate-50 text-slate-700 transition text-left">
                        <i data-lucide="folder-symlink" class="w-3.5 h-3.5 text-slate-450"></i> Pindahkan Berkas
                    </button>
                    <button @click="contextMenu.show = false; openShareModal = true; copyShareStatus = 'Salin Link'; setTimeout(() => lucide.createIcons(), 50);" class="w-full flex items-center gap-2.5 px-4 py-2 hover:bg-slate-50 text-slate-700 transition text-left">
                        <i data-lucide="share-2" class="w-3.5 h-3.5 text-slate-450"></i> Bagikan Berkas
                    </button>
                </div>
                <div class="py-1">
                    <button @click="contextMenu.show = false; openEditDoc = true;" class="w-full flex items-center gap-2.5 px-4 py-2 hover:bg-slate-50 text-slate-700 transition text-left">
                        <i data-lucide="edit" class="w-3.5 h-3.5 text-slate-450"></i> Edit Berkas
                    </button>
                    <form :action="`/documents/${contextMenu.targetId}`" method="POST" onsubmit="confirmDelete(event, 'Hapus Berkas?', 'Berkas akan dipindahkan ke Tempat Sampah!')" class="w-full">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full flex items-center gap-2.5 px-4 py-2 hover:bg-rose-50 text-rose-600 transition text-left">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Hapus Berkas
                        </button>
                    </form>
                </div>
            </div>
        </template>

        <!-- Context Menu: TRASHED DOCUMENT -->
        <template x-if="contextMenu.type === 'trashed'">
            <div class="divide-y divide-slate-100">
                <div class="py-1">
                    <form :action="`/documents/${contextMenu.targetId}/restore`" method="POST" class="w-full">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-2.5 px-4 py-2 hover:bg-slate-50 text-slate-700 transition text-left">
                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5 text-slate-450"></i> Pulihkan Berkas
                        </button>
                    </form>
                </div>
                <div class="py-1">
                    <form :action="`/documents/${contextMenu.targetId}/force-delete`" method="POST" onsubmit="confirmDelete(event, 'Hapus Permanen?', 'Berkas akan dihapus selamanya dari server!')" class="w-full">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full flex items-center gap-2.5 px-4 py-2 hover:bg-rose-50 text-rose-600 transition text-left">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Hapus Permanen
                        </button>
                    </form>
                </div>
            </div>
        </template>

        <!-- Context Menu: BLANK AREA -->
        <template x-if="contextMenu.type === 'blank'">
            <div class="py-1">
                <button @click="contextMenu.show = false; openNewFolder = true" class="w-full flex items-center gap-2.5 px-4 py-2 hover:bg-slate-50 text-slate-700 transition text-left">
                    <i data-lucide="folder-plus" class="w-3.5 h-3.5 text-slate-450"></i> Buat Folder Baru
                </button>
                <button @click="contextMenu.show = false; openUpload = true" class="w-full flex items-center gap-2.5 px-4 py-2 hover:bg-slate-50 text-slate-700 transition text-left">
                    <i data-lucide="upload-cloud" class="w-3.5 h-3.5 text-slate-450"></i> Unggah Berkas Baru
                </button>
                <button @click="contextMenu.show = false; document.getElementById('folderInputDirect').click()" class="w-full flex items-center gap-2.5 px-4 py-2 hover:bg-slate-50 text-slate-700 transition text-left">
                    <i data-lucide="folder-up" class="w-3.5 h-3.5 text-slate-450"></i> Unggah Folder Baru
                </button>
            </div>
        </template>
    </div>

    <!-- MODAL: Bagikan Berkas (Google Drive Style) -->
    <div x-show="openShareModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 animate-in fade-in zoom-in-95 duration-150" @click.away="openShareModal = false; window.location.reload();">
            <!-- Header Modal -->
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                <h3 class="text-md font-bold text-slate-900 flex items-center gap-2">
                    <i data-lucide="share-2" class="w-5 h-5 text-indigo-600"></i> Bagikan Berkas
                </h3>
                <button @click="openShareModal = false; window.location.reload();" class="text-slate-400 hover:text-slate-600 text-xs p-1">✕</button>
            </div>
            
            <template x-if="selectedDoc">
                <div class="space-y-6">
                    <!-- File Name info -->
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Nama Berkas</span>
                        <p class="text-xs font-bold text-slate-800 truncate" x-text="selectedDoc.judul_dokumen"></p>
                    </div>

                    <!-- Owner Section -->
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-2">Pemilik Berkas</span>
                        <div class="flex items-center justify-between bg-slate-50 p-3 rounded-xl border border-slate-150">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center text-xs" x-text="selectedDoc.uploader_name ? selectedDoc.uploader_name[0] : 'S'"></div>
                                <div>
                                    <p class="text-xs font-bold text-slate-800" x-text="selectedDoc.uploader_name"></p>
                                    <span class="text-[10px] text-slate-400">Pegawai (Demo User)</span>
                                </div>
                            </div>
                            <span class="text-[10px] bg-slate-200 text-slate-600 px-2 py-0.5 rounded-md font-semibold">Pemilik</span>
                        </div>
                    </div>
                    
                    <!-- General Access Section -->
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-2">Akses Umum</span>
                        <div class="flex items-start gap-4">
                            <!-- Icon -->
                            <div class="p-3 bg-indigo-50 rounded-xl text-indigo-600 mt-0.5" :key="isShared ? 'globe' : 'lock'">
                                <i :data-lucide="isShared ? 'globe' : 'lock'" class="w-5 h-5"></i>
                            </div>
                            <!-- Selection Dropdown -->
                            <div class="flex-1 space-y-1">
                                <select 
                                    @change="toggleShareStatus()" 
                                    class="w-full text-xs font-semibold bg-white border border-slate-300 rounded-xl px-3 py-2.5 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition cursor-pointer"
                                >
                                    <option value="restricted" :selected="!isShared">🔒 Dibatasi (Hanya pihak internal yang login)</option>
                                    <option value="public" :selected="isShared">🌐 Siapa saja yang memiliki link (Publik)</option>
                                </select>
                                <p class="text-[10px] text-slate-400 leading-relaxed" x-text="isShared ? 'Siapa saja di internet yang memiliki tautan ini dapat melihat berkas tanpa login.' : 'Hanya orang yang memiliki hak akses login sistem yang dapat membuka berkas ini.'"></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Link Display Section (If Shared) -->
                    <div x-show="isShared" x-transition class="bg-indigo-50/50 border border-indigo-100 rounded-xl p-4 space-y-2">
                        <span class="text-[10px] font-bold text-indigo-600 uppercase tracking-wider block">Tautan Berbagi Publik</span>
                        <div class="flex gap-2">
                            <input 
                                type="text" 
                                readonly 
                                :value="shareUrl" 
                                class="flex-1 text-xs px-3 py-2 bg-white border border-slate-200 rounded-xl font-mono focus:outline-none text-slate-600 shadow-sm"
                            >
                            <button 
                                @click="navigator.clipboard.writeText(shareUrl); copyShareStatus = 'Tersalin!';" 
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl border border-indigo-100 transition shrink-0 shadow-sm"
                                x-text="copyShareStatus"
                            >
                                Salin Link
                            </button>
                        </div>
                    </div>
                    
                    <!-- Footer Actions -->
                    <div class="flex justify-end pt-2 border-t border-slate-100">
                        <button 
                            @click="openShareModal = false; window.location.reload();" 
                            class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition shadow-md shadow-slate-950/20"
                        >
                            Selesai
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- MODAL: Buat Folder Baru -->
    <div x-show="openNewFolder" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 animate-in fade-in zoom-in-95 duration-150" @click.away="openNewFolder = false">
            <h3 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                <i data-lucide="folder-plus" class="w-5 h-5 text-indigo-600"></i> Buat Folder Baru
            </h3>
            <form action="{{ route('folders.store') }}" method="POST">
                @csrf
                <input type="hidden" name="parent_id" value="{{ $currentFolder ? $currentFolder->id : '' }}">
                <div class="mb-5">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Nama Folder</label>
                    <input type="text" name="nama_folder" required placeholder="Contoh: Laporan Keuangan" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                </div>
                <div class="flex justify-end gap-2.5">
                    <button type="button" @click="openNewFolder = false" class="px-4 py-2.5 border border-slate-300 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">Batal</button>
                    <button type="submit" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold transition">Buat Folder</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Edit Folder (Rename) -->
    <div x-show="openEditFolder" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 animate-in fade-in zoom-in-95 duration-150" @click.away="openEditFolder = false">
            <h3 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                <i data-lucide="edit-3" class="w-5 h-5 text-indigo-600"></i> Ubah Nama Folder
            </h3>
            <form :action="`/folders/${editFolderId}`" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-5">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Nama Folder Baru</label>
                    <input type="text" name="nama_folder" x-model="editFolderName" required class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                </div>
                <div class="flex justify-end gap-2.5">
                    <button type="button" @click="openEditFolder = false" class="px-4 py-2.5 border border-slate-300 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">Batal</button>
                    <button type="submit" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold transition">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Pindahkan Berkas / Folder -->
    <div x-show="openMoveModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 animate-in fade-in zoom-in-95 duration-150" @click.away="openMoveModal = false">
            <h3 class="text-lg font-bold text-slate-900 mb-2 flex items-center gap-2">
                <i data-lucide="folder-symlink" class="w-5 h-5 text-indigo-600"></i> Pindahkan <span x-text="moveTargetType === 'folder' ? 'Folder' : 'Berkas'"></span>
            </h3>
            <p class="text-xs text-slate-500 mb-4 leading-normal">Pilih folder tujuan untuk memindahkan <strong x-text="moveTargetName"></strong>.</p>
            
            <form :action="moveTargetType === 'folder' ? `/folders/${moveTargetId}/move` : `/documents/${moveTargetId}/move`" method="POST">
                @csrf
                <div class="mb-5">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Folder Tujuan</label>
                    <select :name="moveTargetType === 'folder' ? 'parent_id' : 'folder_id'" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:border-indigo-500 transition">
                        <option value="">Gudang Utama (Root)</option>
                        @foreach($moveFolderOptions as $opt)
                            <!-- Jangan tampilkan opsi jika target adalah folder itu sendiri -->
                            <template x-if="moveTargetType !== 'folder' || moveTargetId != '{{ $opt['id'] }}'">
                                <option value="{{ $opt['id'] }}">{{ $opt['nama_folder'] }}</option>
                            </template>
                        @endforeach
                    </select>
                </div>
                <div class="flex justify-end gap-2.5">
                    <button type="button" @click="openMoveModal = false" class="px-4 py-2.5 border border-slate-300 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">Batal</button>
                    <button type="submit" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold transition">Pindahkan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Unggah Dokumen Baru -->
    <div x-show="openUpload" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 animate-in fade-in zoom-in-95 duration-150" @click.away="openUpload = false">
            <h3 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                <i data-lucide="upload-cloud" class="w-5 h-5 text-indigo-600"></i> Unggah Berkas Eviden
            </h3>
            
            <form action="{{ $currentFolder ? route('folders.store-document', $currentFolder->id) : route('folders.store-document-root') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="space-y-4 mb-6">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Judul Dokumen</label>
                        <input type="text" name="judul_dokumen" required placeholder="Contoh: SK Pelayanan Publik" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Tahun Mulai</label>
                            <input type="number" name="tahun_mulai" placeholder="2026" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:border-indigo-500 transition">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Tahun Selesai</label>
                            <input type="number" name="tahun_selesai" placeholder="2026" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:border-indigo-500 transition">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Pilih Berkas</label>
                        <input type="file" name="file" required class="w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition cursor-pointer">
                    </div>
                </div>
                <div class="flex justify-end gap-2.5">
                    <button type="button" @click="openUpload = false" class="px-4 py-2.5 border border-slate-300 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">Batal</button>
                    <button type="submit" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold transition">Unggah Berkas</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Edit Dokumen (Rename / Update Metadata) -->
    <div x-show="openEditDoc" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 animate-in fade-in zoom-in-95 duration-150" @click.away="openEditDoc = false">
            <h3 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                <i data-lucide="edit-3" class="w-5 h-5 text-indigo-600"></i> Edit Berkas Eviden
            </h3>
            <form :action="selectedDoc ? `/documents/${selectedDoc.id}` : '#'" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="space-y-4 mb-6">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Judul Dokumen Baru</label>
                        <input type="text" name="judul_dokumen" :value="selectedDoc ? selectedDoc.judul_dokumen : ''" required class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Tahun Mulai</label>
                            <input type="number" name="tahun_mulai" :value="selectedDoc && selectedDoc.tahun_mulai !== 'Tanpa Tahun' ? selectedDoc.tahun_mulai : ''" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:border-indigo-500 transition">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Tahun Selesai</label>
                            <input type="number" name="tahun_selesai" :value="selectedDoc && selectedDoc.tahun_selesai ? selectedDoc.tahun_selesai : ''" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:border-indigo-500 transition">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Ganti Berkas (Opsional)</label>
                        <input type="file" name="file" class="w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition cursor-pointer">
                        <p class="text-[10px] text-slate-400 mt-1">Kosongkan jika tidak ingin mengganti file fisik.</p>
                    </div>
                </div>
                <div class="flex justify-end gap-2.5">
                    <button type="button" @click="openEditDoc = false" class="px-4 py-2.5 border border-slate-300 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">Batal</button>
                    <button type="submit" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold transition">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Kaitkan Berkas ke Indikator Penilaian -->
    <div x-show="openLinkIndicator" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-slate-200 animate-in fade-in zoom-in-95 duration-150" @click.away="openLinkIndicator = false">
            <h3 class="text-lg font-bold text-slate-900 mb-2 flex items-center gap-2">
                <i data-lucide="link" class="w-5 h-5 text-indigo-600"></i> Kaitkan Dokumen ke Penilaian
            </h3>
            <p class="text-xs text-slate-500 mb-4 leading-normal">Pilih indikator evaluasi untuk dikaitkan dengan berkas ini.</p>

            <form :action="selectedDoc ? `/documents/${selectedDoc.id}/link-indicator` : '#'" method="POST">
                @csrf
                <div class="mb-6 max-h-64 overflow-y-auto border border-slate-200 rounded-xl p-2 divide-y divide-slate-100">
                    @if($evaluations->isEmpty())
                        <p class="text-xs text-slate-400 italic p-4 text-center">Belum ada evaluasi atau indikator penilaian di sistem.</p>
                    @else
                        @foreach($evaluations as $eval)
                            <div class="p-3">
                                <h4 class="text-xs font-bold text-slate-900 flex items-center gap-1.5 mb-2">
                                    <i data-lucide="award" class="w-3.5 h-3.5 text-indigo-500"></i> {{ $eval->nama_evaluasi }} ({{ $eval->tahun }})
                                </h4>
                                <div class="space-y-1.5 pl-5">
                                    @foreach($eval->indicators as $indicator)
                                        <label class="flex items-start gap-2.5 p-2 hover:bg-slate-50 rounded-lg cursor-pointer transition text-xs">
                                            <input type="checkbox" name="indicator_ids[]" value="{{ $indicator->id }}" x-model="linkedIndicatorIds" class="mt-0.5 text-indigo-600 rounded focus:ring-indigo-500">
                                            <div>
                                                <p class="font-semibold text-slate-800">{{ $indicator->nama_indikator }}</p>
                                                <p class="text-[10px] text-slate-400 leading-snug mt-0.5">{{ $indicator->deskripsi }}</p>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                <div class="flex justify-end gap-2.5">
                    <button type="button" @click="openLinkIndicator = false" class="px-4 py-2.5 border border-slate-300 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">Batal</button>
                    <button type="submit" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold transition" :disabled="!selectedDoc">Simpan Tautan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Kaitkan Folder ke Indikator Penilaian -->
    <div x-show="openLinkFolderIndicator" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-slate-200 animate-in fade-in zoom-in-95 duration-150" @click.away="openLinkFolderIndicator = false">
            <h3 class="text-lg font-bold text-slate-900 mb-2 flex items-center gap-2">
                <i data-lucide="link" class="w-5 h-5 text-indigo-600"></i> Kaitkan Folder ke Penilaian
            </h3>
            <p class="text-xs text-slate-500 mb-4 leading-normal">Pilih indikator evaluasi untuk dikaitkan dengan folder <strong x-text="selectedFolder ? selectedFolder.nama_folder : ''"></strong>.</p>

            <form :action="selectedFolder ? `/folders/${selectedFolder.id}/link-indicator` : '#'" method="POST">
                @csrf
                <div class="mb-6 max-h-64 overflow-y-auto border border-slate-200 rounded-xl p-2 divide-y divide-slate-100">
                    @if($evaluations->isEmpty())
                        <p class="text-xs text-slate-400 italic p-4 text-center">Belum ada evaluasi atau indikator penilaian di sistem.</p>
                    @else
                        @foreach($evaluations as $eval)
                            <div class="p-3">
                                <h4 class="text-xs font-bold text-slate-900 flex items-center gap-1.5 mb-2">
                                    <i data-lucide="award" class="w-3.5 h-3.5 text-indigo-500"></i> {{ $eval->nama_evaluasi }} ({{ $eval->tahun }})
                                </h4>
                                <div class="space-y-1.5 pl-5">
                                    @foreach($eval->indicators as $indicator)
                                        <label class="flex items-start gap-2.5 p-2 hover:bg-slate-50 rounded-lg cursor-pointer transition text-xs">
                                            <input type="radio" name="indicator_id" value="{{ $indicator->id }}" required class="mt-0.5 text-indigo-600 focus:ring-indigo-500">
                                            <div>
                                                <p class="font-semibold text-slate-800">{{ $indicator->nama_indikator }}</p>
                                                <p class="text-[10px] text-slate-400 leading-snug mt-0.5">{{ $indicator->deskripsi }}</p>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                <div class="flex justify-end gap-2.5">
                    <button type="button" @click="openLinkFolderIndicator = false" class="px-4 py-2.5 border border-slate-300 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">Batal</button>
                    <button type="submit" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold transition" :disabled="!selectedFolder">Simpan Tautan</button>
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
        <div class="text-center text-slate-600 text-[10px] tracking-wider uppercase font-semibold">
            EVIDEN Digital Secure Viewer
        </div>
    </div>

    <!-- Hidden Folder Input for Direct Upload -->
    <input type="file" id="folderInputDirect" webkitdirectory directory multiple class="hidden" @change="uploadFiles($event.target.files)">
</div>
@endsection
