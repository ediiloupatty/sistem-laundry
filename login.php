<?php
// File: login.php
// Halaman login untuk semua pengguna

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

if(isset($_POST['login'])) {
    $username = cleanInput($_POST['username']);
    $password = md5($_POST['password']);
    
    $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    $result = mysqli_query($koneksi, $query);
    
    if(mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        // Jika pelanggan, ambil data customer
        if($user['role'] == 'pelanggan') {
            $customer_query = "SELECT * FROM customers WHERE user_id = '".$user['id']."'";
            $customer_result = mysqli_query($koneksi, $customer_query);
            $customer = mysqli_fetch_assoc($customer_result);
            
            $_SESSION['customer_id'] = $customer['id'];
            $_SESSION['nama'] = $customer['nama'];
        }
        
        // Redirect berdasarkan role
        if($user['role'] == 'admin') {
            header("Location: admin/dashboard.php");
        } else {
            header("Location: pelanggan/dashboard.php");
        }
        exit();
    } else {
        $error = "Username atau password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Laundry</title>
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
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
        }
        
        .login-container {
            width: 100%;
            max-width: 420px;
            background: white;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: var(--box-shadow);
            transition: transform 0.3s ease;
        }
        
        .login-container:hover {
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
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
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
        input[type="password"] {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
            outline: none;
        }
        
        button {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.3s;
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
        
        .register-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: var(--text-muted);
        }
        
        .register-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        .demo-info {
            background: rgba(52, 152, 219, 0.1);
            border-left: 3px solid var(--primary-color);
            padding: 15px;
            margin-bottom: 25px;
            font-size: 14px;
            border-radius: 8px;
        }
        
        .demo-info strong {
            color: var(--primary-color);
            display: block;
            margin-bottom: 5px;
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
        
        .login-container {
            animation: fadeIn 0.6s ease-out forwards;
        }
        
        /* Responsive adjustments */
        @media (max-width: 480px) {
            .login-container {
                max-width: 100%;
                border-radius: 0;
                box-shadow: none;
                padding: 30px 20px;
            }
            
            body {
                background: white;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="header-area">
            <div class="logo">
                <i class="fas fa-tshirt"></i>
            </div>
            <h2>Selamat Datang Kembali</h2>
            <p class="subtitle">Silahkan login untuk mengakses Sistem Laundry</p>
        </div>
        
        <div class="demo-info">
            <strong>Demo Account</strong>
            Username: admin<br>
            Password: admin123
        </div>
        
        <?php if($error): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-group">
                    <span class="input-icon">
                        <i class="fas fa-user"></i>
                    </span>
                    <input type="text" id="username" name="username" placeholder="Masukkan username anda" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-group">
                    <span class="input-icon">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" id="password" name="password" placeholder="Masukkan password anda" required>
                </div>
            </div>
            
            <button type="submit" name="login">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>
        
        <div class="register-link">
            Belum punya akun? <a href="register.php">Daftar di sini</a>
        </div>
    </div>
</body>
</html>