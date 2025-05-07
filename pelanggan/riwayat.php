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
```css
<style>
:root {
    --primary-color: #1a73e8;
    --primary-dark: #0d47a1;
    --secondary-color: #263238;
    --accent-color: #00c853;
    --light-gray: #f0f4f8;
    --mid-gray: #e1e8ed;
    --dark-gray: #546e7a;
    --danger: #d32f2f;
    --success: #00c853;
    --border-radius: 6px;
    --box-shadow: 0 6px 16px rgba(0,0,0,0.1);
    --transition: all 0.25s ease-in-out;
}

body {
    font-family: var(--font-family);
    line-height: 1.5;
    color: #5a5c69;
    background-color: var(--light-gray);
}

h1 {
    font-size: 1.8rem;
    font-weight: 700;
    color: #5a5c69;
    margin-bottom: 1.25rem;
    position: relative;
    padding-bottom: 0.6rem;
}

h1:after {
    content: '';
    position: absolute;
    left: 0;
    bottom: 0;
    height: 3px;
    width: 50px;
    background: var(--primary-color);
    border-radius: 2px;
}

/* Stat Cards */
.stat-box {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1.25rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 1.5rem;
    transition: var(--transition);
    box-shadow: var(--box-shadow);
    border-left: 4px solid var(--primary-color);
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.stat-card:nth-child(2) {
    border-left-color: var(--success);
}

.stat-card:nth-child(3) {
    border-left-color: var(--accent-color);
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
}

.stat-value {
    font-size: 1.8rem;
    font-weight: 700;
    color: #5a5c69;
    margin-bottom: 0.4rem;
    line-height: 1.2;
}

.stat-card:nth-child(1) .stat-value {
    color: var(--primary-color);
}

.stat-card:nth-child(2) .stat-value {
    color: var(--success);
}

.stat-card:nth-child(3) .stat-value {
    color: var(--accent-color);
}

.stat-label {
    color: var(--dark-gray);
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 500;
}

/* Filter Section */
.filter-container {
    background: white;
    padding: 1.5rem;
    border-radius: var(--border-radius);
    margin-bottom: 1.5rem;
    box-shadow: var(--box-shadow);
}

.filter-container h3 {
    margin-top: 0;
    margin-bottom: 1rem;
    font-size: 1.1rem;
    color: #5a5c69;
    font-weight: 600;
}

.filter-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1.25rem;
    align-items: end;
}

.form-group {
    margin-bottom: 0;
}

.form-group label {
    display: block;
    margin-bottom: 0.4rem;
    font-weight: 500;
    font-size: 0.85rem;
    color: var(--dark-gray);
}

.form-control {
    width: 100%;
    padding: 0.65rem 0.9rem;
    border: 1px solid var(--mid-gray);
    border-radius: var(--border-radius);
    transition: var(--transition);
    font-size: 0.9rem;
    background-color: var(--light-gray);
}

.form-control:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.1);
}

.btn {
    padding: 0.65rem 1.25rem;
    border-radius: var(--border-radius);
    font-weight: 600;
    font-size: 0.9rem;
    transition: var(--transition);
    cursor: pointer;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    text-decoration: none;
    box-shadow: 0 2px 10px rgba(52, 152, 219, 0.2);
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background-color: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
}

.btn-secondary {
    background-color: var(--mid-gray);
    color: var(--dark-gray);
}

.btn-secondary:hover {
    background-color: var(--dark-gray);
    color: white;
    transform: translateY(-2px);
}

/* Table Styles */
.riwayat-container {
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--box-shadow);
    margin-bottom: 1.5rem;
    background: white;
}

table {
    width: 100%;
    border-collapse: collapse;
}

table thead {
    background-color: var(--secondary-color);
}

table th {
    padding: 1rem 1.25rem;
    font-weight: 600;
    color: white;
    text-align: left;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

table td {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--mid-gray);
    font-size: 0.9rem;
    vertical-align: middle;
}

table tr:last-child td {
    border-bottom: none;
}

table tr:hover td {
    background: var(--light-gray);
}

/* Status Badges */
.status-badge {
    padding: 0.35rem 0.7rem;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    display: inline-flex;
    align-items: center;
    line-height: 1;
    white-space: nowrap;
    color: white;
}

.status-menunggu_konfirmasi {
    background-color: var(--warning);
}

.status-diproses {
    background-color: var(--info);
}

.status-selesai {
    background-color: var(--success);
}

.status-siap_diantar {
    background-color: #6f42c1;
}

.status-dibatalkan {
    background-color: var(--danger);
}

.status-lunas {
    background-color: var(--success);
}

.status-pending {
    background-color: var(--warning);
}

/* Action Links */
.action-link {
    padding: 0.4rem 0.7rem;
    border-radius: 5px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    transition: var(--transition);
    text-decoration: none;
    color: var(--primary-color);
    background: rgba(52, 152, 219, 0.08);
    margin-right: 0.4rem;
    margin-bottom: 0.4rem;
}

.action-link:hover {
    background: rgba(52, 152, 219, 0.15);
    transform: translateY(-2px);
}

.btn-review {
    background: rgba(246, 194, 62, 0.08);
    color: var(--accent-color);
    padding: 0.4rem 0.7rem;
    border-radius: 5px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    transition: var(--transition);
    text-decoration: none;
    margin-right: 0.4rem;
    margin-bottom: 0.4rem;
}

.btn-review:hover {
    background: rgba(246, 194, 62, 0.15);
    transform: translateY(-2px);
}

.btn-payment {
    background: rgba(28, 200, 138, 0.08);
    color: var(--success);
    padding: 0.4rem 0.7rem;
    border-radius: 5px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    transition: var(--transition);
    text-decoration: none;
    margin-right: 0.4rem;
    margin-bottom: 0.4rem;
}

.btn-payment:hover {
    background: rgba(28, 200, 138, 0.15);
    transform: translateY(-2px);
}

/* Alert Styles */
.alert {
    padding: 1rem;
    border-radius: var(--border-radius);
    margin-bottom: 1.25rem;
    position: relative;
    font-weight: 500;
    font-size: 0.9rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.alert-success {
    background: rgba(28, 200, 138, 0.08);
    border-left: 3px solid var(--success);
    color: var(--success);
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 2.5rem 1rem;
    color: var(--dark-gray);
}

.empty-state-icon {
    font-size: 2.5rem;
    margin-bottom: 0.8rem;
    opacity: 0.3;
}

.empty-state-text {
    font-size: 1rem;
    font-weight: 500;
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-fade {
    animation: fadeIn 0.5s ease-in-out;
}

/* Responsive Design */
@media (max-width: 992px) {
    .stat-box {
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    }
}

@media (max-width: 768px) {
    .filter-form {
        grid-template-columns: 1fr;
        gap: 0.8rem;
    }

    .riwayat-container {
        overflow-x: auto;
    }

    table {
        min-width: 800px;
    }

    .stat-card {
        padding: 1.25rem;
    }

    h1 {
        font-size: 1.6rem;
    }
    
    .form-group {
        margin-bottom: 0.6rem;
    }

    .filter-form button, 
    .filter-form a {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 576px) {
    .stat-box {
        grid-template-columns: 1fr;
    }

    table th, 
    table td {
        padding: 0.85rem;
        font-size: 0.85rem;
    }
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
<div class="container mt-4">
    <h1>Riwayat Pesanan</h1>

    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle mr-2"></i>
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
        <h3><i class="fas fa-filter mr-2"></i>Filter Pesanan</h3>
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
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search mr-1"></i> Filter
            </button>
            <a href="riwayat.php" class="btn btn-secondary">
                <i class="fas fa-redo-alt mr-1"></i> Reset
            </a>
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
                        <td class="order-id">#<?php echo $order['id']; ?></td>
                        <td class="order-date"><?php echo formatTanggal($order['tgl_order']); ?></td>
                        <td class="service-type"><?php echo $order['nama_layanan']; ?></td>
                        <td class="order-total"><?php echo formatRupiah($order['total_harga']); ?></td>
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
                                <?php echo ucfirst($payment_method); ?> - <?php echo ucfirst($payment_status); ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="tracking.php?id=<?php echo $order['id']; ?>" class="action-link">
                                    <i class="fas fa-search mr-1"></i> Detail
                                </a>
                                
                                <?php if($order['status'] == 'selesai'): ?>
                                    <a href="invoice.php?id=<?php echo $order['id']; ?>" class="action-link">
                                        <i class="fas fa-file-invoice mr-1"></i> Invoice
                                    </a>
                                    
                                    <?php
                                    // Cek apakah sudah direview
                                    $review_query = "SELECT id FROM reviews WHERE order_id = '{$order['id']}'";
                                    $review_result = mysqli_query($koneksi, $review_query);
                                    if(mysqli_num_rows($review_result) == 0):
                                    ?>
                                        <a href="review.php?id=<?php echo $order['id']; ?>" class="btn-review">
                                            <i class="fas fa-star mr-1"></i> Beri Ulasan
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php if($payment_status == 'pending' && isset($order['metode_pembayaran']) && $order['metode_pembayaran'] != 'cash' && !isset($order['bukti_pembayaran'])): ?>
                                    <a href="pembayaran.php?id=<?php echo $order['id']; ?>" class="btn-payment">
                                        <i class="fas fa-money-bill mr-1"></i> Bayar
                                    </a>
                                <?php endif; ?>
                                
                                <?php if($order['status'] == 'menunggu_konfirmasi'): ?>
                                    <a href="batalkan.php?id=<?php echo $order['id']; ?>" class="action-link" style="color: var(--danger); background: rgba(230, 57, 70, 0.08);"
                                    onclick="return confirm('Yakin ingin membatalkan pesanan ini?')">
                                        <i class="fas fa-times-circle mr-1"></i> Batalkan
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="fas fa-shopping-basket"></i>
                                </div>
                                <div class="empty-state-text">
                                    Belum ada riwayat pesanan
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
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