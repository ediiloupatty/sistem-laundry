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
    
    .page-content {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .page-title {
        color: var(--secondary-color);
        font-size: 1.8rem;
        margin-bottom: 1.5rem;
        font-weight: 700;
        text-align: center;
    }
    
    .review-container {
        background: white;
        padding: 2rem;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        margin-bottom: 2rem;
    }
    
    .order-info {
        background: var(--light-gray);
        padding: 1.5rem;
        border-radius: var(--border-radius);
        margin-bottom: 2rem;
        border: 1px solid var(--medium-gray);
    }
    
    .order-info h3 {
        color: var(--primary-color);
        margin-top: 0;
        margin-bottom: 1rem;
        font-size: 1.2rem;
        font-weight: 600;
        border-bottom: 2px solid var(--primary-color);
        padding-bottom: 0.5rem;
        display: inline-block;
    }
    
    .order-info p {
        margin: 0.5rem 0;
        color: var(--secondary-color);
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: var(--secondary-color);
    }
    
    .form-group p {
        margin-top: 0.25rem;
        color: var(--dark-gray);
        font-size: 0.875rem;
    }
    
    .star-rating {
        display: flex;
        flex-direction: row-reverse;
        justify-content: flex-end;
        padding: 1rem 0;
    }
    
    .star-rating input {
        display: none;
    }
    
    .star-rating label {
        font-size: 2.5rem;
        color: #ddd;
        cursor: pointer;
        padding: 0 0.25rem;
        transition: all 0.2s ease;
    }
    
    .star-rating input:checked ~ label,
    .star-rating label:hover,
    .star-rating label:hover ~ label {
        color: var(--accent-color);
    }
    
    textarea {
        width: 100%;
        padding: 1rem;
        border: 1px solid var(--medium-gray);
        border-radius: var(--border-radius);
        resize: vertical;
        min-height: 150px;
        font-family: inherit;
        font-size: 1rem;
        transition: border-color 0.3s;
    }
    
    textarea:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
    }
    
    .button-group {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        margin-top: 2rem;
    }
    
    .btn {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: var(--border-radius);
        cursor: pointer;
        font-weight: 600;
        font-size: 1rem;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .btn-primary {
        background-color: var(--primary-color);
        color: white;
    }
    
    .btn-primary:hover {
        background-color: var(--primary-dark);
        transform: translateY(-2px);
    }
    
    .btn-secondary {
        background-color: var(--dark-gray);
        color: white;
        text-decoration: none;
    }
    
    .btn-secondary:hover {
        background-color: var(--secondary-color);
        transform: translateY(-2px);
    }
    
    .alert {
        padding: 1rem;
        border-radius: var(--border-radius);
        margin-bottom: 1.5rem;
    }
    
    .alert-error {
        background-color: #fee2e2;
        color: #b91c1c;
        border: 1px solid #f87171;
    }
    
    .review-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    
    .review-header p {
        max-width: 600px;
        margin: 0 auto;
        color: var(--dark-gray);
    }
    
    @media (max-width: 768px) {
        .review-container {
            padding: 1.5rem;
        }
        
        .star-rating label {
            font-size: 2rem;
        }
        
        .button-group {
            flex-direction: column;
        }
        
        .btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="page-content">
    <div class="review-header">
        <h1 class="page-title">Beri Ulasan</h1>
        <p>Ulasan Anda sangat berarti bagi kami untuk meningkatkan kualitas layanan. Silahkan berikan penilaian dan komentar Anda tentang layanan yang telah Anda terima.</p>
    </div>

    <?php if(isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="review-container">
        <div class="order-info">
            <h3>Informasi Pesanan</h3>
            <p><strong>No. Pesanan:</strong> #<?php echo $order_id; ?></p>
            <p><strong>Layanan:</strong> <?php echo $order['nama_layanan']; ?></p>
            <p><strong>Tanggal Order:</strong> <?php echo formatTanggal($order['tgl_order']); ?></p>
            <p><strong>Total Pembayaran:</strong> <?php echo formatRupiah($order['total_harga']); ?></p>
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Rating:</label>
                <div class="star-rating">
                    <input type="radio" id="star5" name="rating" value="5" required>
                    <label for="star5" title="Luar Biasa">★</label>
                    <input type="radio" id="star4" name="rating" value="4">
                    <label for="star4" title="Sangat Baik">★</label>
                    <input type="radio" id="star3" name="rating" value="3">
                    <label for="star3" title="Baik">★</label>
                    <input type="radio" id="star2" name="rating" value="2">
                    <label for="star2" title="Cukup">★</label>
                    <input type="radio" id="star1" name="rating" value="1">
                    <label for="star1" title="Buruk">★</label>
                </div>
                <p>Pilih bintang yang sesuai dengan penilaian Anda terhadap layanan kami</p>
            </div>
            
            <div class="form-group">
                <label>Komentar:</label>
                <textarea 
                    name="komentar" 
                    placeholder="Bagikan pengalaman Anda menggunakan layanan kami. Apa yang Anda sukai? Bagaimana kualitas pelayanan kami? Berikan saran untuk kami agar bisa lebih baik lagi." 
                    required
                ></textarea>
                <p>Saran dan komentar Anda sangat berharga untuk peningkatan kualitas layanan</p>
            </div>
            
            <div class="button-group">
                <a href="riwayat.php" class="btn btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/>
                    </svg>
                    Kembali
                </a>
                <button type="submit" name="submit_review" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425a.247.247 0 0 1 .02-.022Z"/>
                    </svg>
                    Kirim Ulasan
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>