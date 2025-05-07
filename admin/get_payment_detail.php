<?php
// File: admin/get_payment_detail.php
// Mengambil detail pembayaran untuk modal

session_start();
require_once '../config/koneksi.php';
require_once '../includes/functions.php';

// Cek akses admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    exit('Access denied');
}

$order_id = isset($_GET['id']) ? cleanInput($_GET['id']) : 0;

if($order_id == 0) {
    exit('Invalid order ID');
}

// Query untuk mendapatkan detail pesanan
$query = "SELECT o.*, c.nama as nama_pelanggan, c.no_hp, c.alamat,
          p.id as payment_id, p.metode_pembayaran, p.status_pembayaran, 
          p.bukti_pembayaran, p.tgl_pembayaran, p.jumlah_bayar
          FROM orders o
          JOIN customers c ON o.customer_id = c.id
          JOIN payments p ON o.id = p.order_id
          WHERE o.id = '$order_id'";
$result = mysqli_query($koneksi, $query);
$order = mysqli_fetch_assoc($result);

if(!$order) {
    exit('Order not found');
}

// Query untuk mendapatkan detail items
$items_query = "SELECT od.*, s.nama_layanan
                FROM order_details od
                JOIN services s ON od.service_id = s.id
                WHERE od.order_id = '$order_id'";
$items_result = mysqli_query($koneksi, $items_query);
?>

<div class="detail-grid">
    <div>
        <div class="detail-item">
            <div class="detail-label">ID Pesanan:</div>
            <div class="detail-value">#<?php echo $order['id']; ?></div>
        </div>
        
        <div class="detail-item">
            <div class="detail-label">Nama Pelanggan:</div>
            <div class="detail-value"><?php echo $order['nama_pelanggan']; ?></div>
        </div>
        
        <div class="detail-item">
            <div class="detail-label">No. HP:</div>
            <div class="detail-value"><?php echo $order['no_hp']; ?></div>
        </div>
        
        <div class="detail-item">
            <div class="detail-label">Alamat:</div>
            <div class="detail-value"><?php echo $order['alamat']; ?></div>
        </div>
    </div>
    
    <div>
        <div class="detail-item">
            <div class="detail-label">Total Tagihan:</div>
            <div class="detail-value"><?php echo formatRupiah($order['total_harga']); ?></div>
        </div>
        
        <div class="detail-item">
            <div class="detail-label">Metode Pembayaran:</div>
            <div class="detail-value"><?php echo strtoupper($order['metode_pembayaran']); ?></div>
        </div>
        
        <?php if($order['tgl_pembayaran']): ?>
        <div class="detail-item">
            <div class="detail-label">Tanggal Upload:</div>
            <div class="detail-value"><?php echo formatTanggal($order['tgl_pembayaran']); ?></div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if($order['metode_pembayaran'] == 'cash'): ?>
<!-- Tampilan spesial untuk pembayaran cash -->
<div class="cash-payment-container">
    <h4>Pembayaran Cash</h4>
    <p>Pelanggan memilih metode pembayaran tunai. Pelanggan akan membayar langsung ke kurir saat pengantaran/pengambilan.</p>
    
    <?php if($order['bukti_pembayaran']): ?>
    <div class="bukti-container">
        <h4>Bukti Persiapan Pembayaran:</h4>
        <img src="../uploads/bukti_pembayaran/<?php echo $order['bukti_pembayaran']; ?>" 
             class="bukti-image" 
             alt="Bukti Persiapan Pembayaran"
             onclick="window.open(this.src, '_blank')">
    </div>
    <?php else: ?>
    <p>Pelanggan belum mengunggah bukti persiapan pembayaran.</p>
    <?php endif; ?>
</div>
<?php else: ?>
<!-- Tampilan normal untuk pembayaran non-cash -->
<?php if($order['bukti_pembayaran']): ?>
<div class="bukti-container">
    <h4>Bukti Pembayaran:</h4>
    <img src="../uploads/bukti_pembayaran/<?php echo $order['bukti_pembayaran']; ?>" 
         class="bukti-image" 
         alt="Bukti Pembayaran"
         onclick="window.open(this.src, '_blank')">
</div>
<?php endif; ?>
<?php endif; ?>

<h4 style="margin-top: 20px;">Detail Pesanan:</h4>
<table style="width: 100%; margin-top: 10px;">
    <thead>
        <tr>
            <th>Layanan</th>
            <th>Berat</th>
            <th>Subtotal</th>
        </tr>
    </thead>
    <tbody>
        <?php while($item = mysqli_fetch_assoc($items_result)): ?>
        <tr>
            <td><?php echo $item['nama_layanan']; ?></td>
            <td><?php echo $item['berat']; ?> kg</td>
            <td><?php echo formatRupiah($item['subtotal']); ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<div style="margin-top: 20px; text-align: center;">
    <form action="konfirmasi_pembayaran.php" method="POST" style="display: inline-block; margin-right: 10px;">
        <input type="hidden" name="payment_id" value="<?php echo $order['payment_id']; ?>">
        <button type="submit" name="konfirmasi_pembayaran" class="btn btn-success">
            <?php if($order['metode_pembayaran'] == 'cash'): ?>
            Konfirmasi Pembayaran Cash
            <?php else: ?>
            Konfirmasi Lunas
            <?php endif; ?>
        </button>
    </form>
    
    <form action="konfirmasi_pembayaran.php" method="POST" style="display: inline-block;">
        <input type="hidden" name="payment_id" value="<?php echo $order['payment_id']; ?>">
        <button type="submit" name="tolak_pembayaran" class="btn btn-danger">
            Tolak Pembayaran
        </button>
    </form>
</div>