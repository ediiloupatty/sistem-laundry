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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
    :root {
        /* Core Colors */
        --primary-color: #8b5cf6;
        --primary-dark: #7c3aed;
        --primary-light: #ddd6fe;
        --secondary-color: #f9fafb;
        --secondary-dark: #f1f5f9;
        --accent-color: #f59e0b;
        --accent-dark: #d97706;
        
        /* Text & UI Colors */
        --text-color: #111827;
        --text-secondary: #4b5563;
        --text-light: #ffffff;
        --text-muted: #9ca3af;
        --border-color: #e5e7eb;
        --border-hover: #d1d5db;
        
        /* Status Colors */
        --error-color: #ef4444;
        --error-light: #fee2e2;
        --success-color: #10b981;
        --success-light: #a7f3d0;
        --warning-color: #f59e0b;
        --warning-light: #fef3c7;
        --info-color: #3b82f6;
        --info-light: #dbeafe;
        
        /* UI Elements */
        --box-shadow: 0 4px 8px -2px rgba(0, 0, 0, 0.12), 0 2px 4px -1px rgba(0, 0, 0, 0.07);
        --box-shadow-lg: 0 12px 20px -4px rgba(0, 0, 0, 0.12), 0 6px 8px -2px rgba(0, 0, 0, 0.07);
        --box-shadow-inner: inset 0 2px 4px 0 rgba(0, 0, 0, 0.08);
        --card-shadow: 0 2px 5px 0 rgba(0, 0, 0, 0.1), 0 1px 3px 0 rgba(0, 0, 0, 0.07);
        
        /* Animation */
        --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        --transition-slow: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        
        /* Layout - Responsive Adjustments */
        --navbar-height: 70px;
        --navbar-height-mobile: 60px;
        --sidebar-width: 280px;
        --sidebar-width-collapsed: 80px;
        --content-max-width: 1280px;
        --container-padding: 2rem;
        --container-padding-sm: 1rem;
        --grid-gap: 1.5rem;
        --grid-gap-sm: 1rem;
        
        /* Border Radius */
        --border-radius-sm: 6px;
        --border-radius: 10px;
        --border-radius-md: 14px;
        --border-radius-lg: 18px;
        --border-radius-xl: 28px;
        --border-radius-full: 9999px;
        
        /* Breakpoints */
        --breakpoint-sm: 640px;
        --breakpoint-md: 768px;
        --breakpoint-lg: 1024px;
        --breakpoint-xl: 1280px;
        --breakpoint-2xl: 1536px;
        
        /* Typography - Responsive */
        --font-size-xs: 0.75rem;
        --font-size-sm: 0.875rem;
        --font-size-base: 1rem;
        --font-size-lg: 1.125rem;
        --font-size-xl: 1.25rem;
        --font-size-2xl: 1.5rem;
        --font-size-3xl: 1.875rem;
        --font-size-4xl: 2.25rem;
        --line-height-tight: 1.25;
        --line-height-normal: 1.5;
        --line-height-relaxed: 1.75;
        
        /* Spacing System */
        --space-1: 0.25rem;
        --space-2: 0.5rem;
        --space-3: 0.75rem;
        --space-4: 1rem;
        --space-5: 1.25rem;
        --space-6: 1.5rem;
        --space-8: 2rem;
        --space-10: 2.5rem;
        --space-12: 3rem;
        --space-16: 4rem;
        --space-20: 5rem;
        --space-24: 6rem;
        
        /* Z-index System */
        --z-0: 0;
        --z-10: 10;
        --z-20: 20;
        --z-30: 30;
        --z-40: 40;
        --z-50: 50;
        --z-dropdown: 1000;
        --z-sticky: 1100;
        --z-fixed: 1200;
        --z-modal: 1300;
        --z-tooltip: 1400;
    }

    /* Base & Reset Styles */
/* Original styles with modifications */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html {
    font-size: 16px;
    box-sizing: border-box;
    scroll-behavior: smooth;
}

body {
    font-family: 'Nunito', 'Segoe UI', Arial, sans-serif;
    color: var(--text-color);
    line-height: var(--line-height-normal);
    background-color: var(--light-gray);
    margin: 0;
    padding: 0;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    background-color: #f9fafb;
    overflow-x: hidden;
    font-size: 15px;
}

