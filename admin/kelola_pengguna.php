<?php
// File: admin/kelola_pengguna.php
// Halaman untuk admin mengelola data pengguna

session_start();
require_once '../config/koneksi.php';
require_once '../includes/functions.php';

// Cek apakah user adalah admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$page_title = "Kelola Pengguna";

// Proses hapus pengguna jika ada request
if(isset($_POST['delete_user']) && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    
    // Cek apakah user yang akan dihapus bukan user yang sedang login
    if($user_id != $_SESSION['user_id']) {
        // Cek apakah user memiliki pesanan
        $check_orders = "SELECT COUNT(*) as order_count FROM orders WHERE user_id = ?";
        $stmt = mysqli_prepare($koneksi, $check_orders);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        
        if($row['order_count'] > 0) {
            // Jika user memiliki pesanan, jangan hapus tapi set status nonaktif
            $update_query = "UPDATE users SET is_active = 0 WHERE id = ?";
            $update_stmt = mysqli_prepare($koneksi, $update_query);
            mysqli_stmt_bind_param($update_stmt, "i", $user_id);
            
            if(mysqli_stmt_execute($update_stmt)) {
                $success_message = "Pengguna berhasil dinonaktifkan karena memiliki riwayat pesanan.";
            } else {
                $error_message = "Gagal menonaktifkan pengguna.";
            }
        } else {
            // Jika user tidak memiliki pesanan, hapus user
            $delete_query = "DELETE FROM users WHERE id = ?";
            $delete_stmt = mysqli_prepare($koneksi, $delete_query);
            mysqli_stmt_bind_param($delete_stmt, "i", $user_id);
            
            if(mysqli_stmt_execute($delete_stmt)) {
                $success_message = "Pengguna berhasil dihapus.";
            } else {
                $error_message = "Gagal menghapus pengguna.";
            }
        }
    } else {
        $error_message = "Anda tidak dapat menghapus akun anda sendiri.";
    }
}

// Proses mengaktifkan/menonaktifkan pengguna
if(isset($_POST['toggle_status']) && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    $is_active = $_POST['status'] == '1' ? 0 : 1; // Toggle status
    
    // Cek apakah user yang akan diubah bukan user yang sedang login
    if($user_id != $_SESSION['user_id']) {
        $update_query = "UPDATE users SET is_active = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($koneksi, $update_query);
        mysqli_stmt_bind_param($update_stmt, "ii", $is_active, $user_id);
        
        if(mysqli_stmt_execute($update_stmt)) {
            $status_text = $is_active ? "diaktifkan" : "dinonaktifkan";
            $success_message = "Status pengguna berhasil $status_text.";
        } else {
            $error_message = "Gagal mengubah status pengguna.";
        }
    } else {
        $error_message = "Anda tidak dapat menonaktifkan akun anda sendiri.";
    }
}

// Proses edit role pengguna
if(isset($_POST['update_role']) && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['new_role'];
    
    // Cek apakah user yang akan diubah bukan user yang sedang login
    if($user_id != $_SESSION['user_id']) {
        $update_query = "UPDATE users SET role = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($koneksi, $update_query);
        mysqli_stmt_bind_param($update_stmt, "si", $new_role, $user_id);
        
        if(mysqli_stmt_execute($update_stmt)) {
            $success_message = "Role pengguna berhasil diperbarui menjadi $new_role.";
        } else {
            $error_message = "Gagal mengubah role pengguna.";
        }
    } else {
        $error_message = "Anda tidak dapat mengubah role akun anda sendiri.";
    }
}

// Proses reset password
if(isset($_POST['reset_password']) && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    $default_password = "password123"; // Password default setelah reset
    $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
    
    $update_query = "UPDATE users SET password = ? WHERE id = ?";
    $update_stmt = mysqli_prepare($koneksi, $update_query);
    mysqli_stmt_bind_param($update_stmt, "si", $hashed_password, $user_id);
    
    if(mysqli_stmt_execute($update_stmt)) {
        $success_message = "Password pengguna berhasil direset ke '$default_password'.";
    } else {
        $error_message = "Gagal mereset password pengguna.";
    }
}

