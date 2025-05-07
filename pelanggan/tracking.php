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

    .tracking-container {
        background: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }

    /* Timeline status pesanan yang ditingkatkan */
    .status-timeline {
        display: flex;
        justify-content: space-between;
        margin: 40px 0;
        position: relative;
        padding: 0 20px;
    }

    .status-timeline::before {
        content: '';
        position: absolute;
        top: 15px;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(to right, #eee, #eee);
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
        transition: all 0.3s ease;
    }

    .status-item.active .status-circle {
        background: linear-gradient(to right, #28a745, #20c997);
        box-shadow: 0 0 10px rgba(40, 167, 69, 0.5);
    }

    .status-item.current .status-circle {
        background: linear-gradient(to right, #007bff, #17a2b8);
        box-shadow: 0 0 12px rgba(0, 123, 255, 0.6);
        animation: pulse 1.5s infinite;
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }

    .status-name {
        font-size: 13px;
        font-weight: 500;
        color: #555;
        margin-top: 5px;
    }

    /* Grid untuk detail pesanan */
    .detail-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 30px;
        background-color: #f9f9f9;
        padding: 20px;
        border-radius: 8px;
        margin: 20px 0;
        box-shadow: inset 0 0 5px rgba(0,0,0,0.05);
    }

    .detail-item {
        margin-bottom: 18px;
        position: relative;
        padding-left: 5px;
    }

    .detail-label {
        font-weight: 600;
        color: #444;
        margin-bottom: 8px;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .detail-value {
        color: #333;
        font-size: 16px;
        padding: 8px 0;
        border-bottom: 1px dashed #ddd;
    }

    /* Badge untuk status */
    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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

    /* Tampilan tabel item yang ditingkatkan */
    .item-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin: 25px 0;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .item-table thead th {
        background-color: #f8f9fa;
        color: #495057;
        text-align: left;
        padding: 12px 15px;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
    }

    .item-table tbody td {
        padding: 12px 15px;
        border-bottom: 1px solid #dee2e6;
        color: #212529;
    }

    .item-table tbody tr:last-child td {
        border-bottom: none;
    }

    .item-table tbody tr:hover {
        background-color: #f2f7ff;
    }

    /* Informasi pembayaran yang lebih menarik */
    .payment-info {
        background: linear-gradient(to right bottom, #f8f9fa, #e9ecef);
        padding: 25px;
        border-radius: 12px;
        margin-top: 30px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        border-left: 4px solid #007bff;
    }

    .payment-info h3 {
        color: #343a40;
        margin-bottom: 15px;
        font-weight: 600;
        font-size: 18px;
        position: relative;
        padding-bottom: 10px;
    }

    .payment-info h3:after {
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        width: 50px;
        height: 3px;
        background: #007bff;
    }

    .payment-info p {
        margin: 10px 0;
        color: #495057;
        line-height: 1.6;
    }

    .payment-info strong {
        color: #212529;
    }

    /* Peningkatan tampilan tombol */
    .btn-payment {
        display: inline-block;
        padding: 10px 20px;
        background: linear-gradient(to right, #28a745, #20c997);
        color: white;
        text-decoration: none;
        border-radius: 50px;
        margin-top: 20px;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 6px rgba(40, 167, 69, 0.3);
        border: none;
    }

    .btn-payment:hover {
        background: linear-gradient(to right, #218838, #1e9070);
        transform: translateY(-2px);
        box-shadow: 0 6px 8px rgba(40, 167, 69, 0.4);
        color: white;
        text-decoration: none;
    }

    .back-button {
        display: inline-block;
        padding: 8px 18px;
        background: linear-gradient(to right, #6c757d, #5a6268);
        color: white;
        text-decoration: none;
        border-radius: 50px;
        margin-bottom: 25px;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(108, 117, 125, 0.2);
    }

    .back-button:hover {
        background: linear-gradient(to right, #5a6268, #4e555b);
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(108, 117, 125, 0.3);
        color: white;
        text-decoration: none;
    }

    /* Tampilan bukti pembayaran yang ditingkatkan */
    .bukti-container {
        margin-top: 20px;
        background-color: #fff;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 0 5px rgba(0,0,0,0.1);
    }

    .bukti-image {
        max-width: 250px;
        cursor: pointer;
        border: none;
        border-radius: 8px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .bukti-image:hover {
        transform: scale(1.02);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    /* Peringatan pembayaran yang lebih menarik */
    .payment-alert {
        background: linear-gradient(to right bottom, #fff9db, #fff3cd);
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 25px;
        border-left: 4px solid #ffc107;
        box-shadow: 0 2px 8px rgba(255, 193, 7, 0.2);
    }

    .payment-alert strong {
        display: block;
        margin-bottom: 8px;
        color: #856404;
    }

    /* Styling untuk alert success */
    .alert-success {
        background: linear-gradient(to right bottom, #d4edda, #c3e6cb);
        color: #155724;
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 4px solid #28a745;
        box-shadow: 0 2px 8px rgba(40, 167, 69, 0.2);
    }

    /* Judul dan subtitel */
    h1 {
        color: #343a40;
        margin-bottom: 25px;
        font-weight: 700;
        position: relative;
        padding-bottom: 10px;
    }

    h1:after {
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        width: 80px;
        height: 4px;
        background: linear-gradient(to right, #007bff, #6610f2);
    }

    h2 {
        color: #343a40;
        margin: 25px 0 20px;
        font-weight: 600;
        font-size: 22px;
    }

    h3 {
        color: #495057;
        margin: 20px 0 15px;
        font-weight: 600;
        font-size: 18px;
    }

    /* Transisi dan animasi */
    * {
        transition: color 0.3s ease, background-color 0.3s ease;
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