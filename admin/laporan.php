<?php
// File: admin/laporan.php
// Halaman laporan penjualan

session_start();
require_once '../config/koneksi.php';
require_once '../includes/functions.php';

// Cek apakah user adalah admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$page_title = "Laporan";

// Filter default
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$laporan_type = isset($_GET['laporan_type']) ? $_GET['laporan_type'] : 'harian';

// Query untuk mendapatkan data laporan
if($laporan_type == 'harian') {
    $query = "SELECT DATE(o.tgl_order) as tanggal, 
              COUNT(o.id) as total_pesanan,
              SUM(o.total_harga) as total_pendapatan,
              COUNT(DISTINCT o.customer_id) as total_pelanggan
              FROM orders o
              WHERE DATE(o.tgl_order) BETWEEN '$start_date' AND '$end_date'
              AND o.status != 'dibatalkan'
              GROUP BY DATE(o.tgl_order)
              ORDER BY tanggal DESC";
} else {
    // Laporan bulanan
    $query = "SELECT DATE_FORMAT(o.tgl_order, '%Y-%m') as bulan,
              COUNT(o.id) as total_pesanan,
              SUM(o.total_harga) as total_pendapatan,
              COUNT(DISTINCT o.customer_id) as total_pelanggan
              FROM orders o
              WHERE DATE(o.tgl_order) BETWEEN '$start_date' AND '$end_date'
              AND o.status != 'dibatalkan'
              GROUP BY DATE_FORMAT(o.tgl_order, '%Y-%m')
              ORDER BY bulan DESC";
}

$result = mysqli_query($koneksi, $query);

// Query untuk statistik overview
$overview_query = "SELECT 
                   COUNT(*) as total_pesanan,
                   SUM(total_harga) as total_pendapatan,
                   COUNT(DISTINCT customer_id) as total_pelanggan
                   FROM orders
                   WHERE DATE(tgl_order) BETWEEN '$start_date' AND '$end_date'
                   AND status != 'dibatalkan'";
$overview_result = mysqli_query($koneksi, $overview_query);
$overview = mysqli_fetch_assoc($overview_result);

// Query untuk layanan terpopuler
$popular_service_query = "SELECT s.nama_layanan, COUNT(od.id) as total_penggunaan
                         FROM order_details od
                         JOIN services s ON od.service_id = s.id
                         JOIN orders o ON od.order_id = o.id
                         WHERE DATE(o.tgl_order) BETWEEN '$start_date' AND '$end_date'
                         AND o.status != 'dibatalkan'
                         GROUP BY s.id
                         ORDER BY total_penggunaan DESC
                         LIMIT 5";
$popular_service_result = mysqli_query($koneksi, $popular_service_query);

include '../includes/header.php';
?>

