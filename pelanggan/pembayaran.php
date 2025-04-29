<?php
// File: pelanggan/pembayaran.php
// Halaman untuk proses pembayaran

session_start();
require_once '../config/koneksi.php';
require_once '../includes/functions.php';

// Cek apakah user adalah pelanggan
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pelanggan') {
    header("Location: ../login.php");
    exit();
}

$page_title = "Pembayaran";
$order_id = isset($_GET['id']) ? cleanInput($_GET['id']) : 0;
$customer_id = $_SESSION['customer_id'];

// Ambil detail pesanan dan pembayaran
$query = "SELECT o.*, p.metode_pembayaran, p.status_pembayaran, p.jumlah_bayar,
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

// Jika pembayaran sudah lunas atau metode cash, redirect
if($order['status_pembayaran'] == 'lunas' || $order['metode_pembayaran'] == 'cash') {
    header("Location: tracking.php?id=$order_id");
    exit();
}

// Handle upload bukti pembayaran
if(isset($_POST['upload_bukti'])) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    if(isset($_FILES['bukti_pembayaran']) && $_FILES['bukti_pembayaran']['error'] == 0) {
        $file = $_FILES['bukti_pembayaran'];
        
        // Validasi tipe file
        if(!in_array($file['type'], $allowed_types)) {
            $error = "Tipe file tidak diizinkan. Hanya JPG, JPEG, dan PNG.";
        }
        // Validasi ukuran file
        elseif($file['size'] > $max_size) {
            $error = "Ukuran file terlalu besar. Maksimal 2MB.";
        }
        else {
            // Generate nama file unik
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'bukti_' . $order_id . '_' . time() . '.' . $ext;
            $upload_path = '../uploads/bukti_pembayaran/';
            
            // Buat direktori jika belum ada
            if(!file_exists($upload_path)) {
                mkdir($upload_path, 0777, true);
            }
            
            // Upload file
            if(move_uploaded_file($file['tmp_name'], $upload_path . $filename)) {
                // Update database
                $update_query = "UPDATE payments SET 
                                bukti_pembayaran = '$filename',
                                status_pembayaran = 'pending',
                                tgl_pembayaran = NOW()
                                WHERE order_id = '$order_id'";
                
                if(mysqli_query($koneksi, $update_query)) {
                    $_SESSION['success'] = "Bukti pembayaran berhasil diupload! Menunggu verifikasi admin.";
                    header("Location: tracking.php?id=$order_id");
                    exit();
                } else {
                    $error = "Gagal menyimpan data pembayaran.";
                }
            } else {
                $error = "Gagal mengupload file.";
            }
        }
    } else {
        $error = "Silakan pilih file bukti pembayaran.";
    }
}

include '../includes/header.php';
?>

<style>
    .payment-container {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        max-width: 800px;
        margin: 0 auto;
    }
    .payment-info {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .bank-details {
        background: #e9ecef;
        padding: 15px;
        border-radius: 4px;
        margin: 15px 0;
    }
    .bank-account {
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid #ddd;
    }
    .bank-account:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }
    .bank-logo {
        font-weight: bold;
        font-size: 18px;
        margin-bottom: 5px;
    }
    .account-number {
        font-size: 20px;
        font-weight: bold;
        color: #007bff;
        margin: 5px 0;
    }
    .account-name {
        color: #666;
    }
    .upload-form {
        margin-top: 20px;
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
    }
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    .btn-primary {
        background-color: #007bff;
        color: white;
    }
    .btn-primary:hover {
        background-color: #0056b3;
    }
    .instructions {
        background: #fff3cd;
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
    }
    .instructions h4 {
        margin-top: 0;
    }
    .instructions ol {
        margin-bottom: 0;
        padding-left: 20px;
    }
    .qr-code {
        text-align: center;
        margin: 20px 0;
    }
    .qr-code img {
        max-width: 200px;
        height: auto;
    }
</style>

<h1>Pembayaran Pesanan #<?php echo $order_id; ?></h1>

<?php if(isset($error)): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<div class="payment-container">
    <div class="payment-info">
        <h3>Detail Pembayaran</h3>
        <p>Total Pembayaran: <strong><?php echo formatRupiah($order['total_harga']); ?></strong></p>
        <p>Metode Pembayaran: <strong><?php echo strtoupper($order['metode_pembayaran']); ?></strong></p>
        <p>Status: <strong><?php echo ucfirst($order['status_pembayaran']); ?></strong></p>
    </div>
    
    <?php if($order['metode_pembayaran'] == 'transfer'): ?>
        <div class="bank-details">
            <h4>Silakan transfer ke salah satu rekening berikut:</h4>
            
            <div class="bank-account">
                <div class="bank-logo">BCA</div>
                <div class="account-number">1234567890</div>
                <div class="account-name">a.n. Laundry System</div>
            </div>
            
            <div class="bank-account">
                <div class="bank-logo">Mandiri</div>
                <div class="account-number">0987654321</div>
                <div class="account-name">a.n. Laundry System</div>
            </div>
            
            <div class="bank-account">
                <div class="bank-logo">BNI</div>
                <div class="account-number">1122334455</div>
                <div class="account-name">a.n. Laundry System</div>
            </div>
            
            <div class="bank-account">
                <div class="bank-logo">BRI</div>
                <div class="account-number">5544332211</div>
                <div class="account-name">a.n. Laundry System</div>
            </div>
        </div>
        
    <?php elseif(in_array($order['metode_pembayaran'], ['ovo', 'dana', 'gopay'])): ?>
        <div class="bank-details">
            <h4>Silakan transfer ke <?php echo strtoupper($order['metode_pembayaran']); ?>:</h4>
            
            <div class="bank-account">
                <div class="bank-logo"><?php echo strtoupper($order['metode_pembayaran']); ?></div>
                <div class="account-number">081234567890</div>
                <div class="account-name">a.n. Laundry System</div>
            </div>
            
            <div class="qr-code">
                <p>Atau scan QR Code berikut:</p>
                <!-- Placeholder untuk QR Code. Dalam implementasi nyata, generate QR code sesuai metode pembayaran -->
                <img src="../assets/images/qr-placeholder.png" alt="QR Code">
            </div>
        </div>
    <?php endif; ?>
    
    <div class="instructions">
        <h4>Petunjuk Pembayaran:</h4>
        <ol>
            <li>Transfer sesuai dengan jumlah yang tertera</li>
            <li>Simpan bukti pembayaran</li>
            <li>Upload bukti pembayaran di form di bawah ini</li>
            <li>Tunggu konfirmasi dari admin (maks. 1x24 jam)</li>
        </ol>
    </div>
    
    <div class="upload-form">
        <h4>Upload Bukti Pembayaran</h4>
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label>Pilih File Bukti Pembayaran:</label>
                <input type="file" name="bukti_pembayaran" class="form-control" accept="image/*" required>
                <small>Format: JPG, JPEG, PNG. Maksimal 2MB</small>
            </div>
            
            <button type="submit" name="upload_bukti" class="btn btn-primary">Upload Bukti Pembayaran</button>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>