/* Container */
.container {
    width: 100%;
    max-width: var(--content-max-width);
    margin-left: auto;
    margin-right: auto;
    padding-left: var(--container-padding);
    padding-right: var(--container-padding);
}

@media (max-width: 767px) {
    .container {
        padding-left: var(--container-padding-sm);
        padding-right: var(--container-padding-sm);
    }
}

/* Grid System */
.grid {
    display: grid;
    gap: var(--grid-gap);
    grid-template-columns: repeat(12, 1fr);
}

.grid-auto-fit {
    display: grid;
    gap: var(--grid-gap);
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
}

@media (max-width: 767px) {
    .grid, .grid-auto-fit {
        gap: var(--grid-gap-sm);
    }
}

/* Flex Utilities */
.flex {
    display: flex;
}

.flex-col {
    flex-direction: column;
}

.items-center {
    align-items: center;
}

.justify-between {
    justify-content: space-between;
}

/* Modern Header/Navbar Styles */
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
    z-index: var(--z-sticky);
    width: 100%;
    padding: 0 1.5rem;
}

.brand {
    display: flex;
    align-items: center;
    font-size: 1.4rem;
    font-weight: 700;
    height: var(--navbar-height);
    text-decoration: none;
    color: var(--text-light);
    white-space: nowrap;
    min-width: 180px;
    letter-spacing: 0.5px;
    transition: var(--transition);
}

.brand:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

.brand i {
    margin-right: 12px;
    font-size: 1.5rem;
    color: var(--text-light);
    background: rgba(255, 255, 255, 0.2);
    width: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--border-radius);
}

.nav-menu {
    display: flex;
    align-items: center;
    height: var(--navbar-height);
    flex: 1;
    justify-content: flex-start;
    margin-left: 1rem;
}

.nav-menu a {
    color: var(--text-light);
    text-decoration: none;
    padding: 0 1.25rem;
    height: 100%;
    display: flex;
    align-items: center;
    position: relative;
    font-weight: 500;
    transition: var(--transition);
    border-bottom: 3px solid transparent;
    letter-spacing: 0.3px;
    opacity: 0.85;
}

.nav-menu a:hover {
    background-color: rgba(255, 255, 255, 0.1);
    border-bottom: 3px solid rgba(255, 255, 255, 0.7);
    opacity: 1;
}

.nav-menu a.active {
    background-color: rgba(255, 255, 255, 0.15);
    border-bottom: 3px solid var(--text-light);
    font-weight: 600;
    opacity: 1;
}

.nav-menu a i {
    margin-right: 10px;
    width: 20px;
    text-align: center;
    font-size: 1rem;
}

/* Sidebar */
.sidebar {
    width: var(--sidebar-width);
    background-color: var(--text-light);
    height: calc(100vh - var(--navbar-height));
    position: fixed;
    top: var(--navbar-height);
    left: 0;
    overflow-y: auto;
    transition: var(--transition);
    box-shadow: var(--box-shadow);
    z-index: var(--z-30);
}

.sidebar.collapsed {
    width: var(--sidebar-width-collapsed);
}

.main-content {
    margin-left: var(--sidebar-width);
    padding: var(--space-8);
    min-height: calc(100vh - var(--navbar-height));
    transition: var(--transition);
    width: 100%;
    max-width: var(--content-max-width);
    margin: 0 auto;
    flex: 1;
}

.main-content.expanded {
    margin-left: var(--sidebar-width-collapsed);
}

/* MODIFIED: Updated User Menu with Dropdown - Aligned with brand icon */
.user-section {
    display: flex;
    align-items: center;
    height: var(--navbar-height);
    gap: 1.5rem; /* Increased gap between items */
    margin-right: 0.5rem;
    padding-left: 1rem; /* Match padding with brand */
}

/* MODIFIED: User info aligned with the brand icon */
.user-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    position: relative;
    cursor: pointer;
    padding: 0.5rem 1rem;
    border-radius: var(--border-radius);
    transition: var(--transition);
    margin-left: auto; /* Push toward center */
}