<style>
    .filter-container {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    .filter-form {
        display: flex;
        gap: 15px;
        align-items: flex-end;
    }
    .form-group {
        flex: 1;
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
        padding: 8px 15px;
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
    .btn-success {
        background-color: #28a745;
        color: white;
    }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .stat-card h3 {
        margin: 0 0 10px 0;
        color: #666;
        font-size: 16px;
    }
    .stat-card .value {
        font-size: 24px;
        font-weight: bold;
        color: #333;
    }
    .stat-card.primary {
        border-left: 4px solid #007bff;
    }
    .stat-card.success {
        border-left: 4px solid #28a745;
    }
    .stat-card.info {
        border-left: 4px solid #17a2b8;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        margin-top: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
    .chart-container {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    .download-pdf {
        display: inline-block;
        padding: 10px 20px;
        background-color: #dc3545;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        margin-bottom: 20px;
    }
    .download-pdf:hover {
        background-color: #c82333;
    }
</style>

<h1>Laporan Penjualan</h1>

<div class="filter-container">
    <form method="GET" class="filter-form">
        <div class="form-group">
            <label>Jenis Laporan:</label>
            <select name="laporan_type" class="form-control">
                <option value="harian" <?php echo $laporan_type == 'harian' ? 'selected' : ''; ?>>Harian</option>
                <option value="bulanan" <?php echo $laporan_type == 'bulanan' ? 'selected' : ''; ?>>Bulanan</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Tanggal Mulai:</label>
            <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
        </div>
        
        <div class="form-group">
            <label>Tanggal Akhir:</label>
            <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
        </div>
        
        <button type="submit" class="btn btn-primary">Tampilkan</button>
    </form>
</div>

<div class="stats-grid">
    <div class="stat-card primary">
        <h3>Total Pesanan</h3>
        <div class="value"><?php echo $overview['total_pesanan']; ?></div>
    </div>
    
    <div class="stat-card success">
        <h3>Total Pendapatan</h3>
        <div class="value"><?php echo formatRupiah($overview['total_pendapatan']); ?></div>
    </div>
    
    <div class="stat-card info">
        <h3>Total Pelanggan</h3>
        <div class="value"><?php echo $overview['total_pelanggan']; ?></div>
    </div>
</div>

<a href="cetak_laporan.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&type=<?php echo $laporan_type; ?>" 
   class="download-pdf" target="_blank">Download PDF</a>

<!-- Chart Container -->
<div class="chart-container">
    <h3>Grafik Pendapatan</h3>
    <canvas id="pendapatanChart" height="100"></canvas>
</div>

<!-- Tabel Laporan -->
<h2>Detail Laporan</h2>
<table>
    <thead>
        <tr>
            <th><?php echo $laporan_type == 'harian' ? 'Tanggal' : 'Bulan'; ?></th>
            <th>Total Pesanan</th>
            <th>Total Pendapatan</th>
            <th>Total Pelanggan</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = mysqli_fetch_assoc($result)): ?>
        <tr>
            <td>
                <?php 
                if($laporan_type == 'harian') {
                    echo date('d/m/Y', strtotime($row['tanggal']));
                } else {
                    $bulan_tahun = explode('-', $row['bulan']);
                    $nama_bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                                 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                    echo $nama_bulan[(int)$bulan_tahun[1] - 1] . ' ' . $bulan_tahun[0];
                }
                ?>
            </td>
            <td><?php echo $row['total_pesanan']; ?></td>
            <td><?php echo formatRupiah($row['total_pendapatan']); ?></td>
            <td><?php echo $row['total_pelanggan']; ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<!-- Layanan Terpopuler -->
<h2 style="margin-top: 30px;">Layanan Terpopuler</h2>
<table>
    <thead>
        <tr>
            <th>Nama Layanan</th>
            <th>Total Penggunaan</th>
        </tr>
    </thead>
    <tbody>
        <?php while($service = mysqli_fetch_assoc($popular_service_result)): ?>
        <tr>
            <td><?php echo $service['nama_layanan']; ?></td>
            <td><?php echo $service['total_penggunaan']; ?> kali</td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Data untuk grafik
    <?php
    mysqli_data_seek($result, 0); // Reset pointer hasil query
    $labels = [];
    $data = [];
    
    while($row = mysqli_fetch_assoc($result)) {
        if($laporan_type == 'harian') {
            $labels[] = date('d/m', strtotime($row['tanggal']));
        } else {
            $bulan_tahun = explode('-', $row['bulan']);
            $nama_bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 
                          'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            $labels[] = $nama_bulan[(int)$bulan_tahun[1] - 1] . ' ' . $bulan_tahun[0];
        }
        $data[] = $row['total_pendapatan'];
    }
    ?>
    
    const ctx = document.getElementById('pendapatanChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_reverse($labels)); ?>,
            datasets: [{
                label: 'Pendapatan',
                data: <?php echo json_encode(array_reverse($data)); ?>,
                borderColor: '#007bff',
                tension: 0.1,
                fill: false
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                }
            }
        }
    });
</script>

<?php include '../includes/footer.php'; ?>