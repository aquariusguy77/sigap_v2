<aside class="sidebar" id="sidebar">
    <div class="brand">
        <div class="brand-mark">
            <img src="{{ config('branding.logo') }}" alt="{{ config('branding.logo_alt') }}" width="128" height="142">
        </div>
        <div class="brand-text">
            <h1>SIGAP</h1>
            <p>Rudenim Surabaya</p>
        </div>
        <button class="sidebar-collapse" id="sidebarCollapse" type="button"
                aria-label="Sembunyikan menu" title="Sembunyikan menu">
            <x-icon name="chevron-left" />
        </button>
    </div>

    @if ($isSignedIn)
        <span class="nav-label">Menu</span>
        <nav class="menu" aria-label="Menu utama SIGAP">
            @foreach ($menuItems as $item)
                <a class="menu-link {{ request()->routeIs(...($item['active'] ?? [$item['route']])) ? 'active' : '' }}"
                   href="{{ route($item['route']) }}"
                   data-label="{{ $item['label'] }}">
                    <x-icon :name="$item['icon']" class="menu-icon" />
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    @else
        <span class="nav-label">Akses</span>
        <nav class="menu" aria-label="Akses masuk SIGAP">
            <a class="menu-link {{ request()->routeIs('login') ? 'active' : '' }}"
               href="{{ route('login') }}" data-label="Masuk">
                <x-icon name="shield" class="menu-icon" />
                <span>Masuk</span>
            </a>
        </nav>
    @endif

    <div class="sidebar-footer">
        @if ($isSignedIn)
            <div class="status-card">
                <strong>Peran aktif</strong>
                <p class="status-note"><b>{{ $currentRole['label'] }}</b></p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="logout-button" type="submit" title="Keluar">
                    <x-icon name="logout" class="menu-icon" />
                    <span>Keluar</span>
                </button>
            </form>
        @else
            <a class="logout-button" href="{{ route('login') }}" title="Masuk"
               style="color:#cfeaef;background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.12);">
                <x-icon name="shield" class="menu-icon" />
                <span>Masuk</span>
            </a>
        @endif
    </div>
</aside>
