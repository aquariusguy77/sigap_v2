# Panduan SIGAP — Dari Nol Sampai Tayang di Vercel

Aplikasi pendataan pengungsi Rumah Detensi Imigrasi Surabaya.
Seluruh data dan berkas tersimpan di **Firebase**. Tidak ada MySQL, tidak ada SQLite.

Ikuti urutan ini dari atas ke bawah. Setiap tahap ada cara memeriksa hasilnya.

---

## Ringkasan yang akan Anda kerjakan

| Tahap | Isi | Perkiraan waktu |
|---|---|---|
| 1 | Menyiapkan Firebase | 10 menit |
| 2 | Menjalankan di komputer sendiri | 10 menit |
| 3 | Mengunggah ke repositori GitHub baru | 5 menit |
| 4 | Deploy ke Vercel | 10 menit |
| 5 | Pemeriksaan akhir | 5 menit |

Yang perlu disiapkan: akun Google, akun GitHub, akun Vercel, PHP 8.2+, Composer, dan Git.

---

# TAHAP 1 — Menyiapkan Firebase

## 1.1 Membuat proyek

1. Buka <https://console.firebase.google.com>
2. Klik **Add project**, beri nama misalnya `sigap-rudenim`
3. Google Analytics boleh dimatikan
4. Tunggu sampai proyek selesai dibuat

## 1.2 Menghidupkan Realtime Database

1. Menu kiri → **Build → Realtime Database** → **Create Database**
2. Lokasi: pilih **Singapore (asia-southeast1)**
3. Aturan keamanan: pilih **Start in locked mode**
4. Catat alamat database yang muncul di bagian atas, bentuknya seperti:

   ```
   https://sigap-rudenim-default-rtdb.asia-southeast1.firebasedatabase.app/
   ```

   Alamat ini akan dipakai sebagai `FIREBASE_DATABASE_URL`.

## 1.3 Mengatur aturan keamanan

Masuk ke tab **Rules**, ganti isinya menjadi:

```json
{
  "rules": {
    ".read": false,
    ".write": false
  }
}
```

Klik **Publish**.

Aturan ini menutup akses dari luar sepenuhnya. Aplikasi tetap bisa masuk karena
memakai *service account*, yang kewenangannya berada di atas aturan ini.
**Jangan** memakai `".read": true` — itu membuat seluruh data pengungsi dapat
dibaca siapa saja di internet.

## 1.4 Menghidupkan Storage

1. Menu kiri → **Build → Storage** → **Get started**
2. Pilih **Start in production mode**, lokasi sama seperti database
3. Catat nama bucket yang muncul, bentuknya seperti `sigap-rudenim.firebasestorage.app`

Lalu buka tab **Rules** pada Storage dan pastikan isinya:

```
rules_version = '2';
service firebase.storage {
  match /b/{bucket}/o {
    match /{allPaths=**} {
      allow read, write: if false;
    }
  }
}
```

Aplikasi tetap dapat mengunggah karena memakai service account.

## 1.5 Mengambil kunci service account

1. Klik ikon gerigi di kiri atas → **Project settings**
2. Buka tab **Service accounts**
3. Klik **Generate new private key** → **Generate key**
4. Sebuah berkas JSON akan terunduh. Simpan baik-baik.

> ⚠️ Berkas ini adalah kunci induk proyek Firebase Anda.
> **Jangan pernah** memasukkannya ke GitHub atau membagikannya kepada siapa pun.

Isi berkasnya kira-kira seperti ini:

```json
{"type":"service_account","project_id":"sigap-rudenim","private_key_id":"...","private_key":"-----BEGIN PRIVATE KEY-----\n...","client_email":"firebase-adminsdk-xxxxx@sigap-rudenim.iam.gserviceaccount.com", ...}
```

---

# TAHAP 2 — Menjalankan di Komputer Sendiri

Tahap ini penting: pastikan aplikasi jalan di komputer dulu, baru naik ke Vercel.

## 2.1 Menyiapkan berkas

Ekstrak paket aplikasi, lalu buka terminal di dalam foldernya:

```bash
composer install
```

Salin berkas contoh environment:

```bash
cp .env.example .env       # Mac/Linux
copy .env.example .env     # Windows
```

Buat kunci aplikasi:

```bash
php artisan key:generate
```

## 2.2 Mengisi berkas .env

Buka `.env` dengan editor teks, lalu isi tiga baris berikut:

