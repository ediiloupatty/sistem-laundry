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

// Filter default dan penentuan jenis laporan
$laporan_type = isset($_GET['laporan_type']) ? $_GET['laporan_type'] : 'harian';

// Set tanggal berdasarkan jenis laporan
$today = date('Y-m-d');
$currentYear = date('Y');
$currentMonth = date('m');

// Default untuk custom adalah bulan ini
$default_start = date('Y-m-01'); // Awal bulan ini
$default_end = date('Y-m-t');    // Akhir bulan ini

// Set tanggal berdasarkan jenis laporan
if ($laporan_type == 'harian') {
    // Tampilkan data hari ini
    $start_date = $today;
    $end_date = $today;
} elseif ($laporan_type == 'bulanan') {
    // Tampilkan data bulan ini
    $start_date = date('Y-m-01'); // Awal bulan ini
    $end_date = date('Y-m-t');    // Akhir bulan ini
} elseif ($laporan_type == 'tahunan') {
    // Tampilkan data tahun ini
    $start_date = $currentYear . '-01-01'; // Awal tahun
    $end_date = $currentYear . '-12-31';   // Akhir tahun
} else {
    // Custom date range - ambil dari parameter GET jika ada
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : $default_start;
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : $default_end;
}

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
} elseif($laporan_type == 'bulanan') {
    $query = "SELECT DATE_FORMAT(o.tgl_order, '%Y-%m') as bulan,
              COUNT(o.id) as total_pesanan,
              SUM(o.total_harga) as total_pendapatan,
              COUNT(DISTINCT o.customer_id) as total_pelanggan
              FROM orders o
              WHERE DATE(o.tgl_order) BETWEEN '$start_date' AND '$end_date'
              AND o.status != 'dibatalkan'
              GROUP BY DATE_FORMAT(o.tgl_order, '%Y-%m')
              ORDER BY bulan DESC";
} elseif($laporan_type == 'tahunan') {
    $query = "SELECT YEAR(o.tgl_order) as tahun,
              COUNT(o.id) as total_pesanan,
              SUM(o.total_harga) as total_pendapatan,
              COUNT(DISTINCT o.customer_id) as total_pelanggan
              FROM orders o
              WHERE DATE(o.tgl_order) BETWEEN '$start_date' AND '$end_date'
              AND o.status != 'dibatalkan'
              GROUP BY YEAR(o.tgl_order)
              ORDER BY tahun DESC";
} else {
    // Custom - tentukan grouping berdasarkan rentang tanggal
    $date1 = new DateTime($start_date);
    $date2 = new DateTime($end_date);
    $interval = $date1->diff($date2);
    $days = $interval->days;
    
    if ($days > 365) {
        // Jika rentang lebih dari setahun, grouping per tahun
        $query = "SELECT YEAR(o.tgl_order) as tahun,
                  COUNT(o.id) as total_pesanan,
                  SUM(o.total_harga) as total_pendapatan,
                  COUNT(DISTINCT o.customer_id) as total_pelanggan
                  FROM orders o
                  WHERE DATE(o.tgl_order) BETWEEN '$start_date' AND '$end_date'
                  AND o.status != 'dibatalkan'
                  GROUP BY YEAR(o.tgl_order)
                  ORDER BY tahun DESC";
    } elseif ($days > 60) {
        // Jika rentang lebih dari 60 hari, grouping per bulan
        $query = "SELECT DATE_FORMAT(o.tgl_order, '%Y-%m') as bulan,
                  COUNT(o.id) as total_pesanan,
                  SUM(o.total_harga) as total_pendapatan,
                  COUNT(DISTINCT o.customer_id) as total_pelanggan
                  FROM orders o
                  WHERE DATE(o.tgl_order) BETWEEN '$start_date' AND '$end_date'
                  AND o.status != 'dibatalkan'
                  GROUP BY DATE_FORMAT(o.tgl_order, '%Y-%m')
                  ORDER BY bulan DESC";
    } else {
        // Jika kurang dari 60 hari, grouping per hari
        $query = "SELECT DATE(o.tgl_order) as tanggal, 
                  COUNT(o.id) as total_pesanan,
                  SUM(o.total_harga) as total_pendapatan,
                  COUNT(DISTINCT o.customer_id) as total_pelanggan
                  FROM orders o
                  WHERE DATE(o.tgl_order) BETWEEN '$start_date' AND '$end_date'
                  AND o.status != 'dibatalkan'
                  GROUP BY DATE(o.tgl_order)
                  ORDER BY tanggal DESC";
    }
}

