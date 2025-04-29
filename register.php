<?php
// File: register.php
// Halaman registrasi pelanggan baru

session_start();
include 'config/koneksi.php';

// Jika sudah login, redirect ke dashboard
if(isset($_SESSION['user_id'])) {
    if($_SESSION['role'] == 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: pelanggan/dashboard.php");
    }
    exit();
}

$error = '';
$success = '';

if(isset($_POST['register'])) {
    // Ambil dan bersihkan input
    $username = cleanInput($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $nama = cleanInput($_POST['nama']);
    $alamat = cleanInput($_POST['alamat']);
    $no_hp = cleanInput($_POST['no_hp']);
    $email = cleanInput($_POST['email']);
    
    // Validasi
    if(empty($username) || empty($password) || empty($nama)) {
        $error = "Username, password, dan nama harus diisi!";
    } elseif($password != $confirm_password) {
        $error = "Password dan konfirmasi password tidak cocok!";
    } elseif(strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } else {
        // Cek apakah username sudah ada
        $check_query = "SELECT id FROM users WHERE username = '$username'";
        $check_result = mysqli_query($koneksi, $check_query);
        
        if(mysqli_num_rows($check_result) > 0) {
            $error = "Username sudah digunakan!";
        } else {
            // Start transaction
            mysqli_begin_transaction($koneksi);
            
            try {
                // Insert ke tabel users
                $password_hash = md5($password);
                $user_query = "INSERT INTO users (username, password, role) VALUES ('$username', '$password_hash', 'pelanggan')";
                mysqli_query($koneksi, $user_query);
                $user_id = mysqli_insert_id($koneksi);
                
                // Insert ke tabel customers
                $customer_query = "INSERT INTO customers (user_id, nama, alamat, no_hp, email) 
                                 VALUES ('$user_id', '$nama', '$alamat', '$no_hp', '$email')";
                mysqli_query($koneksi, $customer_query);
                
                // Commit transaction
                mysqli_commit($koneksi);
                
                $success = "Registrasi berhasil! Silakan login.";
                
                // Redirect ke login setelah 2 detik
                header("refresh:2;url=login.php");
                
            } catch (Exception $e) {
                // Rollback jika ada error
                mysqli_rollback($koneksi);
                $error = "Registrasi gagal: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - Sistem Laundry</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --primary-dark: #2980b9;
            --secondary-color: #f8f9fa;
            --text-color: #333;
            --text-muted: #6c757d;
            --border-color: #e0e0e0;
            --error-color: #e74c3c;
            --success-color: #2ecc71;
            --box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f9f9f9;
            background-image: linear-gradient(to right, #f9f9f9, #f0f2f5);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 15px;
            position: relative;
            overflow-x: hidden;
        }
        
        .register-container {
            width: 100%;
            max-width: 550px;
            background: white;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: var(--box-shadow);
            transition: transform 0.3s ease;
        }
        
        .register-container:hover {
            transform: translateY(-5px);
        }
        
        .header-area {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo {
            margin-bottom: 15px;
            color: var(--primary-color);
            font-size: 36px;
        }
        
        h2 {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 8px;
        }
        
        .subtitle {
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 25px;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 0;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
            flex: 1;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-color);
            font-weight: 500;
            font-size: 14px;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }
        
        input[type="text"],
        input[type="password"],
        input[type="email"],
        textarea {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        textarea {
            padding: 12px;
            height: 100px;
            resize: vertical;
        }
        
        input[type="text"]:focus,
        input[type="password"]:focus,
        input[type="email"]:focus,
        textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
            outline: none;
        }
        
        button {
            width: 100%;
            padding: 14px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        button:hover {
            background-color: var(--primary-dark);
        }
        
        .error {
            color: var(--error-color);
            background: rgba(231, 76, 60, 0.1);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
            border-left: 3px solid var(--error-color);
        }
        
        .success {
            color: var(--success-color);
            background: rgba(46, 204, 113, 0.1);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
            border-left: 3px solid var(--success-color);
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: var(--text-muted);
        }
        
        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .required {
            color: var(--error-color);
            margin-left: 2px;
        }
        
        .progress-container {
            width: 100%;
            height: 4px;
            background: #eee;
            margin-bottom: 30px;
            border-radius: 2px;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            width: 0;
            border-radius: 2px;
            transition: width 0.3s ease;
            background-color: var(--primary-color);
        }
        
        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .register-container {
            animation: fadeIn 0.6s ease-out forwards;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
        
        @media (max-width: 480px) {
            .register-container {
                padding: 30px 20px;
                margin: 0;
                border-radius: 0;
                box-shadow: none;
            }
            
            body {
                background: white;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="header-area">
            <div class="logo">
                <i class="fas fa-tshirt"></i>
            </div>
            <h2>Buat Akun Baru</h2>
            <p class="subtitle">Daftar sebagai pelanggan untuk menggunakan layanan Laundry kami</p>
        </div>

        <div class="progress-container">
            <div class="progress-bar" style="width: 0%;"></div>
        </div>
        
        <?php if($error): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="registerForm">
            <div class="form-group">
                <label>Username <span class="required">*</span></label>
                <div class="input-group">
                    <span class="input-icon">
                        <i class="fas fa-user"></i>
                    </span>
                    <input type="text" name="username" placeholder="Masukkan username" required value="<?php echo isset($_POST['username']) ? $_POST['username'] : ''; ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Password <span class="required">*</span></label>
                    <div class="input-group">
                        <span class="input-icon">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" name="password" id="password" placeholder="Minimal 6 karakter" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Konfirmasi Password <span class="required">*</span></label>
                    <div class="input-group">
                        <span class="input-icon">
                            <i class="fas fa-check-circle"></i>
                        </span>
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="Masukkan ulang password" required>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label>Nama Lengkap <span class="required">*</span></label>
                <div class="input-group">
                    <span class="input-icon">
                        <i class="fas fa-id-card"></i>
                    </span>
                    <input type="text" name="nama" placeholder="Masukkan nama lengkap anda" required value="<?php echo isset($_POST['nama']) ? $_POST['nama'] : ''; ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Email</label>
                    <div class="input-group">
                        <span class="input-icon">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" name="email" placeholder="contoh@email.com" value="<?php echo isset($_POST['email']) ? $_POST['email'] : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>No. HP</label>
                    <div class="input-group">
                        <span class="input-icon">
                            <i class="fas fa-phone"></i>
                        </span>
                        <input type="text" name="no_hp" placeholder="08xxxxxxxxxx" value="<?php echo isset($_POST['no_hp']) ? $_POST['no_hp'] : ''; ?>">
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label>Alamat</label>
                <textarea name="alamat" placeholder="Masukkan alamat lengkap anda"><?php echo isset($_POST['alamat']) ? $_POST['alamat'] : ''; ?></textarea>
            </div>
            
            <button type="submit" name="register">
                <i class="fas fa-user-plus"></i> Daftar Sekarang
            </button>
        </form>
        
        <div class="login-link">
            Sudah punya akun? <a href="login.php">Login di sini</a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Untuk form progress bar
            const form = document.getElementById('registerForm');
            const formElements = form.querySelectorAll('input, textarea');
            const progressBar = document.querySelector('.progress-bar');
            
            function updateProgress() {
                let filledFields = 0;
                formElements.forEach(element => {
                    if (element.value.trim() !== '') {
                        filledFields++;
                    }
                });
                
                const progress = (filledFields / formElements.length) * 100;
                progressBar.style.width = progress + '%';
            }
            
            // Update awal
            updateProgress();
            
            // Update progress ketika user mengisi form
            formElements.forEach(element => {
                element.addEventListener('input', updateProgress);
            });
            
            // Password confirmation validation
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            
            function validatePassword() {
                if (password.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Password tidak cocok');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            }
            
            password.addEventListener('change', validatePassword);
            confirmPassword.addEventListener('keyup', validatePassword);
        });
    </script>
</body>
</html>