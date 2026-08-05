<?php
session_start();
if (!isset($_SESSION['user_id']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}
$page_title = 'History - Smart Water Guardian';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History - Smart Water Guardian</title>
    
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-auth-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-database-compat.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
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
            --gradient-start: #00d4ff;
            --gradient-end: #7b2ffc;
            --success: #00ff88;
            --danger: #ff6b6b;
            --warning: #ffd700;
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
            --gradient-start: #0066cc;
            --gradient-end: #4a00a0;
            --success: #008844;
            --danger: #cc3333;
            --warning: #cc9900;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-primary);
            min-height: 100vh;
            display: flex;
            transition: background 0.3s ease, color 0.3s ease;
        }
        
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 0;
            pointer-events: none;
            overflow: hidden;
        }
        
        .light-mode .bg-animation .orb { opacity: 0.08; }
        
        .bg-animation .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.25;
            animation: floatOrb 20s ease-in-out infinite;
        }
        
        .bg-animation .orb:nth-child(1) {
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, var(--gradient-start), transparent 70%);
            top: -80px;
            right: -80px;
            animation-delay: 0s;
        }
        
        .bg-animation .orb:nth-child(2) {
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, var(--gradient-end), transparent 70%);
            bottom: -50px;
            left: -50px;
            animation-delay: -7s;
        }
        
        @keyframes floatOrb {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(-50px, -30px) scale(1.1); }
            50% { transform: translate(20px, 40px) scale(0.9); }
            75% { transform: translate(-30px, -20px) scale(1.05); }
        }
        
        .sidebar {
            width: 260px;
            background: var(--bg-sidebar);
            backdrop-filter: blur(20px);
            border-right: 1px solid var(--border-color);
            padding: 24px 0;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            overflow-y: auto;
            z-index: 100;
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1), background 0.3s ease;
        }
        
        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-track { background: transparent; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(0,212,255,0.1); border-radius: 10px; }
        
        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 0 24px 24px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .sidebar-brand .logo-icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: #05080f;
            box-shadow: 0 0 30px rgba(0,212,255,0.3);
            animation: pulseGlow 3s ease-in-out infinite;
        }
        
        @keyframes pulseGlow {
            0%, 100% { box-shadow: 0 0 30px rgba(0,212,255,0.3); }
            50% { box-shadow: 0 0 60px rgba(0,212,255,0.6), 0 0 120px rgba(123,47,252,0.2); }
        }
        
        .sidebar-brand .brand-text {
            font-size: 20px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .sidebar-brand .brand-text span {
            font-weight: 300;
            -webkit-text-fill-color: rgba(255,255,255,0.3);
        }
        
        .light-mode .sidebar-brand .brand-text span {
            -webkit-text-fill-color: rgba(0,0,0,0.2);
        }
        
        .sidebar-nav {
            padding: 16px 12px;
        }
        
        .sidebar-nav .nav-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--text-muted);
            padding: 12px 12px 8px;
            font-weight: 600;
            opacity: 0.5;
        }
        
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.3s ease;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            position: relative;
            opacity: 0.7;
        }
        
        .sidebar-nav a:hover {
            color: var(--text-primary);
            background: rgba(0,212,255,0.08);
            opacity: 1;
        }
        
        .sidebar-nav a.active {
            color: white;
            background: linear-gradient(135deg, rgba(0,212,255,0.15), rgba(123,47,252,0.1));
            box-shadow: 0 0 30px rgba(0,212,255,0.05);
            opacity: 1;
        }
        
        .light-mode .sidebar-nav a.active {
            color: #1a365d;
            background: linear-gradient(135deg, rgba(0,212,255,0.1), rgba(123,47,252,0.05));
        }
        
        .sidebar-nav a.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 20%;
            height: 60%;
            width: 3px;
            background: linear-gradient(180deg, var(--gradient-start), var(--gradient-end));
            border-radius: 0 4px 4px 0;
        }
        
        .sidebar-nav a i { width: 20px; text-align: center; font-size: 16px; }
        
        .sidebar-nav a .nav-badge {
            margin-left: auto;
            background: rgba(0,212,255,0.2);
            color: #00d4ff;
            font-size: 10px;
            padding: 2px 10px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .sidebar-nav .theme-toggle-btn {
            margin-top: 8px;
            border-top: 1px solid var(--border-color);
            padding-top: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: var(--text-secondary);
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
        
        .sidebar-nav .theme-toggle-btn:hover {
            color: var(--text-primary);
            background: rgba(0,212,255,0.08);
            opacity: 1;
        }
        
        .sidebar-nav .theme-toggle-btn i { width: 20px; text-align: center; font-size: 16px; }
        
        .sidebar-nav .logout-link {
            margin-top: 12px;
            border-top: 1px solid var(--border-color);
            padding-top: 12px;
            color: rgba(255,100,100,0.5);
        }
        
        .sidebar-nav .logout-link:hover {
            color: #ff6b6b;
            background: rgba(255,100,100,0.08);
            opacity: 1;
        }
        
        .sidebar-footer {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            padding: 0 24px;
            font-size: 11px;
            color: var(--text-muted);
            text-align: center;
            letter-spacing: 1px;
            opacity: 0.3;
        }
        
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 28px 36px 40px;
            min-height: 100vh;
            position: relative;
            z-index: 1;
        }
        
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
            padding: 16px 24px;
            background: var(--bg-card);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            border: 1px solid var(--border-color);
            flex-wrap: wrap;
            gap: 12px;
        }
        
        .topbar-left { display: flex; align-items: center; gap: 16px; }
        .topbar-left .greeting {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-primary);
        }
        .topbar-left .greeting .highlight {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .topbar-left .sub-text {
            font-size: 14px;
            color: var(--text-muted);
            font-weight: 400;
        }
        
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        .topbar-right .date-display {
            color: var(--text-secondary);
            font-size: 13px;
            padding: 6px 16px;
            background: var(--bg-card);
            border-radius: 50px;
            border: 1px solid var(--border-color);
        }
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--success);
            display: inline-block;
            animation: pulseDot 2s infinite;
            box-shadow: 0 0 20px rgba(0,255,136,0.3);
        }
        @keyframes pulseDot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.8); }
        }
        .status-text {
            font-size: 13px;
            font-weight: 500;
            color: rgba(0,255,136,0.8);
        }
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: var(--text-primary);
            cursor: pointer;
        }
        
        .history-controls {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
            align-items: center;
            background: var(--bg-card);
            backdrop-filter: blur(10px);
            padding: 16px 20px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }
        
        .history-controls select {
            padding: 8px 16px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            background: var(--input-bg);
            color: var(--text-primary);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .history-controls select:focus {
            outline: none;
            border-color: var(--gradient-start);
            box-shadow: 0 0 30px rgba(0,212,255,0.05);
        }
        
        .history-controls select option {
            background: var(--bg-primary);
            color: var(--text-primary);
        }
        
        .history-controls .btn {
            padding: 8px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0,212,255,0.3);
        }
        
        .btn-outline {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
        }
        .btn-outline:hover {
            background: rgba(0,212,255,0.08);
            color: var(--text-primary);
        }
        
        .btn-success {
            background: rgba(0,255,136,0.15);
            color: var(--success);
            border: 1px solid rgba(0,255,136,0.1);
        }
        .btn-success:hover {
            background: rgba(0,255,136,0.25);
            transform: translateY(-2px);
        }
        
        .history-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .history-stat {
            background: var(--bg-card);
            backdrop-filter: blur(10px);
            padding: 16px 20px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        
        .history-stat:hover {
            transform: translateY(-2px);
            border-color: rgba(0,212,255,0.2);
            box-shadow: 0 8px 30px rgba(0,212,255,0.05);
        }
        
        .history-stat .number {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .history-stat .number .trend-up { color: var(--danger); }
        .history-stat .number .trend-down { color: var(--success); }
        
        .history-stat .label {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 2px;
        }
        
        .history-chart-container {
            background: var(--bg-card);
            backdrop-filter: blur(10px);
            padding: 24px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            margin-bottom: 24px;
        }
        
        .history-chart-container h3 {
            color: var(--text-primary);
            margin-bottom: 16px;
            font-size: 16px;
            font-weight: 600;
        }
        
        .history-chart-container h3 i {
            color: var(--gradient-start);
            margin-right: 8px;
        }
        
        .history-table {
            background: var(--bg-card);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            overflow-x: auto;
        }
        
        .history-table h3 {
            color: var(--text-primary);
            margin-bottom: 12px;
            font-size: 16px;
            font-weight: 600;
        }
        
        .history-table h3 i {
            color: var(--gradient-start);
            margin-right: 8px;
        }
        
        .history-table table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        
        .history-table th {
            text-align: left;
            padding: 10px 12px;
            background: rgba(0,212,255,0.03);
            border-bottom: 2px solid var(--border-color);
            font-weight: 600;
            color: var(--text-muted);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .history-table td {
            padding: 10px 12px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-secondary);
        }
        
        .history-table tr:hover td {
            background: rgba(0,212,255,0.02);
        }
        
        .trend-up { color: var(--danger); }
        .trend-down { color: var(--success); }
        
        .no-data-message {
            text-align: center;
            padding: 40px 20px;
        }
        
        .no-data-message .icon {
            font-size: 64px;
            display: block;
            margin-bottom: 16px;
        }
        
        .no-data-message h3 {
            color: var(--text-secondary);
            font-size: 20px;
            margin-bottom: 8px;
        }
        
        .no-data-message p {
            color: var(--text-muted);
            font-size: 16px;
        }
        
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
            color: white;
            font-weight: 500;
            font-size: 14px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.3);
            animation: slideInRight 0.3s ease;
            min-width: 250px;
            backdrop-filter: blur(10px);
        }
        
        .toast-success { background: rgba(0,255,136,0.2); border: 1px solid var(--success); color: var(--success); }
        .toast-error { background: rgba(255,107,107,0.2); border: 1px solid var(--danger); color: var(--danger); }
        .toast-info { background: rgba(0,212,255,0.2); border: 1px solid var(--gradient-start); color: var(--gradient-start); }
        .toast-warning { background: rgba(255,215,0,0.2); border: 1px solid var(--warning); color: var(--warning); }
        
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(50px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            .menu-toggle {
                display: block;
            }
            .history-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .history-stats {
                grid-template-columns: 1fr;
            }
            .history-controls {
                flex-direction: column;
                align-items: stretch;
            }
            .history-controls select {
                width: 100%;
            }
            .topbar {
                flex-direction: column;
                text-align: center;
                padding: 16px;
            }
            .topbar-left { flex-direction: column; }
            .topbar-left .greeting { font-size: 18px; }
            .history-table { padding: 12px; }
            .history-table table { font-size: 12px; }
            .history-table th,
            .history-table td { padding: 6px 8px; }
        }
    </style>
