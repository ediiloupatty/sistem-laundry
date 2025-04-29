<?php
// File: admin/daftar_pesanan.php
// Halaman daftar pesanan untuk admin

session_start();
require_once '../config/koneksi.php';
require_once '../includes/functions.php';

// Cek apakah user adalah admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$page_title = "Daftar Pesanan";

// Handle update status
if(isset($_POST['update_status'])) {
    $order_id = cleanInput($_POST['order_id']);
    $new_status = cleanInput($_POST['status']);
    
    $update_query = "UPDATE orders SET status = '$new_status' WHERE id = '$order_id'";
    if(mysqli_query($koneksi, $update_query)) {
        $success = "Status pesanan berhasil diupdate!";
        
        // Jika status menjadi selesai, update tanggal selesai
        if($new_status == 'selesai') {
            $update_tgl = "UPDATE orders SET tgl_selesai = NOW() WHERE id = '$order_id'";
            mysqli_query($koneksi, $update_tgl);
        }
        
        // Kirim notifikasi ke pelanggan
        $get_customer = "SELECT customer_id FROM orders WHERE id = '$order_id'";
        $result_customer = mysqli_query($koneksi, $get_customer);
        $customer = mysqli_fetch_assoc($result_customer);
        
        $get_user = "SELECT user_id FROM customers WHERE id = '".$customer['customer_id']."'";
        $result_user = mysqli_query($koneksi, $get_user);
        $user = mysqli_fetch_assoc($result_user);
        
        sendNotification($user['user_id'], $order_id, 
            "Status pesanan #$order_id telah diupdate menjadi: " . ucwords(str_replace('_', ' ', $new_status)));
        
    } else {
        $error = "Gagal mengupdate status: " . mysqli_error($koneksi);
    }
}

// Filter dan pencarian
$where_clause = "WHERE 1=1";
$status_filter = "";
$date_filter = "";
$search = "";

if(isset($_GET['status']) && $_GET['status'] != '') {
    $status_filter = cleanInput($_GET['status']);
    $where_clause .= " AND o.status = '$status_filter'";
}

if(isset($_GET['date']) && $_GET['date'] != '') {
    $date_filter = cleanInput($_GET['date']);
    $where_clause .= " AND DATE(o.tgl_order) = '$date_filter'";
}

if(isset($_GET['search']) && $_GET['search'] != '') {
    $search = cleanInput($_GET['search']);
    $where_clause .= " AND (c.nama LIKE '%$search%' OR o.id LIKE '%$search%')";
}

// Query untuk menampilkan semua pesanan
$query = "SELECT o.*, c.nama as nama_pelanggan, c.no_hp, c.alamat 
          FROM orders o 
          JOIN customers c ON o.customer_id = c.id 
          $where_clause 
          ORDER BY o.tgl_order DESC";
