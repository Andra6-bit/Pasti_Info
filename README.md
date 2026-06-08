# Pasti Info 🏆

Pasti Info adalah platform informasi kompetisi berbasis web yang dirancang untuk membantu mahasiswa dan publik menemukan, mengelola, serta mendiskusikan keikutsertaan dalam berbagai perlombaan secara mudah dan terintegrasi. Proyek ini dibangun sebagai bagian dari tugas studi di Universitas Tidar.

---

## 📋 Tentang Project
Pasti Info dikembangkan untuk menjembatani mahasiswa yang sering kesulitan mencari informasi perlombaan terbaru, valid, dan terorganisir. Melalui platform ini, penyelenggara lomba dapat mengajukan publikasi event mereka secara berbayar, sementara pencari info lomba dapat melakukan filter kategori, berlangganan notifikasi Telegram, melakukan bookmark, bahkan berkonsultasi mengenai kelayakan lomba dengan AI Persona sebelum mendaftar.

---

## ✨ Fitur Utama
*   🌐 **Competition Listing & Filters:** Jelajahi daftar kompetisi aktif yang telah terverifikasi dengan fitur pencarian dan filter berbasis kategori.
*   🔐 **User Authentication:** Login cepat menggunakan akun Google (Google OAuth) atau pendaftaran manual secara aman.
*   🤖 **AI Debate Arena (Gemini Integration):** Diskusikan kelayakan keikutsertaan perlombaan Anda secara interaktif bersama 3 Persona AI (Karin, Tiara, Raka).
*   💳 **Xendit Payment Gateway:** Pembayaran biaya publikasi event lomba yang aman, instan, dan terotomatisasi (dilengkapi dengan auto-refund jika pengajuan ditolak).
*   📢 **Telegram Notifications:** Dapatkan notifikasi status pembayaran, hasil review administrasi, serta siaran info lomba baru secara otomatis langsung ke Telegram Anda.
*   💾 **Saved Competitions (Bookmark):** Simpan info lomba favorit Anda ke dalam dashboard personal.
*   🛎️ **Category Subscriptions:** Pilih kategori lomba favorit Anda untuk menerima update terbaru.
*   🖥️ **Admin Dashboard & Audit logs:** Manajemen user, peninjauan (review) submission lomba, histori pembayaran, serta pencatatan audit aktivitas admin yang komprehensif.

---

## 🛠️ Tech Stack
| Teknologi | Kegunaan |
| :--- | :--- |
| **PHP** (v8.x+) | Logika server (backend) & middleware API |
| **MySQL / MariaDB** | Penyimpanan database relasional |
| **HTML5 & CSS3** | Struktur halaman dan styling modern |
| **Vanilla JavaScript** | Logic interaktif client-side & integrasi AJAX |
| **Google Gemini API** | Otak pemrosesan natural language untuk AI Debate Persona |
| **Xendit API** | Pembuatan invoice digital & webhook konfirmasi pembayaran |
| **Telegram Bot API** | Bot pengiriman notifikasi instan ke chat user |

---

## ⚙️ Instalasi & Setup

Ikuti langkah-langkah di bawah ini untuk menjalankan Pasti Info di lingkungan lokal Anda:

### 1. Clone Repositori
Clone repositori ini ke direktori web server lokal Anda (misal `htdocs` untuk XAMPP):
```bash
git clone https://github.com/Andra6-bit/Pasti_Info.git
```

### 2. Konfigurasi Environment Variables
Salin file template `.env.example` menjadi `.env` di root project:
```bash
cp .env.example .env
```
Buka file `.env` tersebut dan lengkapi kredensial database lokal, Xendit API, Gemini API, Google Client ID, serta Telegram Bot Token Anda.

### 3. Import Database
*   Nyalakan MySQL pada XAMPP Control Panel.
*   Buka phpMyAdmin (`http://localhost/phpmyadmin/`) dan buat database baru bernama `db_pinfo`.
*   Import file database cadangan yang terletak di project root: `db_pinfo.sql` (atau gunakan skrip migrasi terbaru).

### 4. Setup Web Server
Pastikan nama folder project Anda di direktori web server bernama `Pasti_Info` agar dapat diakses melalui URL default:
```
http://localhost/Pasti_Info/
```

### 5. Konfigurasi Keamanan `.htaccess`
Pastikan modul rewrite Apache aktif. File `.htaccess` di root folder akan secara otomatis memblokir akses HTTP langsung ke file konfigurasi sensitif seperti `.env`.

### 6. Akses Halaman
Buka browser dan kunjungi:
*   **Halaman Utama:** `http://localhost/Pasti_Info/`
*   **Akun Demo Admin:** Username `admin` \| Password `admin`
*   **Akun Demo User:** Username `budbud` \| Password `budi`

---

## 🔑 Environment Variables
Berikut adalah daftar variabel lingkungan yang wajib dikonfigurasi di dalam file `.env`:

