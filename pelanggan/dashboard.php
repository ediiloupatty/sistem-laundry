<?php
// File: pelanggan/dashboard.php
// Dashboard untuk pelanggan

session_start();
require_once '../config/koneksi.php';
require_once '../includes/functions.php';

// Cek apakah user adalah pelanggan
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pelanggan') {
    header("Location: ../login.php");
    exit();
}

$page_title = "Dashboard Pelanggan";
$customer_id = $_SESSION['customer_id'];

// Query untuk mendapatkan statistik
// Total pesanan pelanggan
$query_total = "SELECT COUNT(*) as total FROM orders WHERE customer_id = '$customer_id'";
$result_total = mysqli_query($koneksi, $query_total);
$total_orders = mysqli_fetch_assoc($result_total)['total'];

// Pesanan dalam proses
$query_process = "SELECT COUNT(*) as total FROM orders WHERE customer_id = '$customer_id' AND status IN ('menunggu_konfirmasi', 'diproses')";
$result_process = mysqli_query($koneksi, $query_process);
$process_orders = mysqli_fetch_assoc($result_process)['total'];

// Total pengeluaran
$query_spending = "SELECT SUM(total_harga) as total FROM orders WHERE customer_id = '$customer_id' AND status != 'dibatalkan'";
$result_spending = mysqli_query($koneksi, $query_spending);
$total_spending = mysqli_fetch_assoc($result_spending)['total'] ?? 0;

