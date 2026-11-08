# Gedung Inn — Platform Pencarian & Perbandingan Gedung Serbaguna Bandung

Implementasi **PHP + MySQL** dari prototipe Figma Make "Gedung Inn". Tampilan dipertahankan
sama seperti prototipe, tetapi seluruh data kini disimpan di database dan dikelola lewat CRUD.

> Konsep: platform **discovery & comparison**, bukan booking. Tidak ada pemesanan, pembayaran,
> atau pengiriman inquiry. Pengunjung diarahkan ke WhatsApp/website resmi pengelola gedung.

---

## 1. Pemetaan 5 bahasa yang diminta

| # | Bahasa | Digunakan untuk | Contoh file |
|---|--------|-----------------|-------------|
| 1 | **HTML** | Struktur seluruh halaman (dirender oleh PHP) | `index.php`, `detail.php`, `partials/header.php` |
| 2 | **CSS** | Seluruh tampilan, design system, responsive | `assets/css/style.css` (3.000+ baris) |
| 3 | **JavaScript** | Filter AJAX, compare tray, drawer, galeri, kalkulator estimasi, modal hapus, form layanan dinamis | `assets/js/app.js`, `assets/js/admin.js` |
| 4 | **PHP** | Routing halaman, validasi, session login, CSRF, upload CSV, rendering komponen | `includes/repository.php`, `admin/*.php` |
| 5 | **PHP MySQL** | Database `gedung_inn`: 9 tabel, relasi FK, seed 8 gedung, seluruh CRUD | `database/schema.sql`, `config/database.php` |

---

## 2. Cara menjalankan (XAMPP)

1. **Salin folder** `gedung-inn` ke `C:\xampp\htdocs\` (atau jalankan langsung dari lokasi ini).
2. Buka **XAMPP Control Panel** → Start **Apache** dan **MySQL**.
3. Impor database:
   - Buka <http://localhost/phpmyadmin>
   - Tab **Import** → pilih `database/schema.sql` → **Go**
   - File ini otomatis membuat database `gedung_inn`, 9 tabel, dan 8 data gedung contoh.
4. Buka aplikasi:
   - Jika folder disalin ke htdocs: <http://localhost/gedung-inn/>
   - Jika dijalankan dengan PHP built-in server:
     ```bash
     cd gedung-inn
     php -S localhost:8000
     ```
     lalu buka <http://localhost:8000/>
5. Masuk area admin: tombol **Admin** di kanan atas, atau <http://localhost/gedung-inn/admin/>

### Akun admin demo

```
Email    : admin@gedunginn.id
Password : admin123
```

Password disimpan sebagai hash (`password_hash` / `password_verify`), bukan teks biasa.
Untuk mengganti password, buat hash baru:

```bash
php -r "echo password_hash('passwordbaru', PASSWORD_DEFAULT), PHP_EOL;"
```

lalu update kolom `users.password_hash` lewat phpMyAdmin.

---

## 3. Konfigurasi

Semua pengaturan ada di `config/config.php`:

```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');   // XAMPP default 3306
define('DB_NAME', 'gedung_inn');
define('DB_USER', 'root');
define('DB_PASS', '');       // XAMPP default kosong
```

Nilai tersebut bisa dioverride lewat environment variable tanpa mengubah file:

```bash
GI_DB_PORT=3307 GI_DB_USER=root php -S localhost:8000
```

`BASE_URL` dideteksi otomatis, jadi aplikasi tetap bekerja di subfolder (`/gedung-inn/`)
maupun di root domain.

Koneksi database juga **mencoba beberapa port secara berurutan**: nilai `DB_PORT`
(default `3306`), lalu `3306`, lalu `3307`. Jadi aplikasi tetap jalan baik pada XAMPP
standar maupun ketika MySQL XAMPP digeser ke `3307` karena port `3306` sudah dipakai
instance MySQL lain (misalnya service MySQL bawaan Windows).

---

## 4. Struktur folder

```
gedung-inn/
├── index.php                  # Katalog: hero, filter, daftar gedung
├── detail.php                 # Detail gedung + panel estimasi + kontak resmi
├── bandingkan.php             # Perbandingan 2–3 gedung
├── .htaccess                  # Keamanan dasar Apache
│
├── config/
│   ├── config.php             # Konstanta app, koneksi, session, BASE_URL
│   └── database.php           # Koneksi PDO + pesan error yang ramah
│
├── includes/
│   ├── functions.php          # Helper: e(), rupiah(), wa_url(), CSRF, flash, auth
│   └── repository.php         # Query & CRUD (masters, venue, filter, CSV)
│
├── partials/
│   ├── header.php  footer.php # Layout publik + compare tray + muat app.js
│   ├── admin-header.php       # Layout admin (sidebar + topbar)
│   ├── icons.php              # Ikon SVG (sama dengan prototipe)
│   ├── filters.php            # Panel filter (sidebar + drawer)
│   ├── venue-card.php         # Kartu gedung (dipakai katalog & API)
│   ├── results.php            # Grid hasil / empty state
│   └── contact.php            # Blok kontak & dua tombol aksi resmi
│
├── api/
│   ├── katalog.php            # JSON hasil filter (dipanggil JavaScript)
│   └── tray.php               # JSON data compare tray
│
├── admin/
│   ├── index.php              # Dashboard (4 statistik + gedung terbaru)
│   ├── login.php  logout.php  # Autentikasi session
│   ├── gedung.php             # Daftar + hapus gedung (DELETE)
│   ├── gedung-form.php        # Tambah / edit gedung (CREATE, UPDATE)
│   ├── impor.php              # Impor CSV: unggah, pratinjau, validasi, simpan
│   ├── master.php             # CRUD master data (kategori/fasilitas/jenis acara)
│   └── template-csv.php       # Unduh template CSV
│
├── assets/
│   ├── css/style.css          # Seluruh desain (dari prototipe, CSS murni)
│   ├── js/app.js              # Interaksi halaman publik
│   ├── js/admin.js            # Interaksi area admin
│   ├── fonts/                 # Inter + Plus Jakarta Sans (lokal, offline)
│   └── img/                   # 8 foto gedung contoh
│
├── database/
│   ├── schema.sql             # Skema + seed (8 gedung, master data, admin)
│   └── .htaccess
│
└── storage/sessions/          # File session PHP (dibuat otomatis)
```

---

## 5. Struktur database

```
users              id, nama, email, password_hash
kategori           id, nama, urutan
fasilitas          id, nama, urutan
jenis_acara        id, nama, urutan

