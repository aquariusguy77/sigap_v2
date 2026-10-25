{{--
    Mengakhiri sesi bila aplikasi dibuka lagi setelah tabnya ditutup.

    Server tidak pernah tahu kapan sebuah tab ditutup, dan kuki sesi tetap
    tersimpan selama peramban masih berjalan (di HP, peramban hampir tidak
    pernah benar-benar ditutup). Karena itu pemeriksaannya dilakukan di sini:

    - sessionStorage hanya hidup selama tab itu terbuka. Penandanya dipasang
      saat petugas masuk lewat halaman login, dan ikut bertahan saat halaman
      dimuat ulang atau berpindah menu.
    - Tab baru tidak punya penanda itu. Bila masih ada tab SIGAP lain yang
      terbuka, tab baru ikut dianggap sah. Bila tidak ada, berarti aplikasi
      dibuka lagi setelah ditutup, dan sesinya diakhiri.

    Halaman disembunyikan selama pemeriksaan supaya isinya tidak sempat
    terlihat sebelum dialihkan ke halaman login.
--}}
<script>
    (() => {
        const PENANDA = 'sigap.tab-aktif';
        const DETAK = 'sigap.detak';
        const BATAS_DETAK = 8000;

        let simpan;
        let baca;
        try {
            simpan = window.sessionStorage;
            baca = window.localStorage;
            simpan.getItem(PENANDA);
        } catch (e) {
            return; // Penyimpanan diblokir peramban; biarkan sesi berjalan seperti biasa.
        }

        const detak = () => { try { baca.setItem(DETAK, String(Date.now())); } catch (e) {} };
        const kanal = 'BroadcastChannel' in window ? new BroadcastChannel('sigap-sesi') : null;

        const mulaiDetak = () => {
            simpan.setItem(PENANDA, '1');
            detak();
            setInterval(detak, 2000);
            document.addEventListener('visibilitychange', detak);
            // Menjawab tab baru yang bertanya apakah masih ada tab SIGAP terbuka.
            kanal?.addEventListener('message', (e) => { if (e.data === 'ada?') kanal.postMessage('ada'); });
        };

        if (simpan.getItem(PENANDA)) {
            mulaiDetak();
            return;
        }

        document.documentElement.style.visibility = 'hidden';

        const izinkan = () => {
            document.documentElement.style.visibility = '';
            mulaiDetak();
        };

        const keluar = () => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = @json(route('logout'));
            form.style.display = 'none';
            [['_token', @json(csrf_token())], ['alasan', 'tab-ditutup']].forEach(([name, value]) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                form.appendChild(input);
            });
            (document.body || document.documentElement).appendChild(form);
            form.submit();
        };

        const detakTerakhir = Number(baca.getItem(DETAK) || 0);
        if (Date.now() - detakTerakhir < BATAS_DETAK) {
            izinkan();
            return;
        }

        // Tab di latar belakang bisa jarang berdetak; tanyakan langsung sebelum memutuskan.
        if (!kanal) {
            keluar();
            return;
        }

        let dijawab = false;
        kanal.addEventListener('message', (e) => {
            if (e.data === 'ada' && !dijawab) {
                dijawab = true;
                izinkan();
            }
        });
        kanal.postMessage('ada?');
        setTimeout(() => { if (!dijawab) keluar(); }, 500);
    })();
</script>