// Eksekusi query untuk data laporan dan simpan hasilnya
$result = mysqli_query($koneksi, $query);

// Simpan data untuk chart sebelum menggunakan hasil query untuk tabel
$chart_labels = [];
$chart_data = [];

// Buat salinan data untuk chart
if (mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        if (isset($row['tanggal'])) {
            $chart_labels[] = date('d/m', strtotime($row['tanggal']));
        } elseif (isset($row['bulan'])) {
            $bulan_tahun = explode('-', $row['bulan']);
            $nama_bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 
                          'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            $chart_labels[] = $nama_bulan[(int)$bulan_tahun[1] - 1] . ' ' . $bulan_tahun[0];
        } elseif (isset($row['tahun'])) {
            $chart_labels[] = $row['tahun'];
        }
        $chart_data[] = $row['total_pendapatan'];
    }
    
    // Reset pointer hasil query untuk digunakan pada tampilan tabel
    mysqli_data_seek($result, 0);
}

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
        height: 400px; /* Tambahkan ketinggian tetap */
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
    /* Tambahan untuk disabled field */
    input[disabled] {
        background-color: #f8f9fa;
        cursor: not-allowed;
    }
</style>

<h1>Laporan Penjualan</h1>

<div class="filter-container">
    <form method="GET" class="filter-form" id="reportForm">
        <div class="form-group">
            <label>Jenis Laporan:</label>
            <select name="laporan_type" class="form-control" id="laporan_type">
                <option value="harian" <?php echo $laporan_type == 'harian' ? 'selected' : ''; ?>>Harian</option>
                <option value="bulanan" <?php echo $laporan_type == 'bulanan' ? 'selected' : ''; ?>>Bulanan</option>
                <option value="tahunan" <?php echo $laporan_type == 'tahunan' ? 'selected' : ''; ?>>Tahunan</option>
                <option value="custom" <?php echo $laporan_type == 'custom' ? 'selected' : ''; ?>>Custom</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Tanggal Mulai:</label>
            <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo $start_date; ?>" <?php echo ($laporan_type != 'custom') ? 'disabled' : ''; ?>>
        </div>
        
        <div class="form-group">
            <label>Tanggal Akhir:</label>
            <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo $end_date; ?>" <?php echo ($laporan_type != 'custom') ? 'disabled' : ''; ?>>
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
    <canvas id="pendapatanChart"></canvas>
</div>

