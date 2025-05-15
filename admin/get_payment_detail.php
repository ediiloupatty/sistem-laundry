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

// Status pembayaran badge
$status_badge = '';
if(isset($order['status_pembayaran'])) {
    switch($order['status_pembayaran']) {
        case 'belum_bayar':
            $status_badge = '<span class="badge bg-warning text-dark">Menunggu Pembayaran</span>';
            break;
        case 'verifikasi':
            $status_badge = '<span class="badge bg-info">Menunggu Verifikasi</span>';
            break;
        case 'lunas':
            $status_badge = '<span class="badge bg-success">Lunas</span>';
            break;
        case 'ditolak':
            $status_badge = '<span class="badge bg-danger">Ditolak</span>';
            break;
        default:
            $status_badge = '<span class="badge bg-secondary">Unknown</span>';
    }
}
?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-primary text-white py-3">
        <h5 class="mb-0"><i class="fas fa-file-invoice"></i> Detail Pembayaran #<?php echo $order['id']; ?></h5>
    </div>
    <div class="card-body">
        <div class="row">
            <!-- Informasi Pelanggan -->
            <div class="col-md-6 mb-4">
                <div class="card h-100 border-light">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-user-circle"></i> Informasi Pelanggan</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="text-muted small mb-1">Nama Lengkap</label>
                            <p class="mb-2 fw-medium"><?php echo $order['nama_pelanggan']; ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small mb-1">No. Telepon</label>
                            <p class="mb-2 fw-medium"><?php echo $order['no_hp']; ?></p>
                        </div>
                        <div>
                            <label class="text-muted small mb-1">Alamat</label>
                            <p class="mb-0 fw-medium"><?php echo $order['alamat']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Informasi Pembayaran -->
            <div class="col-md-6 mb-4">
                <div class="card h-100 border-light">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-credit-card"></i> Informasi Pembayaran</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="text-muted small mb-1">Status Pembayaran</label>
                            <div class="mb-2"><?php echo $status_badge; ?></div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small mb-1">Total Tagihan</label>
                            <p class="mb-2 fw-bold fs-5 text-primary"><?php echo formatRupiah($order['total_harga']); ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small mb-1">Metode Pembayaran</label>
                            <p class="mb-2">
                                <span class="badge bg-light text-dark border">
                                    <?php if($order['metode_pembayaran'] == 'cash'): ?>
                                        <i class="fas fa-money-bill-wave me-1"></i>
                                    <?php else: ?>
                                        <i class="fas fa-university me-1"></i>
                                    <?php endif; ?>
                                    <?php echo strtoupper($order['metode_pembayaran']); ?>
                                </span>
                            </p>
                        </div>
                        <?php if($order['tgl_pembayaran']): ?>
                        <div>
                            <label class="text-muted small mb-1">Tanggal Upload</label>
                            <p class="mb-0"><i class="far fa-calendar-alt me-1"></i> <?php echo formatTanggal($order['tgl_pembayaran']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Pesanan -->
        <div class="card border-light mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="fas fa-shopping-cart"></i> Detail Pesanan</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Layanan</th>
                                <th class="text-center">Berat</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($item = mysqli_fetch_assoc($items_result)): ?>
                            <tr>
                                <td class="align-middle"><?php echo $item['nama_layanan']; ?></td>
                                <td class="align-middle text-center"><?php echo $item['berat']; ?> kg</td>
                                <td class="align-middle text-end"><?php echo formatRupiah($item['subtotal']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                            <tr class="table-light">
                                <td colspan="2" class="fw-bold text-end">Total</td>
                                <td class="fw-bold text-end"><?php echo formatRupiah($order['total_harga']); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if($order['metode_pembayaran'] == 'cash'): ?>
        <!-- Tampilan spesial untuk pembayaran cash -->
        <div class="card border-light mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="fas fa-money-bill-wave"></i> Pembayaran Cash</h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    Pelanggan memilih metode pembayaran tunai. Pelanggan akan membayar langsung ke kurir saat pengantaran/pengambilan.
                </div>
                
                <?php if($order['bukti_pembayaran']): ?>
                <h6 class="mt-3 mb-3">Bukti Persiapan Pembayaran:</h6>
                <div class="text-center">
                    <img src="../uploads/bukti_pembayaran/<?php echo $order['bukti_pembayaran']; ?>" 
                        class="img-thumbnail mb-2 bukti-image" 
                        style="max-height: 300px; cursor: pointer;"
                        alt="Bukti Persiapan Pembayaran"
                        onclick="window.open(this.src, '_blank')">
                    <div class="small text-muted">Klik gambar untuk memperbesar</div>
                </div>
                <?php else: ?>
                <div class="alert alert-warning" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Pelanggan belum mengunggah bukti persiapan pembayaran.
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <!-- Tampilan normal untuk pembayaran non-cash -->
        <?php if($order['bukti_pembayaran']): ?>
        <div class="card border-light mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="fas fa-receipt"></i> Bukti Pembayaran</h6>
            </div>
            <div class="card-body text-center">
                <img src="../uploads/bukti_pembayaran/<?php echo $order['bukti_pembayaran']; ?>" 
                    class="img-thumbnail mb-2 bukti-image" 
                    style="max-height: 300px; cursor: pointer;"
                    alt="Bukti Pembayaran"
                    onclick="window.open(this.src, '_blank')">
                <div class="small text-muted">Klik gambar untuk memperbesar</div>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="d-flex justify-content-center gap-3 mt-4">
            <form action="konfirmasi_pembayaran.php" method="POST">
                <input type="hidden" name="payment_id" value="<?php echo $order['payment_id']; ?>">
                <button type="submit" name="konfirmasi_pembayaran" class="btn btn-success px-4">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php if($order['metode_pembayaran'] == 'cash'): ?>
                    Konfirmasi Pembayaran Cash
                    <?php else: ?>
                    Konfirmasi Lunas
                    <?php endif; ?>
                </button>
            </form>
            
            <form action="konfirmasi_pembayaran.php" method="POST">
                <input type="hidden" name="payment_id" value="<?php echo $order['payment_id']; ?>">
                <button type="submit" name="tolak_pembayaran" class="btn btn-danger px-4">
                    <i class="fas fa-times-circle me-2"></i>
                    Tolak Pembayaran
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    // Optional: Untuk menambahkan animasi saat hover pada bukti pembayaran
    document.addEventListener('DOMContentLoaded', function() {
        const buktiImages = document.querySelectorAll('.bukti-image');
        buktiImages.forEach(img => {
            img.addEventListener('mouseover', function() {
                this.style.opacity = '0.9';
                this.style.transition = 'opacity 0.3s';
            });
            img.addEventListener('mouseout', function() {
                this.style.opacity = '1';
            });
        });
    });
</script>