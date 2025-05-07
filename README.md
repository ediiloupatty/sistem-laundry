# Sistem Manajemen Laundry

Sistem manajemen untuk usaha laundry dengan fitur pemesanan, tracking, pembayaran, dan laporan.

## Fitur

### Untuk Admin
- Dashboard untuk monitoring usaha
- Kelola pesanan (melihat, memproses, mengubah status)
- Konfirmasi pembayaran (manual dan otomatis)
- Kelola layanan dan harga
- Laporan keuangan (harian, bulanan, tahunan, dan custom)
- Cetak invoice dan laporan

### Untuk Pelanggan
- Dashboard dengan ringkasan layanan
- Pemesanan layanan laundry
- Tracking status pesanan
- Pembayaran online/offline
- Riwayat pesanan
- Notifikasi status pesanan

## Perubahan Terbaru

### Sistem Laporan yang Ditingkatkan
- Ditambahkan filter laporan **Tahunan**
- Ditambahkan filter laporan **Custom** dengan tanggal mulai dan akhir
- Optimalisasi filter:
  - Harian, Bulanan, Tahunan: Tidak perlu input tanggal mulai dan akhir
  - Custom: Wajib input tanggal mulai dan tanggal akhir

### Perbaikan Sistem Pembayaran
- Perbaikan bug pada pembayaran cash:
  - Sebelumnya: Pembayaran cash tetap berstatus "pending" dan memerlukan konfirmasi admin
  - Sekarang: Pembayaran cash otomatis terverifikasi tanpa perlu konfirmasi admin

### Peningkatan UI/UX
- Tampilan yang lebih profesional
- Responsif untuk mobile dan desktop
- Navigasi yang lebih intuitif
- Notifikasi real-time untuk pelanggan
- Perbaikan tampilan dashboard

## Teknologi
- PHP 7.4+
- MySQL
- HTML, CSS, JavaScript
- Font Awesome untuk ikon
- Desain responsif dengan CSS variabel dan media queries

## Struktur Folder
- `/admin` - Halaman dan fungsionalitas admin
- `/pelanggan` - Halaman dan fungsionalitas pelanggan
- `/includes` - File yang digunakan di seluruh sistem
- `/ajax` - Handler untuk request AJAX
- `/assets` - CSS, JavaScript, dan gambar
- `/uploads` - Direktori untuk upload bukti pembayaran

## Instalasi

1. Clone repository:
```bash
git clone https://github.com/ediiloupatty/sistem-laundry.git
```

2. Import database dari file SQL di direktori `/database`

3. Konfigurasi database di `config.php`

4. Jalankan di server lokal (XAMPP, WAMP, dll) atau hosting

## Login
- Admin:
  - Username: admin
  - Password: admin123
  
- Pelanggan:
  - Register untuk membuat akun baru

## Lisensi
[MIT License](LICENSE)

## Kontak
GitHub: [ediiloupatty](https://github.com/ediiloupatty)
