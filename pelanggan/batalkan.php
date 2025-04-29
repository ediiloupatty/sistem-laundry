<?php
// File: pelanggan/batalkan.php
// Proses pembatalan pesanan

session_start();
require_once '../config/koneksi.php';
require_once '../includes/functions.php';

// Cek apakah user adalah pelanggan
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pelanggan') {
    header("Location: ../login.php");
    exit();
}

$order_id = isset($_GET['id']) ? cleanInput($_GET['id']) : 0;
$customer_id = $_SESSION['customer_id'];

// Cek apakah pesanan valid dan masih bisa dibatalkan
$query = "SELECT * FROM orders 
          WHERE id = '$order_id' 
          AND customer_id = '$customer_id' 
          AND status = 'menunggu_konfirmasi'";
$result = mysqli_query($koneksi, $query);
$order = mysqli_fetch_assoc($result);

if(!$order) {
    $_SESSION['error'] = "Pesanan tidak dapat dibatalkan.";
    header("Location: riwayat.php");
    exit();
}

// Proses pembatalan
mysqli_begin_transaction($koneksi);

try {
    // Update status pesanan
    $update_order = "UPDATE orders SET status = 'dibatalkan' WHERE id = '$order_id'";
    mysqli_query($koneksi, $update_order);
    
    // Update status pembayaran
    $update_payment = "UPDATE payments SET status_pembayaran = 'dibatalkan' WHERE order_id = '$order_id'";
    mysqli_query($koneksi, $update_payment);
    
    // Update status pickup jika ada
    $update_pickup = "UPDATE pickup_requests SET status_jemput = 'dibatalkan' WHERE order_id = '$order_id'";
    mysqli_query($koneksi, $update_pickup);
    
    // Kirim notifikasi
    sendNotification($_SESSION['user_id'], $order_id, "Pesanan #$order_id telah dibatalkan.");
    
    mysqli_commit($koneksi);
    
    $_SESSION['success'] = "Pesanan berhasil dibatalkan.";
    header("Location: riwayat.php");
    exit();
    
} catch (Exception $e) {
    mysqli_rollback($koneksi);
    $_SESSION['error'] = "Gagal membatalkan pesanan: " . $e->getMessage();
    header("Location: riwayat.php");
    exit();
}
?>