.user-info:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

.user-avatar {
    width: 40px;
    height: 40px;
    background-color: rgba(255, 255, 255, 0.2);
    border-radius: var(--border-radius-full);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    color: var(--text-light);
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.user-details {
    display: flex;
    flex-direction: column;
}

.user-name {
    font-weight: 600;
    font-size: 0.95rem;
    color: var(--text-light);
    max-width: 150px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-role {
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.8);
    letter-spacing: 0.5px;
    text-transform: capitalize;
}

/* MODIFIED: Logout button increased size */
.logout-btn {
    background-color: rgba(255, 255, 255, 0.15);
    color: white;
    padding: 0.65rem 1.25rem; /* Increased padding */
    border-radius: var(--border-radius);
    text-decoration: none;
    transition: var(--transition);
    display: flex;
    align-items: center;
    border: 1px solid rgba(255, 255, 255, 0.1);
    gap: 0.5rem;
    font-weight: 600; /* Made bolder */
    font-size: 1rem; /* Increased font size */
}

.logout-btn:hover {
    background-color: rgba(255, 255, 255, 0.25);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.logout-btn i {
    font-size: 1.1rem; /* Increased icon size */
}

/* Modern Notification Badge */
.notification-badge {
    background: var(--error-color);
    color: white;
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 0.7rem;
    margin-left: 5px;
    position: relative;
    top: -8px;
    right: 5px;
    font-weight: 700;
}

/* Enhanced Notification Styles */
.notification-container {
    position: relative;
    display: flex;
    align-items: center;
    margin-right: 0.5rem;
}

.notification-icon {
    position: relative;
    cursor: pointer;
    padding: 0.75rem;
    border-radius: var(--border-radius-full);
    transition: var(--transition);
    background-color: rgba(255, 255, 255, 0.1);
    height: 40px;
    width: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.notification-icon:hover {
    background-color: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
}

.notification-icon i {
    font-size: 1.1rem;
    color: white;
}

.notification-badge-icon {
    position: absolute;
    top: -4px;
    right: -4px;
    background: var(--error-color);
    color: white;
    min-width: 20px;
    height: 20px;
    border-radius: var(--border-radius-full);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: 700;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    border: 2px solid var(--primary-dark);
    padding: 0 4px;
}

/* MODIFIED: Enhanced chat-like notification dropdown */
.notification-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    width: 380px; /* Slightly wider */
    background: white;
    border-radius: var(--border-radius-lg);
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15); /* Enhanced shadow */
    z-index: 1001;
    display: none;
    margin-top: 15px;
    max-height: 500px; /* Increased max height */
    overflow: hidden;
    animation: fadeIn 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid rgba(0, 0, 0, 0.08);
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-15px); }
    to { opacity: 1; transform: translateY(0); }
}

.notification-dropdown.active {
    display: block;
}

/* MODIFIED: Enhanced notification header */
.notification-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); /* Gradient background */
    border-radius: var(--border-radius-lg) var(--border-radius-lg) 0 0;
    color: white;
}

/* MODIFIED: Enhanced notification title */
.notification-title {
    font-weight: 600;
    color: white; /* Changed to white for contrast */
    font-size: 16px;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
}

.notification-title i {
    margin-right: 8px;
    font-size: 18px;
}