// Pesanan terbaru
$query_latest = "SELECT * FROM orders WHERE customer_id = '$customer_id' ORDER BY tgl_order DESC LIMIT 5";
$result_latest = mysqli_query($koneksi, $query_latest);

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
    
    body {
        background-color: var(--light-gray);
        font-family: 'Nunito', 'Segoe UI', Arial, sans-serif;
    }
    
    .main-content {
        padding: 25px;
    }
    
    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }
    
    .welcome-section {
        margin-bottom: 30px;
    }
    
    .welcome-text {
        font-size: 28px;
        font-weight: 700;
        color: #5a5c69;
        margin-bottom: 10px;
    }
    
    .welcome-subtext {
        font-size: 16px;
        color: var(--dark-gray);
    }
    
    /* Slideshow styling */
    .slideshow-container {
        position: relative;
        width: 100%;
        height: 250px;
        overflow: hidden;
        margin-bottom: 30px;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
    }
    
    .slide {
        position: absolute;
        width: 100%;
        height: 100%;
        opacity: 0;
        transition: opacity 1s ease-in-out;
        background-size: cover;
        background-position: center;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        color: white;
        text-align: center;
        padding: 20px;
    }
    
    .slide.active {
        opacity: 1;
    }
    
    .slide-content {
        background-color: rgba(0, 0, 0, 0.6);
        padding: 20px;
        border-radius: var(--border-radius);
        max-width: 80%;
    }
    
    .slide-title {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 10px;
    }
    
    .slide-description {
        font-size: 16px;
        margin-bottom: 15px;
    }
    
    .slide-button {
        background-color: var(--primary-color);
        color: white;
        border: none;
        padding: 8px 20px;
        border-radius: 4px;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
    }
    
    .slide-button:hover {
        background-color: var(--primary-dark);
    }
    
    .slide-indicators {
        position: absolute;
        bottom: 20px;
        left: 0;
        right: 0;
        display: flex;
        justify-content: center;
        gap: 10px;
    }
    
    .indicator {
        width: 12px;
        height: 12px;
        background-color: rgba(255, 255, 255, 0.5);
        border-radius: 50%;
        cursor: pointer;
        transition: var(--transition);
    }
    
    .indicator.active {
        background-color: white;
        transform: scale(1.2);
    }
    
    /* Dashboard stats cards */
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 25px;
        margin-bottom: 30px;
    }
    
    .dashboard-card {
        background: white;
        padding: 20px;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        transition: var(--transition);
        border-left: 4px solid transparent;
        overflow: hidden;
        position: relative;
    }
    
    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    }
    
    .dashboard-card h3 {
        margin: 0 0 10px 0;
        color: var(--dark-gray);
        font-size: 16px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .dashboard-card .value {
        font-size: 28px;
        font-weight: 700;
        color: #5a5c69;
    }
    
    .dashboard-card .icon {
        position: absolute;
        top: 20px;
        right: 20px;
        font-size: 24px;
        color: var(--mid-gray);
    }
    
    .dashboard-card.primary {
        border-left: 4px solid var(--primary-color);
    }
    
    .dashboard-card.warning {
        border-left: 4px solid var(--warning);
    }
    
    .dashboard-card.success {
        border-left: 4px solid var(--success);
    }
    
    .dashboard-card.info {
        border-left: 4px solid var(--info);
    }
    
    .btn {
        display: inline-block;
        padding: 12px 24px;
        background-color: var(--primary-color);
        color: white;
        text-decoration: none;
        border-radius: var(--border-radius);
        margin-bottom: 20px;
        font-weight: 600;
        transition: var(--transition);
        box-shadow: 0 2px 10px rgba(78, 115, 223, 0.3);
        border: none;
        cursor: pointer;
    }
    
    .btn:hover {
        background-color: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(78, 115, 223, 0.4);
    }
    
    /* Recent orders table */
    .orders-section {
        background-color: white;
        padding: 25px;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        margin-bottom: 30px;
    }
    
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .section-title {
        font-size: 18px;
        font-weight: 700;
        color: #5a5c69;
        margin: 0;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }
    
    th, td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid var(--mid-gray);
    }
    
    th {
        background-color: var(--light-gray);
        font-weight: 600;
        color: var(--dark-gray);
        font-size: 14px;
        text-transform: uppercase;
    }
    
    tr:hover {
        background-color: var(--light-gray);
    }
    
    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .status-menunggu_konfirmasi {
        background-color: var(--warning);
        color: #fff;
    }
    
    .status-diproses {
        background-color: var(--info);
        color: #fff;
    }
    
    .status-selesai {
        background-color: var(--success);
        color: #fff;
    }
    
    .status-siap_diantar {
        background-color: #6f42c1;
        color: #fff;
    }
    
    .status-dibatalkan {
        background-color: var(--danger);
        color: #fff;
    }
    
    .action-link {
        color: var(--primary-color);
        text-decoration: none;
        font-weight: 600;
        transition: var(--transition);
        margin-right: 10px;
    }
    
    .action-link:hover {
        color: var(--primary-dark);
    }
    
    .action-link.success {
        color: var(--success);
    }
    
    .action-link.success:hover {
        color: #169b6b;
    }
    
    /* Animations */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .animate-fade {
        animation: fadeIn 0.5s ease-in-out;
    }
    
    /* Responsiveness */
    @media (max-width: 768px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
        }
        
        .slideshow-container {
            height: 200px;
        }
    }
</style>

