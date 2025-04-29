<?php
// File: pelanggan/tracking.php
// Halaman untuk tracking status pesanan dengan informasi pembayaran

session_start();
require_once '../config/koneksi.php';
require_once '../includes/functions.php';

// Cek apakah user adalah pelanggan
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pelanggan') {
    header("Location: ../login.php");
    exit();
}

$page_title = "Tracking Pesanan";
$customer_id = $_SESSION['customer_id'];

// Cek jika ada ID pesanan spesifik
$order_id = isset($_GET['id']) ? cleanInput($_GET['id']) : null;

if($order_id) {
    // Ambil detail pesanan spesifik
    $query = "SELECT o.*, p.status_pembayaran, p.metode_pembayaran, p.tgl_pembayaran, p.bukti_pembayaran,
              (SELECT nama_layanan FROM services s JOIN order_details od ON s.id = od.service_id WHERE od.order_id = o.id LIMIT 1) as nama_layanan
              FROM orders o
              LEFT JOIN payments p ON o.id = p.order_id
              WHERE o.id = '$order_id' AND o.customer_id = '$customer_id'";
    $result = mysqli_query($koneksi, $query);
    $order = mysqli_fetch_assoc($result);
    
    if(!$order) {
        header("Location: riwayat.php");
        exit();
    }
    
    // Ambil detail item pesanan
    $items_query = "SELECT od.*, s.nama_layanan
                    FROM order_details od
                    JOIN services s ON od.service_id = s.id
                    WHERE od.order_id = '$order_id'";
    $items_result = mysqli_query($koneksi, $items_query);
} else {
    // Tampilkan pesanan yang sedang aktif
    $query = "SELECT o.*, p.status_pembayaran, p.metode_pembayaran,
              (SELECT nama_layanan FROM services s JOIN order_details od ON s.id = od.service_id WHERE od.order_id = o.id LIMIT 1) as nama_layanan
              FROM orders o
              LEFT JOIN payments p ON o.id = p.order_id
              WHERE o.customer_id = '$customer_id' 
              AND o.status NOT IN ('selesai', 'dibatalkan')
              ORDER BY o.tgl_order DESC";
    $result = mysqli_query($koneksi, $query);
}

include '../includes/header.php';
?>

