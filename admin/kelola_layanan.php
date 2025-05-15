<?php
// File: admin/kelola_layanan.php
// Halaman untuk mengelola layanan laundry

session_start();
require_once '../config/koneksi.php';
require_once '../includes/functions.php';

// Cek akses admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$page_title = "Kelola Layanan";

// Handle tambah layanan
if(isset($_POST['tambah_layanan'])) {
    $nama_layanan = cleanInput($_POST['nama_layanan']);
    $deskripsi = cleanInput($_POST['deskripsi']);
    $harga_per_kg = cleanInput($_POST['harga_per_kg']);
    
    $query = "INSERT INTO services (nama_layanan, deskripsi, harga_per_kg) 
              VALUES ('$nama_layanan', '$deskripsi', '$harga_per_kg')";
    
    if(mysqli_query($koneksi, $query)) {
        $success = "Layanan berhasil ditambahkan!";
    } else {
        $error = "Gagal menambahkan layanan: " . mysqli_error($koneksi);
    }
}

// Handle edit layanan
if(isset($_POST['edit_layanan'])) {
    $id = cleanInput($_POST['id']);
    $nama_layanan = cleanInput($_POST['nama_layanan']);
    $deskripsi = cleanInput($_POST['deskripsi']);
    $harga_per_kg = cleanInput($_POST['harga_per_kg']);
    
    $query = "UPDATE services SET 
              nama_layanan = '$nama_layanan',
              deskripsi = '$deskripsi',
              harga_per_kg = '$harga_per_kg'
              WHERE id = '$id'";
    
    if(mysqli_query($koneksi, $query)) {
        $success = "Layanan berhasil diupdate!";
    } else {
        $error = "Gagal mengupdate layanan: " . mysqli_error($koneksi);
    }
}

// Handle hapus layanan
if(isset($_GET['hapus'])) {
    $id = cleanInput($_GET['hapus']);
    
    // Cek apakah layanan sedang digunakan di pesanan
    $check_query = "SELECT COUNT(*) as count FROM order_details WHERE service_id = '$id'";
    $check_result = mysqli_query($koneksi, $check_query);
    $check_data = mysqli_fetch_assoc($check_result);
    
    if($check_data['count'] > 0) {
        $error = "Layanan tidak dapat dihapus karena sudah pernah digunakan dalam pesanan.";
    } else {
        $delete_query = "DELETE FROM services WHERE id = '$id'";
        if(mysqli_query($koneksi, $delete_query)) {
            $success = "Layanan berhasil dihapus!";
        } else {
            $error = "Gagal menghapus layanan: " . mysqli_error($koneksi);
        }
    }
}

// Query untuk mendapatkan semua layanan
$query = "SELECT * FROM services ORDER BY nama_layanan";
$result = mysqli_query($koneksi, $query);

include '../includes/header.php';
?>

<style>
    /* Base styles */
    .services-container {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        max-width: 1200px;
        margin: 0 auto;
        width: 100%;
        box-sizing: border-box;
    }
    .page-header {
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        max-width: 1200px;
        margin: 0 auto 20px;
        width: 100%;
        padding: 0 20px;
        box-sizing: border-box;
    }
    .form-group {
        margin-bottom: 15px;
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
        box-sizing: border-box;
    }
    .btn {
        padding: 8px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        margin-right: 5px;
        margin-bottom: 5px;
        text-align: center;
    }
    .btn-primary {
        background-color: #007bff;
        color: white;
    }
    .btn-success {
        background-color: #28a745;
        color: white;
    }
    .btn-danger {
        background-color: #dc3545;
        color: white;
    }
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-top: 20px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        min-width: 600px; /* Ensures table doesn't get too small */
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
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
        overflow-y: auto;
    }
    .modal-content {
        background-color: white;
        margin: 10% auto;
        padding: 20px;
        border-radius: 8px;
        width: 90%;
        max-width: 500px;
        position: relative;
        box-sizing: border-box;
    }
    .alert {
        padding: 10px 15px;
        border-radius: 4px;
        margin-bottom: 15px;
        max-width: 1200px;
        margin-left: auto;
        margin-right: auto;
        width: 100%;
        box-sizing: border-box;
    }
    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .alert-error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .action-buttons {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }
    
    /* Responsive styles */
    @media (max-width: 768px) {
        .services-container {
            padding: 15px;
            border-radius: 0;
            box-shadow: none;
        }
        .page-header {
            padding: 0 15px;
            flex-direction: column;
            align-items: flex-start;
        }
        h1 {
            font-size: 1.5rem;
            margin-top: 0;
            margin-bottom: 15px;
        }
        .btn {
            padding: 8px 12px;
        }
        th, td {
            padding: 8px;
        }
        .action-buttons {
            flex-direction: column;
            width: 100%;
        }
        .action-buttons .btn {
            width: 100%;
            margin-right: 0;
        }
        .modal-content {
            margin: 5% auto;
            padding: 15px;
            width: 95%;
        }
    }
    
    /* For very small screens */
    @media (max-width: 480px) {
        .services-container {
            padding: 10px;
        }
        .page-header {
            padding: 0 10px;
        }
        th, td {
            padding: 6px;
            font-size: 0.85rem;
        }
        .modal-content {
            margin: 2% auto;
            padding: 12px;
            width: 98%;
        }
    }