<div class="main-content animate-fade">
    <div class="dashboard-header">
        <div class="welcome-section">
            <h1 class="welcome-text">Selamat Datang, <?php echo $_SESSION['nama']; ?></h1>
            <p class="welcome-subtext">Lihat dan kelola pesanan Anda dari dashboard ini</p>
        </div>
        <a href="buat_pesanan.php" class="btn">
            <i class="fas fa-plus"></i> Buat Pesanan Baru
        </a>
    </div>
    
    <!-- Slideshow Container -->
    <div class="slideshow-container">
        <div class="slide active" style="background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('assets/images/slide1.jpg');">
            <div class="slide-content">
                <h2 class="slide-title">Layanan Laundry Terbaik</h2>
                <p class="slide-description">Nikmati layanan laundry profesional dengan hasil terbaik</p>
                <button class="slide-button" onclick="location.href='buat_pesanan.php'">Pesan Sekarang</button>
            </div>
        </div>
        
        <div class="slide" style="background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('assets/images/slide2.jpg');">
            <div class="slide-content">
                <h2 class="slide-title">Promo Spesial</h2>
                <p class="slide-description">Dapatkan diskon 20% untuk pelanggan baru</p>
                <button class="slide-button" onclick="location.href='promo.php'">Lihat Promo</button>
            </div>
        </div>
        
        <div class="slide" style="background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('assets/images/slide3.jpg');">
            <div class="slide-content">
                <h2 class="slide-title">Layanan Antar Jemput</h2>
                <p class="slide-description">Tersedia layanan antar jemput untuk area tertentu</p>
                <button class="slide-button" onclick="location.href='layanan.php'">Cek Area</button>
            </div>
        </div>
        
        <div class="slide-indicators">
            <div class="indicator active" onclick="currentSlide(1)"></div>
            <div class="indicator" onclick="currentSlide(2)"></div>
            <div class="indicator" onclick="currentSlide(3)"></div>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="dashboard-card primary animate-fade">
            <h3>Total Pesanan</h3>
            <div class="value"><?php echo $total_orders; ?></div>
            <div class="icon"><i class="fas fa-shopping-basket"></i></div>
        </div>
        
        <div class="dashboard-card warning animate-fade">
            <h3>Pesanan Dalam Proses</h3>
            <div class="value"><?php echo $process_orders; ?></div>
            <div class="icon"><i class="fas fa-spinner"></i></div>
        </div>
        
        <div class="dashboard-card success animate-fade">
            <h3>Total Pengeluaran</h3>
            <div class="value"><?php echo formatRupiah($total_spending); ?></div>
            <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
        </div>
    </div>

    <div class="orders-section animate-fade">
        <div class="section-header">
            <h2 class="section-title">Pesanan Terbaru</h2>
            <a href="semua_pesanan.php" class="action-link">Lihat Semua</a>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>ID Pesanan</th>
                    <th>Tanggal</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($result_latest) > 0): ?>
                    <?php while($order = mysqli_fetch_assoc($result_latest)): ?>
                    <tr>
                        <td>#<?php echo $order['id']; ?></td>
                        <td><?php echo formatTanggal($order['tgl_order']); ?></td>
                        <td><?php echo formatRupiah($order['total_harga']); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                <?php echo ucwords(str_replace('_', ' ', $order['status'])); ?>
                            </span>
                        </td>
                        <td>
                            <a href="tracking.php?id=<?php echo $order['id']; ?>" class="action-link">
                                <i class="fas fa-search"></i> Detail
                            </a>
                            <?php if($order['status'] == 'selesai'): ?>
                                <a href="invoice.php?id=<?php echo $order['id']; ?>" class="action-link success">
                                    <i class="fas fa-file-invoice"></i> Invoice
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">Belum ada pesanan</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Script untuk slideshow -->
<script>
    let slideIndex = 1;
    let slideInterval;
    
    // Auto slide change every 5 seconds
    function startSlideShow() {
        slideInterval = setInterval(() => {
            changeSlide(1);
        }, 5000);
    }
    
    function changeSlide(n) {
        showSlide(slideIndex += n);
    }
    
    function currentSlide(n) {
        showSlide(slideIndex = n);
        
        // Reset interval when manually changing slides
        clearInterval(slideInterval);
        startSlideShow();
    }
    
    function showSlide(n) {
        const slides = document.getElementsByClassName("slide");
        const indicators = document.getElementsByClassName("indicator");
        
        if (n > slides.length) { slideIndex = 1 }
        if (n < 1) { slideIndex = slides.length }
        
        // Hide all slides
        for (let i = 0; i < slides.length; i++) {
            slides[i].classList.remove("active");
            indicators[i].classList.remove("active");
        }
        
        // Show current slide
        slides[slideIndex - 1].classList.add("active");
        indicators[slideIndex - 1].classList.add("active");
    }
    
    // Initialize slideshow
    document.addEventListener("DOMContentLoaded", function() {
        showSlide(slideIndex);
        startSlideShow();
    });
</script>

<?php include '../includes/footer.php'; ?>