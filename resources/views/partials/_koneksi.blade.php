{{--
    Peringatan koneksi internet terputus.

    SIGAP dirender di server, jadi tanpa ini perangkat yang kehilangan
    internet hanya menampilkan halaman galat bawaan peramban, dan isian
    formulir yang belum terkirim ikut hilang. Skrip di bawah memeriksa
    navigator.onLine sebelum formulir dikirim atau tautan dibuka:
    bila luring, pengiriman dibatalkan, isian tetap di halaman, dan
    pengguna diberi tahu apa yang terjadi.

    Ini hanya menangkap perangkat yang tahu dirinya luring. Gangguan di sisi
    server (Firebase tidak terjangkau) ditangani terpisah lewat halaman
    "Data gagal dimuat".
--}}
<div id="bannerKoneksi" class="flash alert-error" role="alert" aria-live="assertive"
     style="display:none;position:fixed;top:12px;left:50%;transform:translateX(-50%);z-index:2000;width:calc(100% - 32px);max-width:640px;box-shadow:0 10px 28px rgba(0,0,0,.18);">
    <div>
        <strong data-judul>Koneksi internet terputus</strong>
        <p data-pesan style="margin:4px 0 0;"></p>
    </div>
</div>
<script>
    (() => {
        const banner = document.getElementById('bannerKoneksi');

        if (!banner) {
            return;
        }

        const judul = banner.querySelector('[data-judul]');
        const pesan = banner.querySelector('[data-pesan]');
        let penutup = null;

        const tampil = (teksJudul, teksPesan, galat = true) => {
            clearTimeout(penutup);
            judul.textContent = teksJudul;
            pesan.textContent = teksPesan;
            banner.classList.toggle('alert-error', galat);
            // Latar dibuat pekat karena banner melayang di atas isi halaman.
            banner.style.background = galat ? '#fdeded' : '#e9f6ef';
            banner.style.color = galat ? '#a33333' : '#1f6b45';
            banner.style.borderColor = galat ? 'rgba(214,69,69,.35)' : 'rgba(31,107,69,.3)';
            banner.style.display = 'flex';
        };

        const PESAN_LURING = 'Data tidak dapat dimuat atau disimpan sampai koneksi kembali tersambung.';

        window.addEventListener('offline', () => {
            tampil('Koneksi internet terputus', PESAN_LURING);
        });

        window.addEventListener('online', () => {
            tampil('Koneksi kembali tersambung', 'Anda dapat melanjutkan pekerjaan.', false);
            penutup = setTimeout(() => { banner.style.display = 'none'; }, 4000);
        });

        // Formulir: batalkan pengiriman agar isian tidak hilang.
        document.addEventListener('submit', (event) => {
            if (navigator.onLine) {
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();
            tampil(
                'Data belum tersimpan',
                'Koneksi internet terputus, sehingga data belum dikirim. Isian Anda masih ada di halaman ini; periksa koneksi, lalu kirim kembali.'
            );
        }, true);

        // Tautan di dalam aplikasi: jangan berpindah ke halaman galat peramban.
        document.addEventListener('click', (event) => {
            if (navigator.onLine || event.defaultPrevented || event.button !== 0
                || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                return;
            }

            const tautan = event.target.closest('a[href]');

            if (!tautan || tautan.hasAttribute('download') || (tautan.target && tautan.target !== '_self')) {
                return;
            }

            const tujuan = new URL(tautan.href, window.location.href);

            if (tujuan.origin !== window.location.origin
                || (tujuan.pathname === window.location.pathname && tujuan.search === window.location.search)) {
                return;
            }

            event.preventDefault();
            tampil(
                'Data gagal dimuat',
                'Koneksi internet terputus, sehingga halaman yang dituju tidak dapat dimuat. Periksa koneksi, lalu coba lagi.'
            );
        }, true);

        if (!navigator.onLine) {
            tampil('Koneksi internet terputus', PESAN_LURING);
        }
    })();
</script>