<style>
    .tracking-container {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    .status-timeline {
        display: flex;
        justify-content: space-between;
        margin: 30px 0;
        position: relative;
    }
    .status-timeline::before {
        content: '';
        position: absolute;
        top: 15px;
        left: 0;
        width: 100%;
        height: 4px;
        background: #eee;
        z-index: 1;
    }
    .status-item {
        text-align: center;
        position: relative;
        z-index: 2;
        flex: 1;
    }
    .status-circle {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: #eee;
        margin: 0 auto 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: white;
    }
    .status-item.active .status-circle {
        background: #28a745;
    }
    .status-item.current .status-circle {
        background: #007bff;
        animation: pulse 1.5s infinite;
    }
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    .status-name {
        font-size: 12px;
        color: #666;
    }
    .detail-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    .detail-item {
        margin-bottom: 15px;
    }
    .detail-label {
        font-weight: bold;
        color: #666;
        margin-bottom: 5px;
    }
    .detail-value {
        color: #333;
    }
    .status-badge {
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: bold;
    }
    .status-menunggu_konfirmasi { background-color: #ffc107; color: #000; }
    .status-diproses { background-color: #17a2b8; color: #fff; }
    .status-selesai { background-color: #28a745; color: #fff; }
    .status-siap_diantar { background-color: #6f42c1; color: #fff; }
    .status-dibatalkan { background-color: #dc3545; color: #fff; }
    .status-pending { background-color: #ffc107; color: #000; }
    .status-lunas { background-color: #28a745; color: #fff; }
    .payment-info {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-top: 20px;
    }
    .payment-alert {
        background: #fff3cd;
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
        border: 1px solid #ffeeba;
    }
    .btn-payment {
        display: inline-block;
        padding: 8px 15px;
        background-color: #28a745;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        margin-top: 10px;
    }
    .btn-payment:hover {
        background-color: #218838;
    }
    .back-button {
        display: inline-block;
        padding: 8px 15px;
        background-color: #6c757d;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        margin-bottom: 20px;
    }
    .back-button:hover {
        background-color: #5a6268;
    }
    .bukti-container {
        margin-top: 10px;
    }
    .bukti-image {
        max-width: 200px;
        cursor: pointer;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
</style>

<h1>Tracking Pesanan</h1>

<?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <?php 
        echo $_SESSION['success'];
        unset($_SESSION['success']);
        ?>
    </div>
<?php endif; ?>

<?php if($order_id && $order): ?>
    <a href="tracking.php" class="back-button">← Kembali ke Semua Pesanan</a>
    
    <?php if($order['status_pembayaran'] == 'pending' && $order['metode_pembayaran'] != 'cash' && !$order['bukti_pembayaran']): ?>
    <div class="payment-alert">
        <strong>Perhatian!</strong> Silakan lakukan pembayaran untuk memproses pesanan Anda.
        <a href="pembayaran.php?id=<?php echo $order_id; ?>" class="btn-payment">Lakukan Pembayaran</a>
    </div>
    <?php endif; ?>
    
    <div class="tracking-container">
        <h2>Detail Pesanan #<?php echo $order_id; ?></h2>
        
        <!-- Status Timeline -->
        <div class="status-timeline">
            <?php
            $status_flow = ['menunggu_konfirmasi', 'diproses', 'selesai', 'siap_diantar'];
            $current_status_index = array_search($order['status'], $status_flow);
            
            if($order['status'] == 'dibatalkan') {
                echo '<div class="status-item current">';
                echo '<div class="status-circle">X</div>';
                echo '<div class="status-name">Dibatalkan</div>';
                echo '</div>';
            } else {
                foreach($status_flow as $index => $status) {
                    $class = '';
                    if($index < $current_status_index) {
                        $class = 'active';
                    } elseif($index == $current_status_index) {
                        $class = 'current';
                    }
                    
                    echo '<div class="status-item ' . $class . '">';
                    echo '<div class="status-circle">' . ($index + 1) . '</div>';
                    echo '<div class="status-name">' . ucwords(str_replace('_', ' ', $status)) . '</div>';
                    echo '</div>';
                }
            }
            ?>
        </div>
        
        <div class="detail-grid">
            <div>
                <div class="detail-item">
                    <div class="detail-label">Tanggal Pesanan</div>
                    <div class="detail-value"><?php echo formatTanggal($order['tgl_order']); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Status</div>
                    <div class="detail-value">
                        <span class="status-badge status-<?php echo $order['status']; ?>">
                            <?php echo ucwords(str_replace('_', ' ', $order['status'])); ?>
                        </span>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Metode Antar</div>
                    <div class="detail-value"><?php echo ucfirst(str_replace('_', ' ', $order['metode_antar'])); ?></div>
                </div>
            </div>
            
            <div>
                <div class="detail-item">
                    <div class="detail-label">Total Harga</div>
                    <div class="detail-value"><?php echo formatRupiah($order['total_harga']); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Metode Pembayaran</div>
                    <div class="detail-value"><?php echo strtoupper($order['metode_pembayaran']); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Status Pembayaran</div>
                    <div class="detail-value">
                        <span class="status-badge status-<?php echo $order['status_pembayaran']; ?>">
                            <?php echo ucfirst($order['status_pembayaran']); ?>
                        </span>
                    </div>
                </div>
                
                <?php if($order['tgl_selesai']): ?>
                <div class="detail-item">
                    <div class="detail-label">Tanggal Selesai</div>
                    <div class="detail-value"><?php echo formatTanggal($order['tgl_selesai']); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <h3>Detail Item</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="text-align: left; padding: 8px; border-bottom: 2px solid #ddd;">Layanan</th>
                    <th style="text-align: left; padding: 8px; border-bottom: 2px solid #ddd;">Jenis Pakaian</th>
                    <th style="text-align: left; padding: 8px; border-bottom: 2px solid #ddd;">Berat</th>
                    <th style="text-align: right; padding: 8px; border-bottom: 2px solid #ddd;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php while($item = mysqli_fetch_assoc($items_result)): ?>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo $item['nama_layanan']; ?></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo $item['jenis_pakaian']; ?></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo $item['berat']; ?> kg</td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right;"><?php echo formatRupiah($item['subtotal']); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <div class="payment-info">
            <h3>Informasi Pembayaran</h3>
            <p>Metode: <strong><?php echo strtoupper($order['metode_pembayaran']); ?></strong></p>
            <p>Status: <strong><?php echo ucfirst($order['status_pembayaran']); ?></strong></p>
            <?php if($order['tgl_pembayaran']): ?>
            <p>Tanggal Pembayaran: <strong><?php echo formatTanggal($order['tgl_pembayaran']); ?></strong></p>
            <?php endif; ?>
            
            <?php if($order['bukti_pembayaran']): ?>
            <div class="bukti-container">
                <p>Bukti Pembayaran:</p>
                <img src="../uploads/bukti_pembayaran/<?php echo $order['bukti_pembayaran']; ?>" 
                     alt="Bukti Pembayaran" 
                     class="bukti-image"
                     onclick="window.open(this.src, '_blank')">
            </div>
            <?php endif; ?>
            
            <?php if($order['status_pembayaran'] == 'pending' && $order['metode_pembayaran'] != 'cash' && !$order['bukti_pembayaran']): ?>
            <a href="pembayaran.php?id=<?php echo $order_id; ?>" class="btn-payment">Lakukan Pembayaran</a>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="tracking-container">
        <h2>Pesanan Aktif</h2>
        <?php if(mysqli_num_rows($result) > 0): ?>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="text-align: left; padding: 8px; border-bottom: 2px solid #ddd;">ID Pesanan</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 2px solid #ddd;">Tanggal</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 2px solid #ddd;">Layanan</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 2px solid #ddd;">Status</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 2px solid #ddd;">Pembayaran</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 2px solid #ddd;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($order = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">#<?php echo $order['id']; ?></td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo formatTanggal($order['tgl_order']); ?></td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo $order['nama_layanan']; ?></td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                <?php echo ucwords(str_replace('_', ' ', $order['status'])); ?>
                            </span>
                        </td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                            <span class="status-badge status-<?php echo $order['status_pembayaran']; ?>">
                                <?php echo ucfirst($order['status_pembayaran']); ?>
                            </span>
                        </td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                            <a href="tracking.php?id=<?php echo $order['id']; ?>" style="color: #007bff; text-decoration: none;">Detail</a>
                            <?php if($order['status_pembayaran'] == 'pending' && $order['metode_pembayaran'] != 'cash'): ?>
                                | <a href="pembayaran.php?id=<?php echo $order['id']; ?>" style="color: #28a745; text-decoration: none;">Bayar</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Tidak ada pesanan aktif saat ini.</p>
            <a href="buat_pesanan.php" style="color: #007bff; text-decoration: none;">Buat pesanan baru</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>