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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --primary-dark: #2980b9;
            --secondary-color: #2c3e50;
            --accent-color: #f39c12;
            --light-gray: #f5f7f9;
            --mid-gray: #e9ecef;
            --dark-gray: #6c757d;
            --danger: #e74c3c;
            --success: #2ecc71;
            --border-radius: 8px;
            --box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: var(--secondary-color);
            line-height: 1.6;
            overflow-x: hidden;
            background: linear-gradient(135deg, #3498db, #1d6fa5);
            position: relative;
            min-height: 100vh;
        }

        /* Background Animation */
        .bg-animation {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }

        .wave {
            position: absolute;
            width: 200%;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50% 50% 0 0 / 100% 100% 0 0;
            animation: wave-animation 20s linear infinite;
            bottom: 0;
        }

        .wave:nth-child(1) {
            height: 200px;
            animation: wave-animation 20s linear infinite;
            opacity: 0.2;
            bottom: -100px;
        }
        
        .wave:nth-child(2) {
            height: 240px;
            animation: wave-animation 15s linear infinite;
            opacity: 0.3;
            bottom: -150px;
        }

        .wave:nth-child(3) {
            height: 300px;
            animation: wave-animation 12s linear infinite;
            opacity: 0.1;
            bottom: -200px;
        }

        .wave:nth-child(4) {
            height: 180px;
            animation: wave-animation 16s linear infinite reverse;
            opacity: 0.4;
            bottom: -50px;
        }

        @keyframes wave-animation {
            0% {
                transform: translateX(0) scaleY(1);
            }
            50% {
                transform: translateX(-25%) scaleY(1.2);
            }
            100% {
                transform: translateX(-50%) scaleY(1);
            }
        }

        /* Bubble animation */
        .bubble {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            position: absolute;
            animation: bubble-rise 8s infinite ease-in;
        }

        @keyframes bubble-rise {
            0% {
                transform: translateY(100vh) scale(0);
                opacity: 0;
            }
            50% {
                opacity: 0.6;
            }
            100% {
                transform: translateY(-100vh) scale(1.5);
                opacity: 0;
            }
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 2rem;
            position: relative;
            z-index: 1;
        }

        .register-container {
            width: 100%;
            max-width: 700px;
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .header {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 1.8rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 100%;
            height: 20px;
            background: linear-gradient(135deg, transparent 50%, rgba(255, 255, 255, 0.95) 50%);
            z-index: 1;
        }

        .header h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .header-icon {
            font-size: 2.8rem;
            margin-bottom: 1rem;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-10px);
            }
            100% {
                transform: translateY(0px);
            }
        }

        .form-container {
            padding: 2.5rem 2rem 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--secondary-color);
        }

        .form-row {
            display: flex;
            gap: 1.5rem;
        }

        .input-group {
            position: relative;
            flex: 1;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--dark-gray);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            font-size: 1rem;
            border: 1px solid var(--mid-gray);
            border-radius: var(--border-radius);
            transition: var(--transition);
            font-family: 'Poppins', sans-serif;
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
            padding-top: 2.5rem;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.25);
        }

        .btn {
            display: inline-block;
            width: 100%;
            padding: 0.85rem 1rem;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
            font-family: 'Poppins', sans-serif;
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn:hover {
            background: linear-gradient(135deg, #2980b9, #1e6091);
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(52, 152, 219, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 300%;
            height: 300%;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(-50%, -50%) scale(0);
            opacity: 0;
            transition: 0.5s;
        }

        .btn:active::after {
            transform: translate(-50%, -50%) scale(1);
            opacity: 1;
            transition: 0s;
        }

        .error {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger);
            padding: 0.75rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            border-left: 3px solid var(--danger);
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }

        .success {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success);
            padding: 0.75rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            border-left: 3px solid var(--success);
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(46, 204, 113, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(46, 204, 113, 0); }
            100% { box-shadow: 0 0 0 0 rgba(46, 204, 113, 0); }
        }

        .required {
            color: var(--danger);
            margin-left: 2px;
        }

        .progress-container {
            width: 100%;
            height: 4px;
            background-color: rgba(255, 255, 255, 0.3);
            margin-bottom: 1.5rem;
            border-radius: 4px;
            overflow: hidden;
            position: relative;
        }

        .progress-bar {
            height: 100%;
            width: 0;
            background: linear-gradient(90deg, #3498db, #2ecc71);
            transition: width 0.3s ease;
            border-radius: 4px;
            position: relative;
        }

        .progress-bar::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 100%;
            background: linear-gradient(90deg, 
                          rgba(255,255,255,0.1) 25%, 
                          rgba(255,255,255,0.3) 50%, 
                          rgba(255,255,255,0.1) 75%);
            animation: progress-shine 2s infinite linear;
        }

        @keyframes progress-shine {
            0% { background-position: -200px 0; }
            100% { background-position: 200px 0; }
        }

        .login-link {
            margin-top: 1.8rem;
            text-align: center;
            color: var(--dark-gray);
            font-size: 0.95rem;
        }

        .login-link a {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            position: relative;
        }

        .login-link a::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 2px;
            bottom: -2px;
            left: 0;
            background-color: var(--primary-color);
            transform: scaleX(0);
            transform-origin: right;
            transition: transform 0.3s ease;
        }

        .login-link a:hover::after {
            transform: scaleX(1);
            transform-origin: left;
        }

        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .register-container {
            animation: fadeIn 0.8s ease-out forwards;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 1rem;
            }
            
            .form-container {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Background Animation -->
    <div class="bg-animation">
        <div class="wave"></div>
        <div class="wave"></div>
        <div class="wave"></div>
        <div class="wave"></div>
    </div>
    
    <!-- Bubble Animation -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Create bubbles
            for (let i = 0; i < 12; i++) {
                createBubble();
            }
            
            function createBubble() {
                const bubble = document.createElement('div');
                bubble.classList.add('bubble');
                
                // Random position, size and animation duration
                const size = Math.random() * 30 + 10;
                const left = Math.random() * 100;
                const animDuration = Math.random() * 8 + 4;
                const animDelay = Math.random() * 3;
                
                bubble.style.width = `${size}px`;
                bubble.style.height = `${size}px`;
                bubble.style.left = `${left}%`;
                bubble.style.animationDuration = `${animDuration}s`;
                bubble.style.animationDelay = `${animDelay}s`;
                
                document
                    .querySelector('.bg-animation')
                    .appendChild(bubble);
                
                // Remove bubble after animation completes
                setTimeout(() => {
                    bubble.remove();
                    createBubble();
                }, (animDuration + animDelay) * 1000);
            }
        });
    </script>
    
    <div class="container">
        <div class="register-container">
            <div class="header">
                <div class="header-icon">
                    <i class="fas fa-tshirt"></i>
                </div>
                <h1>Buat Akun Baru</h1>
                <p>Daftar sebagai pelanggan untuk menggunakan layanan Laundry kami</p>
            </div>
            
            <div class="form-container">
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
                        <label for="username">Username <span class="required">*</span></label>
                        <div class="input-group">
                            <span class="input-icon">
                                <i class="fas fa-user"></i>
                            </span>
                            <input type="text" id="username" name="username" class="form-control" placeholder="Masukkan username" required value="<?php echo isset($_POST['username']) ? $_POST['username'] : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Password <span class="required">*</span></label>
                            <div class="input-group">
                                <span class="input-icon">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" id="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Konfirmasi Password <span class="required">*</span></label>
                            <div class="input-group">
                                <span class="input-icon">
                                    <i class="fas fa-check-circle"></i>
                                </span>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Masukkan ulang password" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="nama">Nama Lengkap <span class="required">*</span></label>
                        <div class="input-group">
                            <span class="input-icon">
                                <i class="fas fa-id-card"></i>
                            </span>
                            <input type="text" id="nama" name="nama" class="form-control" placeholder="Masukkan nama lengkap anda" required value="<?php echo isset($_POST['nama']) ? $_POST['nama'] : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <div class="input-group">
                                <span class="input-icon">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input type="email" id="email" name="email" class="form-control" placeholder="contoh@email.com" value="<?php echo isset($_POST['email']) ? $_POST['email'] : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="no_hp">No. HP</label>
                            <div class="input-group">
                                <span class="input-icon">
                                    <i class="fas fa-phone"></i>
                                </span>
                                <input type="text" id="no_hp" name="no_hp" class="form-control" placeholder="08xxxxxxxxxx" value="<?php echo isset($_POST['no_hp']) ? $_POST['no_hp'] : ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="alamat">Alamat</label>
                        <div class="input-group">
                            <span class="input-icon" style="top: 1.5rem;">
                                <i class="fas fa-map-marker-alt"></i>
                            </span>
                            <textarea id="alamat" name="alamat" class="form-control" placeholder="Masukkan alamat lengkap anda"><?php echo isset($_POST['alamat']) ? $_POST['alamat'] : ''; ?></textarea>
                        </div>
                    </div>
                    
                    <button type="submit" name="register" class="btn">
                        <i class="fas fa-user-plus"></i> DAFTAR SEKARANG
                    </button>
                </form>
                
                <div class="login-link">
                    Sudah punya akun? <a href="login.php">LOGIN DI SINI</a>
                </div>
            </div>
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
                
                // Update color based on progress
                if (progress < 33) {
                    progressBar.style.background = 'linear-gradient(90deg, #e74c3c, #f39c12)';
                } else if (progress < 66) {
                    progressBar.style.background = 'linear-gradient(90deg, #f39c12, #3498db)';
                } else {
                    progressBar.style.background = 'linear-gradient(90deg, #3498db, #2ecc71)';
                }
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
            
            // Add slight ripple effect on inputs
            formElements.forEach(element => {
                element.addEventListener('focus', function() {
                    this.classList.add('focus-ripple');
                });
                
                element.addEventListener('blur', function() {
                    this.classList.remove('focus-ripple');
                });
            });
        });
    </script>
</body>
</html>