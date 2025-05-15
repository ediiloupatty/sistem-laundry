<?php
// File: admin/kelola_pesanan.php
// Halaman untuk melihat detail dan mengelola pesanan

session_start();
require_once '../config/koneksi.php';
require_once '../includes/functions.php';
require_once '../includes/notification_helper.php'; // Tambahkan ini

// Cek akses admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$page_title = "Detail Pesanan";

// Jika tidak ada ID pesanan
if(!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$order_id = cleanInput($_GET['id']);

// Query untuk mendapatkan detail pesanan
$query_order = "SELECT o.*, c.nama as nama_pelanggan, c.alamat, c.no_hp, c.email
                FROM orders o 
                JOIN customers c ON o.customer_id = c.id 
                WHERE o.id = '$order_id'";
$result_order = mysqli_query($koneksi, $query_order);

if(mysqli_num_rows($result_order) == 0) {
    header("Location: dashboard.php");
    exit();
}

$order = mysqli_fetch_assoc($result_order);

// Query untuk mendapatkan detail item pesanan
$query_items = "SELECT od.*, s.nama_layanan 
                FROM order_details od
                JOIN services s ON od.service_id = s.id
                WHERE od.order_id = '$order_id'";
$result_items = mysqli_query($koneksi, $query_items);

// Query untuk mendapatkan informasi pembayaran
$query_payment = "SELECT * FROM payments WHERE order_id = '$order_id'";
$result_payment = mysqli_query($koneksi, $query_payment);
$payment = mysqli_fetch_assoc($result_payment);

// Handle update status pesanan
if (isset($_POST['update_status'])) {
    // Ambil order_id dari URL atau dari hidden input
    $order_id = isset($_POST['order_id']) ? $_POST['order_id'] : $_GET['id'];
    $new_status = $_POST['status'];
    
    // Query untuk mendapatkan informasi pesanan
    $order_query = "SELECT customer_id, status FROM orders WHERE id = '$order_id'";
    $order_result = mysqli_query($koneksi, $order_query);
    $order_data = mysqli_fetch_assoc($order_result);
    
    // Update status
    $update_query = "UPDATE orders SET status = '$new_status' WHERE id = '$order_id'";
    
    if (mysqli_query($koneksi, $update_query)) {
        // Dapatkan user_id dari customer_id
        $user_id = getUserIdFromCustomerId($order_data['customer_id']);
        
        if ($user_id) {
            // Buat pesan notifikasi berdasarkan status
            $status_messages = [
                'diproses' => 'Pesanan Anda sedang diproses',
                'selesai' => 'Pesanan Anda telah selesai',
                'siap_diantar' => 'Pesanan Anda siap diantar',
                'dibatalkan' => 'Pesanan Anda telah dibatalkan'
            ];
            
            $pesan = $status_messages[$new_status] ?? 'Status pesanan Anda telah diupdate';
            
            // Buat notifikasi
            createNotification($user_id, $pesan, 'status', $order_id);
        }
        
        $_SESSION['flash_message'] = "Status pesanan berhasil diupdate";
        $_SESSION['flash_message_type'] = "success";
        
        // Redirect untuk refresh halaman
        header("Location: kelola_pesanan.php?id=" . $order_id);
        exit();
    }
}

include '../includes/header.php';
?>

<style>
    /* Base styles */
    .container {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        width: 100%;
        max-width: 1200px;
        margin-left: auto;
        margin-right: auto;
        box-sizing: border-box;
    }
    .order-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #ddd;
        padding-bottom: 15px;
        margin-bottom: 15px;
        flex-wrap: wrap;
        gap: 10px;
    }
    .order-detail {
        margin-bottom: 20px;
    }
    .order-detail h3 {
        margin-top: 0;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
    }
    .customer-info {
        background-color: #f9f9f9;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    .item-row {
        padding: 10px 0;
        border-bottom: 1px solid #eee;
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
        background-color: #007bff;
        color: #fff;
    }
    .status-dibatalkan {
        background-color: #dc3545;
        color: #fff;
    }
    .btn {
        padding: 8px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        margin-right: 5px;
        margin-bottom: 5px;
        text-align: center;
    }
    .btn-primary {
        background-color: #007bff;
        color: white;
    }
    .btn-success {
        background-color: #28a745;
        color: white;
    }
    .btn-danger {
        background-color: #dc3545;
        color: white;
    }
    .form-group {
        margin-bottom: 15px;
    }
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }
    .form-control {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-sizing: border-box;
    }
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 15px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        min-width: 600px; /* Minimum width to ensure readability */
    }
    th, td {
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }
    .alert {
        padding: 10px 15px;
        border-radius: 4px;
        margin-bottom: 15px;
    }
    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .alert-error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .col-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    
    /* Action buttons container */
    .action-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    /* Responsive styles */
    @media (max-width: 768px) {
        .container {
            padding: 15px;
            border-radius: 0;
            box-shadow: none;
        }
        .col-2 {
            grid-template-columns: 1fr;
        }
        .order-header {
            flex-direction: column;
            align-items: flex-start;
        }
        h1 {
            font-size: 1.5rem;
            margin-top: 0;
        }
        .status-badge {
            margin-top: 10px;
        }
        .btn {
            width: 100%;
            margin-right: 0;
        }
        th, td {
            padding: 8px 5px;
            font-size: 0.9rem;
        }
        .table-responsive {
            margin-left: -15px;
            margin-right: -15px;
            padding: 0 15px;
            width: calc(100% + 30px);
        }
    }
    
    /* For very small screens */
    @media (max-width: 480px) {
        .container {
            padding: 10px;
        }
        th, td {
            padding: 6px 3px;
            font-size: 0.8rem;
        }
        .table-responsive {
            margin-left: -10px;
            margin-right: -10px;
            padding: 0 10px;
            width: calc(100% + 20px);
        }
    }
