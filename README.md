# Parkir Backend API

Aplikasi backend berbasis REST API untuk sistem manajemen parkir. Dibangun menggunakan framework Laravel modern untuk menangani operasional parkir mulai dari pencatatan kendaraan masuk/keluar, manajemen tarif, membership, hingga pelaporan aktivitas.

## 🚀 Fitur Utama

Berdasarkan struktur kode, berikut adalah fitur utama yang tersedia:

* **Autentikasi Aman**: Menggunakan **Laravel Sanctum** untuk token-based authentication.
* **Transaksi Parkir**:
    * Pencatatan Parkir Masuk (`Check-in`).
    * Pencatatan Parkir Keluar (`Check-out`) dengan perhitungan durasi dan biaya otomatis.
    * Dukungan berbagai metode pembayaran (Cash, QRIS, Transfer).
* **Manajemen Tarif**: CRUD untuk mengatur tarif parkir berdasarkan jenis kendaraan.
* **Manajemen Area**: Pengelolaan data area parkir/lokasi.
* **Membership**: Sistem member untuk pengguna langganan, termasuk riwayat parkir member.
* **Log Aktivitas**: Perekaman jejak aktivitas sistem (`LogAktivitas`).
* **Laporan PDF**: Integrasi dengan `barryvdh/laravel-dompdf` untuk pembuatan laporan.

## 🛠 Teknologi yang Digunakan

Project ini dibangun menggunakan teknologi berikut:

* **Bahasa**: PHP ^8.2
* **Framework**: Laravel 12.0
* **Database**: MySQL / MariaDB
* **Autentikasi**: Laravel Sanctum ^4.0
* **PDF Generator**: Laravel DomPDF ^3.1
* **Testing**: Pest PHP (via require-dev)

## 📋 Prasyarat Instalasi

Sebelum memulai, pastikan sistem Anda memiliki:

* [PHP](https://www.php.net/) versi 8.2 atau lebih baru.
* [Composer](https://getcomposer.org/).
* Database (MySQL/MariaDB).
* Terminal / Command Prompt.

## ⚙️ Cara Instalasi

Ikuti langkah-langkah berikut untuk menjalankan project di komputer lokal:

1.  **Clone Repository**
    ```bash
    git clone [https://github.com/username/parkir-backend.git](https://github.com/username/parkir-backend.git)
    cd parkir-backend
    ```

2.  **Install Dependencies**
    ```bash
    composer install
    ```

3.  **Konfigurasi Environment**
    Salin file `.env.example` menjadi `.env`:
    ```bash
    cp .env.example .env
    ```
    Buka file `.env` dan sesuaikan konfigurasi database Anda:
    ```ini
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=nama_database_parkir
    DB_USERNAME=root
    DB_PASSWORD=
    ```

4.  **Generate App Key**
    ```bash
    php artisan key:generate
    ```

5.  **Jalankan Migrasi Database**
    ```bash
    php artisan migrate
    ```

6.  **Jalankan Server**
    ```bash
    php artisan serve
    ```
    Aplikasi akan berjalan di `http://localhost:8000`.

## 📂 Susunan Project

Struktur utama direktori yang relevan dalam pengembangan API ini:

```text
parkir-backend/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── Api/
│   │           ├── AuthController.php      # Menangani Login/Logout
│   │           ├── TransaksiController.php # Logika Masuk/Keluar Parkir
│   │           ├── TarifController.php     # CRUD Tarif
│   │           ├── MemberController.php    # Manajemen Member
│   │           └── ...
│   └── Models/
│       ├── Transaksi.php   # Model Transaksi
│       ├── Tarif.php       # Model Tarif
│       ├── User.php        # Model User
│       └── ...
├── database/
│   └── migrations/         # Struktur tabel database
├── routes/
│   └── api.php             # Definisi Endpoints API
└── composer.json           # Dependensi project
```

## 📖 Contoh Penggunaan API
Berikut adalah beberapa contoh endpoint yang tersedia (lihat routes/api.php untuk daftar lengkap).
Header Wajib untuk Route Terproteksi: Authorization: Bearer <token_anda>

1. Login (Public)
URL: /api/login
Method: POST

```
JSON

{
    "email": "admin@example.com",
    "password": "password"
}
```

2. Parkir Masuk (Check-in)
URL: /api/parking/in
Method: POST

```
JSON

{
    "plat_nomor": "B 1234 XYZ",
    "jenis_kendaraan": "Mobil",
    "id_area": 1
}
```

3. Parkir Keluar (Check-out)
URL: /api/parking/out
Method: POST

```
JSON

{
    "plat_nomor": "B 1234 XYZ",
    "metode_bayar": "cash"
}
```

## 🤝 Kontribusi
Kontribusi sangat dipersilakan! Jika Anda ingin berkontribusi pada project ini:
Fork repository ini.
Buat branch fitur baru (git checkout -b fitur-keren).
Commit perubahan Anda (git commit -m 'Menambahkan fitur keren').
Push ke branch tersebut (git push origin fitur-keren).
Buat Pull Request.

## 📄 Lisensi
Project ini dilisensikan di bawah MIT License. Silakan lihat file LICENSE untuk informasi lebih lanjut.