.mark-all-read {
    color: rgba(255, 255, 255, 0.9); /* Lighter color for contrast */
    text-decoration: none;
    font-size: 13px;
    cursor: pointer;
    padding: 5px 10px;
    border-radius: var(--border-radius-sm);
    transition: var(--transition);
    font-weight: 500;
    background-color: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.mark-all-read:hover {
    background-color: rgba(255, 255, 255, 0.25);
    text-decoration: none;
}

.notification-list {
    max-height: 430px; /* Increased height */
    overflow-y: auto;
    padding: 0.5rem 0;
    scrollbar-width: thin;
    background-color: #f8fafc; /* Subtle light background */
}

.notification-list::-webkit-scrollbar {
    width: 6px;
}

.notification-list::-webkit-scrollbar-track {
    background: #f5f5f5;
}

.notification-list::-webkit-scrollbar-thumb {
    background: #ddd;
    border-radius: 3px;
}

/* MODIFIED: Chat bubble style for notification items */
.notification-item {
    padding: 16px 20px;
    margin: 8px 12px;
    border-radius: var(--border-radius-lg);
    cursor: pointer;
    transition: var(--transition);
    color: #333; /* Darker text for better readability */
    display: flex;
    flex-direction: column;
    position: relative;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    border-left: none; /* Remove left border */
    background-color: white;
}

.notification-item:hover {
    background-color: #f0f4ff; /* Light blue on hover */
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(67, 97, 238, 0.1);
}

/* MODIFIED: Unread message style with blue chat bubble */
.notification-item.unread {
    background-color: #ebf3ff; /* Light blue background */
    border: 1px solid rgba(67, 97, 238, 0.2);
    position: relative;
}

/* MODIFIED: Better contrast for notification text */
.notification-item .notification-title-text {
    font-weight: 600;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    color: #1e293b; /* Darker color for better contrast */
    font-size: 14px;
}

.notification-item .notification-title-text i {
    margin-right: 10px;
    color: var(--primary-color);
    flex-shrink: 0;
    width: 18px;
    text-align: center;
    font-size: 16px; /* Slightly larger icon */
}

/* MODIFIED: Better contrast for notification message */
.notification-item .notification-message {
    margin-bottom: 8px;
    font-size: 13.5px;
    color: #475569; /* Darker gray for better contrast */
    padding-left: 28px; /* Align with icon */
    word-wrap: break-word;
    word-break: break-word;
    overflow-wrap: break-word;
    white-space: normal;
    width: 100%;
    line-height: 1.6;
}

/* MODIFIED: Enhanced notification time display */
.notification-item .notification-time {
    font-size: 12px;
    color: #64748b; /* Better contrast muted color */
    padding-left: 28px;
    display: flex;
    align-items: center;
    margin-top: 4px;
    font-weight: 500;
}

.notification-item .notification-time i {
    margin-right: 5px;
    font-size: 10px;
    flex-shrink: 0;
    color: var(--primary-color);
}

.notification-empty {
    padding: 40px 20px;
    text-align: center;
    color: #64748b;
    font-size: 14px;
    background-color: #f8fafc;
}

.notification-empty i {
    display: block;
    font-size: 40px;
    color: #cbd5e1;
    margin-bottom: 16px;
}

/* Enhanced triangle pointer for dropdown */
.notification-dropdown:before {
    content: '';
    position: absolute;
    top: -10px;
    right: 18px;
    width: 20px;
    height: 20px;
    background: var(--primary-color); /* Match header gradient start */
    transform: rotate(45deg);
    border: none;
    box-shadow: -2px -2px 5px rgba(0, 0, 0, 0.05);
}

/* Card Component */
.card {
    background-color: var(--text-light);
    border-radius: var(--border-radius);
    box-shadow: var(--card-shadow);
    padding: var(--space-6);
    height: 100%;
    transition: var(--transition);
    margin-bottom: 1.5rem;
    border: 1px solid var(--border-color);
}

.card:hover {
    box-shadow: var(--box-shadow);
    transform: translateY(-2px);
}

.card-header {
    border-bottom: 1px solid var(--border-color);
    padding-bottom: 1rem;
    margin-bottom: 1.25rem;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* Glass Morphism Effect for Special Cards */
.glass-card {
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.5);
    box-shadow: 0 8px 32px rgba(31, 38, 135, 0.1);
}

/* Button Component */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: var(--space-2) var(--space-4);
    border-radius: var(--border-radius);
    font-weight: 500;
    transition: var(--transition);
    cursor: pointer;
    text-decoration: none;
    border: none;
    outline: none;
}

.btn-primary {
    background-color: var(--primary-color);
    color: var(--text-light);
}

.btn-primary:hover {
    background-color: var(--primary-dark);
}

.btn-accent {
    background-color: var(--accent-color);
    color: var(--text-light);
}

.btn-accent:hover {
    background-color: var(--accent-dark);
}

/* Form Elements */
.form-group {
    margin-bottom: var(--space-4);
}

