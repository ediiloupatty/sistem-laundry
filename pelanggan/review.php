<?php
// File: pelanggan/review.php
// Halaman untuk memberikan ulasan

session_start();
require_once '../config/koneksi.php';
require_once '../includes/functions.php';

// Cek apakah user adalah pelanggan
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pelanggan') {
    header("Location: ../login.php");
    exit();
}

$page_title = "Beri Ulasan";
$order_id = isset($_GET['id']) ? cleanInput($_GET['id']) : 0;
$customer_id = $_SESSION['customer_id'];

// Cek apakah pesanan valid dan milik pelanggan ini
$query = "SELECT o.*, 
          (SELECT nama_layanan FROM services s JOIN order_details od ON s.id = od.service_id WHERE od.order_id = o.id LIMIT 1) as nama_layanan
          FROM orders o
          WHERE o.id = '$order_id' AND o.customer_id = '$customer_id' AND o.status = 'selesai'";
$result = mysqli_query($koneksi, $query);
$order = mysqli_fetch_assoc($result);

if(!$order) {
    header("Location: riwayat.php");
    exit();
}

// Cek apakah sudah pernah direview
$review_check = "SELECT id FROM reviews WHERE order_id = '$order_id'";
$review_result = mysqli_query($koneksi, $review_check);
if(mysqli_num_rows($review_result) > 0) {
    header("Location: riwayat.php");
    exit();
}

// Handle form submission
if(isset($_POST['submit_review'])) {
    $rating = cleanInput($_POST['rating']);
    $komentar = cleanInput($_POST['komentar']);
    
    $insert_query = "INSERT INTO reviews (order_id, customer_id, rating, komentar, created_at) 
                    VALUES ('$order_id', '$customer_id', '$rating', '$komentar', NOW())";
    
    if(mysqli_query($koneksi, $insert_query)) {
        $_SESSION['success'] = "Terima kasih atas ulasan Anda!";
        header("Location: riwayat.php");
        exit();
    } else {
        $error = "Gagal menyimpan ulasan: " . mysqli_error($koneksi);
    }
}

include '../includes/header.php';
?>

<style>
    .review-container {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        max-width: 600px;
        margin: 0 auto;
    }
    .order-info {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: bold;
    }
    .star-rating {
        display: flex;
        flex-direction: row-reverse;
        justify-content: flex-end;
    }
    .star-rating input {
        display: none;
    }
    .star-rating label {
        font-size: 30px;
        color: #ddd;
        cursor: pointer;
        padding: 0 5px;
    }
    .star-rating input:checked ~ label,
    .star-rating label:hover,
    .star-rating label:hover ~ label {
        color: #ffc107;
    }
    textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        resize: vertical;
        min-height: 100px;
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
    .btn-secondary {
        background-color: #6c757d;
        color: white;
        text-decoration: none;
        display: inline-block;
    }
    .btn-secondary:hover {
        background-color: #5a6268;
    }
</style>

<h1>Beri Ulasan</h1>

<?php if(isset($error)): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<div class="review-container">
    <div class="order-info">
        <h3>Pesanan #<?php echo $order_id; ?></h3>
        <p>Layanan: <?php echo $order['nama_layanan']; ?></p>
        <p>Tanggal: <?php echo formatTanggal($order['tgl_order']); ?></p>
        <p>Total: <?php echo formatRupiah($order['total_harga']); ?></p>
    </div>
    
    <form method="POST" action="">
        <div class="form-group">
            <label>Rating:</label>
            <div class="star-rating">
                <input type="radio" id="star5" name="rating" value="5" required>
                <label for="star5">★</label>
                <input type="radio" id="star4" name="rating" value="4">
                <label for="star4">★</label>
                <input type="radio" id="star3" name="rating" value="3">
                <label for="star3">★</label>
                <input type="radio" id="star2" name="rating" value="2">
                <label for="star2">★</label>
                <input type="radio" id="star1" name="rating" value="1">
                <label for="star1">★</label>
            </div>
        </div>
        
        <div class="form-group">
            <label>Komentar:</label>
            <textarea name="komentar" placeholder="Bagikan pengalaman Anda menggunakan layanan kami..." required></textarea>
        </div>
        
        <button type="submit" name="submit_review" class="btn btn-primary">Kirim Ulasan</button>
        <a href="riwayat.php" class="btn btn-secondary">Batal</a>
    </form>
</div>

<?php include '../includes/footer.php'; ?>