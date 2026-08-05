<?php
/**
 * Smart Water Guardian - Profile Page
 * User profile management with Avatar Upload & Activity Logging
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}

// User is logged in, get user info from session
$user_id = $_SESSION['user_id'] ?? '';
$user_email = $_SESSION['email'] ?? '';
$first_name = $_SESSION['firstName'] ?? 'User';
$last_name = $_SESSION['lastName'] ?? '';
$user_role = $_SESSION['role'] ?? 'consumer';
$full_name = trim($first_name . ' ' . $last_name);
$is_admin = ($user_role === 'system_admin' || $user_role === 'municipal_admin' || $user_role === 'admin');

// If no user data, redirect to login
if (empty($user_id)) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Smart Water Guardian</title>
    
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-auth-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-database-compat.js"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        /* ============================================================
           PROFILE PAGE - PROFESSIONAL DESIGN
           ============================================================ */
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --bg-primary: #0a0e1a;
            --bg-card: rgba(255,255,255,0.03);
            --bg-sidebar: rgba(10,14,26,0.85);
            --text-primary: #ffffff;
            --text-secondary: rgba(255,255,255,0.7);
            --text-muted: rgba(255,255,255,0.4);
            --border-color: rgba(255,255,255,0.06);
            --shadow-color: rgba(0,212,255,0.05);
            --input-bg: rgba(255,255,255,0.05);
            --input-text: #b8e6ff;
            --avatar-border: rgba(0,212,255,0.2);
        }
        
        .light-mode {
            --bg-primary: #f0f4f8;
            --bg-card: rgba(255,255,255,0.85);
            --bg-sidebar: rgba(255,255,255,0.95);
            --text-primary: #1a365d;
            --text-secondary: #2d3748;
            --text-muted: #4a5568;
            --border-color: #e2e8f0;
            --shadow-color: rgba(0,0,0,0.05);
            --input-bg: #f7fafc;
            --input-text: #2d3748;
            --avatar-border: #e2e8f0;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-primary);
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
            transition: background 0.3s ease, color 0.3s ease;
        }
        
        /* ========== ANIMATED BACKGROUND ========== */
        .bg-animation {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            z-index: 0; pointer-events: none; overflow: hidden;
        }
        .light-mode .bg-animation .orb { opacity: 0.08; }
        
        .bg-animation .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.3;
            animation: floatOrb 20s ease-in-out infinite;
            transition: opacity 0.3s ease;
        }
        .bg-animation .orb:nth-child(1) {
            width: 350px; height: 350px;
            background: radial-gradient(circle, #00d4ff, transparent 70%);
            top: -80px; right: -80px;
        }
        .bg-animation .orb:nth-child(2) {
            width: 250px; height: 250px;
            background: radial-gradient(circle, #7b2ffc, transparent 70%);
            bottom: -50px; left: -50px;
            animation-delay: -7s;
        }
        @keyframes floatOrb {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(-50px, -30px) scale(1.1); }
            50% { transform: translate(20px, 40px) scale(0.9); }
            75% { transform: translate(-30px, -20px) scale(1.05); }
        }
        
        /* ========== SIDEBAR ========== */
        .sidebar {
            width: 260px;
            background: var(--bg-sidebar);
            backdrop-filter: blur(20px);
            border-right: 1px solid var(--border-color);
            padding: 24px 0;
            position: fixed;
            top: 0; bottom: 0; left: 0;
            overflow-y: auto;
            z-index: 100;
            transition: transform 0.4s cubic-bezier(0.4,0,0.2,1), background 0.3s ease;
        }
        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(0,212,255,0.1); border-radius: 10px; }
        
        .sidebar-brand {
            display: flex; align-items: center; gap: 14px;
            padding: 0 24px 24px;
            border-bottom: 1px solid var(--border-color);
        }
        .sidebar-brand .logo-icon {
            width: 44px; height: 44px; border-radius: 14px;
            background: linear-gradient(135deg, #00d4ff, #7b2ffc);
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; color: white;
            box-shadow: 0 0 30px rgba(0,212,255,0.3);
            animation: pulseGlow 3s ease-in-out infinite;
        }
        @keyframes pulseGlow {
            0%, 100% { box-shadow: 0 0 30px rgba(0,212,255,0.3); }
            50% { box-shadow: 0 0 60px rgba(0,212,255,0.6), 0 0 120px rgba(123,47,252,0.2); }
        }
        .sidebar-brand .brand-text {
            font-size: 20px; font-weight: 800;
            background: linear-gradient(135deg, #00d4ff, #7b2ffc);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .sidebar-brand .brand-text span {
            font-weight: 300;
            -webkit-text-fill-color: rgba(255,255,255,0.3);
        }
        .light-mode .sidebar-brand .brand-text span {
            -webkit-text-fill-color: rgba(0,0,0,0.2);
        }
        
        .sidebar-nav { padding: 16px 12px; }
        .sidebar-nav .nav-label {
            font-size: 10px; text-transform: uppercase; letter-spacing: 2px;
            color: var(--text-muted); padding: 12px 12px 8px; font-weight: 600; opacity: 0.5;
        }
        .sidebar-nav a {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px; color: var(--text-secondary);
            text-decoration: none; transition: all 0.3s ease;
            border-radius: 12px; font-size: 14px; font-weight: 500;
            position: relative; opacity: 0.7;
        }
        .sidebar-nav a:hover { color: var(--text-primary); background: rgba(0,212,255,0.08); opacity: 1; }
        .sidebar-nav a.active {
            color: white; background: linear-gradient(135deg, rgba(0,212,255,0.15), rgba(123,47,252,0.1));
            box-shadow: 0 0 30px rgba(0,212,255,0.05); opacity: 1;
        }
        .light-mode .sidebar-nav a.active {
            color: #1a365d;
            background: linear-gradient(135deg, rgba(0,212,255,0.1), rgba(123,47,252,0.05));
        }
        .sidebar-nav a.active::before {
            content: ''; position: absolute; left: 0; top: 20%;
            height: 60%; width: 3px;
            background: linear-gradient(180deg, #00d4ff, #7b2ffc);
            border-radius: 0 4px 4px 0;
        }
        .sidebar-nav a i { width: 20px; text-align: center; font-size: 16px; }
        .sidebar-nav a .nav-badge {
            margin-left: auto; background: rgba(0,212,255,0.2);
            color: #00d4ff; font-size: 10px; padding: 2px 10px; border-radius: 20px; font-weight: 600;
        }
        
        /* ===== THEME TOGGLE BUTTON IN SIDEBAR ===== */
        .sidebar-nav .theme-toggle-btn {
            margin-top: 8px; border-top: 1px solid var(--border-color);
            padding-top: 8px; cursor: pointer;
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px; color: var(--text-secondary);
            border-radius: 12px; transition: all 0.3s ease;
            background: none; border-left: none; border-right: none; border-bottom: none;
            width: 100%; font-family: inherit; font-size: 14px; font-weight: 500; opacity: 0.7;
        }
        .sidebar-nav .theme-toggle-btn:hover {
            color: var(--text-primary); background: rgba(0,212,255,0.08); opacity: 1;
        }
        .sidebar-nav .theme-toggle-btn i { width: 20px; text-align: center; font-size: 16px; }
        
        .sidebar-nav .logout-link {
            margin-top: 12px; border-top: 1px solid var(--border-color);
            padding-top: 12px; color: rgba(255,100,100,0.5);
        }
        .sidebar-nav .logout-link:hover { color: #ff6b6b; background: rgba(255,100,100,0.08); opacity: 1; }
        .sidebar-footer {
            position: absolute; bottom: 20px; left: 0; right: 0;
            padding: 0 24px; font-size: 11px;
            color: var(--text-muted); text-align: center; letter-spacing: 1px; opacity: 0.3;
        }
        
        /* ========== MAIN CONTENT ========== */
        .main-content {
            flex: 1; margin-left: 260px;
            padding: 28px 36px 40px;
            min-height: 100vh; position: relative; z-index: 1;
        }
        
        /* ========== TOP BAR ========== */
        .topbar {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 28px; padding: 16px 24px;
            background: var(--bg-card); backdrop-filter: blur(10px);
            border-radius: 16px; border: 1px solid var(--border-color);
            flex-wrap: wrap; gap: 12px;
        }
        .topbar-left h2 {
            font-size: 24px; font-weight: 700;
            background: linear-gradient(135deg, #00d4ff, #7b2ffc);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .topbar-left p { color: var(--text-muted); font-size: 14px; }
        .topbar-right .date-display {
            color: var(--text-secondary); font-size: 13px;
            padding: 6px 16px; background: var(--bg-card);
            border-radius: 50px; border: 1px solid var(--border-color);
        }
        .menu-toggle {
            display: none; background: none; border: none;
            font-size: 24px; color: var(--text-primary); cursor: pointer;
        }
        
        /* ========== PROFILE GRID ========== */
        .profile-grid {
            display: grid; grid-template-columns: 320px 1fr; gap: 24px;
        }
        .profile-card {
            background: var(--bg-card); backdrop-filter: blur(10px);
            border-radius: 16px; padding: 28px;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        .profile-card:hover { border-color: rgba(0,212,255,0.15); }
        
        /* ========== PROFILE AVATAR WITH UPLOAD ========== */
        .profile-avatar-wrapper {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 16px;
            cursor: pointer;
        }
        
        .profile-avatar {
            width: 100%; height: 100%;
            border-radius: 50%;
            background: linear-gradient(135deg, #00d4ff, #7b2ffc);
            display: flex;
            align-items: center; justify-content: center;
            font-size: 48px; font-weight: 700;
            color: white;
            border: 4px solid var(--avatar-border);
            box-shadow: 0 0 40px var(--shadow-color);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .profile-avatar img {
            width: 100%; height: 100%; object-fit: cover;
        }
        
        .profile-avatar:hover {
            transform: scale(1.02);
            box-shadow: 0 0 60px rgba(0,212,255,0.2);
        }
        
        .avatar-upload-overlay {
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(10px);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 10px;
            color: white;
            opacity: 0;
            transition: opacity 0.3s ease;
            white-space: nowrap;
        }
        
        .profile-avatar-wrapper:hover .avatar-upload-overlay {
            opacity: 1;
        }
        
        .avatar-upload-overlay i {
            margin-right: 4px;
        }
        
        .profile-name {
            text-align: center;
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .profile-email {
            text-align: center;
            color: var(--text-muted);
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .profile-role {
            text-align: center;
            padding: 4px 20px;
            border-radius: 20px;
            font-size: 13px;
            display: inline-block;
            width: auto;
            margin: 0 auto 16px;
            background: rgba(0, 212, 255, 0.12);
            color: #00d4ff;
            border: 1px solid rgba(0, 212, 255, 0.1);
        }
        
        /* ========== METER NUMBER DISPLAY ========== */
        .meter-display {
            background: rgba(0, 212, 255, 0.05);
            border: 1px solid rgba(0, 212, 255, 0.1);
            border-radius: 12px;
            padding: 16px;
            margin: 16px 0;
            text-align: center;
        }
        .light-mode .meter-display {
            background: rgba(0, 212, 255, 0.05);
        }
        
        .meter-display .meter-label {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            opacity: 0.6;
        }
        
        .meter-display .meter-number {
            font-size: 20px;
            font-weight: 700;
            color: #00d4ff;
            font-family: 'Courier New', monospace;
            letter-spacing: 1px;
            margin-top: 4px;
            text-shadow: 0 0 30px rgba(0, 212, 255, 0.2);
        }
        
        .meter-display .meter-status {
            font-size: 12px;
            margin-top: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        
        .meter-status .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }
        .meter-status .status-dot.online { background: #00ff88; box-shadow: 0 0 20px rgba(0,255,136,0.3); }
        .meter-status .status-dot.offline { background: #ff6b6b; box-shadow: 0 0 20px rgba(255,107,107,0.3); }
        .meter-status .status-text.online { color: #00ff88; }
        .meter-status .status-text.offline { color: #ff6b6b; }
        
        /* ========== PROFILE STATS ========== */
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin: 16px 0;
            padding-top: 16px;
            border-top: 1px solid var(--border-color);
        }
        
        .profile-stat { text-align: center; }
        .profile-stat .number {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
        }
        .profile-stat .label {
            font-size: 12px;
            color: var(--text-muted);
        }
        
        .profile-meta {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--border-color);
        }
        .profile-meta p {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 4px;
        }
        .profile-meta p strong { color: var(--text-secondary); }
        .profile-meta .value { color: var(--text-primary); }
        
        /* ========== PROFILE FORM ========== */
        .profile-form .form-group { margin-bottom: 16px; }
        .profile-form label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 4px;
        }
        .profile-form label i { margin-right: 6px; color: #00d4ff; }
        
        .profile-form input,
        .profile-form textarea,
        .profile-form select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: var(--input-bg);
            color: var(--text-primary);
        }
        
        .profile-form input:focus,
        .profile-form textarea:focus,
        .profile-form select:focus {
            outline: none;
            border-color: #00d4ff;
            background: var(--input-bg);
            box-shadow: 0 0 30px rgba(0, 212, 255, 0.05);
        }
        
        .profile-form input::placeholder,
        .profile-form textarea::placeholder {
            color: var(--text-muted);
            opacity: 0.4;
        }
        
        .profile-form input:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .profile-form select option {
            background: var(--bg-primary);
            color: var(--text-primary);
        }
        
        .profile-form .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .profile-form small {
            font-size: 12px;
            color: var(--text-muted);
            opacity: 0.6;
        }
        
        /* ========== PROFILE ACTIONS ========== */
        .profile-actions {
            margin-top: 16px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #00d4ff, #7b2ffc);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 212, 255, 0.3);
        }
        
        .btn-outline {
            background: transparent;
            color: #00d4ff;
            border: 1px solid rgba(0, 212, 255, 0.2);
        }
        .btn-outline:hover {
            background: rgba(0, 212, 255, 0.1);
            border-color: #00d4ff;
        }
        
        .btn-danger {
            background: rgba(255, 107, 107, 0.15);
            color: #ff6b6b;
            border: 1px solid rgba(255, 107, 107, 0.1);
        }
        .btn-danger:hover {
            background: rgba(255, 107, 107, 0.25);
        }
        
        /* ========== TOAST ========== */
        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .toast {
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 500;
            font-size: 14px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.3);
            animation: slideInRight 0.3s ease;
            min-width: 250px;
            backdrop-filter: blur(10px);
        }
        
        .toast-success { background: rgba(0, 255, 136, 0.15); border: 1px solid #00ff88; color: #00ff88; }
        .toast-error { background: rgba(255, 107, 107, 0.15); border: 1px solid #ff6b6b; color: #ff6b6b; }
        .toast-info { background: rgba(0, 212, 255, 0.15); border: 1px solid #00d4ff; color: #00d4ff; }
        
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(50px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        /* ========== RESPONSIVE ========== */
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 20px; }
            .menu-toggle { display: block; }
        }
        
        @media (max-width: 768px) {
            .profile-grid { grid-template-columns: 1fr; }
            .profile-form .form-row { grid-template-columns: 1fr; }
            .topbar {
                flex-direction: column;
                text-align: center;
                padding: 16px;
            }
            .topbar-left h2 { font-size: 22px; }
            .profile-actions { flex-direction: column; }
            .profile-actions .btn { width: 100%; justify-content: center; }
            .profile-stats { grid-template-columns: repeat(3, 1fr); }
        }
    </style>
</head>
<body>
    <!-- ========== ANIMATED BACKGROUND ========== -->
    <div class="bg-animation">
        <div class="orb"></div>
        <div class="orb"></div>
    </div>

    <!-- ========== SIDEBAR ========== -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="logo-icon">
                <i class="fas fa-water"></i>
            </div>
            <div class="brand-text">Smart<span>Water</span></div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-label">Main Menu</div>
            
            <?php if ($is_admin): ?>
                <!-- Admin Sidebar -->
                <a href="admin.php">
                    <i class="fas fa-cog"></i> Admin
                </a>
                <a href="alerts.php">
                    <i class="fas fa-bell"></i> Alerts
                    <span class="nav-badge" id="alertBadge">0</span>
                </a>
                <a href="reviews.php">
                    <i class="fas fa-star"></i> Reviews
                </a>
                <a href="profile.php" class="active">
                    <i class="fas fa-user"></i> Profile
                </a>
            <?php else: ?>
                <!-- Consumer Sidebar -->
                <a href="dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="history.php">
                    <i class="fas fa-chart-line"></i> History
                </a>
                <a href="alerts.php">
                    <i class="fas fa-bell"></i> Alerts
                    <span class="nav-badge" id="alertBadge">0</span>
                </a>
                <a href="thresholds.php">
                    <i class="fas fa-sliders-h"></i> Thresholds
                </a>
                <a href="reviews.php">
                    <i class="fas fa-star"></i> Reviews
                </a>
                <a href="properties.php">
                    <i class="fas fa-home"></i> Properties
                </a>
                <a href="billing.php">
                    <i class="fas fa-credit-card"></i> Billing
                </a>
                <a href="profile.php" class="active">
                    <i class="fas fa-user"></i> Profile
                </a>
            <?php endif; ?>
            
            <!-- Theme Toggle -->
            <button class="theme-toggle-btn" onclick="toggleTheme()" id="themeToggle">
                <i class="fas fa-moon" id="themeIcon"></i>
                <span id="themeLabel">Dark Mode</span>
            </button>
            
            <!-- Logout -->
            <a href="#" onclick="logoutUser()" class="logout-link">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
        <div class="sidebar-footer">v2.0.0 - 2026</div>
    </aside>

    <!-- ========== MAIN CONTENT ========== -->
    <main class="main-content">
        <!-- Top Bar -->
        <header class="topbar">
            <div class="topbar-left">
                <button class="menu-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h2>My Profile</h2>
                <p>Manage your account settings</p>
            </div>
            <div class="topbar-right">
                <span class="date-display">
                    <i class="far fa-calendar-alt"></i> 
                    <?php echo date('l, F j, Y'); ?>
                </span>
            </div>
        </header>

        <!-- Profile Grid -->
        <div class="profile-grid">
            <!-- Left Column -->
            <div class="profile-card">
                <!-- Avatar with Upload -->
                <div class="profile-avatar-wrapper" onclick="document.getElementById('avatarInput').click()">
                    <div class="profile-avatar" id="avatarContainer">
                        <span id="avatarLetter"><?php echo strtoupper(substr($first_name, 0, 1)); ?></span>
                        <img id="avatarImage" style="display:none;" alt="Profile Avatar">
                    </div>
                    <div class="avatar-upload-overlay">
                        <i class="fas fa-camera"></i> Change Photo
                    </div>
                    <input type="file" id="avatarInput" accept="image/*" style="display:none;">
                </div>
                
                <div class="profile-name" id="profileFullName">
                    <?php echo htmlspecialchars($full_name); ?>
                </div>
                <div class="profile-email" id="profileEmail">
                    <?php echo htmlspecialchars($user_email); ?>
                </div>
                <div style="text-align:center;">
                    <span class="profile-role" id="profileRole">
                        <?php echo ucfirst(str_replace('_', ' ', $user_role)); ?>
                    </span>
                </div>
                
                <!-- Meter Number Display -->
                <div class="meter-display" id="meterDisplay">
                    <div class="meter-label">Water Meter</div>
                    <div class="meter-number" id="meterNumber">Loading...</div>
                    <div class="meter-status">
                        <span class="status-dot online" id="meterStatusDot"></span>
                        <span class="status-text online" id="meterStatusText">Online</span>
                    </div>
                </div>
                
                <div class="profile-stats">
                    <div class="profile-stat">
                        <div class="number" id="propertyCount">0</div>
                        <div class="label">Properties</div>
                    </div>
                    <div class="profile-stat">
                        <div class="number" id="deviceCount">0</div>
                        <div class="label">Devices</div>
                    </div>
                    <div class="profile-stat">
                        <div class="number" id="alertCount">0</div>
                        <div class="label">Alerts</div>
                    </div>
                </div>

                <div class="profile-meta">
                    <p><strong>Member since:</strong> <span class="value" id="memberSince">Loading...</span></p>
                    <p><strong>User ID:</strong> <span class="value"><?php echo htmlspecialchars(substr($user_id, 0, 12)) . '...'; ?></span></p>
                </div>
            </div>

            <!-- Right Column -->
            <div class="profile-card">
                <h3 style="margin-bottom:16px;color:var(--text-primary);font-size:18px;">Edit Profile</h3>
                <form id="profileForm" class="profile-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstName"><i class="fas fa-user"></i> First Name</label>
                            <input type="text" id="firstName" value="<?php echo htmlspecialchars($first_name); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="lastName"><i class="fas fa-user"></i> Last Name</label>
                            <input type="text" id="lastName" value="<?php echo htmlspecialchars($last_name); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                        <input type="email" id="email" value="<?php echo htmlspecialchars($user_email); ?>" disabled>
                        <small>Email cannot be changed</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone"><i class="fas fa-phone"></i> Phone Number</label>
                        <input type="tel" id="phone" placeholder="Your phone number" value="">
                    </div>
                    
                    <div class="form-group">
                        <label for="address"><i class="fas fa-home"></i> Address</label>
                        <textarea id="address" rows="3" placeholder="Your home address"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="role"><i class="fas fa-user-tag"></i> Role</label>
                        <select id="role" disabled>
                            <option value="consumer">Consumer</option>
                            <option value="municipal_admin">Municipal Admin</option>
                            <option value="system_admin">System Admin</option>
                        </select>
                        <small>Role cannot be changed. Please contact support if you need role changes.</small>
                    </div>

                    <div class="profile-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <button type="button" class="btn btn-outline" onclick="changePassword()">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                        <button type="button" class="btn btn-danger" onclick="deleteAccount()">
                            <i class="fas fa-trash"></i> Delete Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <script>
        // ============================================================
        // FIREBASE CONFIG
        // ============================================================
        const firebaseConfig = {
            apiKey: "AIzaSyCatcC7yo-a7E7dLAfAWh0iv1BCSoYxUP8",
            authDomain: "smartwaterguardian.firebaseapp.com",
            databaseURL: "https://smartwaterguardian-default-rtdb.firebaseio.com",
            projectId: "smartwaterguardian",
            storageBucket: "smartwaterguardian.firebasestorage.app",
            messagingSenderId: "12612851503",
            appId: "1:12612851503:web:ee8e80d5a46ed28e95b3da",
            measurementId: "G-525B8XXSKM"
        };

        firebase.initializeApp(firebaseConfig);
        const auth = firebase.auth();
        const database = firebase.database();

        let currentUser = null;
        let userData = null;
        const isAdmin = <?php echo $is_admin ? 'true' : 'false'; ?>;

        // ============================================================
        // THEME MANAGEMENT
        // ============================================================
        function getTheme() {
            return localStorage.getItem('swg_theme') || 'dark';
        }

        function setTheme(theme) {
            localStorage.setItem('swg_theme', theme);
            if (theme === 'light') {
                document.body.classList.add('light-mode');
                const icon = document.getElementById('themeIcon');
                const label = document.getElementById('themeLabel');
                if (icon) icon.className = 'fas fa-sun';
                if (label) label.textContent = 'Light Mode';
            } else {
                document.body.classList.remove('light-mode');
                const icon = document.getElementById('themeIcon');
                const label = document.getElementById('themeLabel');
                if (icon) icon.className = 'fas fa-moon';
                if (label) label.textContent = 'Dark Mode';
            }
            if (currentUser) {
                database.ref('users/' + currentUser.uid + '/preferences/theme').set(theme);
            }
        }

        function toggleTheme() {
            const current = getTheme();
            setTheme(current === 'dark' ? 'light' : 'dark');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const theme = getTheme();
            setTheme(theme);
        });

        // ============================================================
        // TOAST NOTIFICATIONS
        // ============================================================
        function showToast(message, type) {
            type = type || 'success';
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = 'toast toast-' + type;
            toast.textContent = message;
            container.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        // ============================================================
        // UPDATE ALERT BADGE
        // ============================================================
        function updateAlertBadge() {
            if (!currentUser) return;
            const alertsRef = database.ref('alerts/' + currentUser.uid);
            alertsRef.on('value', function(snapshot) {
                const data = snapshot.val();
                const count = data ? Object.keys(data).filter(key => !data[key].isRead).length : 0;
                document.getElementById('alertBadge').textContent = count;
            });
        }

        // ============================================================
        // AVATAR UPLOAD
        // ============================================================
        document.getElementById('avatarInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            if (!file.type.startsWith('image/')) {
                showToast('Please select an image file', 'warning');
                return;
            }
            
            if (file.size > 2 * 1024 * 1024) {
                showToast('Image must be less than 2MB', 'warning');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(event) {
                const imageData = event.target.result;
                database.ref('users/' + currentUser.uid + '/avatar').set(imageData)
                    .then(() => {
                        document.getElementById('avatarLetter').style.display = 'none';
                        document.getElementById('avatarImage').style.display = 'block';
                        document.getElementById('avatarImage').src = imageData;
                        showToast('Profile picture updated!', 'success');
                    })
                    .catch((error) => {
                        showToast(error.message, 'error');
                    });
            };
            reader.readAsDataURL(file);
        });

        // ============================================================
        // LOAD USER DATA FROM FIREBASE
        // ============================================================
        auth.onAuthStateChanged(function(user) {
            if (user) {
                currentUser = user;
                console.log('User logged in:', user.uid);
                
                // Load theme preference
                database.ref('users/' + user.uid + '/preferences/theme').once('value')
                    .then(snapshot => {
                        const theme = snapshot.val();
                        if (theme) setTheme(theme);
                    });
                
                updateAlertBadge();
                loadUserData(user.uid);
            } else {
                console.log('No user logged in');
                window.location.href = 'login.php';
            }
        });

        function loadUserData(uid) {
            // Get user data from Firebase
            const userRef = database.ref('users/' + uid);
            userRef.once('value', function(snapshot) {
                userData = snapshot.val();
                if (userData) {
                    console.log('User data loaded:', userData);
                    populateProfile(userData);
                } else {
                    console.warn('No user data found in Firebase');
                }
            });

            // Get properties count
            const propertiesRef = database.ref('properties/' + uid);
            propertiesRef.once('value', function(snapshot) {
                const data = snapshot.val();
                const count = data ? Object.keys(data).length : 0;
                document.getElementById('propertyCount').textContent = count;
            });

            // Get devices count
            const devicesRef = database.ref('devices/' + uid);
            devicesRef.once('value', function(snapshot) {
                const data = snapshot.val();
                const count = data ? Object.keys(data).length : 0;
                document.getElementById('deviceCount').textContent = count;
            });

            // Get alerts count
            const alertsRef = database.ref('alerts/' + uid);
            alertsRef.once('value', function(snapshot) {
                const data = snapshot.val();
                const count = data ? Object.keys(data).length : 0;
                document.getElementById('alertCount').textContent = count;
            });

            // Load meter number
            loadMeterNumber(uid);
            
            // Load avatar
            loadAvatar(uid);
        }

        // ============================================================
        // LOAD AVATAR
        // ============================================================
        function loadAvatar(uid) {
            database.ref('users/' + uid + '/avatar').once('value')
                .then(snapshot => {
                    const data = snapshot.val();
                    if (data) {
                        document.getElementById('avatarLetter').style.display = 'none';
                        document.getElementById('avatarImage').style.display = 'block';
                        document.getElementById('avatarImage').src = data;
                    }
                });
        }

        // ============================================================
        // LOAD METER NUMBER FROM FIREBASE
        // ============================================================
        function loadMeterNumber(uid) {
            if (userData && userData.meterNumber) {
                document.getElementById('meterNumber').textContent = userData.meterNumber;
                document.getElementById('meterStatusText').textContent = 'Registered';
                return;
            }
            
            const propertiesRef = database.ref('properties/' + uid);
            propertiesRef.once('value', function(snapshot) {
                const data = snapshot.val();
                if (data) {
                    const firstProperty = Object.values(data)[0];
                    if (firstProperty && firstProperty.meterId) {
                        document.getElementById('meterNumber').textContent = firstProperty.meterId;
                        document.getElementById('meterStatusText').textContent = 'Registered';
                        
                        const meterRef = database.ref('meters/' + firstProperty.meterId + '/lastReading');
                        meterRef.once('value', function(snapshot) {
                            const meterData = snapshot.val();
                            if (meterData && meterData.status === 'online') {
                                document.getElementById('meterStatusDot').className = 'status-dot online';
                                document.getElementById('meterStatusText').textContent = 'Online';
                                document.getElementById('meterStatusText').className = 'status-text online';
                            } else {
                                document.getElementById('meterStatusDot').className = 'status-dot offline';
                                document.getElementById('meterStatusText').textContent = 'Offline';
                                document.getElementById('meterStatusText').className = 'status-text offline';
                            }
                        });
                    } else {
                        document.getElementById('meterNumber').textContent = 'No meter registered';
                        document.getElementById('meterStatusText').textContent = 'Not registered';
                    }
                } else {
                    document.getElementById('meterNumber').textContent = 'No meter found';
                    document.getElementById('meterStatusText').textContent = 'Not registered';
                }
            }).catch((error) => {
                console.error('Error loading meter:', error);
                document.getElementById('meterNumber').textContent = 'Error loading';
            });
        }

        // ============================================================
        // POPULATE PROFILE
        // ============================================================
        function populateProfile(data) {
            const firstName = data.firstName || '';
            const lastName = data.lastName || '';
            document.getElementById('profileFullName').textContent = firstName + ' ' + lastName;
            document.getElementById('avatarLetter').textContent = firstName.charAt(0).toUpperCase() || 'U';
            
            document.getElementById('profileEmail').textContent = data.email || '';
            
            const role = data.role || 'consumer';
            document.getElementById('profileRole').textContent = role.replace('_', ' ').toUpperCase();
            document.getElementById('role').value = role;
            
            if (data.phone) {
                document.getElementById('phone').value = data.phone;
            }
            
            if (data.address) {
                document.getElementById('address').value = data.address;
            }
            
            if (data.createdAt) {
                const date = new Date(data.createdAt);
                document.getElementById('memberSince').textContent = date.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            } else {
                document.getElementById('memberSince').textContent = 'Just joined!';
            }
        }

        // ============================================================
        // SAVE PROFILE
        // ============================================================
        document.getElementById('profileForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!currentUser) {
                showToast('Please login first', 'error');
                return;
            }
            
            const firstName = document.getElementById('firstName').value.trim();
            const lastName = document.getElementById('lastName').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const address = document.getElementById('address').value.trim();
            
            if (!firstName || !lastName) {
                showToast('Please enter your full name', 'warning');
                return;
            }
            
            const btn = this.querySelector('button[type="submit"]');
            btn.textContent = 'Saving...';
            btn.disabled = true;
            
            try {
                // Do NOT update role - role is read-only
                await database.ref('users/' + currentUser.uid).update({
                    firstName: firstName,
                    lastName: lastName,
                    phone: phone || '',
                    address: address || '',
                    updatedAt: new Date().toISOString()
                });
                
                const response = await fetch('../api/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'set_session',
                        uid: currentUser.uid,
                        email: currentUser.email,
                        firstName: firstName,
                        lastName: lastName,
                        role: '<?php echo $user_role; ?>' // Keep existing role
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('Profile updated successfully!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    throw new Error('Session update failed');
                }
                
            } catch (error) {
                showToast('Error updating profile: ' + error.message, 'error');
            } finally {
                btn.textContent = 'Save Changes';
                btn.disabled = false;
            }
        });

        // ============================================================
        // CHANGE PASSWORD
        // ============================================================
        function changePassword() {
            if (!currentUser) {
                showToast('Please login first', 'error');
                return;
            }
            
            const newPassword = prompt('Enter your new password (min 6 characters):');
            if (newPassword && newPassword.length >= 6) {
                currentUser.updatePassword(newPassword)
                    .then(() => {
                        showToast('Password updated successfully!', 'success');
                    })
                    .catch((error) => {
                        showToast(error.message, 'error');
                    });
            } else if (newPassword) {
                showToast('Password must be at least 6 characters', 'warning');
            }
        }

        // ============================================================
        // DELETE ACCOUNT - DOUBLE CONFIRMATION
        // ============================================================
        function deleteAccount() {
            if (!currentUser) {
                showToast('Please login first', 'error');
                return;
            }
            
            // First confirmation
            if (!confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
                return;
            }
            
            // Second confirmation with specific text
            const confirmation = prompt('Type "DELETE" to confirm account deletion:');
            if (confirmation !== 'DELETE') {
                showToast('Account deletion cancelled', 'info');
                return;
            }
            
            // Third confirmation
            if (!confirm('This will permanently delete all your data. Are you absolutely sure?')) {
                return;
            }
            
            // Proceed with deletion
            database.ref('users/' + currentUser.uid).remove()
                .then(() => {
                    return currentUser.delete();
                })
                .then(() => {
                    showToast('Account deleted successfully', 'success');
                    fetch('../api/auth.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'logout' })
                    }).then(() => {
                        window.location.href = 'login.php';
                    });
                })
                .catch((error) => {
                    // If user needs to re-authenticate
                    if (error.code === 'auth/requires-recent-login') {
                        showToast('Please re-authenticate before deleting your account', 'warning');
                        // Optionally trigger re-authentication flow
                    } else {
                        showToast(error.message, 'error');
                    }
                });
        }

        // ============================================================
        // LOGOUT
        // ============================================================
        function logoutUser() {
            if (confirm('Are you sure you want to logout?')) {
                auth.signOut().then(() => {
                    fetch('../api/auth.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'logout' })
                    }).then(() => {
                        window.location.href = 'login.php';
                    });
                });
            }
        }

        // ============================================================
        // TOGGLE SIDEBAR
        // ============================================================
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        // ============================================================
        // EXPOSE FUNCTIONS
        // ============================================================
        window.logoutUser = logoutUser;
        window.toggleSidebar = toggleSidebar;
        window.changePassword = changePassword;
        window.deleteAccount = deleteAccount;
        window.showToast = showToast;
        window.toggleTheme = toggleTheme;

        console.log('Profile page loaded with no emojis, role disabled, and double delete confirmation!');
    </script>
</body>
</html>