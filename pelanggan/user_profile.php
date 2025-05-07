<?php
// File: user_profile.php
// Script untuk menampilkan informasi profil user dalam bentuk modal popup

if (!isset($_SESSION)) {
    session_start();
}

require_once '../config/koneksi.php';
require_once '../includes/functions.php';

// Cek apakah ini adalah request AJAX untuk mengambil data profil
if (isset($_GET['get_profile_data']) && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    $query = "SELECT id, username, role, created_at FROM users WHERE id = ?";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Hitung jumlah pesanan yang telah dilakukan
        $order_query = "SELECT COUNT(*) as total_orders FROM orders WHERE user_id = ?";
        $order_stmt = mysqli_prepare($koneksi, $order_query);
        mysqli_stmt_bind_param($order_stmt, "i", $user_id);
        mysqli_stmt_execute($order_stmt);
        $order_result = mysqli_stmt_get_result($order_stmt);
        $order_row = mysqli_fetch_assoc($order_result);
        $total_orders = $order_row['total_orders'];
        
        // Tambahkan informasi tentang pesanan
        $row['total_orders'] = $total_orders;
        
        // Total pengeluaran pelanggan (jika role = pelanggan)
        if ($row['role'] == 'pelanggan') {
            $spending_query = "SELECT SUM(total_harga) as total_spending FROM orders WHERE user_id = ? AND status_pesanan = 'selesai'";
            $spending_stmt = mysqli_prepare($koneksi, $spending_query);
            mysqli_stmt_bind_param($spending_stmt, "i", $user_id);
            mysqli_stmt_execute($spending_stmt);
            $spending_result = mysqli_stmt_get_result($spending_stmt);
            $spending_row = mysqli_fetch_assoc($spending_result);
            $total_spending = $spending_row['total_spending'] ?: 0;
            
            $row['total_spending'] = $total_spending;
        }
        
        // Jika admin, tambahkan informasi tentang jumlah pesanan yang dikelola
        if ($row['role'] == 'admin' || $row['role'] == 'karyawan') {
            $processed_query = "SELECT COUNT(*) as processed_orders FROM orders WHERE status_pesanan = 'selesai'";
            $processed_stmt = mysqli_prepare($koneksi, $processed_query);
            mysqli_stmt_execute($processed_stmt);
            $processed_result = mysqli_stmt_get_result($processed_stmt);
            $processed_row = mysqli_fetch_assoc($processed_result);
            $processed_orders = $processed_row['processed_orders'];
            
            $row['processed_orders'] = $processed_orders;
        }
        
        // Konversi tanggal pendaftaran ke format yang lebih mudah dibaca
        $row['formatted_date'] = formatTanggal($row['created_at']);
        
        // Kembalikan data dalam format JSON
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $row]);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Data profil tidak ditemukan']);
        exit;
    }
}

// Cek apakah ini adalah request untuk mengubah password
if (isset($_POST['update_password']) && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi input
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        echo json_encode(['success' => false, 'message' => 'Semua field harus diisi']);
        exit;
    }
    
    if ($new_password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Password baru dan konfirmasi tidak cocok']);
        exit;
    }
    
    // Cek password lama
    $query = "SELECT password FROM users WHERE id = ?";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        if (password_verify($current_password, $row['password'])) {
            // Password lama benar, update password baru
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $update_query = "UPDATE users SET password = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($koneksi, $update_query);
            mysqli_stmt_bind_param($update_stmt, "si", $hashed_password, $user_id);
            
            if (mysqli_stmt_execute($update_stmt)) {
                echo json_encode(['success' => true, 'message' => 'Password berhasil diperbarui']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Gagal memperbarui password']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Password lama tidak benar']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'User tidak ditemukan']);
    }
    
    exit;
}

