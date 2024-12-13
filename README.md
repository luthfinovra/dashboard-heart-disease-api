# Dashboard Riset Database Penyakit

Aplikasi ini adalah API untuk mendukung dashboard riset penyakit, yang dirancang untuk membantu peneliti mengakses dan mengelola data penyakit yang disediakan oleh operator. Aplikasi ini mendukung pembuatan database penyakit dengan struktur data yang dinamis, pengelolaan pengguna dengan peran yang berbeda (admin, operator, dan peneliti), serta pelacakan aktivitas melalui sistem log.

## Fitur Utama
- **Pengelolaan Pengguna**: Registrasi, persetujuan, dan manajemen peran (Admin, Operator, Peneliti).
- **Database Penyakit**: CRUD untuk database penyakit dengan skema yang dapat disesuaikan.
- **Sistem Log**: Pencatatan aktivitas pengguna untuk audit dan pelacakan.
- **Manajemen File**: Unggah dan unduh file terkait data penyakit.

---

## Persyaratan Sistem
1. **PHP** >= 8.2
2. **Composer** untuk manajemen dependensi PHP
3. **Laravel** >= 10
4. **PostgreSQL** sebagai database utama
5. **Postman** (opsional, untuk pengujian API)

---

## Langkah-Langkah Instalasi

### 1. Instal Dependensi
Jalankan perintah berikut untuk menginstal semua dependensi PHP:
```bash
composer install
```

### 2. Konfigurasi Environment
Salin file `.env.example` menjadi `.env`:
```bash
cp .env.example .env
```
Kemudian, sesuaikan file `.env` dengan konfigurasi berikut:
```dotenv
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=dashboard-heart-disease
DB_USERNAME=postgres
DB_PASSWORD=root

ADMIN_DEFAULT_EMAIL=
ADMIN_DEFAULT_PASSWORD=
FILESYSTEM_DISK=
API_KEY=
```

### 3. Generate Key Aplikasi
Jalankan perintah berikut untuk menghasilkan kunci aplikasi:
```bash
php artisan key:generate
```

### 4. Migrasi dan Seeder Database
Jalankan migrasi untuk membuat tabel-tabel di database:
```bash
php artisan migrate
```
Jika Anda ingin menambahkan data awal, jalankan juga:
```bash
php artisan db:seed
```

### 5. Jalankan Server
Jalankan server pengembangan menggunakan perintah berikut:
```bash
php artisan serve
```
Akses aplikasi Anda di browser melalui URL:
```
http://localhost:8000
```

---

## Pengujian API

### Dokumentasi API
Dokumentasi API dapat ditelusuri pada link berikut [Postman](https://www.postman.com/bold-resonance-703748/workspace/new-team-workspace)
- **Autentikasi**: Login, registrasi.
- **Manajemen Penyakit**: CRUD database penyakit.
- **Manajemen Pengguna**: Persetujuan dan pengaturan peran.
- **Log Action**: Pencatatan Log action aplikasi

### Langkah Pengujian
1. Gunakan Akun Admin Default.
2. Tambahkan pengguna operator dan peneliti.
3. Buat database penyakit dan tetapkan operator untuk mengelola data.
4. Gunakan akun peneliti untuk melihat data dan membuat laporan.

---
