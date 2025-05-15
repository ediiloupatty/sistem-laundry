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

// Menambahkan meta viewport untuk responsive
$add_header = '<meta name="viewport" content="width=device-width, initial-scale=1.0">';

include '../includes/header.php';
?>

<div class="container-fluid dashboard-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center dashboard-header">
                <h1 class="dashboard-title">Dashboard Admin</h1>
                <div class="d-none d-md-block">
                    <span class="current-date"><?php echo date('l, d F Y'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="row stats-container">
        <div class="col-12 col-sm-6 col-lg-3 mb-4">
            <div class="dashboard-card primary">
                <div class="card-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="card-content">
                    <h3>Pesanan Hari Ini</h3>
                    <div class="value"><?php echo $today_orders; ?></div>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-sm-6 col-lg-3 mb-4">
            <div class="dashboard-card success">
                <div class="card-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="card-content">
                    <h3>Pendapatan Hari Ini</h3>
                    <div class="value"><?php echo formatRupiah($today_revenue); ?></div>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-sm-6 col-lg-3 mb-4">
            <div class="dashboard-card warning">
                <div class="card-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="card-content">
                    <h3>Menunggu Konfirmasi</h3>
                    <div class="value"><?php echo $pending_orders; ?></div>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-sm-6 col-lg-3 mb-4">
            <div class="dashboard-card info">
                <div class="card-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="card-content">
                    <h3>Total Pelanggan</h3>
                    <div class="value"><?php echo $total_customers; ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="table-card">
                <div class="table-header">
                    <h2>Pesanan Terbaru</h2>
                    <a href="kelola_pesanan.php" class="btn btn-sm btn-primary">Lihat Semua</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
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
                            <?php if(mysqli_num_rows($result_latest) > 0): ?>
                                <?php while($order = mysqli_fetch_assoc($result_latest)): ?>
                                <tr>
                                    <td><strong>#<?php echo $order['id']; ?></strong></td>
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
                                        class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye mr-1"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">Tidak ada pesanan terbaru</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Dashboard styles */
    .dashboard-container {
        padding: 1.5rem;
    }
    
    .dashboard-header {
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #e9ecef;
    }
    
    .dashboard-title {
        margin: 0;
        font-size: 1.75rem;
        font-weight: 600;
        color: #343a40;
    }
    
    .current-date {
        color: #6c757d;
        font-size: 0.9rem;
    }
    
    .dashboard-card {
        display: flex;
        align-items: center;
        background: white;
        padding: 1.25rem;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        height: 100%;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        overflow: hidden;
        position: relative;
    }
    
    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 12px rgba(0,0,0,0.1);
    }
    
    .card-icon {
        font-size: 2rem;
        margin-right: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background-color: rgba(0,0,0,0.05);
    }
    
    .dashboard-card.primary {
        border-left: 4px solid #007bff;
    }
    
    .dashboard-card.primary .card-icon {
        color: #007bff;
    }
    
    .dashboard-card.success {
        border-left: 4px solid #28a745;
    }
    
    .dashboard-card.success .card-icon {
        color: #28a745;
    }
    
    .dashboard-card.warning {
        border-left: 4px solid #ffc107;
    }
    
    .dashboard-card.warning .card-icon {
        color: #ffc107;
    }
    
    .dashboard-card.info {
        border-left: 4px solid #17a2b8;
    }
    
    .dashboard-card.info .card-icon {
        color: #17a2b8;
    }
    
    .card-content {
        flex: 1;
    }
    
    .dashboard-card h3 {
        margin: 0;
        color: #6c757d;
        font-size: 0.875rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .dashboard-card .value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #343a40;
        margin-top: 0.25rem;
    }
    
    .table-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        overflow: hidden;
        margin-bottom: 2rem;
    }
    
    .table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #e9ecef;
    }
    
    .table-header h2 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
        color: #343a40;
    }
    
    .table {
        margin-bottom: 0;
    }
    
    .table th {
        border-top: none;
        font-weight: 600;
        color: #495057;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
    }
    
    .table td {
        vertical-align: middle;
    }
    
    .status-badge {
        display: inline-block;
        padding: 0.35rem 0.75rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .status-menunggu_konfirmasi {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .status-diproses {
        background-color: #d1ecf1;
        color: #0c5460;
    }
    
    .status-selesai {
        background-color: #d4edda;
        color: #155724;
    }
    
    .status-dibatalkan {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    /* Responsive adjustments */
    @media (max-width: 767.98px) {
        .dashboard-container {
            padding: 1rem;
        }
        
        .dashboard-title {
            font-size: 1.5rem;
        }
        
        .dashboard-card {
            padding: 1rem;
        }
        
        .card-icon {
            width: 50px;
            height: 50px;
            font-size: 1.5rem;
        }
        
        .dashboard-card .value {
            font-size: 1.25rem;
        }
        
        .table th, .table td {
            padding: 0.75rem;
        }
        
        .status-badge {
            padding: 0.25rem 0.5rem;
            font-size: 0.7rem;
        }
    }
    
    @media (max-width: 575.98px) {
        .table-responsive {
            border: 0;
        }
        
        .table-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .table-header a {
            margin-top: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
    }
</style>

<?php include '../includes/footer.php'; ?>