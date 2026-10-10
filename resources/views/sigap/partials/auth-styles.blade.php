{{--
    Gaya halaman masuk, dipisah dari gaya utama karena halaman ini tidak
    memakai kerangka aplikasi: tanpa sidebar, tanpa topbar, memenuhi layar.

    Seperti gaya utama, isinya kini berupa berkas statis agar tidak ikut
    terkirim ulang pada setiap permintaan.
--}}
<link rel="stylesheet" href="/css/auth.css?v={{ config('branding.asset_version') }}">
