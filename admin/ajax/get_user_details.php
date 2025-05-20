<?php
session_start();
require_once '../../config/koneksi.php';
require_once '../../includes/functions.php';

// Cek apakah user adalah admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

if(isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    
    // Query untuk mendapatkan data user
    $query = "SELECT id, username, role, is_active, created_at FROM users WHERE id = ?";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if($row = mysqli_fetch_assoc($result)) {
        // Format tanggal
        $row['created_at'] = formatTanggal($row['created_at']);
        
        // Jika user adalah pelanggan, ambil data pesanan
        if($row['role'] == 'pelanggan') {
            // Query untuk mendapatkan jumlah pesanan
            $order_query = "SELECT COUNT(*) as count FROM orders WHERE user_id = ?";
            $order_stmt = mysqli_prepare($koneksi, $order_query);
            mysqli_stmt_bind_param($order_stmt, "i", $user_id);
            mysqli_stmt_execute($order_stmt);
            $order_result = mysqli_stmt_get_result($order_stmt);
            $order_row = mysqli_fetch_assoc($order_result);
            $row['order_count'] = $order_row['count'];
            
            // Query untuk mendapatkan total pengeluaran
            $spending_query = "SELECT SUM(total_harga) as total FROM orders WHERE user_id = ? AND status_pesanan = 'selesai'";
            $spending_stmt = mysqli_prepare($koneksi, $spending_query);
            mysqli_stmt_bind_param($spending_stmt, "i", $user_id);
            mysqli_stmt_execute($spending_stmt);
            $spending_result = mysqli_stmt_get_result($spending_stmt);
            $spending_row = mysqli_fetch_assoc($spending_result);
            $row['total_spending'] = $spending_row['total'] ? number_format($spending_row['total'], 0, ',', '.') : 0;
        }
        
        // Query untuk mendapatkan last login
        $login_query = "SELECT last_login FROM user_login_log WHERE user_id = ? ORDER BY login_time DESC LIMIT 1";
        $login_stmt = mysqli_prepare($koneksi, $login_query);
        mysqli_stmt_bind_param($login_stmt, "i", $user_id);
        mysqli_stmt_execute($login_stmt);
        $login_result = mysqli_stmt_get_result($login_stmt);
        
        if($login_row = mysqli_fetch_assoc($login_result)) {
            $row['last_login'] = formatTanggal($login_row['last_login']);
        }
        
        echo json_encode(['status' => 'success', 'data' => $row]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'User tidak ditemukan']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Parameter tidak lengkap']);
}
?>