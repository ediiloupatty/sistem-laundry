<?php
// File: pelanggan/riwayat.php
// Halaman riwayat pesanan pelanggan dengan informasi pembayaran lengkap

session_start();
require_once '../config/koneksi.php';
require_once '../includes/functions.php';

// Cek apakah user adalah pelanggan
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pelanggan') {
    header("Location: ../login.php");
    exit();
}

$page_title = "Riwayat Pesanan";
$customer_id = $_SESSION['customer_id'];

// Filter berdasarkan status atau tanggal
$where_clause = "WHERE o.customer_id = '$customer_id'";
$status_filter = "";
$date_filter = "";
$payment_filter = "";

if(isset($_GET['status']) && $_GET['status'] != '') {
    $status_filter = cleanInput($_GET['status']);
    $where_clause .= " AND o.status = '$status_filter'";
}

if(isset($_GET['payment_status']) && $_GET['payment_status'] != '') {
    $payment_filter = cleanInput($_GET['payment_status']);
    $where_clause .= " AND p.status_pembayaran = '$payment_filter'";
}

if(isset($_GET['start_date']) && $_GET['start_date'] != '') {
    $start_date = cleanInput($_GET['start_date']);
    $where_clause .= " AND DATE(o.tgl_order) >= '$start_date'";
}

if(isset($_GET['end_date']) && $_GET['end_date'] != '') {
    $end_date = cleanInput($_GET['end_date']);
    $where_clause .= " AND DATE(o.tgl_order) <= '$end_date'";
}

// Query untuk mendapatkan riwayat pesanan
$query = "SELECT o.*, p.status_pembayaran, p.metode_pembayaran, p.bukti_pembayaran,
          (SELECT nama_layanan FROM services s JOIN order_details od ON s.id = od.service_id WHERE od.order_id = o.id LIMIT 1) as nama_layanan
          FROM orders o
          LEFT JOIN payments p ON o.id = p.order_id
          $where_clause
          ORDER BY o.tgl_order DESC";
$result = mysqli_query($koneksi, $query);

include '../includes/header.php';
?>

