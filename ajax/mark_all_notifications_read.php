<?php
// File: ajax/mark_all_notifications_read.php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pelanggan') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

$query = "UPDATE notifications 
          SET is_read = 1 
          WHERE user_id = '$user_id'";

if (mysqli_query($koneksi, $query)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => mysqli_error($koneksi)]);
}
?>