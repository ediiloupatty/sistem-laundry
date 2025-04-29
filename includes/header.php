<?php
// File: includes/header.php
// Header untuk semua halaman

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah user sudah login
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Ambil nama user untuk ditampilkan
$nama_user = isset($_SESSION['nama']) ? $_SESSION['nama'] : $_SESSION['username'];
$role = $_SESSION['role'];

// Cek notifikasi pembayaran pending untuk admin
$payment_notification = '';
if($role == 'admin') {
    $pending_query = "SELECT COUNT(*) as pending_count 
                     FROM payments 
                     WHERE status_pembayaran = 'pending' 
                     AND bukti_pembayaran IS NOT NULL";
    $pending_result = mysqli_query($koneksi, $pending_query);
    $pending_data = mysqli_fetch_assoc($pending_result);
    $pending_count = $pending_data['pending_count'];
    
    if($pending_count > 0) {
        $payment_notification = '<span class="notification-badge">' . $pending_count . '</span>';
    }
}

// Determine active page for navigation highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Laundry - <?php echo $page_title ?? 'Dashboard'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --primary-dark: #2980b9;
            --secondary-color: #f8f9fa;
            --text-color: #333;
            --text-light: #ffffff;
            --text-muted: #6c757d;
            --border-color: #e0e0e0;
            --error-color: #e74c3c;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --info-color: #3498db;
            --box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
            --navbar-height: 60px;
            --content-max-width: 1200px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f9f9f9;
            color: var(--text-color);
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        /* Header/Navbar Styles */
        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: var(--text-light);
            height: var(--navbar-height);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--box-shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
            width: 100%;
        }

        .brand {
            display: flex;
            align-items: center;
            font-size: 22px;
            font-weight: 700;
            padding: 0 20px;
            height: var(--navbar-height);
            text-decoration: none;
            color: var(--text-light);
            white-space: nowrap;
            min-width: 200px;
        }
        
        .brand i {
            margin-right: 10px;
            font-size: 24px;
        }
        
        .nav-menu {
            display: flex;
            align-items: center;
            height: var(--navbar-height);
            flex: 1;
            justify-content: flex-start;
        }
        
        .nav-menu a {
            color: var(--text-light);
            text-decoration: none;
            padding: 0 20px;
            height: 100%;
            display: flex;
            align-items: center;
            position: relative;
            font-weight: 500;
            transition: var(--transition);
            border-bottom: 3px solid transparent;
        }
        
        .nav-menu a:hover,
        .nav-menu a.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-bottom: 3px solid white;
        }
        
        .nav-menu a i {
            margin-right: 8px;
            width: 16px;
            text-align: center;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            height: var(--navbar-height);
            padding: 0 20px;
            white-space: nowrap;
        }
        
        .user-info span {
            display: flex;
            align-items: center;
            font-weight: 500;
            margin-right: 15px;
        }
        
        .user-info span i {
            margin-right: 8px;
        }
        
        .logout-btn {
            background-color: rgba(231, 76, 60, 0.85);
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            transition: var(--transition);
            display: flex;
            align-items: center;
        }
        
        .logout-btn:hover {
            background-color: rgba(192, 57, 43, 1);
            transform: translateY(-2px);
        }
        
        .logout-btn i {
            margin-right: 8px;
        }
        
        /* Notification Badge */
        .notification-badge {
            background: #e74c3c;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 12px;
            margin-left: 5px;
            position: relative;
            top: -8px;
            right: 5px;
            font-weight: 700;
        }
        
        /* Updated Notification Styles */
        .notification-container {
            position: relative;
            display: flex;
            align-items: center;
            margin-right: 15px;
        }

        .notification-icon {
            position: relative;
            cursor: pointer;
            padding: 10px;
            border-radius: 50%;
            transition: var(--transition);
        }

        .notification-icon:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .notification-icon i {
            font-size: 20px;
            color: white;
        }

        .notification-badge-icon {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #e74c3c;
            color: white;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
        }

        .notification-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            width: 320px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            z-index: 1001;
            display: none;
            margin-top: 10px;
            max-height: 400px;
            overflow: hidden;
            animation: fadeIn 0.2s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .notification-dropdown.active {
            display: block;
        }

        .notification-header {
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f8f9fa;
            border-radius: 8px 8px 0 0;
        }

        .notification-title {
            font-weight: 600;
            color: var(--text-color);
        }

        .mark-all-read {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
            transition: var(--transition);
        }

        .mark-all-read:hover {
            background-color: rgba(52, 152, 219, 0.1);
            text-decoration: none;
        }

        .notification-list {
            max-height: 350px;
            overflow-y: auto;
            scrollbar-width: thin;
        }

        .notification-list::-webkit-scrollbar {
            width: 6px;
        }

        .notification-list::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .notification-list::-webkit-scrollbar-thumb {
            background: #ddd;
            border-radius: 3px;
        }
        
        .notification-item {
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: var(--transition);
            color: var(--text-color);
            display: flex;
            flex-direction: column;
        }

        .notification-item:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }

        .notification-item.unread {
            background-color: rgba(52, 152, 219, 0.1);
        }

        .notification-item .notification-title-text {
            font-weight: 600;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            color: var(--text-color);
        }

        .notification-item .notification-title-text i {
            margin-right: 8px;
            color: var(--primary-color);
            flex-shrink: 0;
        }

        .notification-item .notification-message {
            margin-bottom: 5px;
            font-size: 14px;
            color: var(--text-muted);
            padding-left: 24px; /* Align with icon */
            word-wrap: break-word;
            word-break: break-word;
            overflow-wrap: break-word;
            white-space: normal;
            width: 100%;
        }

        .notification-item .notification-time {
            font-size: 12px;
            color: var(--text-muted);
            padding-left: 24px;
            display: flex;
            align-items: center;
        }

        .notification-item .notification-time i {
            margin-right: 5px;
            font-size: 10px;
            flex-shrink: 0;
        }

        .notification-item .notification-time i {
            margin-right: 5px;
            font-size: 10px;
        }

        .notification-empty {
            padding: 20px;
            text-align: center;
            color: var(--text-muted);
        }

        /* Triangle pointer for dropdown */
        .notification-dropdown:before {
            content: '';
            position: absolute;
            top: -8px;
            right: 18px;
            width: 16px;
            height: 16px;
            background: white;
            transform: rotate(45deg);
            border-left: 1px solid rgba(0, 0, 0, 0.05);
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        /* Content Area */
        .main-content {
            padding: 30px;
            width: 100%;
            max-width: var(--content-max-width);
            margin: 0 auto;
            flex: 1;
        }
        
        /* Alert Styles */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .alert i {
            margin-right: 10px;
            font-size: 18px;
        }
        
        .alert-success {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(46, 204, 113, 0.2);
        }
        
        .alert-error {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--error-color);
            border: 1px solid rgba(231, 76, 60, 0.2);
        }
        
        .alert-info {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--info-color);
            border: 1px solid rgba(52, 152, 219, 0.2);
        }
        
        .alert-warning {
            background-color: rgba(243, 156, 18, 0.1);
            color: var(--warning-color);
            border: 1px solid rgba(243, 156, 18, 0.2);
        }

        footer {
            margin-top: auto;
            background: #333;
            color: white;
            text-align: center;
            padding: 15px;
            width: 100%;
        }
        
        /* Mobile Navigation Toggle */
        .nav-toggle {
            display: none;
            font-size: 24px;
            cursor: pointer;
        }
        
        .top-bar {
            display: none;
        }

        /* Mobile Responsiveness */
        @media (max-width: 992px) {
            .navbar {
                height: auto;
                flex-direction: column;
                align-items: stretch;
            }
            
            .top-bar {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0 20px;
                height: var(--navbar-height);
            }
            
            .nav-toggle {
                display: flex;
                height: var(--navbar-height);
                align-items: center;
            }
            
            .nav-menu {
                flex-direction: column;
                width: 100%;
                height: auto;
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.3s ease;
            }
            
            .nav-menu.active {
                max-height: 500px;
            }
            
            .nav-menu a {
                width: 100%;
                padding: 15px 20px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            .user-info {
                width: 100%;
                justify-content: space-between;
                padding: 15px 20px;
                border-top: 1px solid rgba(255, 255, 255, 0.1);
                height: auto;
            }
            
            .notification-dropdown {
                right: 10px;
                width: calc(100% - 20px);
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                padding: 15px;
            }
            
            .brand {
                font-size: 18px;
                min-width: auto;
            }
            
            .user-info span {
                font-size: 14px;
            }
            
            .logout-btn {
                padding: 6px 12px;
                font-size: 14px;
            }
        }
    </style>
    <script>
        // Fungsi untuk membuat tampilan konsisten dan mencegah content shifting
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle menu mobile
            const navToggle = document.getElementById('navToggle');
            const navMenu = document.getElementById('navMenu');
            
            if (navToggle) {
                navToggle.addEventListener('click', function() {
                    navMenu.classList.toggle('active');
                });
            }
            
            // Notification functionality
            // Updated notification functionality
            <?php if($role == 'pelanggan'): ?>
            const notificationIcon = document.getElementById('notificationIcon');
            const notificationDropdown = document.getElementById('notificationDropdown');
            const notificationContainer = document.querySelector('.notification-container');
            const notificationBadge = document.getElementById('notificationBadge');
            let notificationsLoaded = false;
            let notificationTimeout;

            // Show dropdown on hover
            notificationContainer.addEventListener('mouseenter', function(e) {
                clearTimeout(notificationTimeout);
                notificationDropdown.classList.add('active');
                
                // Load notifications if not already loaded
                if (!notificationsLoaded) {
                    fetchNotifications();
                    notificationsLoaded = true;
                }
            });

            // Hide dropdown when mouse leaves
            notificationContainer.addEventListener('mouseleave', function(e) {
                notificationTimeout = setTimeout(() => {
                    notificationDropdown.classList.remove('active');
                }, 300); // Small delay to prevent accidentally closing
            });

            // Keep dropdown open when hovering over it
            notificationDropdown.addEventListener('mouseenter', function() {
                clearTimeout(notificationTimeout);
            });

            notificationDropdown.addEventListener('mouseleave', function() {
                notificationTimeout = setTimeout(() => {
                    notificationDropdown.classList.remove('active');
                }, 300);
            });

            // Also allow click for mobile devices
            notificationIcon.addEventListener('click', function(e) {
                e.stopPropagation();
                notificationDropdown.classList.toggle('active');
                
                if (notificationDropdown.classList.contains('active')) {
                    fetchNotifications();
                    notificationsLoaded = true;
                }
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!notificationDropdown.contains(e.target) && !notificationIcon.contains(e.target)) {
                    notificationDropdown.classList.remove('active');
                }
            });

            // Function to fetch notifications
            function fetchNotifications() {
                console.log('Fetching notifications...');
                
                const ajaxPath = '../ajax/get_notification_count.php';
                
                fetch(ajaxPath)
                    .then(response => response.json())
                    .then(data => {
                        const notificationList = document.getElementById('notificationList');
                        notificationList.innerHTML = '';
                        
                        if (data.success && data.notifications && data.notifications.length > 0) {
                            data.notifications.forEach(notification => {
                                const notifItem = document.createElement('div');
                                notifItem.className = 'notification-item' + (notification.is_read == 0 ? ' unread' : '');
                                notifItem.dataset.id = notification.id;
                                
                                let icon = 'fa-info-circle';
                                if (notification.tipe === 'payment') {
                                    icon = 'fa-credit-card';
                                } else if (notification.tipe === 'order') {
                                    icon = 'fa-shopping-basket';
                                } else if (notification.tipe === 'status') {
                                    icon = 'fa-check-circle';
                                }
                                
                                let displayTitle = '';
                                let displayMessage = notification.pesan;
                                
                                // Create a better title based on the message
                                if (notification.pesan.toLowerCase().includes('pesanan selesai')) {
                                    displayTitle = 'Pesanan Selesai';
                                    displayMessage = 'Pesanan Anda telah selesai dan siap diambil';
                                } else if (notification.pesan.toLowerCase().includes('pembayaran berhasil')) {
                                    displayTitle = 'Pembayaran Berhasil';
                                    displayMessage = 'Pembayaran pesanan Anda telah dikonfirmasi';
                                } else if (notification.pesan.toLowerCase().includes('dalam proses')) {
                                    displayTitle = 'Pesanan Dalam Proses';
                                    displayMessage = 'Pesanan Anda sedang diproses oleh tim kami';
                                } else {
                                    displayTitle = notification.tipe.charAt(0).toUpperCase() + notification.tipe.slice(1);
                                }
                                
                                // Improved handling for order ids
                                if (notification.order_id) {
                                    // Remove any text containing the order ID to avoid duplication
                                    displayMessage = displayMessage.replace(new RegExp(`Pesanan #${notification.order_id}[:\\s]*`, 'g'), '');
                                    
                                    // Format the new message properly
                                    displayMessage = `Pesanan #${notification.order_id} - ${displayMessage}`;
                                }
                                
                                notifItem.innerHTML = `
                                    <div class="notification-title-text">
                                        <i class="fas ${icon}"></i> ${displayTitle}
                                    </div>
                                    <div class="notification-message">${displayMessage}</div>
                                    <div class="notification-time">
                                        <i class="fas fa-clock"></i> ${notification.created_at}
                                    </div>
                                `;
                                
                                notifItem.addEventListener('click', function() {
                                    markAsRead(notification.id);
                                    if (notification.order_id) {
                                        window.location.href = `tracking.php?id=${notification.order_id}`;
                                    }
                                });
                                
                                notificationList.appendChild(notifItem);
                            });
                        } else {
                            notificationList.innerHTML = '<div class="notification-empty">Tidak ada notifikasi</div>';
                        }
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        document.getElementById('notificationList').innerHTML = `<div class="notification-empty">Gagal memuat notifikasi: ${error.message}</div>`;
                    });
            }

            // Function to mark notification as read
            function markAsRead(notifId) {
                fetch('../ajax/mark_notification_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `notif_id=${notifId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateNotificationBadge();
                    }
                })
                .catch(error => console.error('Error:', error));
            }

            // Function to mark all as read
            document.getElementById('markAllRead')?.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                fetch('../ajax/mark_all_notifications_read.php', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        fetchNotifications();
                        updateNotificationBadge();
                    }
                })
                .catch(error => console.error('Error:', error));
            });

            // Function to update notification badge
            function updateNotificationBadge() {
                fetch('../ajax/get_notification_count.php')
                    .then(response => response.json())
                    .then(data => {
                        const unreadCount = data.notifications.filter(n => n.is_read == 0).length;
                        
                        if (unreadCount > 0) {
                            notificationBadge.textContent = unreadCount;
                            notificationBadge.style.display = 'flex';
                        } else {
                            notificationBadge.style.display = 'none';
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }

            // Initial badge update
            updateNotificationBadge();

            // Update notifications periodically
            setInterval(updateNotificationBadge, 30000); // Every 30 seconds
            <?php endif; ?>
            
            // Pre-load semua halaman untuk mencegah shifting saat navigasi
            const preloadLinks = () => {
                const links = document.querySelectorAll('.nav-menu a');
                links.forEach(link => {
                    const href = link.getAttribute('href');
                    if (href && !href.startsWith('#')) {
                        const preloadLink = document.createElement('link');
                        preloadLink.href = href;
                        preloadLink.rel = 'prefetch';
                        document.head.appendChild(preloadLink);
                    }
                });
            };
            
            // Panggil preload links setelah halaman dimuat
            setTimeout(preloadLinks, 1000);
        });
    </script>
</head>
<body>
    <nav class="navbar">
        <?php if(isset($_GET['mobile']) || $_SERVER['HTTP_USER_AGENT'] && (strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false)): ?>
        <div class="top-bar">
            <a href="<?php echo $role == 'admin' ? '../admin/dashboard.php' : '../pelanggan/dashboard.php'; ?>" class="brand">
                <i class="fas fa-tshirt"></i> Sistem Laundry
            </a>
            <div class="nav-toggle" id="navToggle">
                <i class="fas fa-bars"></i>
            </div>
        </div>
        <?php else: ?>
        <a href="<?php echo $role == 'admin' ? '../admin/dashboard.php' : '../pelanggan/dashboard.php'; ?>" class="brand">
            <i class="fas fa-tshirt"></i> Sistem Laundry
        </a>
        <?php endif; ?>
        
        <div class="nav-menu" id="navMenu" class="<?php echo (isset($_GET['mobile']) || (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false))) ? '' : 'active'; ?>">
            <?php if($role == 'admin'): ?>
                <a href="../admin/dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="../admin/daftar_pesanan.php" class="<?php echo $current_page == 'daftar_pesanan.php' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-basket"></i> Pesanan
                </a>
                <a href="../admin/konfirmasi_pembayaran.php" class="<?php echo $current_page == 'konfirmasi_pembayaran.php' ? 'active' : ''; ?>">
                    <i class="fas fa-credit-card"></i> Konfirmasi<?php echo $payment_notification; ?>
                </a>
                <a href="../admin/kelola_layanan.php" class="<?php echo $current_page == 'kelola_layanan.php' ? 'active' : ''; ?>">
                    <i class="fas fa-list-alt"></i> Layanan
                </a>
                <a href="../admin/laporan.php" class="<?php echo $current_page == 'laporan.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i> Laporan
                </a>
            <?php else: ?>
                <a href="../pelanggan/dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="../pelanggan/buat_pesanan.php" class="<?php echo $current_page == 'buat_pesanan.php' ? 'active' : ''; ?>">
                    <i class="fas fa-plus-circle"></i> Buat Pesanan
                </a>
                <a href="../pelanggan/tracking.php" class="<?php echo $current_page == 'tracking.php' ? 'active' : ''; ?>">
                    <i class="fas fa-search"></i> Tracking
                </a>
                <a href="../pelanggan/riwayat.php" class="<?php echo $current_page == 'riwayat.php' ? 'active' : ''; ?>">
                    <i class="fas fa-history"></i> Riwayat
                </a>
            <?php endif; ?>
        </div>
        
        <div class="user-info">
            <?php if($role == 'pelanggan'): ?>
                <div class="notification-container">
                <div class="notification-icon" id="notificationIcon">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge-icon" id="notificationBadge" style="display: none;">0</span>
                </div>
                <div class="notification-dropdown" id="notificationDropdown">
                    <div class="notification-header">
                        <span class="notification-title">Notifikasi</span>
                        <a href="#" class="mark-all-read" id="markAllRead">Tandai semua dibaca</a>
                    </div>
                    <div class="notification-list" id="notificationList">
                        <div class="notification-empty">Memuat notifikasi...</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <span>
                <i class="fas fa-user-circle"></i> <?php echo $nama_user; ?>
            </span>
            <a href="../logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </nav>
    
    <div class="main-content">
    
    <?php
    // This is for displaying flash messages
    if(isset($_SESSION['flash_message'])) {
        $message_type = $_SESSION['flash_message_type'] ?? 'info';
        $icon = 'info-circle';
        
        if($message_type == 'success') {
            $icon = 'check-circle';
        } elseif($message_type == 'error') {
            $icon = 'exclamation-circle';
        } elseif($message_type == 'warning') {
            $icon = 'exclamation-triangle';
        }
        
        echo '<div class="alert alert-' . $message_type . '">';
        echo '<i class="fas fa-' . $icon . '"></i> ' . $_SESSION['flash_message'];
        echo '</div>';
        
        // Clear the message after displaying
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_message_type']);
    }
    ?>