// Pencarian pengguna
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = '';
if(!empty($search)) {
    $search_param = "%$search%";
    $search_condition = "WHERE username LIKE ? OR role LIKE ?";
}

// Filter berdasarkan role
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
if(!empty($role_filter)) {
    $search_condition = empty($search_condition) ? "WHERE role = ?" : "$search_condition AND role = ?";
}

// Query untuk mendapatkan daftar pengguna dengan pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Query untuk mendapatkan total pengguna (untuk pagination)
$count_query = "SELECT COUNT(*) as total FROM users $search_condition";
$count_stmt = mysqli_prepare($koneksi, $count_query);

// Bind parameter untuk pencarian jika ada
if(!empty($search)) {
    if(empty($role_filter)) {
        mysqli_stmt_bind_param($count_stmt, "ss", $search_param, $search_param);
    } else {
        mysqli_stmt_bind_param($count_stmt, "sss", $search_param, $search_param, $role_filter);
    }
} elseif(!empty($role_filter)) {
    mysqli_stmt_bind_param($count_stmt, "s", $role_filter);
}

mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$count_row = mysqli_fetch_assoc($count_result);
$total_users = $count_row['total'];
$total_pages = ceil($total_users / $per_page);

// Query untuk mendapatkan daftar pengguna
$query = "SELECT id, username, role, is_active, created_at FROM users $search_condition ORDER BY id DESC LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($koneksi, $query);

