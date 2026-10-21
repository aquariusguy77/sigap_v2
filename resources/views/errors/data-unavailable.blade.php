{{--
    Ditampilkan ketika Firebase tidak dapat dibaca.

    Halaman ini sengaja berdiri sendiri: tidak memakai layout, sesi, atau
    data pengguna, karena bisa jadi justru sesi itulah yang gagal dibaca.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Data gagal dimuat • SIGAP Rudenim Surabaya</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
               background: #eef4f6; font-family: "Segoe UI", Roboto, Arial, sans-serif; color: #12303a; padding: 16px; box-sizing: border-box; }
        .kartu { max-width: 520px; width: 100%; background: #fff; border-radius: 16px; padding: 32px 28px;
                 box-shadow: 0 10px 30px rgba(13, 92, 109, .12); border-top: 5px solid #b42318; }
        h1 { font-size: 1.35rem; margin: 0 0 12px; color: #b42318; }
        p { line-height: 1.6; margin: 0 0 12px; }
        .kode { font-size: .85rem; color: #5d7884; }
        a.tombol { display: inline-block; margin-top: 8px; padding: 10px 18px; border-radius: 10px;
                   background: #0d5c6d; color: #fff; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <main class="kartu" role="alert">
        <h1>Data gagal dimuat</h1>
        <p>SIGAP tidak dapat terhubung ke basis data saat ini, sehingga data tidak dapat ditampilkan
           atau disimpan. Data yang sudah tersimpan sebelumnya tidak hilang.</p>
        <p>Periksa koneksi internet, lalu muat ulang halaman ini beberapa saat lagi. Bila masalah berlanjut,
           hubungi Admin SIGAP.</p>
        <a class="tombol" href="{{ url()->current() }}">Muat ulang</a>
        <p class="kode" style="margin-top:18px;">Kode galat: 503 (layanan data tidak tersedia)</p>
    </main>
</body>
</html>
