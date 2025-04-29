<?php
// File: index.php
// Halaman utama sistem laundry

session_start();

// Jika sudah login, redirect ke dashboard sesuai role
if(isset($_SESSION['user_id'])) {
    if($_SESSION['role'] == 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: pelanggan/dashboard.php");
    }
    exit();
}

// Jika belum login, redirect ke halaman login
header("Location: login.php");
exit();
?>