</head>
<body>
    <div class="bg-animation">
        <div class="orb"></div>
        <div class="orb"></div>
    </div>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="logo-icon"><i class="fas fa-water"></i></div>
            <div class="brand-text">Smart<span>Water</span></div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-label">Main Menu</div>
            
            <?php
            $role = $_SESSION['role'] ?? 'consumer';
            $current_page = basename($_SERVER['PHP_SELF']);
            ?>
            
            <?php if ($role === 'system_admin' || $role === 'municipal_admin' || $role === 'admin'): ?>
            <a href="admin.php" class="<?php echo $current_page === 'admin.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> Admin
            </a>
            <a href="alerts.php" class="<?php echo $current_page === 'alerts.php' ? 'active' : ''; ?>">
                <i class="fas fa-bell"></i> Alerts
                <span class="nav-badge" id="alertBadge">0</span>
            </a>
            <a href="reviews.php" class="<?php echo $current_page === 'reviews.php' ? 'active' : ''; ?>">
                <i class="fas fa-star"></i> Reviews
            </a>
            <a href="profile.php" class="<?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i> Profile
            </a>
            <?php else: ?>
            <a href="dashboard.php" class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="history.php" class="<?php echo $current_page === 'history.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i> History
            </a>
            <a href="alerts.php" class="<?php echo $current_page === 'alerts.php' ? 'active' : ''; ?>">
                <i class="fas fa-bell"></i> Alerts
                <span class="nav-badge" id="alertBadge">0</span>
            </a>
            <a href="thresholds.php" class="<?php echo $current_page === 'thresholds.php' ? 'active' : ''; ?>">
                <i class="fas fa-sliders-h"></i> Thresholds
            </a>
            <a href="reviews.php" class="<?php echo $current_page === 'reviews.php' ? 'active' : ''; ?>">
                <i class="fas fa-star"></i> Reviews
            </a>
            <a href="properties.php" class="<?php echo $current_page === 'properties.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Properties
            </a>
            <a href="billing.php" class="<?php echo $current_page === 'billing.php' ? 'active' : ''; ?>">
                <i class="fas fa-credit-card"></i> Billing
            </a>
            <a href="profile.php" class="<?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i> Profile
            </a>
            <?php endif; ?>
            
            <button class="theme-toggle-btn" onclick="toggleTheme()" id="themeToggle">
                <i class="fas fa-moon" id="themeIcon"></i>
                <span id="themeLabel">Dark Mode</span>
            </button>
            
            <a href="#" onclick="logoutUser()" class="logout-link">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
        <div class="sidebar-footer">v2.0.0 - 2026</div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <button class="menu-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <div class="greeting">
                        Usage <span class="highlight">History</span>
                    </div>
                    <div class="sub-text">Track your water consumption over time</div>
                </div>
            </div>
            <div class="topbar-right">
                <span class="date-display">
                    <i class="far fa-calendar-alt"></i> 
                    <?php echo date('l, F j, Y'); ?>
                </span>
                <span class="status-dot"></span>
                <span class="status-text">System Online</span>
            </div>
        </header>

        <div class="history-controls">
            <select id="period-select">
                <option value="7">Last 7 Days</option>
                <option value="14">Last 14 Days</option>
                <option value="30" selected>Last 30 Days</option>
                <option value="60">Last 60 Days</option>
                <option value="90">Last 90 Days</option>
            </select>
            <select id="view-select">
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
            </select>
            <button onclick="exportPDF()" class="btn btn-success">
                <i class="fas fa-file-pdf"></i> Export PDF
            </button>
            <button onclick="exportCSV()" class="btn btn-primary">
                <i class="fas fa-file-csv"></i> Export CSV
            </button>
            <button onclick="refreshData()" class="btn btn-outline">
                <i class="fas fa-sync"></i> Refresh
            </button>
        </div>

        <div class="history-stats">
            <div class="history-stat">
                <div class="number" id="statTotal">0.0 L</div>
                <div class="label">Total Usage</div>
            </div>
            <div class="history-stat">
                <div class="number" id="statAverage">0.0 L</div>
                <div class="label">Daily Average</div>
            </div>
            <div class="history-stat">
                <div class="number" id="statPeak">0.0 <span style="font-size:14px;">L/min</span></div>
                <div class="label">Peak Flow</div>
            </div>
            <div class="history-stat">
                <div class="number" id="statDays">0</div>
                <div class="label">Days Tracked</div>
            </div>
        </div>

        <div class="history-chart-container">
            <h3><i class="fas fa-chart-line"></i> Water Consumption Trend</h3>
            <div style="height:300px;">
                <canvas id="historyChart"></canvas>
            </div>
            <div id="noDataMessage" class="no-data-message">
                <span class="icon">📊</span>
                <h3>No Data Available</h3>
                <p>Waiting for ESP32 to send data...</p>
                <div style="margin-top:10px;display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
                    <span style="color:var(--text-muted);font-size:13px;">
                        <i class="fas fa-circle" style="color:#ffd700;font-size:10px;"></i> Check ESP32 Serial Monitor
                    </span>
                    <span style="color:var(--text-muted);font-size:13px;">
                        <i class="fas fa-circle" style="color:#00d4ff;font-size:10px;"></i> Verify Firebase data
                    </span>
                </div>
            </div>
        </div>

        <div class="history-table">
            <h3><i class="fas fa-table"></i> Detailed History</h3>
            <table id="historyTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Total Volume</th>
                        <th>Avg Flow</th>
                        <th>Peak Flow</th>
                        <th>Readings</th>
                        <th>Trend</th>
                    </tr>
                </thead>
                <tbody id="historyTableBody">
                    <tr>
                        <td colspan="6" style="text-align:center;color:var(--text-muted);padding:30px;">
                            <i class="fas fa-spinner fa-spin"></i> Waiting for data from ESP32...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </main>

    <div class="toast-container" id="toastContainer"></div>

    <script>
        var firebaseConfig = {
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
        var auth = firebase.auth();
        var database = firebase.database();

        var historyChart = null;
        var currentUser = null;
        var currentMeterId = null;
        var allHistoryData = [];
        var isListening = false;

        function getTheme() {
            return localStorage.getItem('swg_theme') || 'dark';
        }

        function setTheme(theme) {
            localStorage.setItem('swg_theme', theme);
            if (theme === 'light') {
                document.body.classList.add('light-mode');
                var icon = document.getElementById('themeIcon');
                var label = document.getElementById('themeLabel');
                if (icon) icon.className = 'fas fa-sun';
                if (label) label.textContent = 'Light Mode';
            } else {
                document.body.classList.remove('light-mode');
                var icon = document.getElementById('themeIcon');
                var label = document.getElementById('themeLabel');
                if (icon) icon.className = 'fas fa-moon';
                if (label) label.textContent = 'Dark Mode';
            }
            if (currentUser) {
                database.ref('users/' + currentUser.uid + '/preferences/theme').set(theme);
            }
        }

        function toggleTheme() {
            var current = getTheme();
            setTheme(current === 'dark' ? 'light' : 'dark');
        }

        function showToast(message, type) {
            type = type || 'success';
            var container = document.getElementById('toastContainer');
            var toast = document.createElement('div');
            toast.className = 'toast toast-' + type;
            toast.textContent = message;
            container.appendChild(toast);
            setTimeout(function() {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.3s ease';
                setTimeout(function() { toast.remove(); }, 300);
            }, 4000);
        }

        auth.onAuthStateChanged(function(user) {
            if (user) {
                currentUser = user;
                console.log('User logged in:', user.uid);
                loadAlertCount();
                findUserMeter(user.uid);
            } else {
                window.location.href = 'login.php';
            }
        });

        function loadAlertCount() {
            if (!currentUser) return;
            var alertsRef = database.ref('alerts/' + currentUser.uid);
            alertsRef.on('value', function(snapshot) {
                var data = snapshot.val();
                var count = data ? Object.keys(data).filter(function(key) { return !data[key].isRead; }).length : 0;
                document.getElementById('alertBadge').textContent = count;
            });
        }

        function findUserMeter(uid) {
            console.log('Finding meter for user:', uid);
            
            var propertiesRef = database.ref('properties/' + uid);
            propertiesRef.once('value', function(snapshot) {
                var properties = snapshot.val();
                console.log('Properties found:', properties);
                
                if (properties) {
                    var firstProp = Object.values(properties)[0];
                    if (firstProp && firstProp.meterId) {
                        currentMeterId = firstProp.meterId;
                        console.log('Found meter ID:', currentMeterId);
                        startRealtimeListening(currentMeterId);
                        return;
                    }
                }
                
                var userRef = database.ref('users/' + uid);
                userRef.once('value', function(snap) {
                    var userData = snap.val();
                    if (userData && userData.meterNumber) {
                        currentMeterId = userData.meterNumber;
                        console.log('Found meter from user profile:', currentMeterId);
                        startRealtimeListening(currentMeterId);
                    } else {
                        console.log('No meter found for user');
                        showNoData('No meter assigned to this account. Please contact support.');
                    }
                });
            });
        }

        function startRealtimeListening(meterId) {
            if (isListening) return;
            isListening = true;
            
            console.log('Starting real-time listening for meter:', meterId);
            showToast('Connected to ESP32 - Waiting for data...', 'info');
            
            var historyRef = database.ref('meters/' + meterId + '/history');
            
            historyRef.on('value', function(snapshot) {
                var data = snapshot.val();
                console.log('Real-time history data received:', data);
                
                if (data) {
                    var historyData = [];
                    var totalVolume = 0;
                    
                    for (var date in data) {
                        if (data[date].total !== undefined) {
                            var dayTotal = data[date].total || 0;
                            totalVolume += dayTotal;
                            historyData.push({
                                date: date,
                                total_volume: dayTotal,
                                avg_flow: data[date].avg_flow || 0,
                                peak_flow: data[date].peak_flow || 0,
                                readings: data[date].readings || 24
                            });
                        }
                    }
                    
                    if (historyData.length > 0) {
                        historyData.sort(function(a, b) {
                            return new Date(a.date) - new Date(b.date);
                        });
                        
                        allHistoryData = historyData;
                        
                        updateStats(historyData);
                        renderChartWithData(historyData);
                        renderTableWithData(historyData);
                        document.getElementById('noDataMessage').style.display = 'none';
                        
                        console.log('Loaded', historyData.length, 'days of data. Total volume:', totalVolume.toFixed(1), 'L');
                        showToast('Loaded ' + historyData.length + ' days of water data', 'success');
                    } else {
                        showNoData('No history data found. ESP32 may not have sent data yet.');
                    }
                } else {
                    showNoData('No data in Firebase yet. Waiting for ESP32...');
                }
            }, function(error) {
                console.error('Firebase listener error:', error);
                showToast('Error reading data: ' + error.message, 'error');
            });
            
            var readingRef = database.ref('meters/' + meterId + '/lastReading');
            readingRef.on('value', function(snapshot) {
                var reading = snapshot.val();
                if (reading) {
                    console.log('Live reading:', reading.flow, 'L/min,', reading.volume, 'L,', reading.pressure, 'kPa');
                    
                    if (reading.volume !== undefined) {
                        document.getElementById('statTotal').textContent = reading.volume.toFixed(1) + ' L';
                    }
                }
            });
        }

        function updateStats(data) {
            var totalVolume = 0;
            var maxPeak = 0;
            
            for (var i = 0; i < data.length; i++) {
                totalVolume += data[i].total_volume;
                if (data[i].peak_flow > maxPeak) maxPeak = data[i].peak_flow;
            }
            
            var avgVolume = data.length > 0 ? totalVolume / data.length : 0;
            
            document.getElementById('statTotal').textContent = totalVolume.toFixed(1) + ' L';
            document.getElementById('statAverage').textContent = avgVolume.toFixed(1) + ' L';
            document.getElementById('statPeak').innerHTML = maxPeak.toFixed(1) + ' <span style="font-size:14px;">L/min</span>';
            document.getElementById('statDays').textContent = data.length;
        }

        function showNoData(message) {
            var noDataDiv = document.getElementById('noDataMessage');
            noDataDiv.style.display = 'block';
            noDataDiv.innerHTML = `
                <span class="icon">📊</span>
                <h3>No Data Available</h3>
                <p>${message || 'Waiting for ESP32 to send data...'}</p>
                <div style="margin-top:10px;display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
                    <span style="color:var(--text-muted);font-size:13px;">
                        <i class="fas fa-circle" style="color:#ffd700;font-size:10px;"></i> Check ESP32 Serial Monitor
                    </span>
                    <span style="color:var(--text-muted);font-size:13px;">
                        <i class="fas fa-circle" style="color:#00d4ff;font-size:10px;"></i> Verify Firebase data
                    </span>
                </div>
            `;
            
            var tbody = document.getElementById('historyTableBody');
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align:center;color:var(--text-muted);padding:30px;">
                        <i class="fas fa-spinner fa-spin"></i> ${message || 'Waiting for ESP32 to send data...'}
                    </td>
                </tr>
            `;
        }

        function renderChartWithData(data) {
            var canvas = document.getElementById('historyChart');
            if (!canvas) return;
            
            var ctx = canvas.getContext('2d');
            
            if (historyChart) {
                historyChart.destroy();
                historyChart = null;
            }

            if (!data || data.length === 0) {
                loadEmptyChart();
                return;
            }

            var labels = [];
            var volumes = [];
            var flows = [];
            
            for (var i = 0; i < data.length; i++) {
                var date = new Date(data[i].date);
                labels.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
                volumes.push(data[i].total_volume);
                flows.push(data[i].avg_flow);
            }

            try {
                var isDark = getTheme() === 'dark';
                var textColor = isDark ? 'rgba(255,255,255,0.7)' : 'rgba(0,0,0,0.7)';
                var gridColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';
                
                historyChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Volume (L)',
                                data: volumes,
                                borderColor: '#00d4ff',
                                backgroundColor: 'rgba(0, 212, 255, 0.1)',
                                fill: true,
                                tension: 0.4,
                                yAxisID: 'y',
                                pointBackgroundColor: '#00d4ff',
                                pointBorderColor: 'rgba(0, 212, 255, 0.3)',
                                pointBorderWidth: 2,
                                pointRadius: 4,
                            },
                            {
                                label: 'Avg Flow (L/min)',
                                data: flows,
                                borderColor: '#7b2ffc',
                                backgroundColor: 'rgba(123, 47, 252, 0.1)',
                                fill: true,
                                tension: 0.4,
                                yAxisID: 'y1',
                                pointBackgroundColor: '#7b2ffc',
                                pointBorderColor: 'rgba(123, 47, 252, 0.3)',
                                pointBorderWidth: 2,
                                pointRadius: 4,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    color: textColor,
                                    font: { size: 12, weight: '500' }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': ' + context.parsed.y.toFixed(1);
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                ticks: { color: textColor },
                                grid: { color: gridColor }
                            },
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                title: {
                                    display: true,
                                    text: 'Volume (L)',
                                    color: textColor
                                },
                                beginAtZero: true,
                                ticks: { color: textColor },
                                grid: { color: gridColor }
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                title: {
                                    display: true,
                                    text: 'Flow (L/min)',
                                    color: textColor
                                },
                                grid: { drawOnChartArea: false },
                                beginAtZero: true,
                                ticks: { color: textColor }
                            }
                        }
                    }
                });
                console.log('Chart rendered with real data!');
            } catch (error) {
                console.error('Error creating chart:', error);
                loadEmptyChart();
            }
        }

        function loadEmptyChart() {
            var canvas = document.getElementById('historyChart');
            if (!canvas) return;
            
            var ctx = canvas.getContext('2d');
            if (historyChart) {
                historyChart.destroy();
                historyChart = null;
            }
            
            try {
                var isDark = getTheme() === 'dark';
                var textColor = isDark ? 'rgba(255,255,255,0.2)' : 'rgba(0,0,0,0.2)';
                
                historyChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['No Data Available'],
                        datasets: [{
                            label: 'Volume (L)',
                            data: [0],
                            borderColor: '#00d4ff',
                            backgroundColor: 'rgba(0, 212, 255, 0.05)',
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#00d4ff',
                            pointRadius: 0,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { enabled: false }
                        },
                        scales: {
                            x: { ticks: { color: textColor } },
                            y: { beginAtZero: true, ticks: { color: textColor } }
                        }
                    }
                });
            } catch (error) {
                console.error('Error creating empty chart:', error);
            }
        }

        function renderTableWithData(data) {
            var tbody = document.getElementById('historyTableBody');
            if (!data || data.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" style="text-align:center;color:var(--text-muted);padding:30px;">
                            <i class="fas fa-spinner fa-spin"></i> Waiting for data from ESP32...
                        </td>
                    </tr>
                `;
                return;
            }

            var html = '';
            var reversed = data.slice().reverse();
            for (var i = 0; i < reversed.length; i++) {
                var row = reversed[i];
                var trend = i > 0 ? (row.total_volume - reversed[i - 1].total_volume) : 0;
                var trendClass = trend > 0 ? 'trend-up' : (trend < 0 ? 'trend-down' : '');
                var trendIcon = trend > 0 ? '+' : '';
                var dateObj = new Date(row.date);
                
                html += `
                    <tr>
                        <td>${dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
                        <td><strong>${row.total_volume.toFixed(1)} L</strong></td>
                        <td>${row.avg_flow.toFixed(1)} L/min</td>
                        <td>${row.peak_flow.toFixed(1)} L/min</td>
                        <td>${row.readings || 24}</td>
                        <td class="${trendClass}">
                            ${trendIcon}${trend != 0 ? Number(trend).toFixed(1) : '0'}L
                        </td>
                    </tr>
                `;
            }
            tbody.innerHTML = html;
        }

        function exportPDF() {
            if (!allHistoryData || allHistoryData.length === 0) {
                showToast('No data to export!', 'error');
                return;
            }
            
            showToast('Generating PDF... Please wait.', 'info');
            
            var { jsPDF } = window.jspdf;
            var doc = new jsPDF('p', 'mm', 'a4');
            
            var headers = ['Date', 'Total Volume (L)', 'Avg Flow (L/min)', 'Peak Flow (L/min)', 'Readings', 'Trend'];
            var data = [];
            
            var reversed = allHistoryData.slice().reverse();
            for (var i = 0; i < reversed.length; i++) {
                var row = reversed[i];
                var trend = i > 0 ? (row.total_volume - reversed[i - 1].total_volume) : 0;
                var trendIcon = trend > 0 ? '+' : '';
                var dateObj = new Date(row.date);
                
                data.push([
                    dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }),
                    row.total_volume.toFixed(1) + ' L',
                    row.avg_flow.toFixed(1) + ' L/min',
                    row.peak_flow.toFixed(1) + ' L/min',
                    row.readings || 24,
                    trendIcon + (trend != 0 ? Number(trend).toFixed(1) : '0') + 'L'
                ]);
            }
            
            doc.setFillColor(10, 14, 26);
            doc.rect(0, 0, 210, 40, 'F');
            
            doc.setFontSize(24);
            doc.setTextColor(0, 212, 255);
            doc.text('Smart Water Guardian', 20, 20);
            
            doc.setFontSize(10);
            doc.setTextColor(255, 255, 255, 0.5);
            doc.text('Usage History Report', 20, 30);
            doc.text('Generated: ' + new Date().toLocaleString(), 20, 36);
            
            doc.autoTable({
                startY: 45,
                head: [headers],
                body: data,
                theme: 'dark',
                headStyles: {
                    fillColor: [0, 212, 255],
                    textColor: [10, 14, 26],
                    fontStyle: 'bold',
                    fontSize: 9,
                },
                styles: {
                    textColor: [200, 200, 200],
                    fontSize: 8,
                    cellPadding: 3,
                },
                alternateRowStyles: {
                    fillColor: [20, 25, 45],
                },
                columnStyles: {
                    0: { cellWidth: 25 },
                    1: { cellWidth: 30 },
                    2: { cellWidth: 30 },
                    3: { cellWidth: 30 },
                    4: { cellWidth: 20 },
                    5: { cellWidth: 25 },
                }
            });
            
            var finalY = doc.lastAutoTable.finalY + 10;
            doc.setFontSize(8);
            doc.setTextColor(100, 100, 100);
            doc.text('© 2026 Smart Water Guardian. All rights reserved.', 20, finalY);
            
            doc.save('water-usage-history.pdf');
            showToast('PDF downloaded successfully!', 'success');
        }

        function exportCSV() {
            if (!allHistoryData || allHistoryData.length === 0) {
                showToast('No data to export!', 'error');
                return;
            }
            
            showToast('Generating CSV...', 'info');
            
            var headers = ['Date', 'Total Volume (L)', 'Avg Flow (L/min)', 'Peak Flow (L/min)', 'Readings', 'Trend'];
            var csv = headers.join(',') + '\n';
            
            var reversed = allHistoryData.slice().reverse();
            for (var i = 0; i < reversed.length; i++) {
                var row = reversed[i];
                var trend = i > 0 ? (row.total_volume - reversed[i - 1].total_volume) : 0;
                var trendIcon = trend > 0 ? '+' : '';
                var dateObj = new Date(row.date);
                
                csv += [
                    dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }),
                    row.total_volume.toFixed(1),
                    row.avg_flow.toFixed(1),
                    row.peak_flow.toFixed(1),
                    row.readings || 24,
                    trendIcon + (trend != 0 ? Number(trend).toFixed(1) : '0')
                ].join(',') + '\n';
            }
            
            var blob = new Blob([csv], { type: 'text/csv' });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'water-usage-history.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            
            showToast('CSV downloaded successfully!', 'success');
        }

        function refreshData() {
            showToast('Refreshing data...', 'info');
            if (currentMeterId) {
                startRealtimeListening(currentMeterId);
            } else if (currentUser) {
                findUserMeter(currentUser.uid);
            }
        }

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        function logoutUser() {
            if (confirm('Are you sure you want to logout?')) {
                auth.signOut().then(function() {
                    fetch('../api/auth.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'logout' })
                    }).then(function() {
                        window.location.href = 'login.php';
                    });
                });
            }
        }

        window.logoutUser = logoutUser;
        window.toggleSidebar = toggleSidebar;
        window.exportPDF = exportPDF;
        window.exportCSV = exportCSV;
        window.refreshData = refreshData;
        window.showToast = showToast;
        window.toggleTheme = toggleTheme;

        console.log('History page loaded - waiting for ESP32 data...');
        console.log('Firebase configured:', firebaseConfig.databaseURL);
    </script>
</body>
</html>