<!-- Tabel Laporan -->
<h2>Detail Laporan</h2>
<table>
    <thead>
        <tr>
            <th><?php 
                if ($laporan_type == 'harian' || ($laporan_type == 'custom' && $interval->days <= 60)) {
                    echo 'Tanggal';
                } elseif ($laporan_type == 'bulanan' || ($laporan_type == 'custom' && $interval->days > 60 && $interval->days <= 365)) {
                    echo 'Bulan';
                } else {
                    echo 'Tahun';
                }
                ?></th>
            <th>Total Pesanan</th>
            <th>Total Pendapatan</th>
            <th>Total Pelanggan</th>
        </tr>
    </thead>
    <tbody>
        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td>
                    <?php 
                    if (isset($row['tanggal'])) {
                        echo date('d/m/Y', strtotime($row['tanggal']));
                    } elseif (isset($row['bulan'])) {
                        $bulan_tahun = explode('-', $row['bulan']);
                        $nama_bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                                     'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                        echo $nama_bulan[(int)$bulan_tahun[1] - 1] . ' ' . $bulan_tahun[0];
                    } elseif (isset($row['tahun'])) {
                        echo $row['tahun'];
                    }
                    ?>
                </td>
                <td><?php echo $row['total_pesanan']; ?></td>
                <td><?php echo formatRupiah($row['total_pendapatan']); ?></td>
                <td><?php echo $row['total_pelanggan']; ?></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="4" style="text-align: center;">Tidak ada data untuk periode yang dipilih</td>
            </tr>
        <?php endif; ?>
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
        <?php if (mysqli_num_rows($popular_service_result) > 0): ?>
            <?php while($service = mysqli_fetch_assoc($popular_service_result)): ?>
            <tr>
                <td><?php echo $service['nama_layanan']; ?></td>
                <td><?php echo $service['total_penggunaan']; ?> kali</td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="2" style="text-align: center;">Tidak ada data layanan untuk periode yang dipilih</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Data untuk grafik (diambil dari PHP)
    const chartLabels = <?php echo json_encode(array_reverse($chart_labels)); ?>;
    const chartData = <?php echo json_encode(array_reverse($chart_data)); ?>;
    
    // Pastikan elemen canvas sudah ada sebelum membuat chart
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('pendapatanChart').getContext('2d');
        
        // Cek apakah ada data untuk ditampilkan
        if (chartLabels.length > 0) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: 'Pendapatan',
                        data: chartData,
                        borderColor: '#007bff',
                        tension: 0.1,
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
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
        } else {
            // Jika tidak ada data, tampilkan pesan
            const chartContainer = document.querySelector('.chart-container');
            chartContainer.innerHTML = '<h3>Grafik Pendapatan</h3><p style="text-align: center; padding: 50px 0;">Tidak ada data untuk ditampilkan</p>';
        }
    });

    // Script untuk mengatur tanggal berdasarkan jenis laporan
    document.getElementById('laporan_type').addEventListener('change', function() {
        const selectedType = this.value;
        const startDateField = document.getElementById('start_date');
        const endDateField = document.getElementById('end_date');
        
        const today = new Date();
        const currentYear = today.getFullYear();
        const currentMonth = today.getMonth();
        
        // Format tanggal untuk input
        const formatDate = (date) => {
            const d = new Date(date);
            let month = '' + (d.getMonth() + 1);
            let day = '' + d.getDate();
            const year = d.getFullYear();
            
            if (month.length < 2) month = '0' + month;
            if (day.length < 2) day = '0' + day;
            
            return [year, month, day].join('-');
        };
        
        if (selectedType === 'harian') {
            const todayStr = formatDate(today);
            startDateField.value = todayStr;
            endDateField.value = todayStr;
            startDateField.disabled = true;
            endDateField.disabled = true;
        } else if (selectedType === 'bulanan') {
            // Awal bulan hingga akhir bulan
            const firstDayOfMonth = new Date(currentYear, currentMonth, 1);
            const lastDayOfMonth = new Date(currentYear, currentMonth + 1, 0);
            
            startDateField.value = formatDate(firstDayOfMonth);
            endDateField.value = formatDate(lastDayOfMonth);
            startDateField.disabled = true;
            endDateField.disabled = true;
        } else if (selectedType === 'tahunan') {
            // Awal tahun hingga akhir tahun
            const firstDayOfYear = new Date(currentYear, 0, 1);
            const lastDayOfYear = new Date(currentYear, 11, 31);
            
            startDateField.value = formatDate(firstDayOfYear);
            endDateField.value = formatDate(lastDayOfYear);
            startDateField.disabled = true;
            endDateField.disabled = true;
        } else {
            // Custom - enable fields
            startDateField.disabled = false;
            endDateField.disabled = false;
        }
    });

    // Auto submit form when changing report type (except for custom)
    document.getElementById('laporan_type').addEventListener('change', function() {
        if (this.value !== 'custom') {
            document.getElementById('reportForm').submit();
        }
    });
</script>

<?php include '../includes/footer.php'; ?>