venues             id, nama, kategori_id → kategori,
                   kecamatan, alamat, kapasitas, harga_per_jam, minimal_jam,
                   kontak_nama, kontak_wa, website_resmi, kontak_demo, deskripsi,
                   created_at, updated_at
venue_foto         id, venue_id → venues, url, urutan              (4 foto/gedung)
layanan            id, venue_id → venues, nama, harga, satuan, urutan
venue_fasilitas    venue_id, fasilitas_id                          (many-to-many)
venue_jenis        venue_id, jenis_acara_id                        (many-to-many)
```

Semua relasi memakai **foreign key** dengan `ON DELETE CASCADE` (kecuali `kategori`
memakai `RESTRICT` agar kategori yang masih dipakai tidak bisa dihapus).

---

## 6. Di mana CRUD-nya?

| Operasi | Halaman / file | Implementasi |
|---------|----------------|--------------|
| **C**reate gedung | `admin/gedung-form.php` | Form POST → `validate_venue()` → `venue_save()` (INSERT + relasi, dalam transaksi) |
| **R**ead gedung | `index.php`, `detail.php`, `bandingkan.php`, `admin/gedung.php` | `find_venues()`, `get_venue()`, `get_venues_by_ids()` (PDO prepared statement) |
| **U**pdate gedung | `admin/gedung-form.php?id=N` | UPDATE `venues` + hapus & tulis ulang relasi, satu transaksi |
| **D**elete gedung | `admin/gedung.php` (POST `action=hapus`) | DELETE `venues`; foto/layanan/fasilitas/jenis ikut terhapus (cascade) |
| **C/R/U/D master data** | `admin/master.php` | `master_add()`, `master_rename()`, `master_delete()` untuk 3 tab |
| **Create massal (CSV)** | `admin/impor.php` | Upload → `parse_csv()` → pratinjau + validasi → `import_csv_row()` |

Contoh query aman (prepared statement) di `includes/repository.php`:

```php
$st = db()->prepare('SELECT v.*, k.nama AS kategori
                     FROM venues v JOIN kategori k ON k.id = v.kategori_id
                     WHERE v.id = ?');
$st->execute([$id]);
```

---

## 7. Fitur publik

- **Katalog** — pencarian nama, filter harga (slider), kapasitas minimal, kecamatan,
  kategori, jenis acara, dan 4 opsi pengurutan. Filter berjalan **dua lapis**:
  server-side lewat query string (tetap jalan tanpa JS) dan AJAX ke `api/katalog.php`
  (terasa instan, ada skeleton loading 0,3 detik).
- **Detail gedung** — galeri 4 foto (klik thumbnail menukar foto utama), fasilitas,
  jenis acara, layanan tambahan, serta bagian "Informasi Kontak & Website Resmi".
- **Estimasi biaya** — durasi +/- , pilih layanan, total dihitung langsung oleh JavaScript
  (`harga × durasi` untuk layanan per jam, harga tetap untuk layanan sekali).
- **Dua tombol aksi resmi** (hanya di panel kanan): `Hubungi Pengelola` membuka `wa.me`
  dengan pesan Bahasa Indonesia yang sudah terisi, dan `Website Resmi` membuka tab baru.
- **Bandingkan** — maksimal 3 gedung lewat compare tray (tersimpan di `localStorage`),
  tabel perbandingan menyoroti nilai terbaik (kapasitas terbesar, harga terendah,
  sewa minimal terendah).
- **Tombol kembali** di kiri atas halaman detail & perbandingan (memakai history browser,
  fallback ke katalog).

---

## 8. Fitur admin

- **Login/logout** dengan session + CSRF, pesan error "Email atau password salah".
- **Dashboard** — 4 kartu statistik dihitung dari database (Total Gedung + per kategori)
  dan tabel 5 gedung terbaru.
- **Kelola gedung** — pencarian, filter kategori, edit, hapus dengan modal konfirmasi.
  Perubahan langsung terlihat di katalog publik.
- **Impor CSV** — drag & drop / pilih file, pratinjau 3+ baris, baris tidak valid
  ditandai merah beserta alasannya, lalu hanya baris valid yang disimpan.
- **Master data** — tab Kategori / Fasilitas / Jenis Acara dengan tambah, ubah, hapus.
  Item baru langsung muncul di form gedung dan filter katalog.

---

## 9. Keamanan yang diterapkan

- **PDO prepared statement** di semua query (anti SQL injection).
- **`htmlspecialchars`** lewat helper `e()` pada seluruh output (anti XSS).
- **CSRF token** pada setiap form POST.
- **Password hashing** (`password_hash`) + `session_regenerate_id` saat login.
- **Validasi server-side** untuk semua input (angka > 0, WA 10–13 digit, URL http/https).
- `.htaccess` memblokir akses langsung ke `config/`, `includes/`, `database/`, `storage/`.

---

## 10. Catatan

- Foto gedung berasal dari Unsplash (`assets/img/credits.txt`) dan **bukan** foto lokasi
  sebenarnya. Nomor WhatsApp & website adalah data contoh (`example.com`) yang ditandai
  pada halaman detail, sesuai etika prototipe.
- Prototipe React menyimpan data di state; versi ini memindahkannya ke MySQL sehingga
  data tetap ada setelah halaman dimuat ulang.
- Desain (warna `#1F4D3A` / `#E8B04B`, font Plus Jakarta Sans + Inter, grid 12 kolom,
  radius kartu 12px) dipertahankan dari prototipe.

---

## 11. Troubleshooting

| Gejala | Penyebab & solusi |
|--------|-------------------|
| "Koneksi database gagal" | MySQL belum jalan, atau database belum diimpor. Jalankan MySQL dan impor `database/schema.sql`. |
| Halaman admin meminta login terus | Folder `storage/sessions` tidak bisa ditulis. Pastikan folder ada dan Apache punya izin tulis. |
| Gambar tidak muncul | Folder `assets/img` belum tersalin, atau `BASE_URL` salah. |
| CSS tidak termuat | Buka `assets/css/style.css` langsung di browser; jika 404, cek penyalinan folder `assets`. |
| Port 3306 sudah dipakai | Set `GI_DB_PORT` ke port lain, atau hentikan MySQL lain. |
