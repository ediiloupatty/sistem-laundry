<?php
// File: admin/cetak_invoice.php
// Halaman untuk mencetak invoice pesanan

session_start();
require_once '../config/koneksi.php';
require_once '../includes/functions.php';

// Cek akses admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Jika tidak ada ID pesanan
if(!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$order_id = cleanInput($_GET['id']);

// Query untuk mendapatkan detail pesanan
$query_order = "SELECT o.*, c.nama as nama_pelanggan, c.alamat, c.no_hp, c.email
                FROM orders o 
                JOIN customers c ON o.customer_id = c.id 
                WHERE o.id = '$order_id'";
$result_order = mysqli_query($koneksi, $query_order);

if(mysqli_num_rows($result_order) == 0) {
    header("Location: dashboard.php");
    exit();
}

$order = mysqli_fetch_assoc($result_order);

// Query untuk mendapatkan detail item pesanan
$query_items = "SELECT od.*, s.nama_layanan 
                FROM order_details od
                JOIN services s ON od.service_id = s.id
                WHERE od.order_id = '$order_id'";
$result_items = mysqli_query($koneksi, $query_items);

// Query untuk mendapatkan informasi pembayaran
$query_payment = "SELECT * FROM payments WHERE order_id = '$order_id'";
$result_payment = mysqli_query($koneksi, $query_payment);
$payment = mysqli_fetch_assoc($result_payment);

// Ambil data toko dari database atau konfigurasi
$query_toko = "SELECT * FROM settings WHERE setting_key = 'toko_info'";
$result_toko = mysqli_query($koneksi, $query_toko);
$toko_info = mysqli_fetch_assoc($result_toko);

// Jika data toko tidak ada, gunakan default
if(!$toko_info) {
    $toko = [
        'nama' => 'Laundry App',
        'alamat' => 'Jl. Contoh No. 123',
        'telepon' => '081234567890',
        'email' => 'info@laundryapp.com'
    ];
} else {
    $toko = json_decode($toko_info['setting_value'], true);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $order_id; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .invoice-header {
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
        }
        .toko-info {
            text-align: right;
        }
        .invoice-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 5px;
        }
        .customer-info, .order-info {
            margin-bottom: 20px;
        }
        .customer-info h3, .order-info h3 {
            color: #555;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
        }
        .total-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-lunas {
            background-color: #28a745;
            color: #fff;
        }
        .status-pending {
            background-color: #ffc107;
            color: #333;
        }
        .status-dibatalkan {
            background-color: #dc3545;
            color: #fff;
        }
        .status-menunggu_konfirmasi {
            background-color: #ffc107;
            color: #000;
        }
        .status-diproses {
            background-color: #17a2b8;
            color: #fff;
        }
        .status-selesai {
            background-color: #28a745;
            color: #fff;
        }
        .status-siap_diantar {
            background-color: #007bff;
            color: #fff;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #777;
            text-align: center;
        }
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
            font-size: 14px;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .actions {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
        }
        @media print {
            body {
                background-color: #fff;
                padding: 0;
            }
            .invoice-container {
                box-shadow: none;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="invoice-header">
            <div>
                <h1 class="invoice-title">INVOICE</h1>
                <p>No. Invoice: #<?php echo $order_id; ?></p>
                <p>Tanggal: <?php echo formatTanggal($order['tgl_order']); ?></p>
            </div>
            <div class="toko-info">
                <h2><?php echo $toko['nama']; ?></h2>
                <p><?php echo $toko['alamat']; ?></p>
                <p>Telp: <?php echo $toko['telepon']; ?></p>
                <p>Email: <?php echo $toko['email']; ?></p>
            </div>
        </div>
        
        <div class="info-grid">
            <div class="customer-info">
                <h3>Informasi Pelanggan</h3>
                <p><strong>Nama:</strong> <?php echo $order['nama_pelanggan']; ?></p>
                <p><strong>Alamat:</strong> <?php echo $order['alamat']; ?></p>
                <p><strong>No. HP:</strong> <?php echo $order['no_hp']; ?></p>
                <p><strong>Email:</strong> <?php echo $order['email']; ?></p>
            </div>
            
            <div class="order-info">
                <h3>Informasi Pesanan</h3>
                <p><strong>Status Pesanan:</strong> 
                    <span class="status-badge status-<?php echo $order['status']; ?>">
                        <?php echo ucwords(str_replace('_', ' ', $order['status'])); ?>
                    </span>
                </p>
                <p><strong>Metode Antar:</strong> <?php echo ucfirst(str_replace('_', ' ', $order['metode_antar'])); ?></p>
                
                <?php if($order['metode_antar'] == 'jemput'): ?>
                <p><strong>Alamat Jemput:</strong> <?php echo $order['alamat_jemput']; ?></p>
                <?php endif; ?>
                
                <?php if(isset($payment)): ?>
                <p>
                    <strong>Status Pembayaran:</strong>
                    <span class="status-badge status-<?php echo $payment['status_pembayaran']; ?>">
                        <?php 
                            $statusLabels = [
                                'pending' => 'Menunggu Pembayaran',
                                'lunas' => 'Lunas',
                                'dibatalkan' => 'Dibatalkan',
                                'ditolak' => 'Ditolak'
                            ];
                            echo $statusLabels[$payment['status_pembayaran']] ?? ucfirst($payment['status_pembayaran']);
                        ?>
                    </span>
                </p>
                <p><strong>Metode Pembayaran:</strong> <?php echo ucfirst($payment['metode_pembayaran'] ?? '-'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Layanan</th>
                    <th>Jenis Pakaian</th>
                    <th>Jumlah</th>
                    <th>Berat (kg)</th>
                    <th>Harga</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                mysqli_data_seek($result_items, 0); // Reset pointer result ke awal
                while($item = mysqli_fetch_assoc($result_items)): 
                ?>
                <tr>
                    <td><?php echo $item['nama_layanan']; ?></td>
                    <td><?php echo $item['jenis_pakaian']; ?></td>
                    <td><?php echo $item['jumlah']; ?></td>
                    <td><?php echo $item['berat']; ?> kg</td>
                    <td><?php echo formatRupiah($item['harga']); ?></td>
                    <td><?php echo formatRupiah($item['subtotal']); ?></td>
                </tr>
                <?php endwhile; ?>
                <tr class="total-row">
                    <td colspan="5" style="text-align: right;">Total</td>
                    <td><?php echo formatRupiah($order['total_harga']); ?></td>
                </tr>
            </tbody>
        </table>
        
        <?php if(!empty($order['catatan'])): ?>
        <div>
            <h3>Catatan:</h3>
            <p><?php echo $order['catatan']; ?></p>
        </div>
        <?php endif; ?>
        
        <div class="footer">
            <p>Terima kasih telah menggunakan jasa laundry kami!</p>
            <p>Mohon simpan invoice ini sebagai bukti pembayaran dan pengambilan pesanan.</p>
        </div>
        
        <div class="actions no-print">
            <a href="kelola_pesanan.php?id=<?php echo $order_id; ?>" class="btn btn-primary">Kembali</a>
            <button onclick="window.print()" class="btn btn-primary">Cetak</button>
        </div>
    </div>
</body>
</html>