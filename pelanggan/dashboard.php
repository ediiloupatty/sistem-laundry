<?php
// File: pelanggan/dashboard.php
// Dashboard untuk pelanggan

session_start();
require_once '../config/koneksi.php';
require_once '../includes/functions.php';

// Cek apakah user adalah pelanggan
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pelanggan') {
    header("Location: ../login.php");
    exit();
}

$page_title = "Dashboard Pelanggan";
$customer_id = $_SESSION['customer_id'];

// Query untuk mendapatkan statistik
// Total pesanan pelanggan
$query_total = "SELECT COUNT(*) as total FROM orders WHERE customer_id = '$customer_id'";
$result_total = mysqli_query($koneksi, $query_total);
$total_orders = mysqli_fetch_assoc($result_total)['total'];

// Pesanan dalam proses
$query_process = "SELECT COUNT(*) as total FROM orders WHERE customer_id = '$customer_id' AND status IN ('menunggu_konfirmasi', 'diproses')";
$result_process = mysqli_query($koneksi, $query_process);
$process_orders = mysqli_fetch_assoc($result_process)['total'];

// Total pengeluaran
$query_spending = "SELECT SUM(total_harga) as total FROM orders WHERE customer_id = '$customer_id' AND status != 'dibatalkan'";
$result_spending = mysqli_query($koneksi, $query_spending);
$total_spending = mysqli_fetch_assoc($result_spending)['total'] ?? 0;

// Pesanan terbaru
$query_latest = "SELECT * FROM orders WHERE customer_id = '$customer_id' ORDER BY tgl_order DESC LIMIT 5";
$result_latest = mysqli_query($koneksi, $query_latest);

include '../includes/header.php';
?>

<style>
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .dashboard-card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .dashboard-card h3 {
        margin: 0 0 10px 0;
        color: #666;
        font-size: 16px;
    }
    .dashboard-card .value {
        font-size: 24px;
        font-weight: bold;
        color: #333;
    }
    .dashboard-card.primary {
        border-left: 4px solid #007bff;
    }
    .dashboard-card.warning {
        border-left: 4px solid #ffc107;
    }
    .dashboard-card.success {
        border-left: 4px solid #28a745;
    }
    .btn {
        display: inline-block;
        padding: 10px 20px;
        background-color: #007bff;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        margin-bottom: 20px;
    }
    .btn:hover {
        background-color: #0056b3;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        margin-top: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    th, td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }
    th {
        background-color: #f8f9fa;
        font-weight: bold;
    }
    .status-badge {
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: bold;
    }
    .status-menunggu_konfirmasi {
        background-color: #ffc107;
        color: #000;
    }
    .status-diproses {
        background-color: #17a2b8;
        color: #fff;
    }
    .status-selesai {
        background-color: #28a745;
        color: #fff;
    }
    .status-siap_diantar {
        background-color: #6f42c1;
        color: #fff;
    }
    .status-dibatalkan {
        background-color: #dc3545;
        color: #fff;
    }
</style>

<h1>Dashboard Pelanggan</h1>

<a href="buat_pesanan.php" class="btn">Buat Pesanan Baru</a>

<div class="dashboard-grid">
    <div class="dashboard-card primary">
        <h3>Total Pesanan</h3>
        <div class="value"><?php echo $total_orders; ?></div>
    </div>
    
    <div class="dashboard-card warning">
        <h3>Pesanan Dalam Proses</h3>
        <div class="value"><?php echo $process_orders; ?></div>
    </div>
    
    <div class="dashboard-card success">
        <h3>Total Pengeluaran</h3>
        <div class="value"><?php echo formatRupiah($total_spending); ?></div>
    </div>
</div>

<h2>Pesanan Terbaru</h2>
<table>
    <thead>
        <tr>
            <th>ID Pesanan</th>
            <th>Tanggal</th>
            <th>Total</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php if(mysqli_num_rows($result_latest) > 0): ?>
            <?php while($order = mysqli_fetch_assoc($result_latest)): ?>
            <tr>
                <td>#<?php echo $order['id']; ?></td>
                <td><?php echo formatTanggal($order['tgl_order']); ?></td>
                <td><?php echo formatRupiah($order['total_harga']); ?></td>
                <td>
                    <span class="status-badge status-<?php echo $order['status']; ?>">
                        <?php echo ucwords(str_replace('_', ' ', $order['status'])); ?>
                    </span>
                </td>
                <td>
                    <a href="tracking.php?id=<?php echo $order['id']; ?>" 
                       style="color: #007bff; text-decoration: none;">Detail</a>
                    <?php if($order['status'] == 'selesai'): ?>
                        | <a href="invoice.php?id=<?php echo $order['id']; ?>" 
                             style="color: #28a745; text-decoration: none;">Invoice</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="5" style="text-align: center;">Belum ada pesanan</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php include '../includes/footer.php'; ?>