</style>

<div class="page-header">
    <h1>Kelola Layanan</h1>
    <button class="btn btn-primary" onclick="openAddModal()">Tambah Layanan Baru</button>
</div>

<?php if(isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if(isset($error)): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<div class="services-container">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Layanan</th>
                    <th>Deskripsi</th>
                    <th>Harga/kg</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while($service = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo $service['id']; ?></td>
                    <td><?php echo $service['nama_layanan']; ?></td>
                    <td><?php echo $service['deskripsi']; ?></td>
                    <td><?php echo formatRupiah($service['harga_per_kg']); ?></td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-success" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($service)); ?>)">Edit</button>
                            <a href="?hapus=<?php echo $service['id']; ?>" 
                               class="btn btn-danger" 
                               onclick="return confirm('Apakah Anda yakin ingin menghapus layanan ini?')">Hapus</a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Layanan -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <h3>Tambah Layanan Baru</h3>
        <form method="POST">
            <div class="form-group">
                <label>Nama Layanan:</label>
                <input type="text" name="nama_layanan" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Deskripsi:</label>
                <textarea name="deskripsi" class="form-control" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label>Harga per kg:</label>
                <input type="number" name="harga_per_kg" class="form-control" required>
            </div>
            
            <div class="action-buttons">
                <button type="submit" name="tambah_layanan" class="btn btn-primary">Simpan</button>
                <button type="button" onclick="closeAddModal()" class="btn btn-danger">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Layanan -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <h3>Edit Layanan</h3>
        <form method="POST">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="form-group">
                <label>Nama Layanan:</label>
                <input type="text" name="nama_layanan" id="edit_nama_layanan" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Deskripsi:</label>
                <textarea name="deskripsi" id="edit_deskripsi" class="form-control" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label>Harga per kg:</label>
                <input type="number" name="harga_per_kg" id="edit_harga_per_kg" class="form-control" required>
            </div>
            
            <div class="action-buttons">
                <button type="submit" name="edit_layanan" class="btn btn-primary">Update</button>
                <button type="button" onclick="closeEditModal()" class="btn btn-danger">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('addModal').style.display = 'block';
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
}

function closeAddModal() {
    document.getElementById('addModal').style.display = 'none';
    document.body.style.overflow = 'auto'; // Restore scrolling
}

function openEditModal(service) {
    document.getElementById('edit_id').value = service.id;
    document.getElementById('edit_nama_layanan').value = service.nama_layanan;
    document.getElementById('edit_deskripsi').value = service.deskripsi;
    document.getElementById('edit_harga_per_kg').value = service.harga_per_kg;
    
    document.getElementById('editModal').style.display = 'block';
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
    document.body.style.overflow = 'auto'; // Restore scrolling
}

// Close modal when clicking outside
window.onclick = function(event) {
    let addModal = document.getElementById('addModal');
    let editModal = document.getElementById('editModal');
    
    if (event.target == addModal) {
        addModal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    if (event.target == editModal) {
        editModal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}
</script>

<?php include '../includes/footer.php'; ?>