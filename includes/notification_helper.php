<?php
// File: includes/notification_helper.php

function createNotification($user_id, $pesan, $tipe, $order_id = null) {
    global $koneksi;
    
    $user_id = mysqli_real_escape_string($koneksi, $user_id);
    $pesan = mysqli_real_escape_string($koneksi, $pesan);
    $tipe = mysqli_real_escape_string($koneksi, $tipe);
    $order_id = $order_id ? mysqli_real_escape_string($koneksi, $order_id) : 'NULL';
    
    $query = "INSERT INTO notifications (user_id, pesan, tipe, order_id, is_read, created_at) 
              VALUES ('$user_id', '$pesan', '$tipe', $order_id, 0, NOW())";
    
    return mysqli_query($koneksi, $query);
}

// Fungsi untuk mendapatkan user_id dari customer_id
function getUserIdFromCustomerId($customer_id) {
    global $koneksi;
    
    $query = "SELECT u.id as user_id 
              FROM users u 
              JOIN customers c ON u.id = c.user_id 
              WHERE c.id = '$customer_id'";
    
    $result = mysqli_query($koneksi, $query);
    if ($row = mysqli_fetch_assoc($result)) {
        return $row['user_id'];
    }
    return null;
}
?>