.form-label {
    display: block;
    margin-bottom: var(--space-2);
    font-weight: 500;
    color: var(--text-secondary);
}

.form-control {
    width: 100%;
    padding: var(--space-3);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    background-color: var(--text-light);
    transition: var(--transition);
}

.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: var(--box-shadow-inner);
    outline: none;
}

/* Alert Styles */
.alert {
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
    border-radius: var(--border-radius);
    display: flex;
    align-items: center;
    box-shadow: var(--card-shadow);
    animation: slideInDown 0.3s ease-out;
    border-left: 4px solid transparent;
}

@keyframes slideInDown {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.alert i {
    margin-right: 12px;
    font-size: 1.25rem;
    opacity: 0.9;
}

.alert-success {
    background-color: var(--success-light);
    color: var(--success-color);
    border-left-color: var(--success-color);
}

.alert-error {
    background-color: var(--error-light);
    color: var(--error-color);
    border-left-color: var(--error-color);
}

.alert-info {
    background-color: var(--info-light);
    color: var(--info-color);
    border-left-color: var(--info-color);
}

.alert-warning {
    background-color: var(--warning-light);
    color: var(--warning-color);
    border-left-color: var(--warning-color);
}

/* Badges */
.badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 9999px;
}

.badge-primary {
    background-color: var(--primary-color);
    color: white;
}

.badge-success {
    background-color: var(--success-color);
    color: white;
}

.badge-warning {
    background-color: var(--warning-color);
    color: white;
}

.badge-danger {
    background-color: var(--error-color);
    color: white;
}

.badge-info {
    background-color: var(--info-color);
    color: white;
}

/* Modern Footer */
footer {
    margin-top: auto;
    background: linear-gradient(to right, #2d3748, #1a202c);
    color: white;
    text-align: center;
    padding: 1.25rem;
    width: 100%;
    font-size: 0.875rem;
}

/* Mobile Responsiveness */
@media (max-width: 992px) {
    .navbar {
        height: auto;
        flex-direction: column;
        align-items: stretch;
        padding: 0;
    }
    
    .top-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 1rem;
        height: var(--navbar-height-mobile);
        width: 100%;
    }
    
    /* Ensure sidebar is hidden/adjustable on mobile */
    .sidebar {
        transform: translateX(-100%);
        z-index: var(--z-fixed);
        transition: transform 0.3s ease;
    }
    
    .sidebar.active {
        transform: translateX(0);
    }
    
    .main-content {
        margin-left: 0 !important;
        padding: var(--space-4);
        width: 100%;
    }
    
    /* Fix navigation menu behavior on mobile */
    .nav-menu {
        flex-direction: column;
        height: auto;
        margin-left: 0;
        overflow: hidden;
        max-height: 0;
        transition: max-height 0.3s ease-in-out;
        width: 100%;
    }
    
    .nav-menu.active {
        max-height: 400px;
    }
    
    .nav-menu a {
        width: 100%;
        height: auto;
        padding: 0.75rem 1.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
}

/* MODIFIED: Improved mobile styles */
@media (max-width: 768px) {
    /* Rearrange user section: Username, Notification, Logout */
    .user-section {
    height: auto;
    padding: 15px 1.5rem; /* Diperbesar dari 10px 1rem */
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem; /* Diperbesar dari 0.75rem */
    width: 100%;
}

/* User info aligned better */
.user-info {
    display: flex;
    align-items: center;
    justify-content: center; /* Tetap center */
    padding: 1rem; /* Diperbesar dari 0.5rem */
    margin: 0;
    gap: 1rem; /* Diperbesar dari 0.75rem */
    width: 100%;
    min-height: 60px; /* Tambahkan minimum height */
}
    
    /* Username position */
    #userProfileButton {
        margin-right: auto; /* Tambahkan kembali untuk dorong ke kiri */
        margin-left: 10px; /* Tambahkan margin kiri untuk tidak terlalu pinggir */
        font-size: 0.9rem;
        text-align: left; /* Ubah rata teks ke kiri lagi */
        max-width: 150px;
        order: 1;
    }
    
    /* Notification before logout */
    .notification-container {
        order: 2; /* Middle element */
        margin: 0;
    }
    
    /* Logout button responsive size */
    .logout-btn {
        padding: 0.4rem 0.6rem;
        font-size: 0.9rem;
        order: 3; /* Last element */
        margin-left: 0;
        white-space: nowrap;
    }
    
    /* Make notification dropdown more mobile-friendly */
    .notification-dropdown {
        position: fixed;
        top: var(--navbar-height-mobile);
        right: 10px;
        width: 320px !important; /* Slightly larger */
        max-width: calc(100% - 20px);
        max-height: 75vh; /* Larger visible area */
        z-index: var(--z-modal);
    }
    
    .notification-dropdown:before {
        right: 20px; /* Position arrow correctly */
    }
}

