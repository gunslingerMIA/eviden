<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EVIDEN - Manajemen Bukti Fisik</title>
    
    <!-- Muat Tailwind CSS dari Vite -->
    @vite(['resources/css/app.css'])

    <!-- Alpine.js (Untuk modul modal interaktif) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 h-screen overflow-hidden flex" x-data="{ sidebarOpen: false }">

    <!-- Mobile Sidebar Overlay Backdrop -->
    <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-40 xl:hidden" x-cloak x-transition.opacity></div>

    <!-- Sidebar Menu Kiri -->
    <aside 
        class="w-56 xl:w-60 bg-slate-900 text-slate-100 flex flex-col justify-between shrink-0 h-full overflow-y-auto z-50 transition-transform duration-300"
        :class="sidebarOpen ? 'fixed inset-y-0 left-0 translate-x-0' : 'fixed inset-y-0 left-0 -translate-x-full xl:relative xl:translate-x-0'"
    >
        <div>
            <!-- Header Brand -->
            <div class="p-4 xl:p-5 border-b border-slate-800 flex items-center gap-3">
                <div class="bg-indigo-600 p-1.5 xl:p-2 rounded-lg text-white font-bold text-sm xl:text-base">EV</div>
                <div class="flex-1 min-w-0">
                    <h1 class="font-bold text-base xl:text-lg leading-none">EVIDEN</h1>
                    <span class="text-[10px] xl:text-xs text-slate-400">Bukti Dukung Evaluasi</span>
                </div>
                <!-- Close button mobile -->
                <button @click="sidebarOpen = false" class="xl:hidden p-1 text-slate-400 hover:text-white transition">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            
            <!-- List Link Menu -->
            <nav class="p-3 xl:p-4 space-y-1">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 xl:px-4 py-2.5 xl:py-3 rounded-lg text-xs xl:text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <i data-lucide="layout-dashboard" class="w-4 h-4 shrink-0"></i> Dashboard
                </a>
                <a href="{{ route('folders.index') }}" class="flex items-center gap-3 px-3 xl:px-4 py-2.5 xl:py-3 rounded-lg text-xs xl:text-sm font-medium transition {{ request()->routeIs('folders.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <i data-lucide="database" class="w-4 h-4 shrink-0"></i> File Manajer
                </a>
                <a href="{{ route('evaluations.index') }}" class="flex items-center gap-3 px-3 xl:px-4 py-2.5 xl:py-3 rounded-lg text-xs xl:text-sm font-medium transition {{ request()->routeIs('evaluations.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <i data-lucide="check-square" class="w-4 h-4 shrink-0"></i> Indikator Evaluasi
                </a>
            </nav>
        </div>

        <!-- Bypass Account Status with User Switcher -->
        @php
            $allUsersForSwitch = \App\Models\User::all();
            $activeUserForSidebar = \App\Models\User::find(session('active_user_id', \App\Models\User::first()->id ?? null));
        @endphp
        <div class="p-3 xl:p-4 border-t border-slate-800 bg-slate-950/40 relative" x-data="{ openSwitcher: false }">
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2 min-w-0">
                    <div class="w-7 h-7 xl:w-8 xl:h-8 rounded-full bg-indigo-500/20 text-indigo-400 flex items-center justify-center font-bold text-[10px] xl:text-xs shrink-0" 
                         x-text="'{{ $activeUserForSidebar ? substr($activeUserForSidebar->name, 0, 1) : 'U' }}'">
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] xl:text-xs font-semibold text-slate-300 truncate">{{ $activeUserForSidebar ? $activeUserForSidebar->name : 'No User' }}</p>
                        <span class="text-[8px] xl:text-[9px] bg-amber-500/20 text-amber-400 px-1 py-0.5 rounded font-mono font-bold">PIC ACTIVE</span>
                    </div>
                </div>
                <!-- Trigger Switch Dropdown -->
                <button @click="openSwitcher = !openSwitcher" class="text-slate-400 hover:text-white transition shrink-0 p-1">
                    <i data-lucide="chevrons-up-down" class="w-3.5 h-3.5"></i>
                </button>
            </div>

            <!-- Switcher Dropdown (Slide-up menu) -->
            <div x-show="openSwitcher" @click.away="openSwitcher = false" 
                 class="absolute bottom-full left-3 right-3 mb-2 bg-slate-800 border border-slate-700 rounded-lg p-1.5 space-y-1 text-[10px] xl:text-xs shadow-xl z-50" 
                 x-cloak x-transition>
                <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block px-2 py-1 border-b border-slate-700/60 mb-1">Pilih Akun Simulasi</span>
                @foreach($allUsersForSwitch as $usr)
                    <form action="{{ route('switch-user', $usr->id) }}" method="POST" class="w-full">
                        @csrf
                        <button type="submit" 
                                class="w-full flex items-center justify-between px-2 py-1.5 rounded text-left transition"
                                :class="'{{ $usr->id }}' == '{{ $activeUserForSidebar->id ?? '' }}' ? 'bg-indigo-600 text-white font-bold' : 'text-slate-300 hover:bg-slate-700 hover:text-white'">
                            <span class="truncate pr-2">{{ $usr->name }}</span>
                            @if($activeUserForSidebar && $usr->id == $activeUserForSidebar->id)
                                <i data-lucide="check" class="w-3 h-3 text-white shrink-0"></i>
                            @endif
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    </aside>

    <!-- Halaman Utama Kanan -->
    <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">
        <header class="h-14 xl:h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 xl:px-6 shrink-0">
            <div class="flex items-center gap-3">
                <!-- Hamburger Menu Button (mobile/tablet) -->
                <button @click="sidebarOpen = !sidebarOpen" class="xl:hidden p-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>
                <div class="text-xs xl:text-sm text-slate-500 font-medium">Sistem Pengarsipan & Evaluasi Digital</div>
            </div>
            <span class="text-[10px] xl:text-xs font-semibold bg-emerald-100 text-emerald-800 px-2 xl:px-2.5 py-1 rounded-full">Koneksi Database OK</span>
        </header>

        <main class="flex-1 p-4 xl:p-6 overflow-y-auto">
            @yield('content')
        </main>
    </div>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Initialize Lucide Icons & SweetAlert Helpers -->
    <script>
        lucide.createIcons();

        // Prevent default drag/drop behavior globally to prevent the browser from opening dropped files
        window.addEventListener("dragover", function(e) {
            e.preventDefault();
        }, false);
        window.addEventListener("drop", function(e) {
            e.preventDefault();
        }, false);

        // Custom SweetAlert2 Toast
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        // Trigger session notifications
        @if(session('success'))
            Toast.fire({
                icon: 'success',
                title: '{{ session('success') }}'
            });
        @endif

        @if(session('error'))
            Toast.fire({
                icon: 'error',
                title: '{{ session('error') }}'
            });
        @endif

        // Global Confirmation Dialog
        function confirmDelete(e, title = 'Apakah Anda yakin?', text = 'Tindakan ini tidak dapat dibatalkan!') {
            e.preventDefault();
            const form = e.target.closest('form') || e.currentTarget || e.target;
            Swal.fire({
                title: title,
                text: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#4f46e5', // indigo-600
                cancelButtonColor: '#f43f5e', // rose-500
                confirmButtonText: 'Ya, Lanjutkan!',
                cancelButtonText: 'Batal',
                customClass: {
                    popup: 'rounded-2xl border border-slate-100 shadow-2xl p-6',
                    title: 'text-lg font-bold text-slate-800',
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    if (form) {
                        HTMLFormElement.prototype.submit.call(form);
                    }
                }
            });
        }
    </script>
</body>
</html>
