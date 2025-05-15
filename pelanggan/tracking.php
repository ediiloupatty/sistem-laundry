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

    /* Responsive container */
    .container {
        width: 100%;
        padding-right: 15px;
        padding-left: 15px;
        margin-right: auto;
        margin-left: auto;
    }

    .tracking-container {
        background: white;
        padding: 15px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        width: 100%;
        overflow-x: hidden;
    }

    /* Mobile-friendly typography */
    h1 {
        color: #343a40;
        margin-bottom: 20px;
        font-weight: 700;
        position: relative;
        padding-bottom: 10px;
        font-size: 24px;
    }

    h1:after {
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        width: 60px;
        height: 3px;
        background: linear-gradient(to right, #007bff, #6610f2);
    }

    h2 {
        color: #343a40;
        margin: 20px 0 15px;
        font-weight: 600;
        font-size: 20px;
    }

    h3 {
        color: #495057;
        margin: 15px 0 10px;
        font-weight: 600;
        font-size: 16px;
    }

    /* Improved mobile timeline */
    .status-timeline {
        display: flex;
        justify-content: space-between;
        margin: 30px 0;
        position: relative;
        padding: 0;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 10px; /* Space for scrollbar */
    }

    .status-timeline::before {
        content: '';
        position: absolute;
        top: 15px;
        left: 0;
        width: 100%;
        height: 3px;
        background: #eee;
        z-index: 1;
    }

    .status-item {
        text-align: center;
        position: relative;
        z-index: 2;
        flex: 0 0 auto;
        min-width: 80px;
        margin: 0 10px;
    }

    .status-circle {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: #eee;
        margin: 0 auto 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: white;
        transition: all 0.3s ease;
    }

    .status-item.active .status-circle {
        background: linear-gradient(to right, #28a745, #20c997);
        box-shadow: 0 0 8px rgba(40, 167, 69, 0.5);
    }

    .status-item.current .status-circle {
        background: linear-gradient(to right, #007bff, #17a2b8);
        box-shadow: 0 0 10px rgba(0, 123, 255, 0.6);
        animation: pulse 1.5s infinite;
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }

    .status-name {
        font-size: 12px;
        font-weight: 500;
        color: #555;
        margin-top: 5px;
    }

    /* Mobile-friendly grid */
    .detail-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 15px;
        background-color: #f9f9f9;
        padding: 15px;
        border-radius: 8px;
        margin: 15px 0;
        box-shadow: inset 0 0 5px rgba(0,0,0,0.05);
    }

    @media (min-width: 768px) {
        .detail-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            padding: 20px;
        }
    }

    .detail-item {
        margin-bottom: 15px;
        position: relative;
        padding-left: 3px;
    }

    .detail-label {
        font-weight: 600;
        color: #444;
        margin-bottom: 6px;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .detail-value {
        color: #333;
        font-size: 15px;
        padding: 6px 0;
        border-bottom: 1px dashed #ddd;
        word-break: break-word;
    }

    /* Responsive badges */
    .status-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        display: inline-block;
        margin-top: 2px;
    }

    .status-menunggu_konfirmasi { 
        background: linear-gradient(to right, #ffc107, #ffcf40);
        color: #000; 
    }

    .status-diproses { 
        background: linear-gradient(to right, #17a2b8, #20c997);
        color: #fff; 
    }

    .status-selesai { 
        background: linear-gradient(to right, #28a745, #20c997);
        color: #fff; 
    }

    .status-siap_diantar { 
        background: linear-gradient(to right, #6f42c1, #6610f2);
        color: #fff; 
    }

    .status-dibatalkan { 
        background: linear-gradient(to right, #dc3545, #c82333);
        color: #fff; 
    }

    .status-pending { 
        background: linear-gradient(to right, #ffc107, #ffcf40);
        color: #000; 
    }

    .status-lunas { 
        background: linear-gradient(to right, #28a745, #20c997);
        color: #fff; 
    }

    /* Mobile-optimized tables */
    .responsive-table {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 15px;
    }

    .item-table {
        width: 100%;
        min-width: 500px; /* Ensure minimum width for scrolling */
        border-collapse: separate;
        border-spacing: 0;
        margin: 15px 0;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .item-table thead th {
        background-color: #f8f9fa;
        color: #495057;
        text-align: left;
        padding: 10px 12px;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
        font-size: 13px;
    }

    .item-table tbody td {
        padding: 10px 12px;
        border-bottom: 1px solid #dee2e6;
        color: #212529;
        font-size: 13px;
    }

    .item-table tbody tr:last-child td {
        border-bottom: none;
    }

    .item-table tbody tr:hover {
        background-color: #f2f7ff;
    }

    /* Card layout for mobile order listing */
    .order-card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 12px;
        margin-bottom: 15px;
        border-left: 3px solid #007bff;
    }

    .order-card-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        padding-bottom: 8px;
        border-bottom: 1px solid #eee;
    }

    .order-card-id {
        font-weight: 600;
        color: #333;
    }

    .order-card-date {
        color: #666;
        font-size: 13px;
    }

    .order-card-content {
        margin-bottom: 10px;
    }

    .order-card-content p {
        margin: 5px 0;
        font-size: 14px;
    }

    .order-card-actions {
        display: flex;
        gap: 10px;
        margin-top: 10px;
    }

    /* Mobile-friendly payment info */
    .payment-info {
        background: linear-gradient(to right bottom, #f8f9fa, #e9ecef);
        padding: 15px;
        border-radius: 12px;
        margin-top: 20px;
        box-shadow: 0 3px 8px rgba(0,0,0,0.05);
        border-left: 4px solid #007bff;
    }

    .payment-info h3 {
        color: #343a40;
        margin-bottom: 12px;
        font-weight: 600;
        font-size: 16px;
        position: relative;
        padding-bottom: 8px;
    }

    .payment-info h3:after {
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        width: 40px;
        height: 3px;
        background: #007bff;
    }

    .payment-info p {
        margin: 8px 0;
        color: #495057;
        line-height: 1.5;
        font-size: 14px;
    }

    /* Optimized buttons */
    .back-button {
        display: inline-block;
        padding: 8px 16px;
        background: linear-gradient(to right, #6c757d, #5a6268);
        color: white;
        text-decoration: none;
        border-radius: 50px;
        margin-bottom: 15px;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(108, 117, 125, 0.2);
        font-size: 13px;
    }

    .back-button:hover {
        background: linear-gradient(to right, #5a6268, #4e555b);
        color: white;
        text-decoration: none;
    }

    .btn-payment {
        display: inline-block;
        padding: 8px 18px;
        background: linear-gradient(to right, #28a745, #20c997);
        color: white;
        text-decoration: none;
        border-radius: 50px;
        margin-top: 15px;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 3px 5px rgba(40, 167, 69, 0.3);
        border: none;
        font-size: 14px;
        text-align: center;
    }

    .btn-payment:hover {
        background: linear-gradient(to right, #218838, #1e9070);
        color: white;
        text-decoration: none;
    }

    /* Mobile-friendly alerts */
    .payment-alert {
        background: linear-gradient(to right bottom, #fff9db, #fff3cd);
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 4px solid #ffc107;
        box-shadow: 0 2px 8px rgba(255, 193, 7, 0.2);
        font-size: 14px;
    }

    .payment-alert strong {
        display: block;
        margin-bottom: 6px;
        color: #856404;
    }

    .alert-success {
        background: linear-gradient(to right bottom, #d4edda, #c3e6cb);
        color: #155724;
        padding: 12px 15px;
        border-radius: 8px;
        margin-bottom: 15px;
        border-left: 4px solid #28a745;
        box-shadow: 0 2px 8px rgba(40, 167, 69, 0.2);
        font-size: 14px;
    }

    /* Bukti pembayaran mobile */
    .bukti-container {
        margin-top: 15px;
        background-color: #fff;
        padding: 12px;
        border-radius: 8px;
        box-shadow: 0 0 5px rgba(0,0,0,0.1);
    }

    .bukti-image {
        max-width: 100%;
        height: auto;
        cursor: pointer;
        border: none;
        border-radius: 6px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }

    /* Action buttons */
    .btn-action {
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
        display: inline-block;
        margin: 0 3px;
    }

    .btn-detail {
        background-color: #e7f3ff;
        color: #007bff;
        border: 1px solid #cce5ff;
    }

    .btn-pay {
        background-color: #e8f5e9;
        color: #28a745;
        border: 1px solid #c8e6c9;
    }
    
    /* Mobile spacing adjustments */
    @media (max-width: 767px) {
        .tracking-container {
            padding: 12px;
        }
        
        h1 {
            font-size: 22px;
        }
        
        h2 {
            font-size: 18px;
        }
        
        .btn-payment, .back-button {
            display: block;
            width: 100%;
            text-align: center;
        }
    }
</style>

<div class="container">
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
            <div class="responsive-table">
                <table class="item-table">
                    <thead>
                        <tr>
                            <th>Layanan</th>
                            <th>Jenis Pakaian</th>
                            <th>Berat</th>
                            <th style="text-align: right;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($item = mysqli_fetch_assoc($items_result)): ?>
                        <tr>
                            <td><?php echo $item['nama_layanan']; ?></td>
                            <td><?php echo $item['jenis_pakaian']; ?></td>
                            <td><?php echo $item['berat']; ?> kg</td>
                            <td style="text-align: right;"><?php echo formatRupiah($item['subtotal']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
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
                <div class="order-list">
                    <?php while($order = mysqli_fetch_assoc($result)): ?>
                    <div class="order-card">
                        <div class="order-card-header">
                            <span class="order-card-id">#<?php echo $order['id']; ?></span>
                            <span class="order-card-date"><?php echo formatTanggal($order['tgl_order']); ?></span>
                        </div>
                        <div class="order-card-content">
                            <p><strong>Layanan:</strong> <?php echo $order['nama_layanan']; ?></p>
                            <p>
                                <strong>Status:</strong> 
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php echo ucwords(str_replace('_', ' ', $order['status'])); ?>
                                </span>
                            </p>
                            <p>
                                <strong>Pembayaran:</strong>
                                <span class="status-badge status-<?php echo $order['status_pembayaran']; ?>">
                                    <?php echo ucfirst($order['status_pembayaran']); ?>
                                </span>
                            </p>
                        </div>
                        <div class="order-card-actions">
                            <a href="tracking.php?id=<?php echo $order['id']; ?>" class="btn-action btn-detail">Detail</a>
                            <?php if($order['status_pembayaran'] == 'pending' && $order['metode_pembayaran'] != 'cash'): ?>
                                <a href="pembayaran.php?id=<?php echo $order['id']; ?>" class="btn-action btn-pay">Bayar</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p>Tidak ada pesanan aktif saat ini.</p>
                <a href="buat_pesanan.php" class="btn-payment">Buat pesanan baru</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>