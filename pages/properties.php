<?php
session_start();
if (!isset($_SESSION['user_id']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}

// Get user role for sidebar
$user_role = $_SESSION['role'] ?? 'consumer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Properties - Smart Water Guardian 🏠</title>
    
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-auth-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-database-compat.js"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #0a0e1a;
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
            transition: background 0.3s ease;
        }
        
        /* ========== LIGHT MODE ========== */
        body.light-mode {
            background: #f0f4f8;
        }
        
        body.light-mode .sidebar {
            background: rgba(255, 255, 255, 0.95);
            border-right: 1px solid #e2e8f0;
        }
        
        body.light-mode .sidebar-brand .brand-text {
            -webkit-text-fill-color: #1a365d;
            background: none;
            color: #1a365d;
        }
        
        body.light-mode .sidebar-nav a {
            color: rgba(0, 0, 0, 0.5);
        }
        
        body.light-mode .sidebar-nav a:hover {
            color: #1a365d;
            background: rgba(0, 0, 0, 0.05);
        }
        
        body.light-mode .sidebar-nav a.active {
            color: #1a365d;
            background: linear-gradient(135deg, rgba(0, 212, 255, 0.1), rgba(123, 47, 252, 0.05));
        }
        
        body.light-mode .sidebar-footer {
            color: rgba(0, 0, 0, 0.2);
        }
        
        body.light-mode .sidebar-nav .nav-label {
            color: rgba(0, 0, 0, 0.2);
        }
        
        body.light-mode .sidebar-nav .logout-link {
            color: rgba(255, 100, 100, 0.3);
        }
        
        body.light-mode .topbar {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid #e2e8f0;
        }
        
        body.light-mode .topbar-left h2 {
            color: #1a365d;
        }
        
        body.light-mode .topbar-left p {
            color: rgba(0, 0, 0, 0.4);
        }
        
        body.light-mode .topbar-right .date-display {
            color: rgba(0, 0, 0, 0.4);
            background: rgba(0, 0, 0, 0.03);
            border: 1px solid #e2e8f0;
        }
        
        body.light-mode .menu-toggle {
            color: #1a365d;
        }
        
        body.light-mode .property-card {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid #e2e8f0;
        }
        
        body.light-mode .property-card:hover {
            border-color: rgba(0, 212, 255, 0.2);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.05);
        }
        
        body.light-mode .property-card .name {
            color: #1a365d;
        }
        
        body.light-mode .property-card .address {
            color: rgba(0, 0, 0, 0.4);
        }
        
        body.light-mode .property-card .meter {
            color: #0066cc;
            background: rgba(0, 100, 200, 0.05);
        }
        
        body.light-mode .property-card .actions {
            border-top: 1px solid #e2e8f0;
        }
        
        body.light-mode .btn-edit {
            background: rgba(0, 100, 200, 0.08);
            color: #0066cc;
        }
        
        body.light-mode .btn-edit:hover {
            background: rgba(0, 100, 200, 0.15);
        }
        
        body.light-mode .btn-delete {
            background: rgba(200, 50, 50, 0.08);
            color: #cc3333;
        }
        
        body.light-mode .btn-delete:hover {
            background: rgba(200, 50, 50, 0.15);
        }
        
        body.light-mode .btn-primary-prop {
            background: rgba(0, 200, 100, 0.08);
            color: #008844;
        }
        
        body.light-mode .btn-primary-prop:hover {
            background: rgba(0, 200, 100, 0.15);
        }
        
        body.light-mode .primary-badge {
            color: #0066cc;
            background: rgba(0, 100, 200, 0.08);
        }
        
        body.light-mode .status-active {
            background: rgba(0, 200, 100, 0.08);
            color: #008844;
        }
        
        body.light-mode .status-inactive {
            background: rgba(200, 50, 50, 0.08);
            color: #cc3333;
        }
        
        body.light-mode .modal-content {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid #e2e8f0;
        }
        
        body.light-mode .modal-content h2 {
            color: #1a365d;
        }
        
        body.light-mode .modal-content .form-group label {
            color: rgba(0, 0, 0, 0.4);
        }
        
        body.light-mode .modal-content .form-group input {
            background: rgba(255, 255, 255, 0.5);
            color: #1a365d;
            border: 1px solid #e2e8f0;
        }
        
        body.light-mode .modal-content .form-group input:focus {
            border-color: rgba(0, 100, 200, 0.3);
        }
        
        body.light-mode .btn-submit {
            color: white;
        }
        
        body.light-mode .btn-cancel {
            color: rgba(0, 0, 0, 0.3);
            border: 1px solid #e2e8f0;
        }
        
        body.light-mode .btn-cancel:hover {
            background: rgba(0, 0, 0, 0.05);
        }
        
        body.light-mode .btn-add {
            color: white;
        }
        
        body.light-mode .bg-animation .orb {
            opacity: 0.08;
        }
        
        body.light-mode .theme-toggle-btn {
            color: rgba(0, 0, 0, 0.4);
            border-top: 1px solid #e2e8f0;
        }
        
        body.light-mode .theme-toggle-btn:hover {
            color: #1a365d;
            background: rgba(0, 0, 0, 0.05);
        }
        
        /* ========== THEME TOGGLE BUTTON ========== */
        .theme-toggle-btn {
            margin-top: 8px;
            border-top: 1px solid rgba(255,255,255,0.05);
            padding-top: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: rgba(255, 255, 255, 0.5);
            border-radius: 12px;
            transition: all 0.3s ease;
            background: none;
            border-left: none;
            border-right: none;
            border-bottom: none;
            width: 100%;
            font-family: inherit;
            font-size: 14px;
            font-weight: 500;
            opacity: 0.7;
        }
        
        .theme-toggle-btn:hover {
            color: white;
            background: rgba(0, 212, 255, 0.08);
            opacity: 1;
        }
        
        body.light-mode .theme-toggle-btn {
            color: rgba(0, 0, 0, 0.4);
            border-top: 1px solid #e2e8f0;
        }
        
        body.light-mode .theme-toggle-btn:hover {
            color: #1a365d;
            background: rgba(0, 0, 0, 0.05);
        }
        
        .theme-toggle-btn i {
            width: 20px;
            text-align: center;
            font-size: 16px;
        }
        
        /* Animated Background */
        .bg-animation {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            pointer-events: none;
            overflow: hidden;
        }
        .bg-animation .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(120px);
            opacity: 0.12;
            animation: floatOrb 20s ease-in-out infinite;
        }
        .bg-animation .orb:nth-child(1) {
            width: 500px; height: 500px;
            background: radial-gradient(circle, #7fc9ff, transparent 70%);
            top: -200px; right: -100px;
        }
        .bg-animation .orb:nth-child(2) {
            width: 400px; height: 400px;
            background: radial-gradient(circle, #7b2ffc, transparent 70%);
            bottom: -200px; left: -100px;
            animation-delay: -7s;
        }
        @keyframes floatOrb {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(60px, -40px) scale(1.1); }
            50% { transform: translate(-30px, 50px) scale(0.9); }
            75% { transform: translate(40px, -30px) scale(1.05); }
        }
        
        /* Sidebar */
        .sidebar {
            width: 260px;
            background: rgba(4,8,18,0.95);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(127,201,255,0.04);
            padding: 24px 0;
            position: fixed;
            top: 0; bottom: 0; left: 0;
            overflow-y: auto;
            z-index: 100;
            transition: transform 0.4s cubic-bezier(0.4,0,0.2,1), background 0.3s ease;
        }
        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(127,201,255,0.1); border-radius: 10px; }
        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 0 24px 24px;
            border-bottom: 1px solid rgba(127,201,255,0.04);
        }
        .sidebar-brand .logo-icon {
            width: 44px; height: 44px;
            border-radius: 14px;
            background: linear-gradient(135deg, #7fc9ff, #7b2ffc);
            display: flex;
            align-items: center; justify-content: center;
            font-size: 22px; color: #05080f;
            box-shadow: 0 0 30px rgba(127,201,255,0.05);
            animation: pulseGlow 3s ease-in-out infinite;
        }
        @keyframes pulseGlow {
            0%, 100% { box-shadow: 0 0 30px rgba(127,201,255,0.05); }
            50% { box-shadow: 0 0 60px rgba(127,201,255,0.08); }
        }
        .sidebar-brand .brand-text { font-size: 20px; font-weight: 800; color: #7fc9ff; }
        .sidebar-nav { padding: 16px 12px; }
        .sidebar-nav .nav-label {
            font-size: 10px; text-transform: uppercase; letter-spacing: 2px;
            color: rgba(127,201,255,0.15); padding: 12px 12px 8px; font-weight: 600;
        }
        .sidebar-nav a {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px; color: rgba(127,201,255,0.35);
            text-decoration: none; transition: all 0.3s ease;
            border-radius: 12px; font-size: 14px; font-weight: 500;
            position: relative;
        }
        .sidebar-nav a:hover { color: #7fc9ff; background: rgba(127,201,255,0.04); }
        .sidebar-nav a.active {
            color: #7fc9ff; background: rgba(127,201,255,0.06);
            box-shadow: 0 0 30px rgba(127,201,255,0.02);
        }
        .sidebar-nav a.active::before {
            content: ''; position: absolute; left: 0; top: 20%;
            height: 60%; width: 3px;
            background: linear-gradient(180deg, #7fc9ff, #7b2ffc);
            border-radius: 0 4px 4px 0;
        }
        .sidebar-nav a i { width: 20px; text-align: center; font-size: 16px; }
        .sidebar-nav a .nav-badge {
            margin-left: auto;
            background: rgba(0, 212, 255, 0.2);
            color: #00d4ff;
            font-size: 10px;
            padding: 2px 10px;
            border-radius: 20px;
            font-weight: 600;
        }
        .sidebar-nav .logout-link {
            margin-top: 12px; border-top: 1px solid rgba(127,201,255,0.04);
            padding-top: 12px; color: rgba(255,100,100,0.3);
        }
        .sidebar-nav .logout-link:hover { color: #ff6b6b; background: rgba(255,100,100,0.05); }
        .sidebar-footer {
            position: absolute; bottom: 20px; left: 0; right: 0;
            padding: 0 24px; font-size: 11px;
            color: rgba(127,201,255,0.08); text-align: center; letter-spacing: 1px;
        }
        
        /* Main Content */
        .main-content {
            flex: 1; margin-left: 260px;
            padding: 28px 36px 40px;
            min-height: 100vh; position: relative; z-index: 1;
        }
        .topbar {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 28px; padding: 16px 24px;
            background: rgba(255,255,255,0.02); backdrop-filter: blur(10px);
            border-radius: 16px; border: 1px solid rgba(127,201,255,0.04);
            flex-wrap: wrap; gap: 12px;
            transition: background 0.3s ease;
        }
        .topbar-left h2 { font-size: 24px; font-weight: 700; color: #7fc9ff; }
        .topbar-left p { color: rgba(127,201,255,0.25); font-size: 14px; }
        .topbar-right .date-display {
            color: rgba(127,201,255,0.25); font-size: 13px;
            padding: 6px 16px; background: rgba(127,201,255,0.03);
            border-radius: 50px; border: 1px solid rgba(127,201,255,0.04);
        }
        .menu-toggle {
            display: none; background: none; border: none;
            font-size: 24px; color: #7fc9ff; cursor: pointer;
        }
        
        .btn-add {
            padding: 10px 24px;
            background: linear-gradient(135deg, #7fc9ff, #7b2ffc);
            color: #05080f; border: none; border-radius: 12px;
            font-weight: 600; cursor: pointer; transition: all 0.3s ease;
        }
        .btn-add:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(127,201,255,0.15); }
        
        .properties-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 4px;
        }
        .property-card {
            background: rgba(255,255,255,0.02);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 24px;
            border: 1px solid rgba(127,201,255,0.04);
            transition: all 0.3s ease;
        }
        .property-card:hover {
            border-color: rgba(127,201,255,0.1);
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(127,201,255,0.02);
        }
        .property-card .header {
            display: flex; justify-content: space-between; align-items: flex-start;
            margin-bottom: 8px;
        }
        .property-card .name { color: #7fc9ff; font-size: 18px; font-weight: 600; }
        .property-card .address { color: rgba(127,201,255,0.3); font-size: 14px; }
        .property-card .meter {
            color: #b8e6ff; font-family: monospace; font-size: 13px;
            padding: 4px 12px; background: rgba(127,201,255,0.04);
            border-radius: 8px; display: inline-block; margin-top: 8px;
        }
        .property-card .status {
            padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 500;
        }
        .status-active { background: rgba(0,255,136,0.08); color: #00ff88; }
        .status-inactive { background: rgba(255,107,107,0.08); color: #ff6b6b; }
        .property-card .actions {
            margin-top: 14px; padding-top: 14px;
            border-top: 1px solid rgba(127,201,255,0.04);
            display: flex; gap: 6px; flex-wrap: wrap;
        }
        .property-card .actions button {
            padding: 6px 14px; border: none; border-radius: 8px;
            font-size: 12px; font-weight: 500; cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-edit { background: rgba(127,201,255,0.08); color: #7fc9ff; }
        .btn-edit:hover { background: rgba(127,201,255,0.15); }
        .btn-delete { background: rgba(255,107,107,0.08); color: #ff6b6b; }
        .btn-delete:hover { background: rgba(255,107,107,0.15); }
        .btn-primary-prop { background: rgba(0,255,136,0.08); color: #00ff88; }
        .btn-primary-prop:hover { background: rgba(0,255,136,0.15); }
        .primary-badge {
            font-size: 10px; color: #7fc9ff;
            background: rgba(127,201,255,0.08);
            padding: 2px 10px; border-radius: 12px;
            display: inline-block; margin-top: 4px;
        }
        
        /* Modal */
        .modal {
            display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.8); backdrop-filter: blur(20px);
            z-index: 1000; align-items: center; justify-content: center; padding: 20px;
        }
        .modal.show { display: flex; animation: modalFadeIn 0.3s ease; }
        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .modal-content {
            background: rgba(4,8,18,0.98);
            border-radius: 16px; padding: 32px;
            max-width: 500px; width: 100%;
            border: 1px solid rgba(127,201,255,0.06);
            box-shadow: 0 0 60px rgba(127,201,255,0.02);
            transition: background 0.3s ease;
        }
        .modal-content .close {
            float: right; font-size: 28px; cursor: pointer;
            color: rgba(127,201,255,0.2); background: none; border: none;
            transition: color 0.3s;
        }
        .modal-content .close:hover { color: #7fc9ff; transform: rotate(90deg); }
        .modal-content h2 { color: #7fc9ff; font-size: 22px; margin-bottom: 16px; }
        .modal-content .form-group { margin-bottom: 16px; }
        .modal-content .form-group label {
            display: block; color: rgba(127,201,255,0.4);
            font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .modal-content .form-group input {
            width: 100%; padding: 12px 16px;
            border: 1px solid rgba(127,201,255,0.06);
            border-radius: 12px; background: rgba(127,201,255,0.02);
            color: #b8e6ff; font-size: 14px;
        }
        .modal-content .form-group input:focus {
            outline: none; border-color: rgba(127,201,255,0.15);
        }
        .btn-submit {
            padding: 12px 30px; width: 100%;
            background: linear-gradient(135deg, #7fc9ff, #7b2ffc);
            color: #05080f; border: none; border-radius: 12px;
            font-weight: 600; cursor: pointer; transition: all 0.3s ease;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(127,201,255,0.15); }
        .btn-cancel {
            padding: 12px 30px; width: 100%; margin-top: 8px;
            background: rgba(127,201,255,0.04); color: rgba(127,201,255,0.3);
            border: 1px solid rgba(127,201,255,0.06); border-radius: 12px;
            font-weight: 600; cursor: pointer; transition: all 0.3s ease;
        }
        .btn-cancel:hover { background: rgba(127,201,255,0.08); }
        
        /* Toast */
        .toast-container {
            position: fixed; bottom: 20px; right: 20px;
            z-index: 9999; display: flex; flex-direction: column; gap: 10px;
        }
        .toast {
            padding: 12px 24px; border-radius: 10px; color: white;
            font-weight: 500; font-size: 14px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.3);
            animation: slideInRight 0.3s ease;
            min-width: 250px; backdrop-filter: blur(10px);
        }
        .toast-success { background: rgba(0,255,136,0.1); border: 1px solid #00ff88; color: #00ff88; }
        .toast-error { background: rgba(255,107,107,0.1); border: 1px solid #ff6b6b; color: #ff6b6b; }
        .toast-info { background: rgba(127,201,255,0.08); border: 1px solid #7fc9ff; color: #7fc9ff; }
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(50px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 20px; }
            .menu-toggle { display: block; }
        }
        @media (max-width: 768px) {
            .topbar { flex-direction: column; text-align: center; padding: 16px; }
            .topbar-left h2 { font-size: 22px; }
            .properties-grid { grid-template-columns: 1fr; }
            .modal-content { padding: 20px; margin: 10px; }
        }
        @media (max-width: 480px) {
            .property-card { padding: 16px; }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="bg-animation">
        <div class="orb"></div>
        <div class="orb"></div>
    </div>

    <!-- ========== SIDEBAR ========== -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="logo-icon"><i class="fas fa-water"></i></div>
            <div class="brand-text">Smart<span style="color:rgba(127,201,255,0.2);">Water</span></div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-label">Main Menu</div>
            <a href="dashboard.php">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="history.php">
                <i class="fas fa-chart-line"></i> History 📊
            </a>
            <a href="alerts.php">
                <i class="fas fa-bell"></i> Alerts 🔔
                <span class="nav-badge" id="alertBadge">0</span>
            </a>
            <a href="thresholds.php">
                <i class="fas fa-sliders-h"></i> Thresholds ⚙️
            </a>
            <a href="reviews.php">
                <i class="fas fa-star"></i> Reviews ⭐
            </a>
            <a href="properties.php" class="active">
                <i class="fas fa-home"></i> Properties 🏠
            </a>
            <a href="billing.php">
                <i class="fas fa-credit-card"></i> Billing 💰
            </a>
            <?php if ($user_role === 'system_admin' || $user_role === 'municipal_admin' || $user_role === 'admin'): ?>
            <a href="admin.php">
                <i class="fas fa-cog"></i> Admin 🛠️
            </a>
            <?php endif; ?>
            <a href="profile.php">
                <i class="fas fa-user"></i> Profile 👤
            </a>
            
            <!-- Theme Toggle -->
            <button class="theme-toggle-btn" onclick="toggleTheme()" id="themeToggle">
                <i class="fas fa-moon" id="themeIcon"></i>
                <span id="themeLabel">Dark Mode</span>
            </button>
            
            <a href="#" onclick="logoutUser()" class="logout-link">
                <i class="fas fa-sign-out-alt"></i> Logout 🚪
            </a>
        </nav>
        <div class="sidebar-footer">v2.0.0 • ✦ 2026</div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <button class="menu-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h2>🏠 My Properties</h2>
                <p>Manage all your properties in one place</p>
            </div>
            <div class="topbar-right">
                <span class="date-display">
                    <i class="far fa-calendar-alt"></i> 
                    <?php echo date('l, F j, Y'); ?>
                </span>
                <button class="btn-add" onclick="openAddPropertyModal()">
                    <i class="fas fa-plus"></i> Add Property
                </button>
            </div>
        </header>

        <div class="properties-grid" id="propertiesContainer">
            <div style="text-align:center;padding:60px 20px;color:rgba(127,201,255,0.15);grid-column:1/-1;">
                <i class="fas fa-spinner fa-spin" style="font-size:32px;"></i>
                <p style="margin-top:16px;">Loading properties...</p>
            </div>
        </div>
    </main>

    <!-- Add Property Modal -->
    <div id="addPropertyModal" class="modal">
        <div class="modal-content">
            <button class="close" onclick="closeModal()">&times;</button>
            <h2><i class="fas fa-home"></i> Add New Property</h2>
            <form id="addPropertyForm">
                <div class="form-group">
                    <label>🏠 Property Name</label>
                    <input type="text" id="propName" placeholder="e.g., My Home, Office, etc." required>
                </div>
                <div class="form-group">
                    <label>📍 Address</label>
                    <input type="text" id="propAddress" placeholder="123 Main Street, City" required>
                </div>
                <div class="form-group">
                    <label>📟 Meter Number</label>
                    <input type="text" id="propMeter" placeholder="MTR-2026-0001" required>
                </div>
                <button type="submit" class="btn-submit">✅ Add Property</button>
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <script>
        const firebaseConfig = {
            apiKey: "AIzaSyCatcC7yo-a7E7dLAfAWh0iv1BCSoYxUP8",
            authDomain: "smartwaterguardian.firebaseapp.com",
            databaseURL: "https://smartwaterguardian-default-rtdb.firebaseio.com",
            projectId: "smartwaterguardian",
            storageBucket: "smartwaterguardian.firebasestorage.app",
            messagingSenderId: "12612851503",
            appId: "1:12612851503:web:ee8e80d5a46ed28e95b3da"
        };
        firebase.initializeApp(firebaseConfig);
        const auth = firebase.auth();
        const database = firebase.database();

        let currentUser = null;
        let allProperties = [];
        let propertiesOrder = [];

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

        // Load theme on page load
        document.addEventListener('DOMContentLoaded', function() {
            const theme = getTheme();
            setTheme(theme);
        });

        // ============================================================
        // AUTH CHECK
        // ============================================================
        auth.onAuthStateChanged(function(user) {
            if (user) {
                currentUser = user;
                loadProperties();
                loadAlertBadge();
            } else {
                window.location.href = 'login.php';
            }
        });

        // ============================================================
        // LOAD ALERT BADGE
        // ============================================================
        function loadAlertBadge() {
            if (!currentUser) return;
            const alertsRef = database.ref('alerts/' + currentUser.uid);
            alertsRef.on('value', function(snapshot) {
                const data = snapshot.val();
                const count = data ? Object.keys(data).filter(key => !data[key].isRead).length : 0;
                document.getElementById('alertBadge').textContent = count;
            });
        }

        // ============================================================
        // TOAST NOTIFICATIONS
        // ============================================================
        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.textContent = message;
            container.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        // ============================================================
        // LOAD PROPERTIES
        // ============================================================
        function loadProperties() {
            const propsRef = database.ref('properties/' + currentUser.uid);
            propsRef.on('value', function(snapshot) {
                const data = snapshot.val();
                allProperties = [];
                if (data) {
                    for (let id in data) {
                        allProperties.push({
                            id: id,
                            ...data[id]
                        });
                    }
                }
                renderProperties(allProperties);
            });
        }

        // ============================================================
        // RENDER PROPERTIES
        // ============================================================
        function renderProperties(properties) {
            const container = document.getElementById('propertiesContainer');
            
            if (!properties || properties.length === 0) {
                container.innerHTML = `
                    <div style="text-align:center;padding:60px 20px;background:rgba(255,255,255,0.02);border-radius:16px;border:1px solid rgba(127,201,255,0.04);grid-column:1/-1;">
                        <span style="font-size:64px;display:block;margin-bottom:16px;">🏠</span>
                        <h3 style="color:rgba(255,255,255,0.5);font-size:20px;">No Properties Yet</h3>
                        <p style="color:rgba(127,201,255,0.2);margin-top:8px;">Add your first property to start monitoring water usage</p>
                        <button class="btn-add" style="margin-top:16px;" onclick="openAddPropertyModal()">
                            <i class="fas fa-plus"></i> Add Your First Property
                        </button>
                    </div>
                `;
                return;
            }

            let html = '';
            properties.forEach((prop, index) => {
                const isPrimary = index === 0;
                const statusClass = prop.is_active !== false ? 'status-active' : 'status-inactive';
                const statusText = prop.is_active !== false ? '🟢 Active' : '🔴 Inactive';
                
                html += `
                    <div class="property-card">
                        <div class="header">
                            <div class="name">${prop.propertyName || 'Unnamed'}</div>
                            <span class="status ${statusClass}">${statusText}</span>
                        </div>
                        <div class="address">📍 ${prop.address || 'No address'}</div>
                        <div class="meter">📟 ${prop.meterId || 'No meter'}</div>
                        ${isPrimary ? '<div class="primary-badge">⭐ Primary Property</div>' : ''}
                        <div class="actions">
                            ${!isPrimary ? `<button class="btn-primary-prop" onclick="makePrimary('${prop.id}')">⭐ Make Primary</button>` : ''}
                            <button class="btn-edit" onclick="editProperty('${prop.id}')">✏️ Edit</button>
                            <button class="btn-delete" onclick="deleteProperty('${prop.id}')">🗑️ Delete</button>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
        }

        // ============================================================
        // ADD PROPERTY
        // ============================================================
        function openAddPropertyModal() {
            document.getElementById('addPropertyModal').classList.add('show');
            document.getElementById('addPropertyForm').reset();
        }

        function closeModal() {
            document.getElementById('addPropertyModal').classList.remove('show');
        }

        document.getElementById('addPropertyForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const name = document.getElementById('propName').value.trim();
            const address = document.getElementById('propAddress').value.trim();
            const meter = document.getElementById('propMeter').value.trim().toUpperCase();

            if (!name || !address || !meter) {
                showToast('⚠️ Please fill in all fields', 'warning');
                return;
            }

            const propRef = database.ref('properties/' + currentUser.uid).push();
            propRef.set({
                propertyName: name,
                address: address,
                meterId: meter,
                is_active: true,
                createdAt: new Date().toISOString()
            }).then(() => {
                showToast('✅ Property added successfully! 🎉', 'success');
                closeModal();
            }).catch((error) => {
                showToast('❌ ' + error.message, 'error');
            });
        });

        // ============================================================
        // MAKE PRIMARY
        // ============================================================
        function makePrimary(propId) {
            const propIndex = allProperties.findIndex(p => p.id === propId);
            if (propIndex > 0) {
                const [prop] = allProperties.splice(propIndex, 1);
                allProperties.unshift(prop);
                renderProperties(allProperties);
                showToast('⭐ Primary property updated!', 'success');
            }
        }

        // ============================================================
        // DELETE PROPERTY
        // ============================================================
        function deleteProperty(propId) {
            if (!confirm('🗑️ Delete this property?')) return;
            database.ref('properties/' + currentUser.uid + '/' + propId).remove()
                .then(() => showToast('🗑️ Property deleted!', 'success'))
                .catch((error) => showToast('❌ ' + error.message, 'error'));
        }

        // ============================================================
        // EDIT PROPERTY
        // ============================================================
        function editProperty(propId) {
            const prop = allProperties.find(p => p.id === propId);
            if (!prop) return;
            const newName = prompt('Enter new property name:', prop.propertyName);
            if (newName && newName.trim()) {
                database.ref('properties/' + currentUser.uid + '/' + propId + '/propertyName').set(newName.trim())
                    .then(() => showToast('✅ Property updated!', 'success'));
            }
        }

        // ============================================================
        // SIDEBAR TOGGLE
        // ============================================================
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        // ============================================================
        // LOGOUT
        // ============================================================
        function logoutUser() {
            if (confirm('Are you sure you want to logout? 👋')) {
                auth.signOut().then(() => {
                    window.location.href = 'login.php';
                });
            }
        }

        // Close modal on outside click
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                closeModal();
            }
        }

        // Expose functions
        window.openAddPropertyModal = openAddPropertyModal;
        window.closeModal = closeModal;
        window.toggleSidebar = toggleSidebar;
        window.logoutUser = logoutUser;
        window.makePrimary = makePrimary;
        window.deleteProperty = deleteProperty;
        window.editProperty = editProperty;
        window.showToast = showToast;
        window.toggleTheme = toggleTheme;

        console.log('🏠 Properties page loaded with dark/light mode!');
    </script>
</body>
</html>