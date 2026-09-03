<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0d5c6d">
    <title>{{ ($title ?? ($pageHeading ?? 'SIGAP')) }} • SIGAP Rudenim Surabaya</title>
    <meta name="description" content="SIGAP — Sistem Informasi & Gerakan Administratif Pengungsi Rudenim Surabaya.">
    <link rel="icon" type="image/png" href="{{ config('branding.logo') }}">
    <link rel="apple-touch-icon" href="{{ config('branding.logo') }}">
    @include('sigap.partials.styles')
</head>
<body>
    <script>
        // Terapkan sebelum render supaya sidebar tidak "berkedip" saat halaman dimuat.
        try {
            if (localStorage.getItem('sigap.sidebar') === 'collapsed') {
                document.body.classList.add('sidebar-collapsed');
            }
        } catch (e) {}
    </script>

    <div class="overlay" id="overlay"></div>
    <div class="app-shell">
        @include('partials.sidebar')

        <main class="main-content">
            @include('partials.topbar')

            <div class="page-body">
                @if (session('status'))
                    <div class="flash" role="status">
                        <x-icon name="check" class="chip-icon" />
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                {{--
                    Kegagalan unggah berkas dilaporkan terpisah dari galat
                    validasi, karena data induknya sudah tersimpan dan yang
                    perlu diulang hanya berkasnya.
                --}}
                @if (! empty(session('uploadErrors')))
                    <div class="flash alert-error" role="alert">
                        <x-icon name="alert" class="chip-icon" />
                        <div>
                            <strong>Berkas berikut gagal diunggah</strong>
                            <ul>
                                @foreach (session('uploadErrors') as $uploadError)
                                    <li>{{ $uploadError }}</li>
                                @endforeach
                            </ul>
                            <p>Data lainnya sudah tersimpan. Ulangi unggahnya lewat menu Dokumen.</p>
                        </div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="flash alert-error" role="alert">
                        <x-icon name="alert" class="chip-icon" />
                        <div>
                            <strong>Periksa kembali isian berikut</strong>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                @yield('content')

                <footer class="footer">
                    <strong>SIGAP</strong> — Sistem Informasi &amp; Gerakan Administratif Pengungsi
                    &bull; Rumah Detensi Imigrasi Surabaya &bull; {{ now()->format('Y') }}
                </footer>
            </div>
        </main>
    </div>

    <script>
        (() => {
            const body = document.body;
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            const toggleBtn = document.getElementById('sidebarToggle');
            const collapseBtn = document.getElementById('sidebarCollapse');

            // --- Drawer untuk layar kecil ---
            const setDrawer = (open) => {
                sidebar?.classList.toggle('open', open);
                overlay?.classList.toggle('visible', open);
            };

            toggleBtn?.addEventListener('click', () => setDrawer(true));
            overlay?.addEventListener('click', () => setDrawer(false));

            document.querySelectorAll('.menu-link').forEach((link) => {
                link.addEventListener('click', () => {
                    if (window.innerWidth <= 980) setDrawer(false);
                });
            });

            // --- Lipat / buka sidebar di layar besar ---
            const syncCollapseLabel = () => {
                if (!collapseBtn) return;
                const collapsed = body.classList.contains('sidebar-collapsed');
                const label = collapsed ? 'Tampilkan menu' : 'Sembunyikan menu';
                collapseBtn.setAttribute('aria-label', label);
                collapseBtn.setAttribute('title', label);
            };

            collapseBtn?.addEventListener('click', () => {
                const collapsed = body.classList.toggle('sidebar-collapsed');
                try {
                    localStorage.setItem('sigap.sidebar', collapsed ? 'collapsed' : 'expanded');
                } catch (e) {}
                syncCollapseLabel();
            });

            syncCollapseLabel();

            // Esc menutup drawer di layar kecil
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') setDrawer(false);
            });
        })();
    </script>
</body>
</html>