// Kode untuk pembaruan nama pengguna/username (opsional)
if (isset($_POST['update_username']) && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $new_username = trim($_POST['new_username']);
    
    // Validasi username baru
    if (empty($new_username)) {
        echo json_encode(['success' => false, 'message' => 'Username tidak boleh kosong']);
        exit;
    }
    
    // Cek apakah username sudah digunakan
    $check_query = "SELECT id FROM users WHERE username = ? AND id != ?";
    $check_stmt = mysqli_prepare($koneksi, $check_query);
    mysqli_stmt_bind_param($check_stmt, "si", $new_username, $user_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        echo json_encode(['success' => false, 'message' => 'Username sudah digunakan']);
        exit;
    }
    
    // Update username
    $update_query = "UPDATE users SET username = ? WHERE id = ?";
    $update_stmt = mysqli_prepare($koneksi, $update_query);
    mysqli_stmt_bind_param($update_stmt, "si", $new_username, $user_id);
    
    if (mysqli_stmt_execute($update_stmt)) {
        // Update session
        $_SESSION['username'] = $new_username;
        
        echo json_encode(['success' => true, 'message' => 'Username berhasil diperbarui']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui username']);
    }
    
    exit;
}
?>

<!-- Modal untuk Profil User -->
<div class="profile-modal" id="profileModal">
    <div class="profile-content">
        <div class="profile-header">
            <h3>Profil Pengguna</h3>
            <span class="close-profile" id="closeProfile">&times;</span>
        </div>
        <div class="profile-body">
            <div class="profile-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="profile-info">
                <div class="profile-item">
                    <span class="profile-label">Username</span>
                    <span class="profile-value" id="profileUsername">Loading...</span>
                    <button class="edit-btn" id="editUsernameBtn"><i class="fas fa-edit"></i></button>
                </div>
                <div class="profile-item">
                    <span class="profile-label">Role</span>
                    <span class="profile-value" id="profileRole">Loading...</span>
                </div>
                <div class="profile-item">
                    <span class="profile-label">Bergabung Sejak</span>
                    <span class="profile-value" id="profileJoined">Loading...</span>
                </div>
                
                <!-- Informasi khusus pelanggan -->
                <div class="profile-item" id="orderInfoContainer" style="display: none;">
                    <span class="profile-label">Total Pesanan</span>
                    <span class="profile-value" id="profileOrders">Loading...</span>
                </div>
                <div class="profile-item" id="spendingInfoContainer" style="display: none;">
                    <span class="profile-label">Total Pengeluaran</span>
                    <span class="profile-value" id="profileSpending">Loading...</span>
                </div>
                
                <!-- Informasi khusus admin/karyawan -->
                <div class="profile-item" id="processedOrdersContainer" style="display: none;">
                    <span class="profile-label">Pesanan Diproses</span>
                    <span class="profile-value" id="profileProcessed">Loading...</span>
                </div>
            </div>
            
            <div class="profile-actions">
                <button class="change-password-btn" id="changePasswordBtn">Ubah Password</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Edit Username -->
<div class="profile-modal" id="usernameModal">
    <div class="profile-content">
        <div class="profile-header">
            <h3>Edit Username</h3>
            <span class="close-profile" id="closeUsername">&times;</span>
        </div>
        <div class="profile-body">
            <form id="usernameForm">
                <div class="form-group">
                    <label for="newUsername">Username Baru</label>
                    <input type="text" id="newUsername" name="new_username" class="form-control" required>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal untuk Ubah Password -->
<div class="profile-modal" id="passwordModal">
    <div class="profile-content">
        <div class="profile-header">
            <h3>Ubah Password</h3>
            <span class="close-profile" id="closePassword">&times;</span>
        </div>
        <div class="profile-body">
            <form id="passwordForm">
                <div class="form-group">
                    <label for="currentPassword">Password Saat Ini</label>
                    <div class="password-input-container">
                        <input type="password" id="currentPassword" name="current_password" class="form-control" required>
                        <i class="fas fa-eye toggle-password" data-target="currentPassword"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label for="newPassword">Password Baru</label>
                    <div class="password-input-container">
                        <input type="password" id="newPassword" name="new_password" class="form-control" required>
                        <i class="fas fa-eye toggle-password" data-target="newPassword"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirmPassword">Konfirmasi Password Baru</label>
                    <div class="password-input-container">
                        <input type="password" id="confirmPassword" name="confirm_password" class="form-control" required>
                        <i class="fas fa-eye toggle-password" data-target="confirmPassword"></i>
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elements for profile modal
    const profileModal = document.getElementById('profileModal');
    const closeProfile = document.getElementById('closeProfile');
    const changePasswordBtn = document.getElementById('changePasswordBtn');
    const editUsernameBtn = document.getElementById('editUsernameBtn');
    
    // Elements for password modal
    const passwordModal = document.getElementById('passwordModal');
    const closePassword = document.getElementById('closePassword');
    const passwordForm = document.getElementById('passwordForm');
    
    // Elements for username modal
    const usernameModal = document.getElementById('usernameModal');
    const closeUsername = document.getElementById('closeUsername');
    const usernameForm = document.getElementById('usernameForm');
    
    // User info element in the header
    const userInfoElement = document.querySelector('.user-info span');
    
    // Add click event to user info to show profile modal
    if (userInfoElement) {
        userInfoElement.style.cursor = 'pointer';
        userInfoElement.addEventListener('click', function() {
            loadProfileData();
            showModal(profileModal);
        });
    }
    
    // Close buttons
    if (closeProfile) closeProfile.addEventListener('click', () => hideModal(profileModal));
    if (closePassword) closePassword.addEventListener('click', () => hideModal(passwordModal));
    if (closeUsername) closeUsername.addEventListener('click', () => hideModal(usernameModal));
    
    // Change password button
    if (changePasswordBtn) {
        changePasswordBtn.addEventListener('click', function() {
            hideModal(profileModal);
            showModal(passwordModal);
        });
    }
    
    // Edit username button
    if (editUsernameBtn) {
        editUsernameBtn.addEventListener('click', function() {
            // Pre-fill current username
            document.getElementById('newUsername').value = document.getElementById('profileUsername').textContent;
            hideModal(profileModal);
            showModal(usernameModal);
        });
    }
    
    // Password form submission
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(passwordForm);
            formData.append('update_password', true);
            
            fetch('user_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    hideModal(passwordModal);
                    passwordForm.reset();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat memproses permintaan');
            });
        });
    }
    
    // Username form submission
    if (usernameForm) {
        usernameForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(usernameForm);
            formData.append('update_username', true);
            
            fetch('user_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    hideModal(usernameModal);
                    // Reload profile data to update the username
                    loadProfileData();
                    // Update the displayed username in the header
                    const usernameInHeader = userInfoElement.querySelector('i').outerHTML + ' ' + formData.get('new_username');
                    userInfoElement.innerHTML = usernameInHeader;
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat memproses permintaan');
            });
        });
    }
    
    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(icon => {
        icon.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                this.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    });
    
    // Close modals when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === profileModal) hideModal(profileModal);
        if (event.target === passwordModal) hideModal(passwordModal);
        if (event.target === usernameModal) hideModal(usernameModal);
    });
    
    // Functions
    function showModal(modal) {
        modal.style.display = 'block';
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);
    }
    
    function hideModal(modal) {
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    }
    
    function loadProfileData() {
        fetch('user_profile.php?get_profile_data=1')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const profileData = data.data;
                    
                    // Update profile information
                    document.getElementById('profileUsername').textContent = profileData.username;
                    document.getElementById('profileRole').textContent = capitalizeFirstLetter(profileData.role);
                    document.getElementById('profileJoined').textContent = profileData.formatted_date;
                    
                    // Show or hide role-specific information
                    if (profileData.role === 'pelanggan') {
                        document.getElementById('orderInfoContainer').style.display = 'flex';
                        document.getElementById('spendingInfoContainer').style.display = 'flex';
                        document.getElementById('processedOrdersContainer').style.display = 'none';
                        
                        document.getElementById('profileOrders').textContent = profileData.total_orders;
                        document.getElementById('profileSpending').textContent = formatRupiah(profileData.total_spending);
                    } else if (profileData.role === 'admin' || profileData.role === 'karyawan') {
                        document.getElementById('orderInfoContainer').style.display = 'none';
                        document.getElementById('spendingInfoContainer').style.display = 'none';
                        document.getElementById('processedOrdersContainer').style.display = 'flex';
                        
                        document.getElementById('profileProcessed').textContent = profileData.processed_orders;
                    }
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat memuat data profil');
            });
    }
    
    function capitalizeFirstLetter(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }
    
    function formatRupiah(angka) {
        return "Rp " + new Intl.NumberFormat('id-ID').format(angka);
    }
});
</script>

