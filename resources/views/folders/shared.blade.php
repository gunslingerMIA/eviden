<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EVIDEN - Berbagi Dokumen Publik</title>
    
    <!-- Muat Tailwind CSS dari Vite -->
    @vite(['resources/css/app.css'])

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex flex-col justify-between">

    <!-- Header Publik -->
    <header class="h-16 bg-slate-950 border-b border-slate-800 flex items-center justify-between px-6 shadow-md">
        <div class="flex items-center gap-2">
            <span class="bg-indigo-600 px-2.5 py-1.5 rounded-lg text-white font-bold text-sm">EV</span>
            <span class="text-sm font-semibold tracking-wide text-slate-200">EVIDEN - Tautan Publik</span>
        </div>
        <div>
            <a href="{{ asset('storage/' . $document->file_path) }}" download class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold transition flex items-center gap-1.5 shadow-md shadow-indigo-900/40">
                <i data-lucide="download" class="w-4 h-4"></i> Unduh Berkas
            </a>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col items-center justify-center p-6 overflow-hidden">
        
        <!-- Info Dokumen -->
        <div class="w-full max-w-4xl text-center mb-6">
            <h1 class="text-xl font-bold text-white leading-normal">{{ $document->judul_dokumen }}</h1>
            <p class="text-xs text-slate-400 mt-1.5">
                Diunggah oleh: <strong class="text-slate-300">{{ $document->uploader->name ?? 'System' }}</strong> &bull; 
                Format: <span class="uppercase font-mono font-bold text-white">{{ $document->ekstensi }}</span> &bull; 
                Ukuran: <span>{{ number_format($document->ukuran_file / 1024, 0) }} KB</span>
            </p>
        </div>

        <!-- Peninjau File (Preview Area) -->
        <div class="w-full max-w-4xl flex-1 bg-slate-950/60 rounded-2xl border border-slate-800/80 overflow-hidden flex items-center justify-center shadow-2xl">
            @if(strtolower($document->ekstensi) === 'pdf')
                <iframe src="{{ asset('storage/' . $document->file_path) }}" class="w-full h-full bg-white" frameborder="0"></iframe>
            
            @elseif(in_array(strtolower($document->ekstensi), ['png', 'jpg', 'jpeg', 'svg', 'webp']))
                <img src="{{ asset('storage/' . $document->file_path) }}" class="max-w-full max-h-full object-contain p-4" alt="Shared Document">
            
            @else
                <!-- Fallback format file office / zip -->
                <div class="text-center p-8 space-y-4 max-w-sm">
                    <div class="w-16 h-16 bg-slate-800 text-slate-400 rounded-full flex items-center justify-center mx-auto">
                        <i data-lucide="folder-archive" class="w-8 h-8"></i>
                    </div>
                    <h3 class="font-bold text-white text-md">Pratinjau Tidak Tersedia</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Dokumen dengan format <strong class="uppercase text-white font-mono">{{ $document->ekstensi }}</strong> tidak mendukung pratinjau langsung di web browser.<br>Silakan klik tombol unduh untuk melihat berkas secara lokal.
                    </p>
                    <a href="{{ asset('storage/' . $document->file_path) }}" download class="inline-flex items-center gap-2 px-5 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold transition shadow-md shadow-indigo-900/40">
                        <i data-lucide="download" class="w-4 h-4"></i> Unduh Berkas
                    </a>
                </div>
            @endif
        </div>
    </main>

    <!-- Footer Publik -->
    <footer class="h-12 bg-slate-950 flex items-center justify-center text-[10px] text-slate-500 border-t border-slate-900/40 tracking-wider">
        EVIDEN Digital &copy; 2026. Diarsip Secara Aman.
    </footer>

    <!-- Initialize Lucide Icons -->
    <script>
        lucide.createIcons();
    </script>
</body>
</html>