| Variabel | Deskripsi | Contoh / Default |
| :--- | :--- | :--- |
| `DB_HOST` | Host database MySQL lokal | `localhost` |
| `DB_NAME` | Nama database project | `db_pinfo` |
| `DB_USER` | Username database dengan hak akses terbatas | `pinfo_app` |
| `DB_PASS` | Password dari DB user | `password_anda` |
| `GEMINI_API_KEY` | Kunci API Google Gemini untuk AI Persona | `AIzaSy...` |
| `XENDIT_SECRET_KEY` | API Secret Key dari Dashboard Xendit | `xnd_development_...` |
| `XENDIT_WEBHOOK_TOKEN` | Callback/webhook token verifikasi dari Xendit | `token_anda` |
| `XENDIT_SUCCESS_URL` | URL pengalihan setelah pembayaran berhasil | `http://localhost/Pasti_Info/...` |
| `XENDIT_FAILURE_URL` | URL pengalihan jika pembayaran gagal | `http://localhost/Pasti_Info/...` |
| `GOOGLE_CLIENT_ID` | Client ID dari Google Cloud Console untuk OAuth | `client_id.apps.googleusercontent.com` |
| `TELEGRAM_BOT_TOKEN` | Token Bot Telegram untuk pengiriman notifikasi | `123456:ABC-DEF...` |

---

## 👥 Role & Akses
Aksesibilitas sistem dibagi menjadi tiga tingkatan peran utama:

| Role | Batasan Akses | Deskripsi Fitur |
| :--- | :--- | :--- |
| **Guest** | Anonim / Belum login | Hanya dapat melihat daftar kompetisi yang dipublikasikan dan detail umum lomba. Pembatasan waktu sesi tamu aktif selama 60 detik sebelum dipaksa masuk ke halaman auth. |
| **User** | Akun terdaftar | Mengelola bookmark lomba, mengedit profil, mengajukan (submit) lomba baru lewat form modal, membayar via Xendit, berkonsultasi di AI Debate Arena, serta berlangganan notifikasi kategori. |
| **Admin** | Pengelola sistem | Membuka dashboard administrator, meninjau pengajuan lomba (approve/reject), mencatat log audit sistem, melacak histori transaksi finansial, mengubah konfigurasi global biaya submission, dan melakukan moderasi data. |

---

## 📁 Struktur Folder
Berikut adalah struktur direktori utama pada project Pasti Info:
```
Pasti_Info/
├── admin/                     # File view & logic halaman administrator
├── assets/                    # Static assets
│   ├── css/                   # Stylesheets (global.css, navbar.css, dsb.)
│   ├── images/                # Poster kompetisi & avatar default
│   └── js/                    # Client-side scripts (chat-ai.js, bookmark.js)
├── components/                # Komponen HTML/PHP yang dapat digunakan ulang (reusable)
├── config/                    # File konfigurasi utama (database connection)
├── controllers/               # Logika pemrosesan backend (PHP endpoints)
├── helpers/                   # Fungsi utilitas (Google OAuth, Xendit API, Telegram Bot)
├── pages/                     # File view utama bagi pengguna biasa (home, detail, chat-ai)
├── scripts/                   # Skrip backup database & template SQL migrasi
├── .env.example               # Template environment configuration
└── README.md                  # Dokumentasi teknis proyek
```

---

## 🔒 Security Measures
Beberapa sistem keamanan database dan aplikasi yang sudah diimplementasikan meliputi:
1.  **Strict Parameterized Queries:** Mencegah celah keamanan *SQL Injection* menggunakan prepared statements pada seluruh database transactions.
2.  **Robust Password Hashing:** Seluruh kata sandi pengguna lokal dienkripsi secara aman menggunakan algoritma **bcrypt** (`password_hash`).
3.  **Role-Based Security Guards:** Validasi otorisasi server-side yang ketat untuk mengamankan akses admin berdasarkan status role sesi, bukan sekadar username.
4.  **Google Token Validation:** Verifikasi claims JWT (`iss`, `aud`, `exp`) untuk menghindari pemalsuan identitas login OAuth.
5.  **HMAC-SHA256 Webhook Verification:** Verifikasi kecocokan tanda tangan (signature) webhook dari Xendit untuk memvalidasi callback asli.
6.  **Referrer Whitelisting:** Memvalidasi target redirect link setelah transaksi pembayaran guna menghindari *open-redirect* attacks.
7.  **Least Privilege Database User:** Penggunaan akun DB khusus `pinfo_app` yang dibatasi hanya untuk operasi DML (`SELECT`, `INSERT`, `UPDATE`, `DELETE`), mencegah eksploitasi DDL/DCL.
8.  **Apache Hardening:** Konfigurasi `.htaccess` untuk memblokir akses langsung browser ke file `.env`.
9.  **Activity Log Audit Trail:** Table `activity_logs` didesain untuk melacak histori manipulasi data krusial di backend.

---

## 🤝 Tim Pengembang
Proyek ini dirancang dan dibangun oleh tim mahasiswa **Universitas Tidar**:

*   **Muhammad Dafa Falah Labib** — *Product Manager & Fullstack Developer*
*   **Ahmad Syauqy Wardani** — *Product Owner & Frontend Developer*
*   **Rasyad Lintang Wahyunindar** — *UI/UX Designer (Lead)*
*   **Nayla Izza Arzatie** — *UI/UX Designer (Support)*
*   **Muhammad Andra Firdaus** — *Frontend Developer (Slicing)*

---

## 📄 Lisensi
Proyek ini dilisensikan di bawah **MIT License**.