<style>
    .stat-box {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20px;
    }
    .stat-card {
        background-color: #f8f9fa;
        border-radius: 5px;
        padding: 15px;
        flex: 1;
        margin: 0 10px;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .stat-value {
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 5px;
    }
    .stat-label {
        color: #6c757d;
    }
    .filter-container {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    .filter-form {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: flex-end;
    }
    .form-group {
        flex: 1;
        min-width: 200px;
    }
    .form-control {
        width: 100%;
        padding: 8px;
        border: 1px solid #ced4da;
        border-radius: 4px;
    }
    .btn {
        padding: 8px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    .btn-primary {
        background-color: #007bff;
        color: white;
    }
    .btn-secondary {
        background-color: #6c757d;
        color: white;
    }
    .riwayat-container {
        overflow-x: auto;
    }
    table {
        width: 100%;
        border-collapse: collapse;
    }
    table th, table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }
    table th {
        background-color: #f8f9fa;
    }
    .status-badge {
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: bold;
        display: inline-block;
    }
    .status-menunggu_konfirmasi {
        background-color: #17a2b8;
        color: white;
    }
    .status-diproses {
        background-color: #ffc107;
        color: #212529;
    }
    .status-selesai {
        background-color: #28a745;
        color: white;
    }
    .status-siap_diantar {
        background-color: #6f42c1;
        color: white;
    }
    .status-dibatalkan {
        background-color: #dc3545;
        color: white;
    }
    .status-lunas {
        background-color: #28a745;
        color: white;
    }
    .status-pending {
        background-color: #ffc107;
        color: #212529;
    }
    .payment-method {
        font-size: 11px;
        color: #6c757d;
    }
    .action-link {
        margin-right: 5px;
        color: #007bff;
        text-decoration: none;
    }
    .btn-review, .btn-payment {
        display: inline-block;
        padding: 3px 8px;
        font-size: 12px;
        border-radius: 3px;
        margin-top: 3px;
        text-decoration: none;
    }
    .btn-review {
        background-color: #17a2b8;
        color: white;
    }
    .btn-payment {
        background-color: #28a745;
        color: white;
    }
    .alert-success {
        background-color: #d4edda;
        color: #155724;
        padding: 10px;
        border-radius: 4px;
        margin-bottom: 15px;
    }
</style>

<h1>Riwayat Pesanan</h1>

<?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <?php 
        echo $_SESSION['success'];
        unset($_SESSION['success']);
        ?>
    </div>
<?php endif; ?>

<!-- Statistik Ringkasan -->
<?php
$stat_query = "SELECT 
               COUNT(*) as total_pesanan,
               SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as pesanan_selesai,
               SUM(total_harga) as total_pengeluaran
               FROM orders
               WHERE customer_id = '$customer_id'";
$stat_result = mysqli_query($koneksi, $stat_query);
$stats = mysqli_fetch_assoc($stat_result);
?>

<div class="stat-box">
    <div class="stat-card">
        <div class="stat-value"><?php echo $stats['total_pesanan']; ?></div>
        <div class="stat-label">Total Pesanan</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $stats['pesanan_selesai']; ?></div>
        <div class="stat-label">Pesanan Selesai</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo formatRupiah($stats['total_pengeluaran']); ?></div>
        <div class="stat-label">Total Pengeluaran</div>
    </div>
</div>

<!-- Filter -->
<div class="filter-container">
    <form method="GET" class="filter-form">
        <div class="form-group">
            <label>Status Pesanan:</label>
            <select name="status" class="form-control">
                <option value="">Semua Status</option>
                <option value="menunggu_konfirmasi" <?php echo $status_filter == 'menunggu_konfirmasi' ? 'selected' : ''; ?>>Menunggu Konfirmasi</option>
                <option value="diproses" <?php echo $status_filter == 'diproses' ? 'selected' : ''; ?>>Diproses</option>
                <option value="selesai" <?php echo $status_filter == 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                <option value="siap_diantar" <?php echo $status_filter == 'siap_diantar' ? 'selected' : ''; ?>>Siap Diantar</option>
                <option value="dibatalkan" <?php echo $status_filter == 'dibatalkan' ? 'selected' : ''; ?>>Dibatalkan</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Status Pembayaran:</label>
            <select name="payment_status" class="form-control">
                <option value="">Semua Pembayaran</option>
                <option value="pending" <?php echo $payment_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="lunas" <?php echo $payment_filter == 'lunas' ? 'selected' : ''; ?>>Lunas</option>
                <option value="dibatalkan" <?php echo $payment_filter == 'dibatalkan' ? 'selected' : ''; ?>>Dibatalkan</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Dari Tanggal:</label>
            <input type="date" name="start_date" class="form-control" value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : ''; ?>">
        </div>
        
        <div class="form-group">
            <label>Sampai Tanggal:</label>
            <input type="date" name="end_date" class="form-control" value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : ''; ?>">
        </div>
        
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="riwayat.php" class="btn btn-secondary">Reset</a>
    </form>
</div>

<!-- Tabel Riwayat -->
<div class="riwayat-container">
    <table>
        <thead>
            <tr>
                <th>ID Pesanan</th>
                <th>Tanggal</th>
                <th>Layanan</th>
                <th>Total</th>
                <th>Status Pesanan</th>
                <th>Status Pembayaran</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if(mysqli_num_rows($result) > 0): ?>
                <?php while($order = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td>#<?php echo $order['id']; ?></td>
                    <td><?php echo formatTanggal($order['tgl_order']); ?></td>
                    <td><?php echo $order['nama_layanan']; ?></td>
                    <td><?php echo formatRupiah($order['total_harga']); ?></td>
                    <td>
                        <span class="status-badge status-<?php echo $order['status']; ?>">
                            <?php echo formatStatus($order['status']); ?>
                        </span>
                    </td>
                    <td>
                        <?php 
                        // Check if payment status exists and is not empty
                        $payment_status = isset($order['status_pembayaran']) && !empty($order['status_pembayaran']) 
                                        ? $order['status_pembayaran'] 
                                        : 'pending';
                        
                        // Get payment method
                        $payment_method = isset($order['metode_pembayaran']) && !empty($order['metode_pembayaran']) 
                                        ? strtolower($order['metode_pembayaran']) 
                                        : 'n/a';
                        
                        // Combine method and status
                        $combined_status = $payment_method . '-' . $payment_status;
                        
                        // Define status classes based on payment status
                        $status_class = '';
                        switch($payment_status) {
                            case 'lunas':
                                $status_class = 'status-lunas';
                                break;
                            case 'pending':
                                $status_class = 'status-pending';
                                break;
                            case 'dibatalkan':
                                $status_class = 'status-dibatalkan';
                                break;
                            default:
                                $status_class = 'status-pending';
                        }
                        ?>
                        <span class="status-badge <?php echo $status_class; ?>">
                            <?php echo $combined_status; ?>
                        </span>
                    </td>
                    <td>
                        <a href="tracking.php?id=<?php echo $order['id']; ?>" class="action-link">Detail</a>
                        
                        <?php if($order['status'] == 'selesai'): ?>
                            <a href="invoice.php?id=<?php echo $order['id']; ?>" class="action-link">Invoice</a>
                            
                            <?php
                            // Cek apakah sudah direview
                            $review_query = "SELECT id FROM reviews WHERE order_id = '{$order['id']}'";
                            $review_result = mysqli_query($koneksi, $review_query);
                            if(mysqli_num_rows($review_result) == 0):
                            ?>
                                <a href="review.php?id=<?php echo $order['id']; ?>" class="btn btn-review">Beri Ulasan</a>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if($payment_status == 'pending' && isset($order['metode_pembayaran']) && $order['metode_pembayaran'] != 'cash' && !isset($order['bukti_pembayaran'])): ?>
                            <a href="pembayaran.php?id=<?php echo $order['id']; ?>" class="btn-payment">Bayar</a>
                        <?php endif; ?>
                        
                        <?php if($order['status'] == 'menunggu_konfirmasi'): ?>
                            <a href="batalkan.php?id=<?php echo $order['id']; ?>" class="action-link" 
                               onclick="return confirm('Yakin ingin membatalkan pesanan ini?')" style="color: #dc3545;">Batalkan</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center;">Belum ada riwayat pesanan</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
// Tambahkan ini ke includes/functions.php jika belum ada
function formatStatus($status) {
    $status_labels = [
        'menunggu_konfirmasi' => 'Menunggu Konfirmasi',
        'diproses' => 'Diproses',
        'selesai' => 'Selesai',
        'siap_diantar' => 'Siap Diantar',
        'dibatalkan' => 'Dibatalkan'
    ];
    
    return isset($status_labels[$status]) ? $status_labels[$status] : ucfirst($status);
}
?>

<?php include '../includes/footer.php'; ?>