<?php
// File: config/koneksi.php
// Koneksi ke database

$host = "localhost";
$user = "root";
$pass = "";
$db = "db_laundry";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if(!$koneksi){
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Fungsi untuk mencegah SQL Injection
function cleanInput($input) {
    global $koneksi;
    return mysqli_real_escape_string($koneksi, htmlspecialchars(trim($input)));
}

// Fungsi untuk cek login
function cekLogin() {
    session_start();
    if(!isset($_SESSION['user_id'])) {
        header("Location: ../login.php");
        exit();
    }
}

// Fungsi untuk cek role
function cekRole($required_role) {
    if($_SESSION['role'] != $required_role) {
        header("Location: ../index.php");
        exit();
    }
}
?>