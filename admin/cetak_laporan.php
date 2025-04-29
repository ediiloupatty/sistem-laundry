<?php
// File: admin/cetak_laporan.php
// Halaman untuk mencetak laporan ke PDF

session_start();
require_once '../config/koneksi.php';
require_once '../includes/functions.php';

// Cek apakah user adalah admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$laporan_type = isset($_GET['type']) ? $_GET['type'] : 'harian';

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

// Karena XAMPP biasanya belum ada library PDF, kita buat HTML dulu yang bisa di-print
// Jika ingin menggunakan library PDF seperti FPDF atau TCPDF, bisa diinstall terpisah
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan - <?php echo date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date)); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        h1, h2, h3 {
            text-align: center;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header img {
            max-width: 150px;
            margin-bottom: 10px;
        }
        .info {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
        }
        .text-right {
            text-align: right;
        }
        .summary-box {
            border: 1px solid #000;
            padding: 15px;
            margin-bottom: 20px;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
        }
        @media print {
            body {
                margin: 0;
                padding: 10px;
            }
            .no-print {
                display: none;
            }
        }
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .print-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">Cetak Laporan</button>
    
    <div class="header">
        <h1>LAPORAN PENJUALAN</h1>
        <h2>SISTEM LAUNDRY</h2>
        <p>Periode: <?php echo date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date)); ?></p>
        <p>Jenis Laporan: <?php echo ucwords($laporan_type); ?></p>
    </div>
    
    <div class="summary-box">
        <h3>RINGKASAN</h3>
        <table>
            <tr>
                <td width="50%">Total Pesanan</td>
                <td class="text-right"><?php echo $overview['total_pesanan']; ?> pesanan</td>
            </tr>
            <tr>
                <td>Total Pendapatan</td>
                <td class="text-right"><?php echo formatRupiah($overview['total_pendapatan']); ?></td>
            </tr>
            <tr>
                <td>Total Pelanggan</td>
                <td class="text-right"><?php echo $overview['total_pelanggan']; ?> pelanggan</td>
            </tr>
        </table>
    </div>
    
    <h3>DETAIL LAPORAN</h3>
    <table>
        <thead>
            <tr>
                <th><?php echo $laporan_type == 'harian' ? 'Tanggal' : 'Bulan'; ?></th>
                <th class="text-right">Total Pesanan</th>
                <th class="text-right">Total Pendapatan</th>
                <th class="text-right">Total Pelanggan</th>
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
                <td class="text-right"><?php echo $row['total_pesanan']; ?></td>
                <td class="text-right"><?php echo formatRupiah($row['total_pendapatan']); ?></td>
                <td class="text-right"><?php echo $row['total_pelanggan']; ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <?php
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
    ?>
    
    <h3>LAYANAN TERPOPULER</h3>
    <table>
        <thead>
            <tr>
                <th>Nama Layanan</th>
                <th class="text-right">Total Penggunaan</th>
            </tr>
        </thead>
        <tbody>
            <?php while($service = mysqli_fetch_assoc($popular_service_result)): ?>
            <tr>
                <td><?php echo $service['nama_layanan']; ?></td>
                <td class="text-right"><?php echo $service['total_penggunaan']; ?> kali</td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <div class="footer">
        <p>Dicetak pada: <?php echo date('d/m/Y H:i:s'); ?></p>
        <p>Oleh: <?php echo $_SESSION['username']; ?> (Admin)</p>
        <br><br>
        <p>_________________________</p>
        <p>Tanda Tangan Admin</p>
    </div>
    
    <script>
        // Auto print ketika halaman dibuka
        window.onload = function() {
            // window.print();
        }
    </script>
</body>
</html>