$result = mysqli_query($koneksi, $query);

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
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    .btn:hover {
        background-color: #0056b3;
    }
    .btn-secondary {
        background-color: #6c757d;
    }
    .btn-secondary:hover {
        background-color: #5a6268;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        background: white;
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
    .status-badge {
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: bold;
    }
    .status-menunggu_konfirmasi { background-color: #ffc107; color: #000; }
    .status-diproses { background-color: #17a2b8; color: #fff; }
    .status-selesai { background-color: #28a745; color: #fff; }
    .status-siap_diantar { background-color: #6f42c1; color: #fff; }
    .status-dibatalkan { background-color: #dc3545; color: #fff; }
    .action-buttons {
        display: flex;
        gap: 5px;
    }
    .btn-action {
        padding: 5px 10px;
        font-size: 12px;
        text-decoration: none;
    }
    .btn-detail { background-color: #007bff; color: white; }
    .btn-edit { background-color: #ffc107; color: #000; }
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }
    .modal-content {
        background-color: white;
        margin: 15% auto;
        padding: 20px;
        border-radius: 8px;
        width: 90%;
        max-width: 500px;
    }
</style>

<h1>Daftar Pesanan</h1>

<?php if(isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if(isset($error)): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<div class="filter-container">
    <form method="GET" class="filter-form">
        <div class="form-group">
            <label>Status:</label>
            <select name="status" class="form-control">
                <option value="">Semua Status</option>
                <option value="menunggu_konfirmasi" <?php echo $status_filter == 'menunggu_konfirmasi' ? 'selected' : ''; ?>>Menunggu Konfirmasi</option>
                <option value="diproses" <?php echo $status_filter == 'diproses' ? 'selected' : ''; ?>>Diproses</option>
                <option value="selesai" <?php echo $status_filter == 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                <option value="siap_diantar" <?php echo $status_filter == 'siap_diantar' ? 'selected' : ''; ?>>Siap Diantar</option>
                <option value="dibatalkan" <?php echo $status_filter == 'dibatalkan' ? 'selected' : ''; ?>>Dibatalkan</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Tanggal:</label>
            <input type="date" name="date" class="form-control" value="<?php echo $date_filter; ?>">
        </div>
        
        <div class="form-group">
            <label>Cari:</label>
            <input type="text" name="search" class="form-control" placeholder="Nama pelanggan atau ID pesanan" value="<?php echo $search; ?>">
        </div>
        
        <button type="submit" class="btn">Filter</button>
        <a href="daftar_pesanan.php" class="btn btn-secondary">Reset</a>
    </form>
</div>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Pelanggan</th>
            <th>Tanggal</th>
            <th>Total</th>
            <th>Metode</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php if(mysqli_num_rows($result) > 0): ?>
            <?php while($order = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td>#<?php echo $order['id']; ?></td>
                <td>
                    <?php echo $order['nama_pelanggan']; ?><br>
                    <small><?php echo $order['no_hp']; ?></small>
                </td>
                <td><?php echo formatTanggal($order['tgl_order']); ?></td>
                <td><?php echo formatRupiah($order['total_harga']); ?></td>
                <td><?php echo ucfirst(str_replace('_', ' ', $order['metode_antar'])); ?></td>
                <td>
                    <span class="status-badge status-<?php echo $order['status']; ?>">
                        <?php echo ucwords(str_replace('_', ' ', $order['status'])); ?>
                    </span>
                </td>
                <td>
                    <div class="action-buttons">
                        <a href="kelola_pesanan.php?id=<?php echo $order['id']; ?>" class="btn-action btn-detail">Detail</a>
                        <button onclick="openUpdateModal('<?php echo $order['id']; ?>', '<?php echo $order['status']; ?>')" class="btn-action btn-edit">Update</button>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="7" style="text-align: center;">Tidak ada pesanan ditemukan</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- Modal Update Status -->
<div id="updateModal" class="modal">
    <div class="modal-content">
        <h3>Update Status Pesanan</h3>
        <form method="POST">
            <input type="hidden" name="order_id" id="update_order_id">
            <div class="form-group">
                <label>Status Baru:</label>
                <select name="status" id="update_status" class="form-control">
                    <option value="menunggu_konfirmasi">Menunggu Konfirmasi</option>
                    <option value="diproses">Diproses</option>
                    <option value="selesai">Selesai</option>
                    <option value="siap_diantar">Siap Diantar</option>
                    <option value="dibatalkan">Dibatalkan</option>
                </select>
            </div>
            <div style="margin-top: 20px;">
                <button type="submit" name="update_status" class="btn">Update</button>
                <button type="button" onclick="closeUpdateModal()" class="btn btn-secondary">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openUpdateModal(orderId, currentStatus) {
        document.getElementById('update_order_id').value = orderId;
        document.getElementById('update_status').value = currentStatus;
        document.getElementById('updateModal').style.display = 'block';
    }
    
    function closeUpdateModal() {
        document.getElementById('updateModal').style.display = 'none';
    }
    
    // Tutup modal jika klik di luar
    window.onclick = function(event) {
        let modal = document.getElementById('updateModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
</script>

<?php include '../includes/footer.php'; ?>