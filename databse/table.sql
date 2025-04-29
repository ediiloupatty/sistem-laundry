-- Database Schema untuk Sistem Laundry

-- 1. Tabel Users
CREATE TABLE `users` (
    `id` int(11) PRIMARY KEY AUTO_INCREMENT,
    `username` varchar(50) NOT NULL UNIQUE,
    `password` varchar(255) NOT NULL,
    `role` enum('admin','pelanggan','karyawan') NOT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP
);

-- 2. Tabel Customers
CREATE TABLE `customers` (
    `id` int(11) PRIMARY KEY AUTO_INCREMENT,
    `user_id` int(11),
    `nama` varchar(100) NOT NULL,
    `alamat` text,
    `no_hp` varchar(15),
    `email` varchar(100),
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 3. Tabel Services (Layanan)
CREATE TABLE `services` (
    `id` int(11) PRIMARY KEY AUTO_INCREMENT,
    `nama_layanan` varchar(100) NOT NULL,
    `harga_per_kg` decimal(10,2) NOT NULL,
    `deskripsi` text,
    `durasi_hari` int(11) DEFAULT 1,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP
);

-- 4. Tabel Orders (Pesanan)
CREATE TABLE `orders` (
    `id` int(11) PRIMARY KEY AUTO_INCREMENT,
    `customer_id` int(11),
    `total_harga` decimal(10,2),
    `tgl_order` timestamp DEFAULT CURRENT_TIMESTAMP,
    `tgl_selesai` datetime,
    `status` enum('menunggu_konfirmasi','diproses','selesai','siap_diantar','dibatalkan') DEFAULT 'menunggu_konfirmasi',
    `metode_antar` enum('jemput','antar_sendiri') NOT NULL,
    `alamat_jemput` text,
    `catatan` text,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- 5. Tabel Order Details
CREATE TABLE `order_details` (
    `id` int(11) PRIMARY KEY AUTO_INCREMENT,
    `order_id` int(11),
    `service_id` int(11),
    `jenis_pakaian` varchar(100),
    `jumlah` int(11),
    `berat` decimal(5,2),
    `harga` decimal(10,2),
    `subtotal` decimal(10,2),
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id)
);

-- 6. Tabel Pickup Requests
CREATE TABLE `pickup_requests` (
    `id` int(11) PRIMARY KEY AUTO_INCREMENT,
    `order_id` int(11),
    `tgl_jemput` datetime,
    `status_jemput` enum('pending','dijemput','dibatalkan') DEFAULT 'pending',
    `karyawan_id` int(11),
    `catatan` text,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (karyawan_id) REFERENCES users(id)
);

-- 7. Tabel Payments
CREATE TABLE `payments` (
    `id` int(11) PRIMARY KEY AUTO_INCREMENT,
    `order_id` int(11),
    `metode_pembayaran` enum('cash','transfer','ovo','dana','gopay') NOT NULL,
    `jumlah_bayar` decimal(10,2) NOT NULL,
    `status_pembayaran` enum('pending','lunas','dibatalkan') DEFAULT 'pending',
    `bukti_pembayaran` varchar(255),
    `tgl_pembayaran` datetime,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- 8. Tabel Reports
CREATE TABLE `reports` (
    `id` int(11) PRIMARY KEY AUTO_INCREMENT,
    `tgl_laporan` date NOT NULL,
    `jenis_laporan` enum('harian','bulanan','tahunan') NOT NULL,
    `total_pendapatan` decimal(10,2) NOT NULL,
    `total_pesanan` int(11) NOT NULL,
    `file_pdf` varchar(255),
    `created_by` int(11),
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- 9. Tabel Inventory (Opsional untuk manajemen stok)
CREATE TABLE `inventory` (
    `id` int(11) PRIMARY KEY AUTO_INCREMENT,
    `nama_barang` varchar(100) NOT NULL,
    `jenis` enum('detergen','pewangi','kantong','lainnya') NOT NULL,
    `stok` int(11) DEFAULT 0,
    `satuan` varchar(20),
    `harga_satuan` decimal(10,2),
    `min_stok` int(11) DEFAULT 5,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 10. Tabel Promos (Opsional untuk sistem promo)
CREATE TABLE `promos` (
    `id` int(11) PRIMARY KEY AUTO_INCREMENT,
    `kode_promo` varchar(20) NOT NULL UNIQUE,
    `deskripsi` text,
    `jenis_diskon` enum('persen','nominal') NOT NULL,
    `nilai_diskon` decimal(10,2) NOT NULL,
    `min_transaksi` decimal(10,2) DEFAULT 0,
    `max_diskon` decimal(10,2),
    `tgl_mulai` date NOT NULL,
    `tgl_selesai` date NOT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP
);

-- 11. Tabel Reviews (Opsional untuk ulasan pelanggan)
CREATE TABLE `reviews` (
    `id` int(11) PRIMARY KEY AUTO_INCREMENT,
    `order_id` int(11),
    `customer_id` int(11),
    `rating` int(11) CHECK (rating >= 1 AND rating <= 5),
    `komentar` text,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- 12. Tabel Notifications (Opsional untuk sistem notifikasi)
CREATE TABLE `notifications` (
    `id` int(11) PRIMARY KEY AUTO_INCREMENT,
    `user_id` int(11),
    `order_id` int(11),
    `tipe` enum('email','sms','whatsapp') NOT NULL,
    `pesan` text NOT NULL,
    `status` enum('pending','terkirim','gagal') DEFAULT 'pending',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `sent_at` datetime,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Insert data awal untuk admin
INSERT INTO `users` (`username`, `password`, `role`) VALUES 
('admin', MD5('admin123'), 'admin');

-- Insert data awal untuk layanan
INSERT INTO `services` (`nama_layanan`, `harga_per_kg`, `deskripsi`, `durasi_hari`) VALUES 
('Cuci Biasa', 5000, 'Layanan cuci regular (2-3 hari)', 3),
('Cuci Express', 7500, 'Layanan cuci express (1 hari)', 1),
('Cuci Setrika', 8000, 'Layanan cuci + setrika (2-3 hari)', 3),
('Setrika Saja', 3000, 'Layanan setrika saja (1 hari)', 1);

-- Insert data awal untuk inventory (Opsional)
INSERT INTO `inventory` (`nama_barang`, `jenis`, `stok`, `satuan`, `harga_satuan`, `min_stok`) VALUES 
('Detergen Rinso', 'detergen', 50, 'kg', 15000, 10),
('Pewangi Molto', 'pewangi', 30, 'liter', 25000, 5),
('Kantong Plastik Besar', 'kantong', 100, 'pcs', 1000, 20);

-- Insert data awal untuk promo (Opsional)
INSERT INTO `promos` (`kode_promo`, `deskripsi`, `jenis_diskon`, `nilai_diskon`, `min_transaksi`, `tgl_mulai`, `tgl_selesai`) VALUES 
('NEWUSER', 'Diskon 20% untuk pelanggan baru', 'persen', 20, 50000, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY)),
('WEEKEND10', 'Diskon 10% setiap weekend', 'persen', 10, 30000, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 60 DAY));