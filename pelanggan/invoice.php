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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $order_id; ?> - Sistem Laundry</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', sans-serif;
        background-color: #f1f5f9;
        color: #334155;
        line-height: 1.6;
        padding: 2rem 1rem;
    }

    .invoice-container {
        max-width: 800px;
        margin: 0 auto;
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        overflow: hidden;
    }

    .invoice-header {
        background-color: var(--primary-color);
        color: white;
        padding: 2rem;
        text-align: center;
        position: relative;
    }

    .invoice-header h1 {
        margin: 0;
        font-size: 2.5rem;
        font-weight: 700;
        letter-spacing: -0.5px;
    }

    .brand-name {
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        opacity: 0.9;
    }

    .invoice-number {
        font-size: 1rem;
        background: rgba(255, 255, 255, 0.2);
        padding: 0.5rem 1rem;
        border-radius: 50px;
        display: inline-block;
        margin-top: 1rem;
    }

    .invoice-body {
        padding: 2rem;
    }

    .invoice-info {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        margin-bottom: 2.5rem;
    }

    .invoice-detail {
        background: var(--light-gray);
        padding: 1.5rem;
        border-radius: var(--border-radius);
        height: 100%;
    }

    .invoice-detail h3 {
        color: var(--secondary-color);
        font-size: 1rem;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid var(--primary-color);
        display: inline-block;
    }

    .invoice-detail p {
        margin: 0.5rem 0;
        color: var(--secondary-color);
        line-height: 1.5;
    }

    .invoice-customer strong {
        color: var(--secondary-color);
        font-size: 1.1rem;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 2rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border-radius: var(--border-radius);
        overflow: hidden;
        table-layout: fixed; /* Menambahkan fixed table layout */
    }

    th {
        background-color: var(--secondary-color);
        color: white;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        padding: 1rem;
        text-align: left;
        white-space: nowrap; /* Mencegah text wrapping pada header */
    }

    td {
        padding: 1rem;
        border-bottom: 1px solid var(--mid-gray);
        vertical-align: middle; /* Rata tengah secara vertikal */
    }

    tr:last-child td {
        border-bottom: none;
    }

    tr:nth-child(even) {
        background-color: var(--light-gray);
    }

    /* Mendefinisikan lebar kolom tabel */
    th:nth-child(1), td:nth-child(1) { width: 20%; }
    th:nth-child(2), td:nth-child(2) { width: 20%; }
    th:nth-child(3), td:nth-child(3) { width: 10%; }
    th:nth-child(4), td:nth-child(4) { width: 15%; }
    th:nth-child(5), td:nth-child(5) { width: 15%; }
    th:nth-child(6), td:nth-child(6) { width: 20%; }

    .text-right {
        text-align: right;
    }

    .total-row td {
        font-weight: 700;
        background-color: var(--secondary-color);
        color: white;
        font-size: 1.1rem;
        padding: 1rem;
    }

    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.875rem;
        font-weight: 600;
        text-transform: capitalize;
        margin-top: 0.25rem;
    }

    .status-paid {
        background-color: rgba(46, 204, 113, 0.1);
        color: #2ecc71;
    }

    .status-pending {
        background-color: rgba(243, 156, 18, 0.1);
        color: #f39c12;
    }

    .note-box {
        background-color: var(--light-gray);
        border-left: 4px solid var(--primary-color);
        padding: 1.25rem;
        border-radius: var(--border-radius);
        margin-bottom: 2rem;
    }

    .note-box strong {
        display: block;
        margin-bottom: 0.5rem;
        color: var(--secondary-color);
    }

    .invoice-footer {
        text-align: center;
        padding: 2rem;
        background-color: var(--light-gray);
        color: var(--dark-gray);
        border-top: 1px solid var(--mid-gray);
    }

    .invoice-footer p {
        margin: 0.25rem 0;
    }

    .button-group {
        position: fixed;
        top: 20px;
        right: 20px;
        display: flex;
        gap: 1rem;
        z-index: 100;
    }

    .btn {
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
        border: none;
        box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
    }

    .btn-primary {
        background: var(--primary-color);
        color: white;
    }

    .btn-primary:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
    }

    .btn-secondary {
        background: var(--secondary-color);
        color: white;
    }

    .btn-secondary:hover {
        background: #0f172a;
        transform: translateY(-2px);
    }

    .company-logo {
        font-size: 1.5rem;
        font-weight: 700;
        letter-spacing: -0.5px;
        color: white;
        display: inline-block;
        margin-bottom: 0.5rem;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: var(--border-radius);
        padding: 0.5rem 1rem;
    }

    .payment-details {
        margin-top: 0.5rem;
    }

    .label {
        font-weight: 600;
        color: var(--secondary-color);
        display: inline-block;
        min-width: 130px; /* Menetapkan lebar minimum untuk label */
    }

    .info-value {
        display: inline-block;
    }

    @media print {
        .button-group {
            display: none;
        }
        
        body {
            padding: 0;
            background: white;
        }
        
        .invoice-container {
            box-shadow: none;
            max-width: 100%;
        }

        .invoice-header {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        th {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .total-row td {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .status-badge {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }

    @media (max-width: 768px) {
        .invoice-info {
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
        
        .button-group {
            position: static;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .invoice-header {
            padding: 1.5rem;
        }

        .invoice-body {
            padding: 1.5rem;
        }

        .invoice-footer {
            padding: 1.5rem;
        }

        table {
            font-size: 0.85rem;
        }

        th, td {
            padding: 0.75rem 0.5rem;
        }

        /* Penyesuaian lebar kolom tabel untuk mobile */
        table {
            table-layout: auto;
        }
        
        th:nth-child(1), td:nth-child(1),
        th:nth-child(2), td:nth-child(2),
        th:nth-child(3), td:nth-child(3),
        th:nth-child(4), td:nth-child(4),
        th:nth-child(5), td:nth-child(5),
        th:nth-child(6), td:nth-child(6) {
            width: auto;
        }
    }

    /* Responsif untuk layar kecil */
    @media (max-width: 576px) {
        .invoice-body {
            padding: 1rem;
        }
        
        table {
            display: block;
            overflow-x: auto;
            white-space: nowrap;
        }
        
        .label {
            min-width: 100px;
        }
    }
    </style>
</head>
<body>
    <div class="button-group">
        <a href="riwayat.php" class="btn btn-secondary">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/>
            </svg>
            Kembali
        </a>
        <button class="btn btn-primary" onclick="window.print()">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                <path d="M5 1a2 2 0 0 0-2 2v1h10V3a2 2 0 0 0-2-2zm6 8H5a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1"/>
                <path fill-rule="evenodd" d="M0 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-1v-2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2H2a2 2 0 0 1-2-2zm2.5 1a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1"/>
            </svg>
            Cetak
        </button>
    </div>
    
    <div class="invoice-container">
        <div class="invoice-header">
            <div class="company-logo">FRESH LAUNDRY</div>
            <h1>INVOICE</h1>
            <div class="invoice-number">INV-<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></div>
        </div>
        
        <div class="invoice-body">
            <div class="invoice-info">
                <div class="invoice-detail invoice-customer">
                    <h3>INFORMASI PELANGGAN</h3>
                    <p><strong><?php echo $order['nama']; ?></strong></p>
                    <p><?php echo $order['alamat']; ?></p>
                    <p>Telp: <?php echo $order['no_hp']; ?></p>
                    <p>Email: <?php echo $order['email']; ?></p>
                </div>
                
                <div class="invoice-detail">
                    <h3>DETAIL INVOICE</h3>
                    <p>
                        <span class="label">Tanggal Order:</span>
                        <span class="info-value"><?php echo formatTanggal($order['tgl_order']); ?></span>
                    </p>
                    <p>
                        <span class="label">Status Order:</span>
                        <span class="info-value">
                            <span class="status-badge <?php echo ($order['status'] == 'selesai') ? 'status-paid' : 'status-pending'; ?>">
                                <?php echo ucwords(str_replace('_', ' ', $order['status'])); ?>
                            </span>
                        </span>
                    </p>
                    <div class="payment-details">
                        <p>
                            <span class="label">Status Pembayaran:</span>
                            <span class="info-value">
                                <span class="status-badge <?php echo ($order['status_pembayaran'] == 'lunas') ? 'status-paid' : 'status-pending'; ?>">
                                    <?php echo ucfirst($order['status_pembayaran']); ?>
                                </span>
                            </span>
                        </p>
                        <p>
                            <span class="label">Metode Pembayaran:</span>
                            <span class="info-value"><?php echo ucfirst($order['metode_pembayaran']); ?></span>
                        </p>
                        <?php if(!empty($order['tgl_pembayaran'])): ?>
                            <p>
                                <span class="label">Tanggal Pembayaran:</span>
                                <span class="info-value"><?php echo formatTanggal($order['tgl_pembayaran']); ?></span>
                            </p>
                        <?php endif; ?>
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
                    <?php 
                    $total_items = 0;
                    $total_berat = 0;
                    while($item = mysqli_fetch_assoc($items_result)): 
                        $total_items += $item['jumlah'];
                        $total_berat += $item['berat'];
                    ?>
                    <tr>
                        <td><?php echo $item['nama_layanan']; ?></td>
                        <td><?php echo $item['jenis_pakaian']; ?></td>
                        <td><?php echo $item['jumlah']; ?> pcs</td>
                        <td><?php echo $item['berat']; ?> kg</td>
                        <td><?php echo formatRupiah($item['harga']); ?></td>
                        <td class="text-right"><?php echo formatRupiah($item['subtotal']); ?></td>
                    </tr>
                    <?php endwhile; 
                    mysqli_data_seek($items_result, 0); // Reset pointer untuk digunakan lagi jika perlu
                    ?>
                    <tr class="total-row">
                        <td colspan="5" class="text-right">Total (<?php echo $total_items; ?> item, <?php echo $total_berat; ?> kg):</td>
                        <td class="text-right"><?php echo formatRupiah($order['total_harga']); ?></td>
                    </tr>
                </tbody>
            </table>
            
            <?php if($order['catatan']): ?>
            <div class="note-box">
                <strong>Catatan:</strong>
                <?php echo $order['catatan']; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="invoice-footer">
            <p><strong>Terima kasih telah menggunakan layanan kami</strong></p>
            <p>Jika ada pertanyaan, silakan hubungi kami di (021) 1234567</p>
            <p>support@freshlaundry.com | www.freshlaundry.com</p>
        </div>
    </div>
</body>
</html>