@php
    $initials = collect(explode(' ', trim($currentUser['name'])))
        ->filter()
        ->take(2)
        ->map(fn (string $part) => strtoupper(substr($part, 0, 1)))
        ->implode('');
@endphp

<header class="topbar">
    <div class="topbar-left">
        <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Buka menu">
            <x-icon name="menu" class="menu-icon" />
        </button>
        <div class="page-title">
            <h2>{{ $pageHeading }}</h2>
            <p>{{ $pageDescription }}</p>
        </div>
    </div>

    <div class="topbar-right">
        @if ($isSignedIn)
            <form class="toolbar-search" method="GET" action="{{ route('refugees.index') }}" role="search">
                <x-icon name="search" class="chip-icon" />
                <input type="search" name="keyword" value="{{ request('keyword') }}"
                       placeholder="Cari pengungsi..." aria-label="Cari pengungsi">
            </form>
        @endif

        {{--
            Kartu identitas, sekaligus jalan menuju halaman ganti kata sandi
            bagi peran yang berwenang mengurus akun.

            Bagi peran lain kartu ini tetap berupa teks biasa — menautkannya
            hanya akan mengantar mereka ke penolakan 403.
        --}}
        @if ($isSignedIn && $canManageSettings)
            <a class="user-chip" href="{{ route('account.password.edit') }}" title="Kelola kata sandi akun">
                <div class="avatar">{{ $initials !== '' ? $initials : 'TM' }}</div>
                <div class="user-chip-text">
                    <strong>{{ $currentUser['name'] }}</strong>
                    <small>{{ $currentRole['label'] }}</small>
                </div>
            </a>
        @else
            <div class="user-chip">
                <div class="avatar">{{ $initials !== '' ? $initials : 'TM' }}</div>
                <div class="user-chip-text">
                    <strong>{{ $currentUser['name'] }}</strong>
                    <small>{{ $isSignedIn ? $currentRole['label'] : 'Belum masuk' }}</small>
                </div>
            </div>
        @endif
    </div>
</header>
