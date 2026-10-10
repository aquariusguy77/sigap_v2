<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

{{--
    Gaya utama dipindahkan ke berkas statis public/css/sigap.css.

    Sebelumnya seluruh 29 KB CSS ini ditulis sebaris di dalam <style> pada
    setiap halaman, sehingga ikut terkirim ulang pada setiap permintaan dan
    tidak pernah bisa disimpan peramban. Sebagai berkas tersendiri, isinya
    cukup diunduh sekali lalu dipakai ulang dari singgahan peramban.

    Vercel menyajikannya lewat aturan rute /(css|js|images|fonts|build)/(.*)
    yang sudah ada di vercel.json.

    Tanda ?v= memaksa peramban mengunduh ulang begitu berkasnya berubah,
    sehingga perubahan tampilan tidak tertahan oleh singgahan yang lama.
--}}
<link rel="stylesheet" href="/css/sigap.css?v={{ config('branding.asset_version') }}">
