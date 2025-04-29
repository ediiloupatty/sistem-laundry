<?php
// File: includes/functions.php
// Fungsi-fungsi umum yang digunakan dalam sistem

// Fungsi format rupiah
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// Fungsi format tanggal Indonesia
function formatTanggal($tanggal) {
    return date('d/m/Y H:i', strtotime($tanggal));
}

// Fungsi untuk generate kode pesanan
function generateKodePesanan() {
    global $koneksi;
    $tahun = date('Y');
    $bulan = date('m');
    
    // Ambil pesanan terakhir
    $query = "SELECT id FROM orders ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($koneksi, $query);
    
    if(mysqli_num_rows($result) > 0) {
        $last_id = mysqli_fetch_assoc($result)['id'];
        $nomor = $last_id + 1;
    } else {
        $nomor = 1;
    }
    
    return "ORD-" . $tahun . $bulan . "-" . str_pad($nomor, 4, '0', STR_PAD_LEFT);
}

// Fungsi untuk mengirim notifikasi
function sendNotification($user_id, $order_id, $message, $type = 'email') {
    global $koneksi;
    
    $query = "INSERT INTO notifications (user_id, order_id, tipe, pesan) 
              VALUES ('$user_id', '$order_id', '$type', '$message')";
    
    return mysqli_query($koneksi, $query);
}

// Fungsi untuk menghitung total harga
function hitungTotalHarga($order_id) {
    global $koneksi;
    
    $query = "SELECT SUM(subtotal) as total FROM order_details WHERE order_id = '$order_id'";
    $result = mysqli_query($koneksi, $query);
    $data = mysqli_fetch_assoc($result);
    
    return $data['total'];
}

// Fungsi untuk mengupdate stok inventory
function updateStok($nama_barang, $jumlah, $operasi = 'kurang') {
    global $koneksi;
    
    if($operasi == 'kurang') {
        $query = "UPDATE inventory SET stok = stok - $jumlah WHERE nama_barang = '$nama_barang'";
    } else {
        $query = "UPDATE inventory SET stok = stok + $jumlah WHERE nama_barang = '$nama_barang'";
    }
    
    return mysqli_query($koneksi, $query);
}

// Fungsi untuk cek promo
function cekPromo($kode_promo, $total_belanja) {
    global $koneksi;
    
    $tanggal_sekarang = date('Y-m-d');
    $query = "SELECT * FROM promos WHERE kode_promo = '$kode_promo' 
              AND is_active = 1 
              AND '$tanggal_sekarang' BETWEEN tgl_mulai AND tgl_selesai 
              AND min_transaksi <= $total_belanja";
    
    $result = mysqli_query($koneksi, $query);
    
    if(mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    
    return false;
}

// Fungsi untuk menghitung diskon
function hitungDiskon($total, $promo) {
    if($promo['jenis_diskon'] == 'persen') {
        $diskon = ($total * $promo['nilai_diskon']) / 100;
        
        // Cek max diskon jika ada
        if($promo['max_diskon'] > 0 && $diskon > $promo['max_diskon']) {
            $diskon = $promo['max_diskon'];
        }
    } else {
        $diskon = $promo['nilai_diskon'];
    }
    
    return $diskon;
}
?>