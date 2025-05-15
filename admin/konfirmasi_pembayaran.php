<?php
// File: admin/konfirmasi_pembayaran.php
// Halaman untuk admin mengkonfirmasi pembayaran

session_start();
require_once '../config/koneksi.php';
require_once '../includes/functions.php';
require_once '../includes/notification_helper.php';

// Cek apakah user adalah admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$page_title = "Konfirmasi Pembayaran";

// Handle konfirmasi pembayaran
if (isset($_POST['konfirmasi_pembayaran'])) {
    $payment_id = $_POST['payment_id'];
    
    // Query untuk mendapatkan informasi pembayaran
    $payment_query = "SELECT p.*, o.customer_id 
                      FROM payments p 
                      JOIN orders o ON p.order_id = o.id 
                      WHERE p.id = '$payment_id'";
    $payment_result = mysqli_query($koneksi, $payment_query);
    $payment_data = mysqli_fetch_assoc($payment_result);
    
    // Update status pembayaran di tabel payments
    $update_query = "UPDATE payments 
             SET status_pembayaran = 'lunas', 
             konfirmasi_pada = NOW(), 
             dikonfirmasi_oleh = '" . $_SESSION['user_id'] . "' 
             WHERE id = '$payment_id'";
    
    if (mysqli_query($koneksi, $update_query)) {
        // Dapatkan user_id dari customer_id
        $user_id = getUserIdFromCustomerId($payment_data['customer_id']);
        
        if ($user_id) {
            // Buat notifikasi
            $pesan = "Pembayaran Anda telah dikonfirmasi";
            createNotification($user_id, $pesan, 'payment', $payment_data['order_id']);
        }
        
        $_SESSION['flash_message'] = "Pembayaran berhasil dikonfirmasi";
        $_SESSION['flash_message_type'] = "success";
    }
}

// Handle tolak pembayaran
if (isset($_POST['tolak_pembayaran'])) {
    $payment_id = $_POST['payment_id'];
    
    // Query untuk mendapatkan informasi pembayaran
    $payment_query = "SELECT p.*, o.customer_id 
                      FROM payments p 
                      JOIN orders o ON p.order_id = o.id 
                      WHERE p.id = '$payment_id'";
    $payment_result = mysqli_query($koneksi, $payment_query);
    $payment_data = mysqli_fetch_assoc($payment_result);
    
    // Update status pembayaran
    $update_query = "UPDATE payments 
                 SET status_pembayaran = 'ditolak', 
                     konfirmasi_pada = NOW(), 
                     dikonfirmasi_oleh = '" . $_SESSION['user_id'] . "' 
                 WHERE id = '$payment_id'";
    
    if (mysqli_query($koneksi, $update_query)) {
        // Dapatkan user_id dari customer_id
        $user_id = getUserIdFromCustomerId($payment_data['customer_id']);
        
        if ($user_id) {
            // Buat notifikasi
            $pesan = "Pembayaran Anda ditolak, silahkan upload ulang bukti pembayaran yang valid";
            createNotification($user_id, $pesan, 'payment', $payment_data['order_id']);
        }
        
        $_SESSION['flash_message'] = "Pembayaran telah ditolak";
        $_SESSION['flash_message_type'] = "warning";
    }
}

// Query untuk mendapatkan pesanan dengan pembayaran pending
// PERUBAHAN: Menampilkan semua pembayaran cash dan non-cash yang pending
$query = "SELECT o.*, c.nama as nama_pelanggan, c.no_hp, 
          p.metode_pembayaran, p.status_pembayaran, p.bukti_pembayaran, p.tgl_pembayaran,
          p.jumlah_bayar
          FROM orders o
          JOIN customers c ON o.customer_id = c.id
          JOIN payments p ON o.id = p.order_id
          WHERE (p.bukti_pembayaran IS NOT NULL OR p.metode_pembayaran = 'cash') 
          AND p.status_pembayaran = 'pending'
          ORDER BY p.tgl_pembayaran DESC";
$result = mysqli_query($koneksi, $query);

include '../includes/header.php';
?>

