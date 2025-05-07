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

        .login-container {
            width: 100%;
            max-width: 400px;
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

        .input-group {
            position: relative;
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

        .demo-info {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--primary-color);
            padding: 0.75rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            border-left: 3px solid var(--primary-color);
            position: relative;
            overflow: hidden;
        }

        .demo-info::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.4) 50%, transparent 100%);
            animation: shine 3s infinite linear;
        }

        @keyframes shine {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .demo-info strong {
            display: block;
            margin-bottom: 0.25rem;
            color: var(--secondary-color);
        }

        .text-center {
            text-align: center;
        }

        .register-link {
            margin-top: 1.8rem;
            text-align: center;
            color: var(--dark-gray);
            font-size: 0.95rem;
        }

        .register-link a {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            position: relative;
        }

        .register-link a::after {
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

        .register-link a:hover::after {
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

        .login-container {
            animation: fadeIn 0.8s ease-out forwards;
        }

        /* Responsive */
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
        <div class="login-container">
            <div class="header">
                <div class="header-icon">
                    <i class="fas fa-tshirt"></i>
                </div>
                <h1>Sistem Laundry</h1>
                <p>Masuk untuk menggunakan layanan</p>
            </div>
            
            <div class="form-container">
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
                            <input type="text" id="username" name="username" class="form-control" placeholder="Masukkan username" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-group">
                            <span class="input-icon">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password" required>
                        </div>
                    </div>
                    
                    <button type="submit" name="login" class="btn">
                        <i class="fas fa-sign-in-alt"></i> LOGIN
                    </button>
                </form>
                
                <div class="register-link">
                    Belum punya akun? <a href="register.php">REGISTER</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>