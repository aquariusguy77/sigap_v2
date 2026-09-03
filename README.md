# SIGAP Rudenim Surabaya

**Sistem Informasi & Gerakan Administratif Pengungsi** — aplikasi pendataan
pengungsi luar negeri untuk wilayah kerja Rumah Detensi Imigrasi Surabaya.

> 📘 **Baru pertama kali memasang? Buka [PANDUAN.md](PANDUAN.md).**
> Berisi langkah lengkap dari menyiapkan Firebase sampai berhasil tayang di Vercel.

---

## Arsitektur

Seluruh data tersimpan di **Firebase**. Aplikasi tidak memakai MySQL maupun SQLite.

| Bagian | Tempat penyimpanan |
|---|---|
| Data pengungsi, penempatan, dokumen, audit, laporan | Firebase Realtime Database |
| Akun pengguna dan kata sandi | Firebase Realtime Database (node `users`) |
| Berkas dokumen | Firebase Realtime Database, atau Firebase Storage |
| Sesi login | Cookie terenkripsi |

Karena tidak ada database relasional, aplikasi dapat berjalan di lingkungan
serverless seperti Vercel tanpa perlu menyewa server basis data.

## Modul

| Modul | Keterangan |
|---|---|
| Dashboard | Statistik dari data nyata, sebaran per lokasi, aktivitas terbaru |
| Data Pengungsi | Tambah, lihat, ubah, hapus. Form bertahap empat langkah |
| Penempatan | Dua kategori: fasilitas IOM dan mandiri. Lihat catatan di bawah |
| Dokumen | Unggah berkas beserta tautan unduhnya. Lihat catatan penyimpanan di bawah |
| Pencarian & Filter | Berdasarkan kebangsaan, status, lokasi, kelengkapan dokumen |
| Riwayat Perubahan | Setiap perubahan tercatat: pelaku, waktu, nilai lama dan baru |
| Laporan | Lima jenis laporan, dapat diunduh sebagai **PDF** dan **CSV** |
| Pengaturan | Hak akses per peran dan daftar akun |

## Data acuan operasional

Seluruhnya diatur di `config/sigap.php` bagian `reference`, sehingga dapat
disesuaikan tanpa menyentuh kode.

**Dokumen yang wajib dikumpulkan** hanya dua: Kartu Pengungsi dan Kartu Wajib
Lapor.

**Status data pengungsi** hanya dua: Aktif dan Perlu Verifikasi.

**Lokasi hunian pada data pengungsi** ada tiga pilihan: CH Puspa Agro, CH Green
Bamboo, dan Pengungsi Mandiri. Alamat rinci bagi pengungsi mandiri dicatat lewat
menu Penempatan, bukan di formulir data pengungsi.

**Penempatan** terbagi dua kategori:

| Kategori | Lokasi | Yang dicatat |
|---|---|---|
| Fasilitas IOM | Community House | Dipilih dari daftar: CH Puspa Agro atau CH Green Bamboo |
| Mandiri | Mencari sendiri | Alamat lengkap, ditambah lintang dan bujur bila tersedia |

Karena pengungsi mandiri tinggal menyebar, halaman detail penempatannya
menyediakan tombol **Petunjuk Arah** dan **Lihat di Peta** yang membuka Google
Maps. Tombol arah juga muncul di daftar penempatan agar petugas dapat langsung
berangkat dari ponsel. Titik tujuan diambil dari koordinat bila ada; bila belum
diisi, Google Maps mencarinya dari alamat. Pengungsi berfasilitas IOM tidak
menampilkan tombol ini karena lokasinya terpusat di dua Community House.

## Penyimpanan berkas dokumen

Ditentukan oleh `FIREBASE_STORAGE_DISK`.

| Nilai | Berkas disimpan di | Catatan |
|---|---|---|
| `rtdb` | Realtime Database, sebagai base64 di node `document_files` | Tidak perlu paket berbayar. **Pilihan untuk Vercel.** |
| `firebase-rest` | Firebase Storage | Paling rapi, tetapi Firebase mensyaratkan paket Blaze |
| `local` | Folder `storage/` di komputer | Pengembangan saja; gagal di hosting yang baca-saja |

Pada mode `rtdb`, isi berkas sengaja ditaruh di node tersendiri dan bukan
menempel pada dokumen, karena daftar dokumen membaca seluruh node sekaligus.
Berkas hanya dapat diambil lewat rute `/dokumen/berkas/{kunci}` yang mewajibkan
pengguna sudah masuk — tidak ada URL publik ke berkas mana pun. Ukuran maksimal
per berkas mengikuti `FIREBASE_STORAGE_RTDB_MAX_KB`, bawaannya 5 MB.

## Hak akses

| Peran | Kewenangan |
|---|---|
| Admin | Seluruh fungsi, termasuk hapus data dan pengaturan |
| Petugas Pendataan | Kelola pengungsi, penempatan, dan dokumen |
| Supervisor | Meninjau perubahan, memverifikasi dokumen, mengunduh laporan |

## Menjalankan cepat

```bash
composer install
cp .env.example .env
php artisan key:generate
# isi FIREBASE_DATABASE_URL dan FIREBASE_SERVICE_ACCOUNT_JSON di .env
php artisan sigap:seed
php artisan serve
```

Langkah rincinya ada di [PANDUAN.md](PANDUAN.md).

## Struktur folder

```
app/
├── Auth/          Penyedia autentikasi berbasis Firebase
├── Console/       Perintah pengisian data awal
├── Exceptions/    Galat unggah berkas dan tulis Firebase
├── Firebase/      Record dan repository, pengganti model Eloquent
├── Http/          Controller, middleware, validasi
├── Providers/     Pendaftaran layanan
└── Services/      Token Google, Realtime Database, Storage, ekspor laporan
config/            Termasuk config/sigap.php dan config/branding.php
resources/views/   Tampilan Blade dan CSS
routes/web.php     Seluruh rute aplikasi
api/index.php      Titik masuk khusus Vercel
```

## Keamanan

- Kata sandi disimpan sebagai hash bcrypt di Firebase.
- Otorisasi Firebase memakai service account, ditukar menjadi access token
  berumur pendek secara otomatis.
- Aturan keamanan Firebase dapat ditutup rapat karena aplikasi mengakses lewat
  service account.
- Setiap form dilindungi token CSRF; hak akses diperiksa di rute dan di controller.

⚠️ **Jangan memasukkan data pengungsi asli** ke deployment yang dapat diakses
publik. Gunakan data sintetis.

## Lisensi

MIT — bebas dipakai dan dimodifikasi untuk keperluan internal Rumah Detensi Imigrasi.