```env
FIREBASE_DATABASE_URL=https://sigap-rudenim-default-rtdb.asia-southeast1.firebasedatabase.app/

FIREBASE_STORAGE_BUCKET=sigap-rudenim.firebasestorage.app

FIREBASE_SERVICE_ACCOUNT_JSON={"type":"service_account","project_id":"...seluruh isi berkas JSON tadi..."}
```

Untuk `FIREBASE_SERVICE_ACCOUNT_JSON`, buka berkas JSON yang tadi terunduh,
salin **seluruh isinya**, lalu tempel dalam **satu baris**. Jangan menekan Enter
di tengah-tengah.

> Kalau JSON-nya sulit ditempel dalam satu baris, ubah dulu menjadi base64:
> ```bash
> base64 -w 0 nama-berkas.json          # Mac/Linux
> ```
> Hasilnya boleh langsung ditempel sebagai nilai `FIREBASE_SERVICE_ACCOUNT_JSON`.

## 2.3 Mengisi data awal

```bash
php artisan sigap:seed
```

Perintah ini membuat tiga akun dan beberapa data contoh **sintetis** di Firebase:

| Peran | Email | Kata sandi |
|---|---|---|
| Admin | `admin@sigap-rudenim.local` | `Sigap@Admin2026` |
| Petugas | `petugas@sigap-rudenim.local` | `Sigap@Petugas2026` |
| Supervisor | `supervisor@sigap-rudenim.local` | `Sigap@Supervisor2026` |

**Cara memeriksa:** buka Firebase Console → Realtime Database. Seharusnya sudah
muncul node `users`, `refugees`, `placements`, `documents`, dan `audit_trails`.

Kalau perintah gagal, pesan galatnya akan menyebutkan bagian mana yang salah —
biasanya `FIREBASE_DATABASE_URL` keliru atau service account belum terisi.

## 2.4 Menjalankan aplikasi

```bash
php artisan serve
```

Buka <http://localhost:8000>, masuk memakai akun Admin di atas.

**Cara memeriksa:** coba tambah satu data pengungsi, lalu lihat di Firebase
Console — data itu harus langsung muncul di node `refugees`.

## 2.5 Menghidupkan unggah berkas ke Firebase Storage

Di `.env`, ubah satu baris:

```env
FIREBASE_STORAGE_DISK=firebase-rest
```

Jalankan ulang `php artisan serve`, lalu coba unggah dokumen di menu Dokumen.

**Cara memeriksa:** buka Firebase Console → Storage. Berkasnya harus muncul di
folder `documents/`. Di halaman Dokumen juga muncul tombol unduh.

Kalau kredensial belum benar, aplikasi akan menolak unggahan dan menampilkan
pesan yang menjelaskan apa yang kurang — berkas tidak akan tercatat setengah jadi.

---

# TAHAP 3 — Mengunggah ke Repositori GitHub Baru

## 3.1 Membuat repositori

1. Buka <https://github.com/new>
2. Nama repositori: misalnya `sigap-rudenim-surabaya`
3. Pilih **Private** (dianjurkan, karena ini aplikasi internal)
4. **Jangan** centang "Add a README file" atau berkas lainnya
5. Klik **Create repository**

## 3.2 Mengunggah

Di terminal, dari dalam folder aplikasi:

```bash
git init
git add .
git commit -m "SIGAP Rudenim Surabaya"
git branch -M main
git remote add origin https://github.com/NAMA-ANDA/sigap-rudenim-surabaya.git
git push -u origin main
```

> **Gunakan git, jangan mengunggah lewat halaman web GitHub.**
> Antarmuka web menjatuhkan berkas berawalan titik seperti `.gitignore` dan
> `.env.example`. Tanpa `.gitignore`, folder `vendor` berisi puluhan ribu berkas
> bisa ikut terunggah dan repositori menjadi kacau.

## 3.3 Memastikan tidak ada rahasia yang ikut

```bash
git ls-files | grep -c "^\.env$"
```

Hasilnya **harus `0`**. Kalau muncul angka lain, berarti `.env` ikut ter-commit.
Hentikan, jalankan `git rm --cached .env`, commit ulang, lalu **buat ulang kunci
service account** di Firebase Console karena kunci lama sudah bocor.

---

# TAHAP 4 — Deploy ke Vercel

## 4.1 Mengimpor proyek

1. Buka <https://vercel.com> lalu masuk memakai akun GitHub
2. Klik **Add New → Project**
3. Pilih repositori `sigap-rudenim-surabaya` → **Import**
4. **Jangan mengubah** Build Command maupun Output Directory. Biarkan kosong.
5. Jangan klik Deploy dulu — buka dulu bagian **Environment Variables** di bawah

