<?php
// File: ajax/get_notification_count.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Pastikan tidak ada output sebelum ini
ob_start();

session_start();

// Debug: Cek koneksi
if (!file_exists('../config/koneksi.php')) {
    echo json_encode(['success' => false, 'message' => 'File koneksi tidak ditemukan']);
    exit;
}

require_once '../config/koneksi.php';

// Debug: Cek koneksi database
if (!$koneksi) {
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal']);
    exit;
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pelanggan') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized - User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Query untuk mengambil notifikasi
$query = "SELECT * FROM notifications 
          WHERE user_id = '$user_id' 
          ORDER BY created_at DESC 
          LIMIT 20";

// Debug: tampilkan query
error_log("Query: " . $query);

$result = mysqli_query($koneksi, $query);

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($koneksi)]);
    exit;
}

$notifications = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Format tanggal
    try {
        $created_at = new DateTime($row['created_at']);
        $now = new DateTime();
        $interval = $now->diff($created_at);
        
        if ($interval->days > 0) {
            $time_ago = $interval->days . ' hari yang lalu';
        } elseif ($interval->h > 0) {
            $time_ago = $interval->h . ' jam yang lalu';
        } elseif ($interval->i > 0) {
            $time_ago = $interval->i . ' menit yang lalu';
        } else {
            $time_ago = 'Baru saja';
        }
        
        $row['created_at'] = $time_ago;
    } catch (Exception $e) {
        $row['created_at'] = $row['created_at'];
    }
    
    $notifications[] = $row;
}

// Bersihkan output buffer sebelum mengirim response
ob_end_clean();

echo json_encode([
    'success' => true,
    'notifications' => $notifications,
    'debug' => [
        'user_id' => $user_id,
        'count' => count($notifications)
    ]
]);
?>