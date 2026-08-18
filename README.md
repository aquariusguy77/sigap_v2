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
| Berkas dokumen | Firebase Storage |
| Sesi login | Cookie terenkripsi |

Karena tidak ada database relasional, aplikasi dapat berjalan di lingkungan
serverless seperti Vercel tanpa perlu menyewa server basis data.

## Modul

| Modul | Keterangan |
|---|---|
| Dashboard | Statistik dari data nyata, sebaran per lokasi, aktivitas terbaru |
| Data Pengungsi | Tambah, lihat, ubah, hapus. Form bertahap empat langkah |
| Penempatan | Lokasi hunian aktif dan riwayat mutasi |
| Dokumen | Unggah berkas ke Firebase Storage beserta tautan unduhnya |
| Pencarian & Filter | Berdasarkan kebangsaan, status, lokasi, kelengkapan dokumen |
| Riwayat Perubahan | Setiap perubahan tercatat: pelaku, waktu, nilai lama dan baru |
| Laporan | Lima jenis laporan, dapat diunduh sebagai **PDF** dan **CSV** |
| Pengaturan | Hak akses per peran dan daftar akun |

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