## 4.2 Mengisi Environment Variables

Isi enam variabel berikut. Untuk masing-masing, centang **Production**,
**Preview**, dan **Development**.

| Key | Value |
|---|---|
| `APP_KEY` | Jalankan `php artisan key:generate --show` di komputer, salin hasilnya utuh termasuk awalan `base64:` |
| `FIREBASE_DATABASE_URL` | Alamat Realtime Database dari Tahap 1.2 |
| `FIREBASE_SERVICE_ACCOUNT_JSON` | Seluruh isi berkas JSON service account |
| `FIREBASE_STORAGE_BUCKET` | Nama bucket dari Tahap 1.4 |
| `FIREBASE_STORAGE_DISK` | `firebase-rest` |
| `SIGAP_SAMPLE_DATA_ENABLED` | `false` |

Klik **Deploy** dan tunggu sekitar dua menit.

## 4.3 Bila lupa mengisi environment variable

Environment variable yang ditambahkan setelah deploy **tidak berlaku otomatis**.
Buka tab **Deployments** → klik titik tiga pada deployment paling atas →
**Redeploy**.

---

# TAHAP 5 — Pemeriksaan Akhir

Buka alamat aplikasi Anda, lalu periksa satu per satu:

- [ ] Halaman login terbuka, bukan halaman putih atau galat 500
- [ ] Bisa masuk memakai akun Admin
- [ ] Menu Data Pengungsi menampilkan data
- [ ] Bisa menambah satu data, dan datanya muncul di Firebase Console
- [ ] Menu Dokumen bisa mengunggah berkas, lalu berkasnya muncul di Firebase Storage
- [ ] Menu Laporan bisa mengunduh PDF dan CSV
- [ ] Menu Riwayat Perubahan mencatat semua tindakan tadi
- [ ] `APP_DEBUG` bernilai `false` (atau tidak diisi sama sekali)

## Bila muncul galat 500

1. Buka Vercel → tab **Logs**, baca pesan galatnya
2. Bila perlu, tambahkan sementara `APP_DEBUG` = `true`, redeploy, buka halaman,
   baca pesan lengkapnya
3. **Kembalikan `APP_DEBUG` ke `false`** dan redeploy lagi

Galat yang paling sering terjadi:

| Pesan | Penyebab |
|---|---|
| `No application encryption key has been specified` | `APP_KEY` belum diisi, atau sudah diisi tapi belum redeploy |
| Data kosong padahal di Firebase ada | `FIREBASE_DATABASE_URL` salah, atau service account salah tempel |
| Unggah dokumen ditolak | `FIREBASE_SERVICE_ACCOUNT_JSON` belum diisi, atau `FIREBASE_STORAGE_BUCKET` keliru |

---

# Hal Penting yang Harus Diingat

**Jangan memasukkan data pengungsi asli.** Aplikasi ini dapat diakses dari
internet. Seluruh isinya harus data sintetis. Data sebenarnya hanya boleh
diolah di lingkungan tertutup milik instansi.

**Ganti kata sandi bawaan.** Tiga akun hasil `sigap:seed` memakai kata sandi
yang tertulis di panduan ini, jadi sudah tidak rahasia.

**Jangan pernah commit `.env` atau berkas service account.** Bila terlanjur,
segera buat ulang kunci di Firebase Console.

**Deployment Vercel ini untuk demonstrasi.** Pengujian performa dan keamanan
tetap dijalankan di lingkungan lokal.

---

# Lampiran: Perintah yang Sering Dipakai

```bash
# Mengisi ulang akun saja, tanpa menyentuh data
php artisan sigap:seed --akun-saja

# Mengisi data contoh saja
php artisan sigap:seed --data-saja

# Membuat APP_KEY untuk Vercel
php artisan key:generate --show

# Membersihkan cache setelah mengubah .env
php artisan config:clear

# Menjalankan aplikasi
php artisan serve
```

# Lampiran: Struktur Data di Firebase

```
sigap-rudenim-default-rtdb
├── users/            akun petugas (kata sandi dalam bentuk hash)
├── refugees/         data pengungsi
├── placements/       lokasi hunian dan mutasi
├── documents/        keterangan berkas dan tautan unduhnya
├── audit_trails/     catatan semua perubahan data
└── reports/          riwayat unduhan laporan
```

Berkas dokumen sendiri tersimpan di Firebase Storage pada folder `documents/`.