<style>
    /* Base Styles */
    * {
        box-sizing: border-box;
    }
    
    .container {
        width: 100%;
        max-width: 1200px;
        padding: 0 15px;
        margin: 0 auto;
    }
    
    .konfirmasi-container {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        width: 100%;
        overflow-x: hidden;
    }
    
    .bukti-container {
        max-width: 100%;
        margin: 20px 0;
    }
    
    .bukti-image {
        width: 100%;
        border: 1px solid #ddd;
        border-radius: 4px;
        cursor: pointer;
    }
    
    /* Table Responsive */
    .table-responsive {
        overflow-x: auto;
        width: 100%;
        -webkit-overflow-scrolling: touch;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        min-width: 600px; /* Memastikan lebar minimal tabel */
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
    
    /* Buttons */
    .btn {
        padding: 8px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        margin-right: 5px;
        margin-bottom: 5px;
        white-space: nowrap;
    }
    
    .btn-success {
        background-color: #28a745;
        color: white;
    }
    
    .btn-danger {
        background-color: #dc3545;
        color: white;
    }
    
    .btn-primary {
        background-color: #007bff;
        color: white;
    }
    
    /* Modal Styling */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
        overflow-y: auto; /* Menambahkan scroll pada modal container */
        padding: 15px; /* Menambahkan padding untuk scrollbar */
    }
    
    .modal-content {
        background-color: white;
        margin: 20px auto; /* Mengurangi margin-top dari 5% ke 20px */
        padding: 20px;
        border-radius: 8px;
        width: 90%;
        max-width: 600px;
        max-height: 90vh; /* Membatasi tinggi maksimal */
        overflow-y: auto; /* Menambahkan scroll pada konten */
        position: relative; /* Untuk posisi tombol close */
    }
    
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
        position: sticky; /* Membuat header tetap di atas saat scroll */
        top: -20px; /* Offset untuk padding */
        background-color: white;
        z-index: 1;
    }
    
    .close {
        font-size: 24px;
        cursor: pointer;
        position: absolute;
        right: 20px;
        top: 15px;
    }
    
    .detail-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
    
    .detail-item {
        margin-bottom: 10px;
    }
    
    .detail-label {
        font-weight: bold;
        color: #666;
    }
    
    .detail-value {
        color: #333;
        word-break: break-word;
    }
    
    /* Alert messages */
    .alert {
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
    }
    
    .alert-success {
        background-color: #d4edda;
        color: #155724;
    }
    
    .alert-warning {
        background-color: #fff3cd;
        color: #856404;
    }
    
    /* Responsive Styles */
    @media (max-width: 768px) {
        .modal-content {
            width: 95%;
            margin: 10px auto;
            max-height: 95vh;
            padding: 15px;
        }
        
        .detail-grid {
            grid-template-columns: 1fr;
        }
        
        .close {
            top: 10px;
            right: 15px;
        }
        
        .btn {
            padding: 6px 12px;
            font-size: 14px;
            display: inline-block;
            margin-bottom: 5px;
        }
        
        th, td {
            padding: 8px;
            font-size: 14px;
        }
        
        h1 {
            font-size: 24px;
            margin-bottom: 15px;
        }
    }
</style>

<div class="container">
    <h1>Konfirmasi Pembayaran</h1>

    <?php if(isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_message_type']; ?>">
            <?php 
                echo $_SESSION['flash_message']; 
                unset($_SESSION['flash_message']);
                unset($_SESSION['flash_message_type']);
            ?>
        </div>
    <?php endif; ?>

    <div class="konfirmasi-container">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID Pesanan</th>
                        <th>Pelanggan</th>
                        <th>Total</th>
                        <th>Metode</th>
                        <th>Tanggal Upload</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($result) > 0): ?>
                        <?php while($order = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td>
                                <?php echo $order['nama_pelanggan']; ?><br>
                                <small><?php echo $order['no_hp']; ?></small>
                            </td>
                            <td><?php echo formatRupiah($order['total_harga']); ?></td>
                            <td><?php echo strtoupper($order['metode_pembayaran']); ?></td>
                            <td><?php echo formatTanggal($order['tgl_pembayaran']); ?></td>
                            <td>
                                <button onclick="openModal('<?php echo $order['id']; ?>')" class="btn btn-primary">Detail</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">Tidak ada pembayaran yang perlu dikonfirmasi</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Detail Pembayaran -->
<div id="paymentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Detail Pembayaran</h3>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        
        <div id="modalContent">
            <!-- Content will be loaded here -->
        </div>
    </div>
</div>

<script>
function openModal(orderId) {
    document.getElementById('paymentModal').style.display = 'block';
    // Mencegah scroll pada body saat modal terbuka
    document.body.style.overflow = 'hidden';
    
    fetch('get_payment_detail.php?id=' + orderId)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(data => {
            document.getElementById('modalContent').innerHTML = data;
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Gagal memuat detail pembayaran');
        });
}

function closeModal() {
    document.getElementById('paymentModal').style.display = 'none';
    // Mengembalikan scroll pada body
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
window.onclick = function(event) {
    let modal = document.getElementById('paymentModal');
    if (event.target == modal) {
        closeModal();
    }
}

// Close modal with ESC key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeModal();
    }
});
</script>

<?php include '../includes/footer.php'; ?>