// Bind parameter untuk pencarian jika ada
if(!empty($search)) {
    if(empty($role_filter)) {
        mysqli_stmt_bind_param($stmt, "ssii", $search_param, $search_param, $per_page, $offset);
    } else {
        mysqli_stmt_bind_param($stmt, "sssii", $search_param, $search_param, $role_filter, $per_page, $offset);
    }
} elseif(!empty($role_filter)) {
    mysqli_stmt_bind_param($stmt, "sii", $role_filter, $per_page, $offset);
} else {
    mysqli_stmt_bind_param($stmt, "ii", $per_page, $offset);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

include '../includes/header.php';
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Kelola Pengguna</h1>
        <a href="dashboard.php" class="btn btn-primary">
            <i class="fas fa-home"></i> Kembali ke Beranda
        </a>
    </div>
    
    <?php if(isset($success_message)): ?>
        <div class="alert alert-success">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>
    
    <?php if(isset($error_message)): ?>
        <div class="alert alert-danger">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>
    
    <!-- Filter dan Pencarian -->
    <div class="filter-container">
        <form method="GET" class="filter-form">
            <div class="form-group">
                <input type="text" name="search" class="form-control" placeholder="Cari username atau role..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="form-group">
                <select name="role" class="form-control">
                    <option value="">Semua Role</option>
                    <option value="admin" <?php echo $role_filter == 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="karyawan" <?php echo $role_filter == 'karyawan' ? 'selected' : ''; ?>>Karyawan</option>
                    <option value="pelanggan" <?php echo $role_filter == 'pelanggan' ? 'selected' : ''; ?>>Pelanggan</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
            <?php if(!empty($search) || !empty($role_filter)): ?>
                <a href="kelola_pengguna.php" class="btn btn-secondary">Reset</a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Tabel Pengguna -->
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Tanggal Daftar</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($result) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td>
                                <span class="badge <?php 
                                    echo $row['role'] == 'admin' ? 'badge-primary' : 
                                        ($row['role'] == 'karyawan' ? 'badge-success' : 'badge-info'); 
                                ?>">
                                    <?php echo ucfirst($row['role']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo $row['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo $row['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                </span>
                            </td>
                            <td><?php echo formatTanggal($row['created_at']); ?></td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-info view-user" data-id="<?php echo $row['id']; ?>" data-username="<?php echo htmlspecialchars($row['username']); ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-warning edit-role" data-id="<?php echo $row['id']; ?>" data-role="<?php echo $row['role']; ?>" data-username="<?php echo htmlspecialchars($row['username']); ?>">
                                        <i class="fas fa-user-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-secondary reset-password" data-id="<?php echo $row['id']; ?>" data-username="<?php echo htmlspecialchars($row['username']); ?>">
                                        <i class="fas fa-key"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm <?php echo $row['is_active'] ? 'btn-warning' : 'btn-success'; ?> toggle-status" 
                                            data-id="<?php echo $row['id']; ?>" 
                                            data-status="<?php echo $row['is_active']; ?>"
                                            data-username="<?php echo htmlspecialchars($row['username']); ?>">
                                        <i class="fas <?php echo $row['is_active'] ? 'fa-toggle-off' : 'fa-toggle-on'; ?>"></i>
                                    </button>
                                    <?php if($row['id'] != $_SESSION['user_id']): ?>
                                        <button type="button" class="btn btn-sm btn-danger delete-user" data-id="<?php echo $row['id']; ?>" data-username="<?php echo htmlspecialchars($row['username']); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">Tidak ada data pengguna.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if($total_pages > 1): ?>
        <div class="pagination-container">
            <ul class="pagination">
                <?php if($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>">
                            &laquo; Sebelumnya
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
                
                <?php if($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>">
                            Selanjutnya &raquo;
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Detail Pengguna -->
<div class="modal fade" id="userDetailModal" tabindex="-1" role="dialog" aria-labelledby="userDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userDetailModalLabel">Detail Pengguna</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="user-details">
                    <div class="text-center mb-4">
                        <i class="fas fa-user-circle fa-5x"></i>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Username:</span>
                        <span class="detail-value" id="detailUsername"></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Role:</span>
                        <span class="detail-value" id="detailRole"></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value" id="detailStatus"></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Tanggal Daftar:</span>
                        <span class="detail-value" id="detailCreated"></span>
                    </div>
                    <div class="detail-item" id="orderCountContainer">
                        <span class="detail-label">Total Pesanan:</span>
                        <span class="detail-value" id="detailOrderCount"></span>
                    </div>
                    <div class="detail-item" id="totalSpendingContainer">
                        <span class="detail-label">Total Pengeluaran:</span>
                        <span class="detail-value" id="detailTotalSpending"></span>
                    </div>
                    <div class="detail-item" id="lastLoginContainer">
                        <span class="detail-label">Login Terakhir:</span>
                        <span class="detail-value" id="detailLastLogin"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Role -->
<div class="modal fade" id="editRoleModal" tabindex="-1" role="dialog" aria-labelledby="editRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editRoleModalLabel">Edit Role Pengguna</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editRoleForm" method="post">
                    <input type="hidden" name="user_id" id="editUserId">
                    <input type="hidden" name="update_role" value="1">
                    
                    <div class="form-group">
                        <label for="editUsername">Username:</label>
                        <input type="text" class="form-control" id="editUsername" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="newRole">Role:</label>
                        <select class="form-control" id="newRole" name="new_role">
                            <option value="admin">Admin</option>
                            <option value="karyawan">Karyawan</option>
                            <option value="pelanggan">Pelanggan</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Reset Password -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" role="dialog" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resetPasswordModalLabel">Reset Password</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Password pengguna <strong id="resetUsername"></strong> akan direset ke default (password123).</p>
                <p>Apakah Anda yakin?</p>
                
                <form id="resetPasswordForm" method="post">
                    <input type="hidden" name="user_id" id="resetUserId">
                    <input type="hidden" name="reset_password" value="1">
                    
                    <button type="submit" class="btn btn-warning">Reset Password</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Toggle Status -->
<div class="modal fade" id="toggleStatusModal" tabindex="-1" role="dialog" aria-labelledby="toggleStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="toggleStatusModalLabel">Ubah Status Pengguna</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin <span id="statusAction"></span> pengguna <strong id="toggleUsername"></strong>?</p>
                
                <form id="toggleStatusForm" method="post">
                    <input type="hidden" name="user_id" id="toggleUserId">
                    <input type="hidden" name="status" id="toggleStatus">
                    <input type="hidden" name="toggle_status" value="1">
                    
                    <button type="submit" class="btn btn-primary">Ya, Ubah Status</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Delete -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" role="dialog" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteUserModalLabel">Hapus Pengguna</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus pengguna <strong id="deleteUsername"></strong>?</p>
                <p>Tindakan ini tidak dapat dibatalkan!</p>
                
                <form id="deleteUserForm" method="post">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <input type="hidden" name="delete_user" value="1">
                    
                    <button type="submit" class="btn btn-danger">Ya, Hapus</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Buat file AJAX untuk detail pengguna jika belum ada
$ajax_dir = dirname(__FILE__) . '/ajax';
if (!file_exists($ajax_dir)) {
    mkdir($ajax_dir, 0755, true);
}

$ajax_file = $ajax_dir . '/get_user_details.php';
if (!file_exists($ajax_file)) {
    $ajax_content = '<?php
session_start();
require_once \'../../config/koneksi.php\';
require_once \'../../includes/functions.php\';

// Cek apakah user adalah admin
if(!isset($_SESSION[\'user_id\']) || $_SESSION[\'role\'] != \'admin\') {
    echo json_encode([\'status\' => \'error\', \'message\' => \'Unauthorized\']);
    exit();
}

if(isset($_POST[\'user_id\'])) {
    $user_id = $_POST[\'user_id\'];
    
    // Query untuk mendapatkan data user
    $query = "SELECT id, username, role, is_active, created_at FROM users WHERE id = ?";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if($row = mysqli_fetch_assoc($result)) {
        // Format tanggal
        $row[\'created_at\'] = formatTanggal($row[\'created_at\']);
        
        // Jika user adalah pelanggan, ambil data pesanan
        if($row[\'role\'] == \'pelanggan\') {
            // Query untuk mendapatkan jumlah pesanan
            $order_query = "SELECT COUNT(*) as count FROM orders WHERE user_id = ?";
            $order_stmt = mysqli_prepare($koneksi, $order_query);
            mysqli_stmt_bind_param($order_stmt, "i", $user_id);
            mysqli_stmt_execute($order_stmt);
            $order_result = mysqli_stmt_get_result($order_stmt);
            $order_row = mysqli_fetch_assoc($order_result);
            $row[\'order_count\'] = $order_row[\'count\'];
            
            // Query untuk mendapatkan total pengeluaran
            $spending_query = "SELECT SUM(total_harga) as total FROM orders WHERE user_id = ? AND status_pesanan = \'selesai\'";
            $spending_stmt = mysqli_prepare($koneksi, $spending_query);
            mysqli_stmt_bind_param($spending_stmt, "i", $user_id);
            mysqli_stmt_execute($spending_stmt);
            $spending_result = mysqli_stmt_get_result($spending_stmt);
            $spending_row = mysqli_fetch_assoc($spending_result);
            $row[\'total_spending\'] = $spending_row[\'total\'] ? number_format($spending_row[\'total\'], 0, \',\', \'.\') : 0;
        }
        
        // Query untuk mendapatkan last login
        $login_query = "SELECT last_login FROM user_login_log WHERE user_id = ? ORDER BY login_time DESC LIMIT 1";
        $login_stmt = mysqli_prepare($koneksi, $login_query);
        mysqli_stmt_bind_param($login_stmt, "i", $user_id);
        mysqli_stmt_execute($login_stmt);
        $login_result = mysqli_stmt_get_result($login_stmt);
        
        if($login_row = mysqli_fetch_assoc($login_result)) {
            $row[\'last_login\'] = formatTanggal($login_row[\'last_login\']);
        }
        
        echo json_encode([\'status\' => \'success\', \'data\' => $row]);
    } else {
        echo json_encode([\'status\' => \'error\', \'message\' => \'User tidak ditemukan\']);
    }
} else {
    echo json_encode([\'status\' => \'error\', \'message\' => \'Parameter tidak lengkap\']);
}
?>';
    file_put_contents($ajax_file, $ajax_content);
}
?>

<!-- Script untuk fungsi-fungsi AJAX dan event handling -->
<script>
$(document).ready(function() {
    // Handler untuk tombol lihat detail
    $('.view-user').click(function() {
        var userId = $(this).data('id');
        var username = $(this).data('username');
        
        // Reset form
        $('#detailUsername').text('Memuat...');
        $('#detailRole').text('Memuat...');
        $('#detailStatus').text('Memuat...');
        $('#detailCreated').text('Memuat...');
        $('#detailOrderCount').text('Memuat...');
        $('#detailTotalSpending').text('Memuat...');
        $('#detailLastLogin').text('Memuat...');
        
        // Tampilkan modal
        $('#userDetailModal').modal('show');
        
        // Ambil data user dengan AJAX
        $.ajax({
            url: 'ajax/get_user_details.php',
            type: 'POST',
            data: {user_id: userId},
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    var userData = response.data;
                    
                    // Isi data ke modal
                    $('#detailUsername').text(userData.username);
                    $('#detailRole').text(userData.role.charAt(0).toUpperCase() + userData.role.slice(1));
                    $('#detailStatus').text(userData.is_active == 1 ? 'Aktif' : 'Nonaktif');
                    $('#detailStatus').removeClass().addClass(userData.is_active == 1 ? 'badge badge-success' : 'badge badge-danger');
                    $('#detailCreated').text(userData.created_at);
                    
                    // Tampilkan atau sembunyikan informasi pesanan berdasarkan role
                    if(userData.role == 'pelanggan') {
                        $('#orderCountContainer, #totalSpendingContainer').show();
                        $('#detailOrderCount').text(userData.order_count);
                        $('#detailTotalSpending').text('Rp ' + userData.total_spending);
                    } else {
                        $('#orderCountContainer, #totalSpendingContainer').hide();
                    }
                    
                    // Tampilkan last login jika ada
                    if(userData.last_login) {
                        $('#lastLoginContainer').show();
                        $('#detailLastLogin').text(userData.last_login);
                    } else {
                        $('#lastLoginContainer').hide();
                    }
                } else {
                    alert('Gagal memuat data: ' + response.message);
                }
            },
            error: function() {
                alert('Terjadi kesalahan saat memuat data pengguna.');
            }
        });
    });
    
    // Handler untuk tombol edit role
    $('.edit-role').click(function() {
        var userId = $(this).data('id');
        var username = $(this).data('username');
        var role = $(this).data('role');
        
        // Isi data ke modal
        $('#editUserId').val(userId);
        $('#editUsername').val(username);
        $('#newRole').val(role);
        
        // Tampilkan modal
        $('#editRoleModal').modal('show');
    });
    
    // Handler untuk tombol reset password
    $('.reset-password').click(function() {
        var userId = $(this).data('id');
        var username = $(this).data('username');
        
        // Isi data ke modal
        $('#resetUserId').val(userId);
        $('#resetUsername').text(username);
        
        // Tampilkan modal
        $('#resetPasswordModal').modal('show');
    });
    
    // Handler untuk tombol toggle status
    $('.toggle-status').click(function() {
        var userId = $(this).data('id');
        var username = $(this).data('username');
        var status = $(this).data('status');
        var actionText = status == 1 ? 'menonaktifkan' : 'mengaktifkan';
        
        // Isi data ke modal
        $('#toggleUserId').val(userId);
        $('#toggleStatus').val(status);
        $('#toggleUsername').text(username);
        $('#statusAction').text(actionText);
        
        // Tampilkan modal
        $('#toggleStatusModal').modal('show');
    });
    
    // Handler untuk tombol hapus
    $('.delete-user').click(function() {
        var userId = $(this).data('id');
        var username = $(this).data('username');
        
        // Isi data ke modal
        $('#deleteUserId').val(userId);
        $('#deleteUsername').text(username);
        
        // Tampilkan modal
        $('#deleteUserModal').modal('show');
    });
    
    // Auto-hide alert setelah 5 detik
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    
    // Tambahkan konfirmasi sebelum submit form hapus
    $('#deleteUserForm').submit(function() {
        return confirm('Anda yakin ingin menghapus pengguna ini?');
    });
    
    // Tambahkan tooltip ke tombol-tombol aksi
    $('.btn-group button').tooltip({
        placement: 'top',
        trigger: 'hover',
        title: function() {
            if($(this).hasClass('view-user')) return 'Lihat Detail';
            if($(this).hasClass('edit-role')) return 'Edit Role';
            if($(this).hasClass('reset-password')) return 'Reset Password';
            if($(this).hasClass('toggle-status')) {
                return $(this).data('status') == 1 ? 'Nonaktifkan' : 'Aktifkan';
            }
            if($(this).hasClass('delete-user')) return 'Hapus';
            return '';
        }
    });
});
</script>

<style>
.filter-container {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 25px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    border: 1px solid #e9ecef;
}

.filter-form {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: center;
}

.filter-form .form-group {
    margin-bottom: 0;
    flex: 1;
    min-width: 200px;
}

.filter-form .btn {
    margin-right: 5px;
    min-width: 100px;
}

.table-container {
    overflow-x: auto;
    margin-bottom: 30px;
    border-radius: 8px;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
}

.table {
    margin-bottom: 0;
}

.table thead th {
    background-color: #f1f5f9;
    border-bottom: 2px solid #dee2e6;
    color: #495057;
    font-weight: 600;
    vertical-align: middle;
}

.table tbody tr:hover {
    background-color: #f5f8fa;
}

.pagination-container {
    display: flex;
    justify-content: center;
    margin-top: 20px;
}

.pagination .page-item.active .page-link {
    background-color: #007bff;
    border-color: #007bff;
}

.pagination .page-link {
    color: #007bff;
}

.pagination .page-link:hover {
    background-color: #e9ecef;
}

.btn-group {
    white-space: nowrap;
}

.btn-group .btn {
    margin-right: 2px;
    border-radius: 4px;
}

/* Detail user modal */
.detail-item {
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
    display: flex;
}

.detail-label {
    font-weight: 600;
    width: 160px;
    color: #495057;
}

.detail-value {
    flex: 1;
}

.modal-body .user-details {
    padding: 10px;
}

.modal-body .fa-user-circle {
    color: #6c757d;
    margin-bottom: 20px;
}

/* Badge styling */
.badge {
    font-size: 90%;
    padding: 6px 10px;
    font-weight: 500;
    border-radius: 50px;
}

.badge-primary {
    background-color: #007bff;
}

.badge-success {
    background-color: #28a745;
}

.badge-info {
    background-color: #17a2b8;
}

.badge-danger {
    background-color: #dc3545;
}

.badge-warning {
    background-color: #ffc107;
    color: #212529;
}

/* Alert styling */
.alert {
    border-radius: 8px;
    padding: 15px 20px;
    margin-bottom: 25px;
    border-left: 5px solid;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border-left-color: #28a745;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border-left-color: #dc3545;
}

/* Form controls */
.form-control:focus {
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    border-color: #80bdff;
}

/* Button hover effects */
.btn-primary:hover {
    background-color: #0069d9;
    border-color: #0062cc;
}

.btn-danger:hover {
    background-color: #c82333;
    border-color: #bd2130;
}

/* Responsive adjustments */
@media (max-width: 992px) {
    .filter-form {
        gap: 10px;
    }
}

@media (max-width: 768px) {
    .filter-form {
        flex-direction: column;
    }
    
    .filter-form .form-group {
        width: 100%;
        margin-bottom: 15px;
    }
    
    .btn-group button {
        padding: 0.25rem 0.4rem;
        font-size: 0.875rem;
    }
    
    .detail-item {
        flex-direction: column;
    }
    
    .detail-label {
        width: 100%;
        margin-bottom: 5px;
    }
    
    .table td, .table th {
        padding: 0.5rem;
    }
}

@media (max-width: 576px) {
    .container {
        padding: 10px;
    }
    
    h1 {
        font-size: 1.75rem;
    }
    
    .btn-group {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }
    
    .modal-dialog {
        margin: 0.5rem;
    }
}
</style>

<?php include '../includes/footer.php'; ?>