<style>
/* Styles for profile modal */
.profile-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.5);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.profile-modal.show {
    opacity: 1;
}

.profile-content {
    background-color: #fff;
    margin: 10% auto;
    padding: 0;
    border-radius: 8px;
    width: 450px;
    max-width: 90%;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    animation: slideIn 0.3s ease;
    overflow: hidden;
}

@keyframes slideIn {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.profile-header {
    background-color: #f8f9fa;
    padding: 15px 20px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.profile-header h3 {
    margin: 0;
    color: #343a40;
    font-size: 1.25rem;
}

.close-profile {
    color: #aaa;
    font-size: 24px;
    font-weight: bold;
    cursor: pointer;
    transition: color 0.2s ease;
}

.close-profile:hover {
    color: #555;
}

.profile-body {
    padding: 20px;
}

.profile-avatar {
    text-align: center;
    margin-bottom: 20px;
}

.profile-avatar i {
    font-size: 6rem;
    color: #6c757d;
}

.profile-info {
    margin-bottom: 20px;
}

.profile-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #e9ecef;
}

.profile-item:last-child {
    border-bottom: none;
}

.profile-label {
    font-weight: bold;
    color: #495057;
}

.profile-value {
    color: #343a40;
    text-align: right;
    flex-grow: 1;
    margin-left: 15px;
    margin-right: 10px;
}

.profile-actions {
    display: flex;
    justify-content: center;
    margin-top: 20px;
}

.change-password-btn {
    background-color: #007bff;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.2s ease;
}

.change-password-btn:hover {
    background-color: #0069d9;
}

.edit-btn {
    background: none;
    border: none;
    color: #007bff;
    cursor: pointer;
    font-size: 14px;
    padding: 0 5px;
}

.edit-btn:hover {
    color: #0056b3;
}

/* Form styles */
.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #495057;
}

.form-control {
    width: 100%;
    padding: 8px 10px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 14px;
}

.btn {
    display: inline-block;
    font-weight: 400;
    text-align: center;
    white-space: nowrap;
    vertical-align: middle;
    user-select: none;
    border: 1px solid transparent;
    padding: 8px 12px;
    font-size: 14px;
    border-radius: 4px;
    transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out;
}

.btn-primary {
    color: #fff;
    background-color: #007bff;
    border-color: #007bff;
    width: 100%;
}

.btn-primary:hover {
    background-color: #0069d9;
    border-color: #0062cc;
}

/* Password input with toggle */
.password-input-container {
    position: relative;
}

.toggle-password {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #6c757d;
}

/* Responsive adjustments */
@media (max-width: 576px) {
    .profile-content {
        margin: 20% auto;
        width: 95%;
    }
    
    .profile-avatar i {
        font-size: 4rem;
    }
}
</style>