@media (max-width: 576px) {
    .notification-dropdown {
        width: calc(100% - 20px) !important;
        left: 10px;
        right: 10px;
        max-height: 65vh;
    }
    
    #userProfileButton {
        font-size: 0.85rem;
        max-width: 110px; /* Slightly larger */
    }
    
    .logout-btn {
        font-size: 0.8rem;
        padding: 0.3rem 0.5rem;
    }
}

/* Helper Classes */
.text-center { text-align: center; }
.mt-1 { margin-top: 0.25rem; }
.mt-2 { margin-top: 0.5rem; }
.mt-3 { margin-top: 1rem; }
.mt-4 { margin-top: 1.5rem; }
.mt-5 { margin-top: 2rem; }
.mb-1 { margin-bottom: 0.25rem; }
.mb-2 { margin-bottom: 0.5rem; }
.mb-3 { margin-bottom: 1rem; }
.mb-4 { margin-bottom: 1.5rem; }
.mb-5 { margin-bottom: 2rem; }

/* Animations for Page Elements */
.fade-in {
    animation: fadeIn 0.5s ease-in-out;
}

.slide-in-right {
    animation: slideInRight 0.5s ease-in-out;
}

@keyframes slideInRight {
    from {
        transform: translateX(30px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* ADDED: Chat-like animation for notification items */
@keyframes messageIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.notification-item {
    animation: messageIn 0.3s ease-out;
    animation-fill-mode: both;
}

.notification-item:nth-child(1) { animation-delay: 0.05s; }
.notification-item:nth-child(2) { animation-delay: 0.1s; }
.notification-item:nth-child(3) { animation-delay: 0.15s; }
.notification-item:nth-child(4) { animation-delay: 0.2s; }
.notification-item:nth-child(5) { animation-delay: 0.25s; }
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
                            notificationList.innerHTML = '<div class="notification-empty"><i class="fas fa-bell-slash"></i>Tidak ada notifikasi</div>';
                        }
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        document.getElementById('notificationList').innerHTML = `<div class="notification-empty"><i class="fas fa-exclamation-triangle"></i>Gagal memuat notifikasi: ${error.message}</div>`;
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
            
            // Add smooth page transitions
            document.querySelectorAll('.nav-menu a').forEach(link => {
                link.addEventListener('click', function(e) {
                    // Only if it's not an external link and not prevented already
                    if (this.getAttribute('href').indexOf('#') !== 0 && 
                        !this.getAttribute('target') && 
                        e.button == 0 && 
                        !e.ctrlKey && !e.metaKey) {
                        
                        e.preventDefault(); // Prevent default link behavior
                        
                        // Add fade out effect to the main content
                        const mainContent = document.querySelector('.main-content');
                        if (mainContent) {
                            mainContent.style.opacity = '0';
                            mainContent.style.transition = 'opacity 0.3s ease';
                        }
                        
                        // Navigate after a short delay
                        setTimeout(() => {
                            window.location.href = this.getAttribute('href');
                        }, 300);
                    }
                });
            });
            
            // Pre-load pages for faster navigation
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
            
            // Call preload links after page load
            setTimeout(preloadLinks, 1000);
            
            // Add subtle hover effects to interactive elements
            document.querySelectorAll('button, .logout-btn, .nav-menu a').forEach(el => {
                el.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                
                el.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
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

            
        <span id="userProfileButton" style="cursor: pointer;">
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