</style>

<div class="container">
    <div class="order-header">
        <div>
            <h1>Detail Pesanan #<?php echo $order_id; ?></h1>
            <p>Tanggal Order: <?php echo formatTanggal($order['tgl_order']); ?></p>
        </div>
        <div>
            <span class="status-badge status-<?php echo $order['status']; ?>">
                <?php echo ucwords(str_replace('_', ' ', $order['status'])); ?>
            </span>
        </div>
    </div>

    <?php if(isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_message_type']; ?>">
            <?php 
                echo $_SESSION['flash_message']; 
                unset($_SESSION['flash_message']);
                unset($_SESSION['flash_message_type']);
            ?>
        </div>
    <?php endif; ?>

    <div class="col-2">
        <div class="order-detail">
            <h3>Informasi Pelanggan</h3>
            <div class="customer-info">
                <p><strong>Nama:</strong> <?php echo $order['nama_pelanggan']; ?></p>
                <p><strong>Alamat:</strong> <?php echo $order['alamat']; ?></p>
                <p><strong>No. HP:</strong> <?php echo $order['no_hp']; ?></p>
                <p><strong>Email:</strong> <?php echo $order['email']; ?></p>
            </div>

            <h3>Informasi Pesanan</h3>
            <p><strong>Metode Antar:</strong> <?php echo ucfirst(str_replace('_', ' ', $order['metode_antar'])); ?></p>
            
            <?php if($order['metode_antar'] == 'jemput'): ?>
            <p><strong>Alamat Jemput:</strong> <?php echo $order['alamat_jemput']; ?></p>
            <?php endif; ?>
            
            <?php if(!empty($order['catatan'])): ?>
            <p><strong>Catatan:</strong> <?php echo $order['catatan']; ?></p>
            <?php endif; ?>
            
            <?php if(!empty($order['tgl_selesai'])): ?>
            <p><strong>Tanggal Selesai:</strong> <?php echo formatTanggal($order['tgl_selesai']); ?></p>
            <?php endif; ?>
        </div>

        <div class="order-detail">
            <h3>Status Pesanan</h3>
            <form method="POST">
                <div class="form-group">
                    <label for="status">Update Status:</label>
                    <select name="status" id="status" class="form-control">
                        <option value="menunggu_konfirmasi" <?php if($order['status'] == 'menunggu_konfirmasi') echo 'selected'; ?>>Menunggu Konfirmasi</option>
                        <option value="diproses" <?php if($order['status'] == 'diproses') echo 'selected'; ?>>Diproses</option>
                        <option value="selesai" <?php if($order['status'] == 'selesai') echo 'selected'; ?>>Selesai</option>
                        <option value="siap_diantar" <?php if($order['status'] == 'siap_diantar') echo 'selected'; ?>>Siap Diantar</option>
                        <option value="dibatalkan" <?php if($order['status'] == 'dibatalkan') echo 'selected'; ?>>Dibatalkan</option>
                    </select>
                </div>
                <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
            </form>

            <h3>Status Pembayaran</h3>
            
            <?php if(isset($payment)): ?>
            <div style="margin-top: 15px;">
                <p><strong>Status Pembayaran:</strong> 
                    <?php 
                        $statusLabels = [
                            'pending' => 'Menunggu Pembayaran',
                            'lunas' => 'Lunas',
                            'dibatalkan' => 'Dibatalkan',
                            'ditolak' => 'Ditolak'
                        ];
                        echo $statusLabels[$payment['status_pembayaran']] ?? ucfirst($payment['status_pembayaran']);
                    ?>
                </p>
                <p><strong>Metode Pembayaran:</strong> <?php echo ucfirst($payment['metode_pembayaran'] ?? '-'); ?></p>
                <p><strong>Jumlah Bayar:</strong> <?php echo formatRupiah($payment['jumlah_bayar'] ?? 0); ?></p>
                <p><strong>Tanggal Pembayaran:</strong> 
                    <?php echo !empty($payment['tgl_pembayaran']) ? formatTanggal($payment['tgl_pembayaran']) : '-'; ?>
                </p>
            </div>
            <?php else: ?>
            <p>Belum ada informasi pembayaran</p>
            <?php endif; ?>
            
            <p>Untuk konfirmasi pembayaran, silahkan akses halaman <a href="konfirmasi_pembayaran.php">Konfirmasi Pembayaran</a></p>
        </div>
    </div>

    <div class="order-detail">
        <h3>Detail Item</h3>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Layanan</th>
                        <th>Jenis Pakaian</th>
                        <th>Jumlah</th>
                        <th>Berat (kg)</th>
                        <th>Harga</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($item = mysqli_fetch_assoc($result_items)): ?>
                    <tr>
                        <td><?php echo $item['nama_layanan']; ?></td>
                        <td><?php echo $item['jenis_pakaian']; ?></td>
                        <td><?php echo $item['jumlah']; ?></td>
                        <td><?php echo $item['berat']; ?> kg</td>
                        <td><?php echo formatRupiah($item['harga']); ?></td>
                        <td><?php echo formatRupiah($item['subtotal']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <tr>
                        <td colspan="5" style="text-align: right;"><strong>Total</strong></td>
                        <td><strong><?php echo formatRupiah($order['total_harga']); ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="action-buttons">
        <a href="dashboard.php" class="btn btn-primary">Kembali ke Dashboard</a>
        <a href="cetak_invoice.php?id=<?php echo $order_id; ?>" target="_blank" class="btn btn-success">Cetak Invoice</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>