<?php
// File: pelanggan/invoice.php
// Halaman untuk mencetak invoice

session_start();
require_once '../config/koneksi.php';
require_once '../includes/functions.php';

// Cek apakah user adalah pelanggan
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pelanggan') {
    header("Location: ../login.php");
    exit();
}

$order_id = isset($_GET['id']) ? cleanInput($_GET['id']) : 0;
$customer_id = $_SESSION['customer_id'];

// Ambil detail pesanan
$query = "SELECT o.*, c.nama, c.alamat, c.no_hp, c.email,
          p.status_pembayaran, p.metode_pembayaran, p.tgl_pembayaran
          FROM orders o
          JOIN customers c ON o.customer_id = c.id
          LEFT JOIN payments p ON o.id = p.order_id
          WHERE o.id = '$order_id' AND o.customer_id = '$customer_id'";
$result = mysqli_query($koneksi, $query);
$order = mysqli_fetch_assoc($result);

if(!$order) {
    header("Location: riwayat.php");
    exit();
}

// Ambil detail item pesanan
$items_query = "SELECT od.*, s.nama_layanan
                FROM order_details od
                JOIN services s ON od.service_id = s.id
                WHERE od.order_id = '$order_id'";
$items_result = mysqli_query($koneksi, $items_query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?php echo $order_id; ?> - Sistem Laundry</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border: 1px solid #ddd;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            color: #333;
        }
        .invoice-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .invoice-info div {
            width: 48%;
        }
        .invoice-detail {
            margin-bottom: 20px;
        }
        .invoice-detail h3 {
            margin: 0 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 2px solid #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
        }
        .text-right {
            text-align: right;
        }
        .total-row {
            font-weight: bold;
            font-size: 1.1em;
        }
        .status {
            margin: 20px 0;
            padding: 10px;
            background: #e9ecef;
            border-radius: 4px;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            color: #666;
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
        @media print {
            .print-button {
                display: none;
            }
            body {
                padding: 0;
            }
            .invoice-container {
                border: none;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">Cetak Invoice</button>
    
    <div class="invoice-container">
        <div class="header">
            <h1>INVOICE</h1>
            <p>Sistem Laundry</p>
            <p>Nomor Invoice: INV-<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></p>
        </div>
        
        <div class="invoice-info">
            <div>
                <div class="invoice-detail">
                    <h3>Informasi Pelanggan</h3>
                    <p><strong><?php echo $order['nama']; ?></strong></p>
                    <p><?php echo $order['alamat']; ?></p>
                    <p>Telp: <?php echo $order['no_hp']; ?></p>
                    <p>Email: <?php echo $order['email']; ?></p>
                </div>
            </div>
            
            <div>
                <div class="invoice-detail">
                    <h3>Detail Invoice</h3>
                    <p><strong>Tanggal:</strong> <?php echo formatTanggal($order['tgl_order']); ?></p>
                    <p><strong>Status:</strong> <?php echo ucwords(str_replace('_', ' ', $order['status'])); ?></p>
                    <p><strong>Pembayaran:</strong> <?php echo ucfirst($order['status_pembayaran']); ?></p>
                    <p><strong>Metode:</strong> <?php echo ucfirst($order['metode_pembayaran']); ?></p>
                </div>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Layanan</th>
                    <th>Jenis Pakaian</th>
                    <th>Jumlah</th>
                    <th>Berat (kg)</th>
                    <th>Harga/kg</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php while($item = mysqli_fetch_assoc($items_result)): ?>
                <tr>
                    <td><?php echo $item['nama_layanan']; ?></td>
                    <td><?php echo $item['jenis_pakaian']; ?></td>
                    <td><?php echo $item['jumlah']; ?> pcs</td>
                    <td><?php echo $item['berat']; ?> kg</td>
                    <td><?php echo formatRupiah($item['harga']); ?></td>
                    <td class="text-right"><?php echo formatRupiah($item['subtotal']); ?></td>
                </tr>
                <?php endwhile; ?>
                <tr class="total-row">
                    <td colspan="5" class="text-right">Total:</td>
                    <td class="text-right"><?php echo formatRupiah($order['total_harga']); ?></td>
                </tr>
            </tbody>
        </table>
        
        <?php if($order['catatan']): ?>
        <div class="status">
            <strong>Catatan:</strong> <?php echo $order['catatan']; ?>
        </div>
        <?php endif; ?>
        
        <div class="footer">
            <p>Terima kasih telah menggunakan layanan kami</p>
            <p>Jika ada pertanyaan, silakan hubungi kami di (021) 1234567</p>
        </div>
    </div>
</body>
</html>