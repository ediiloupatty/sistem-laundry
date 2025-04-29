<?php
// File: admin/dashboard.php
// Dashboard untuk admin

session_start();
require_once '../config/koneksi.php';
require_once '../includes/functions.php';

// Cek apakah user adalah admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$page_title = "Dashboard Admin";

// Query untuk mendapatkan statistik
// Total pesanan hari ini
$query_today = "SELECT COUNT(*) as total FROM orders WHERE DATE(tgl_order) = CURDATE()";
$result_today = mysqli_query($koneksi, $query_today);
$today_orders = mysqli_fetch_assoc($result_today)['total'];

// Total pendapatan hari ini
$query_revenue = "SELECT SUM(total_harga) as total FROM orders WHERE DATE(tgl_order) = CURDATE() AND status != 'dibatalkan'";
$result_revenue = mysqli_query($koneksi, $query_revenue);
$today_revenue = mysqli_fetch_assoc($result_revenue)['total'] ?? 0;

// Pesanan yang perlu diproses
$query_pending = "SELECT COUNT(*) as total FROM orders WHERE status = 'menunggu_konfirmasi'";
$result_pending = mysqli_query($koneksi, $query_pending);
$pending_orders = mysqli_fetch_assoc($result_pending)['total'];

// Total pelanggan
$query_customers = "SELECT COUNT(*) as total FROM customers";
$result_customers = mysqli_query($koneksi, $query_customers);
$total_customers = mysqli_fetch_assoc($result_customers)['total'];

// Pesanan terbaru
$query_latest = "SELECT o.*, c.nama as nama_pelanggan 
                 FROM orders o 
                 JOIN customers c ON o.customer_id = c.id 
                 ORDER BY o.tgl_order DESC 
                 LIMIT 5";
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
    .dashboard-card.success {
        border-left: 4px solid #28a745;
    }
    .dashboard-card.warning {
        border-left: 4px solid #ffc107;
    }
    .dashboard-card.info {
        border-left: 4px solid #17a2b8;
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
</style>

<h1>Dashboard Admin</h1>

<div class="dashboard-grid">
    <div class="dashboard-card primary">
        <h3>Pesanan Hari Ini</h3>
        <div class="value"><?php echo $today_orders; ?></div>
    </div>
    
    <div class="dashboard-card success">
        <h3>Pendapatan Hari Ini</h3>
        <div class="value"><?php echo formatRupiah($today_revenue); ?></div>
    </div>
    
    <div class="dashboard-card warning">
        <h3>Menunggu Konfirmasi</h3>
        <div class="value"><?php echo $pending_orders; ?></div>
    </div>
    
    <div class="dashboard-card info">
        <h3>Total Pelanggan</h3>
        <div class="value"><?php echo $total_customers; ?></div>
    </div>
</div>

<h2>Pesanan Terbaru</h2>
<table>
    <thead>
        <tr>
            <th>ID Pesanan</th>
            <th>Pelanggan</th>
            <th>Tanggal</th>
            <th>Total</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php while($order = mysqli_fetch_assoc($result_latest)): ?>
        <tr>
            <td>#<?php echo $order['id']; ?></td>
            <td><?php echo $order['nama_pelanggan']; ?></td>
            <td><?php echo formatTanggal($order['tgl_order']); ?></td>
            <td><?php echo formatRupiah($order['total_harga']); ?></td>
            <td>
                <span class="status-badge status-<?php echo $order['status']; ?>">
                    <?php echo ucwords(str_replace('_', ' ', $order['status'])); ?>
                </span>
            </td>
            <td>
                <a href="kelola_pesanan.php?id=<?php echo $order['id']; ?>" 
                   style="color: #007bff; text-decoration: none;">Detail</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php include '../includes/footer.php'; ?>