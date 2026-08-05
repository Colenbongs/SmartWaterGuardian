<?php
/**
 * Smart Water Guardian - Dashboard Page
 * Displays: Flow Rate, Total Volume, Pressure
 * No battery display
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}

// User is logged in, get user info
$user_id = $_SESSION['user_id'] ?? '';
$user_email = $_SESSION['email'] ?? '';
$first_name = $_SESSION['firstName'] ?? 'User';
$last_name = $_SESSION['lastName'] ?? '';
$user_role = $_SESSION['role'] ?? 'consumer';
$full_name = trim($first_name . ' ' . $last_name);

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
    <title>Dashboard - Smart Water Guardian</title>
    
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-auth-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-database-compat.js"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- jsPDF for PDF Generation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --bg-primary: #0a0e1a;
            --bg-card: rgba(255,255,255,0.03);
            --bg-sidebar: rgba(10,14,26,0.8);
            --text-primary: #ffffff;
            --text-secondary: rgba(255,255,255,0.7);
            --text-muted: rgba(255,255,255,0.4);
            --border-color: rgba(255,255,255,0.05);
            --shadow-color: rgba(0,212,255,0.05);
        }
        
        .light-mode {
            --bg-primary: #f0f4f8;
            --bg-card: rgba(255,255,255,0.8);
            --bg-sidebar: rgba(255,255,255,0.95);
            --text-primary: #1a365d;
            --text-secondary: #2d3748;
            --text-muted: #4a5568;
            --border-color: #e2e8f0;
            --shadow-color: rgba(0,0,0,0.05);
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-primary);
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
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
            transition: opacity 0.3s ease;
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
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, #00d4ff, transparent 70%);
            top: -100px;
            left: -100px;
            animation-delay: 0s;
        }
        .bg-animation .orb:nth-child(2) {
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, #7b2ffc, transparent 70%);
            bottom: -50px;
            right: -50px;
            animation-delay: -7s;
        }
        .bg-animation .orb:nth-child(3) {
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, #00ff88, transparent 70%);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: -14s;
        }
        
        @keyframes floatOrb {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(50px, -30px) scale(1.1); }
            50% { transform: translate(-20px, 40px) scale(0.9); }
            75% { transform: translate(30px, -20px) scale(1.05); }
        }
        
        .sidebar {
            width: 270px;
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
        .sidebar::-webkit-scrollbar-thumb { background: rgba(0, 212, 255, 0.2); border-radius: 10px; }
        
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
            background: linear-gradient(135deg, #00d4ff, #7b2ffc);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: white;
            box-shadow: 0 0 30px rgba(0, 212, 255, 0.3);
            animation: pulseGlow 3s ease-in-out infinite;
        }
        
        @keyframes pulseGlow {
            0%, 100% { box-shadow: 0 0 30px rgba(0, 212, 255, 0.3); }
            50% { box-shadow: 0 0 60px rgba(0, 212, 255, 0.6); }
        }
        
        .sidebar-brand .brand-text {
            font-size: 20px;
            font-weight: 800;
            background: linear-gradient(135deg, #00d4ff, #7b2ffc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .sidebar-brand .brand-text span {
            font-weight: 300;
            -webkit-text-fill-color: rgba(255,255,255,0.4);
        }
        
        .sidebar-nav { padding: 16px 12px; }
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
            background: rgba(0, 212, 255, 0.08);
            opacity: 1;
        }
        
        .sidebar-nav a.active {
            color: white;
            background: linear-gradient(135deg, rgba(0, 212, 255, 0.15), rgba(123, 47, 252, 0.1));
            box-shadow: 0 0 30px rgba(0, 212, 255, 0.05);
            opacity: 1;
        }
        
        .sidebar-nav a.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 20%;
            height: 60%;
            width: 3px;
            background: linear-gradient(180deg, #00d4ff, #7b2ffc);
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
            background: rgba(0, 212, 255, 0.08);
            opacity: 1;
        }
        
        .sidebar-nav .theme-toggle-btn i { width: 20px; text-align: center; font-size: 16px; }
        
        .sidebar-nav .logout-link {
            margin-top: 12px;
            border-top: 1px solid var(--border-color);
            padding-top: 12px;
            color: rgba(255, 100, 100, 0.5);
        }
        
        .sidebar-nav .logout-link:hover {
            color: #ff6b6b;
            background: rgba(255, 100, 100, 0.08);
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
            margin-left: 270px;
            padding: 28px 36px 40px;
            min-height: 100vh;
            position: relative;
            z-index: 1;
        }
        
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
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
            background: linear-gradient(135deg, #00d4ff, #7b2ffc);
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
            background: #00ff88;
            display: inline-block;
            animation: pulseDot 2s infinite;
            box-shadow: 0 0 20px rgba(0, 255, 136, 0.3);
        }
        @keyframes pulseDot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.8); }
        }
        .status-text {
            font-size: 13px;
            font-weight: 500;
            color: rgba(0, 255, 136, 0.8);
        }
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: var(--text-primary);
            cursor: pointer;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: var(--bg-card);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 22px 24px;
            border: 1px solid var(--border-color);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            border-color: rgba(0, 212, 255, 0.2);
            box-shadow: 0 10px 40px var(--shadow-color);
        }
        
        .stat-card .glow-line {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, #00d4ff, transparent);
            opacity: 0;
            transition: opacity 0.4s ease;
        }
        .stat-card:hover .glow-line { opacity: 1; }
        
        .stat-card .stat-icon { font-size: 28px; margin-bottom: 10px; display: block; }
        .stat-card .stat-content h3 {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }
        .stat-card .stat-number {
            font-size: 32px;
            font-weight: 800;
            color: var(--text-primary);
            margin: 4px 0;
        }
        .stat-card .stat-number small {
            font-size: 14px;
            font-weight: 400;
            color: var(--text-muted);
        }
        .stat-card .stat-change {
            font-size: 12px;
            color: var(--text-muted);
        }
        .stat-card.blue .glow-line { background: linear-gradient(90deg, transparent, #00d4ff, transparent); }
        .stat-card.green .glow-line { background: linear-gradient(90deg, transparent, #00ff88, transparent); }
        .stat-card.purple .glow-line { background: linear-gradient(90deg, transparent, #7b2ffc, transparent); }
        
        .quick-actions {
            background: var(--bg-card);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 24px;
            border: 1px solid var(--border-color);
        }
        
        .quick-actions h3 {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 16px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .quick-actions h3 i { margin-right: 8px; color: #00d4ff; }
        
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
        }
        
        .action-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: center;
            padding: 14px 20px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(255,255,255,0.03);
            color: var(--text-secondary);
        }
        
        .action-btn:hover {
            transform: translateY(-3px);
            border-color: rgba(0, 212, 255, 0.3);
            box-shadow: 0 8px 30px var(--shadow-color);
        }
        .action-btn i { font-size: 16px; }
        
        .action-btn.blue-btn { border-color: rgba(0, 212, 255, 0.15); }
        .action-btn.blue-btn:hover {
            background: rgba(0, 212, 255, 0.1);
            border-color: #00d4ff;
            color: #00d4ff;
            box-shadow: 0 0 40px rgba(0, 212, 255, 0.1);
        }
        .action-btn.green-btn { border-color: rgba(0, 255, 136, 0.15); }
        .action-btn.green-btn:hover {
            background: rgba(0, 255, 136, 0.1);
            border-color: #00ff88;
            color: #00ff88;
            box-shadow: 0 0 40px rgba(0, 255, 136, 0.1);
        }
        .action-btn.orange-btn { border-color: rgba(255, 107, 107, 0.15); }
        .action-btn.orange-btn:hover {
            background: rgba(255, 107, 107, 0.1);
            border-color: #ff6b6b;
            color: #ff6b6b;
            box-shadow: 0 0 40px rgba(255, 107, 107, 0.1);
        }
        .action-btn.gold-btn { border-color: rgba(255, 215, 0, 0.15); }
        .action-btn.gold-btn:hover {
            background: rgba(255, 215, 0, 0.1);
            border-color: #ffd700;
            color: #ffd700;
            box-shadow: 0 0 40px rgba(255, 215, 0, 0.1);
        }
        
        /* Toast */
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
        .toast-warning { background: rgba(255, 215, 0, 0.15); border: 1px solid #ffd700; color: #ffd700; }
        
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(50px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        /* PDF Report Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(20px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal.show { display: flex; animation: modalFadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        
        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.9) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        
        .modal-content {
            background: rgba(20, 25, 45, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 36px;
            max-width: 560px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            border: 1px solid rgba(0, 212, 255, 0.1);
            box-shadow: 0 0 80px rgba(0, 212, 255, 0.05);
        }
        
        .modal-content::-webkit-scrollbar { width: 4px; }
        .modal-content::-webkit-scrollbar-track { background: transparent; }
        .modal-content::-webkit-scrollbar-thumb { background: rgba(0, 212, 255, 0.3); border-radius: 10px; }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .modal-header h2 { font-size: 22px; font-weight: 700; color: white; }
        .modal-header h2 i { color: #ffd700; margin-right: 10px; }
        .modal-close {
            font-size: 28px;
            cursor: pointer;
            color: rgba(255,255,255,0.3);
            transition: all 0.3s ease;
            background: none;
            border: none;
        }
        .modal-close:hover { color: white; transform: rotate(90deg); }
        
        .modal-body p { color: rgba(255,255,255,0.5); margin-bottom: 20px; font-size: 14px; }
        .modal-body .btn-generate {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #00d4ff, #7b2ffc);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .modal-body .btn-generate:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 40px rgba(0, 212, 255, 0.3);
        }
        .modal-body .btn-generate:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        .modal-body .btn-generate .spinner {
            display: none;
            animation: spin 1s linear infinite;
        }
        .modal-body .btn-generate.loading .spinner { display: inline-block; }
        .modal-body .btn-generate.loading .btn-text { display: none; }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .report-preview {
            background: rgba(255,255,255,0.03);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
            border: 1px solid rgba(255,255,255,0.05);
            max-height: 200px;
            overflow-y: auto;
        }
        .report-preview .row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px solid rgba(255,255,255,0.03);
            font-size: 13px;
        }
        .report-preview .row .label { color: rgba(255,255,255,0.4); }
        .report-preview .row .value { color: rgba(255,255,255,0.8); }
        .report-preview .row:last-child { border-bottom: none; font-weight: 700; }
        
        @media (max-width: 1200px) { .stats-cards { grid-template-columns: repeat(2, 1fr); } }
        
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 20px; }
            .menu-toggle { display: block; }
            .actions-grid { grid-template-columns: repeat(2, 1fr); }
        }
        
        @media (max-width: 768px) {
            .stats-cards { grid-template-columns: 1fr; }
            .actions-grid { grid-template-columns: 1fr; }
            .topbar { flex-direction: column; text-align: center; padding: 16px; }
            .topbar-left { flex-direction: column; }
            .topbar-left .greeting { font-size: 18px; }
            .modal-content { padding: 20px; margin: 10px; }
            .sidebar { width: 280px; }
        }
    </style>
</head>
<body>
    <!-- ========== ANIMATED BACKGROUND ========== -->
    <div class="bg-animation">
        <div class="orb"></div>
        <div class="orb"></div>
        <div class="orb"></div>
    </div>

    <!-- ========== SIDEBAR ========== -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="logo-icon"><i class="fas fa-water"></i></div>
            <div class="brand-text">Smart<span>Water</span></div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-label">Main Menu</div>
            <a href="dashboard.php" class="active">
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
            <?php if ($user_role === 'system_admin' || $user_role === 'municipal_admin' || $user_role === 'admin'): ?>
            <a href="admin.php">
                <i class="fas fa-cog"></i> Admin
            </a>
            <?php endif; ?>
            <a href="profile.php">
                <i class="fas fa-user"></i> Profile
            </a>
            
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

    <!-- ========== MAIN CONTENT ========== -->
    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <button class="menu-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <div class="greeting">
                        Welcome back, <span class="highlight"><?php echo htmlspecialchars($first_name); ?></span>
                    </div>
                    <div class="sub-text">Real-time water monitoring dashboard</div>
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

        <!-- Stats Cards - Only 3: Flow, Volume, Pressure -->
        <section class="stats-cards">
            <div class="stat-card blue">
                <div class="glow-line"></div>
                <span class="stat-icon"><i class="fas fa-tint"></i></span>
                <div class="stat-content">
                    <h3>Flow Rate</h3>
                    <div class="stat-number" id="current-usage">0.0 <small>L/min</small></div>
                    <p class="stat-change" id="flow-status">Waiting for data...</p>
                </div>
            </div>
            <div class="stat-card green">
                <div class="glow-line"></div>
                <span class="stat-icon"><i class="fas fa-chart-bar"></i></span>
                <div class="stat-content">
                    <h3>Total Volume</h3>
                    <div class="stat-number" id="today-total">0.0 <small>L</small></div>
                    <p class="stat-change" id="volume-status">Today's usage</p>
                </div>
            </div>
            <div class="stat-card purple">
                <div class="glow-line"></div>
                <span class="stat-icon"><i class="fas fa-gauge-high"></i></span>
                <div class="stat-content">
                    <h3>Pressure</h3>
                    <div class="stat-number" id="pressure-display">0.0 <small>kPa</small></div>
                    <p class="stat-change" id="pressure-status">System pressure</p>
                </div>
            </div>
        </section>

        <!-- Quick Actions -->
        <section class="quick-actions">
            <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
            <div class="actions-grid">
                <button onclick="triggerESP32()" class="action-btn blue-btn">
                    <i class="fas fa-microchip"></i> Trigger ESP32
                </button>
                <button onclick="openReportModal()" class="action-btn green-btn">
                    <i class="fas fa-file-pdf"></i> Generate Report
                </button>
                <button onclick="viewHistory()" class="action-btn orange-btn">
                    <i class="fas fa-history"></i> View History
                </button>
                <button onclick="openCalculator()" class="action-btn gold-btn">
                    <i class="fas fa-calculator"></i> Bill Calculator
                </button>
            </div>
        </section>
    </main>

    <!-- ========== PDF REPORT MODAL ========== -->
    <div id="reportModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-file-pdf"></i> Generate Report</h2>
                <button class="modal-close" onclick="closeReportModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Generate a PDF report with your water usage data including flow rate, total volume, and pressure.</p>
                
                <div class="report-preview" id="reportPreview">
                    <div class="row"><span class="label">User</span><span class="value" id="reportName"><?php echo htmlspecialchars($full_name); ?></span></div>
                    <div class="row"><span class="label">Email</span><span class="value" id="reportEmail"><?php echo htmlspecialchars($user_email); ?></span></div>
                    <div class="row"><span class="label">Date</span><span class="value" id="reportDate">Loading...</span></div>
                    <div class="row"><span class="label">Flow Rate</span><span class="value" id="reportFlow">0.0 L/min</span></div>
                    <div class="row"><span class="label">Total Volume</span><span class="value" id="reportVolume">0 L</span></div>
                    <div class="row"><span class="label">Pressure</span><span class="value" id="reportPressure">0 kPa</span></div>
                    <div class="row"><span class="label">Device Status</span><span class="value" id="reportStatus">--</span></div>
                </div>
                
                <button class="btn-generate" id="generateReportBtn" onclick="generatePDFReport()">
                    <span class="btn-text"><i class="fas fa-download"></i> Download PDF Report</span>
                    <span class="spinner"><i class="fas fa-spinner"></i></span>
                </button>
            </div>
        </div>
    </div>

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
        let currentMeterId = null;
        let currentReading = null;
        let alertCount = 0;
        let userName = '<?php echo htmlspecialchars($full_name); ?>';
        let userEmail = '<?php echo htmlspecialchars($user_email); ?>';

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
                document.getElementById('themeIcon').className = 'fas fa-sun';
                document.getElementById('themeLabel').textContent = 'Light Mode';
            } else {
                document.body.classList.remove('light-mode');
                document.getElementById('themeIcon').className = 'fas fa-moon';
                document.getElementById('themeLabel').textContent = 'Dark Mode';
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
        // TOAST
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
            }, 5000);
        }

        // ============================================================
        // AUTH STATE
        // ============================================================
        auth.onAuthStateChanged(function(user) {
            if (user) {
                currentUser = user;
                console.log('User logged in:', user.uid);
                loadDashboardData(user.uid);
                database.ref('users/' + user.uid + '/preferences/theme').once('value')
                    .then(snapshot => {
                        const theme = snapshot.val();
                        if (theme) setTheme(theme);
                    });
            } else {
                window.location.href = 'login.php';
            }
        });

        // ============================================================
        // LOAD DASHBOARD DATA
        // ============================================================
        function loadDashboardData(uid) {
            const propertiesRef = database.ref('properties/' + uid);
            propertiesRef.once('value', function(snapshot) {
                const properties = snapshot.val();
                if (properties) {
                    const firstProperty = Object.values(properties)[0];
                    if (firstProperty && firstProperty.meterId) {
                        currentMeterId = firstProperty.meterId;
                        const meterRef = database.ref('meters/' + currentMeterId + '/lastReading');
                        meterRef.on('value', function(snapshot) {
                            const data = snapshot.val();
                            if (data) {
                                currentReading = data;
                                updateDashboardUI(data);
                            }
                        });
                    }
                }
            });

            const alertsRef = database.ref('alerts/' + uid);
            alertsRef.on('value', function(snapshot) {
                const data = snapshot.val();
                const count = data ? Object.keys(data).filter(key => !data[key].isRead).length : 0;
                alertCount = count;
                document.getElementById('alertBadge').textContent = count;
            });
        }

        // ============================================================
        // UPDATE DASHBOARD UI - FLOW, VOLUME, PRESSURE ONLY
        // ============================================================
        function updateDashboardUI(data) {
            const flow = data.flow || data.flowRate || 0;
            const volume = data.volume || data.totalVolume || 0;
            const pressure = data.pressure || 0;
            const status = data.status || 'offline';
            
            // Update Flow Rate
            document.getElementById('current-usage').innerHTML = Number(flow).toFixed(1) + ' <small>L/min</small>';
            
            // Update Total Volume
            document.getElementById('today-total').innerHTML = Number(volume).toFixed(1) + ' <small>L</small>';
            
            // Update Pressure
            document.getElementById('pressure-display').innerHTML = Number(pressure).toFixed(1) + ' <small>kPa</small>';
            
            // Update flow status based on flow rate
            const flowStatus = document.getElementById('flow-status');
            if (flow > 0) {
                if (flow > 20) {
                    flowStatus.textContent = 'High flow detected';
                    flowStatus.style.color = '#ff6b6b';
                } else if (flow > 10) {
                    flowStatus.textContent = 'Moderate flow';
                    flowStatus.style.color = '#ffd700';
                } else {
                    flowStatus.textContent = 'Normal flow';
                    flowStatus.style.color = '#00ff88';
                }
            } else {
                flowStatus.textContent = 'No flow detected';
                flowStatus.style.color = 'var(--text-muted)';
            }
            
            // Update volume status
            const volumeStatus = document.getElementById('volume-status');
            if (volume > 0) {
                volumeStatus.textContent = Number(volume).toFixed(1) + ' L used today';
                volumeStatus.style.color = '#00d4ff';
            } else {
                volumeStatus.textContent = 'No water used today';
                volumeStatus.style.color = 'var(--text-muted)';
            }
            
            // Update pressure status
            const pressureStatus = document.getElementById('pressure-status');
            if (pressure > 0) {
                if (pressure > 80) {
                    pressureStatus.textContent = 'High pressure';
                    pressureStatus.style.color = '#ff6b6b';
                } else if (pressure > 40) {
                    pressureStatus.textContent = 'Normal pressure';
                    pressureStatus.style.color = '#00ff88';
                } else {
                    pressureStatus.textContent = 'Low pressure';
                    pressureStatus.style.color = '#ffd700';
                }
            } else {
                pressureStatus.textContent = 'No pressure data';
                pressureStatus.style.color = 'var(--text-muted)';
            }
        }

        // ============================================================
        // TRIGGER ESP32
        // ============================================================
        function triggerESP32() {
            if (!currentUser) {
                showToast('Please login first', 'error');
                return;
            }

            const propertiesRef = database.ref('properties/' + currentUser.uid);
            propertiesRef.once('value', function(snapshot) {
                const properties = snapshot.val();
                let meterId = null;
                let propertyId = null;
                
                if (properties) {
                    const firstProp = Object.values(properties)[0];
                    if (firstProp && firstProp.meterId) {
                        meterId = firstProp.meterId;
                        propertyId = firstProp.id || Object.keys(properties)[0];
                    }
                }
                
                if (!meterId) {
                    showToast('No meter found. Creating one for you...', 'info');
                    const newMeterId = 'MTR-' + Date.now();
                    const newPropertyRef = database.ref('properties/' + currentUser.uid).push();
                    newPropertyRef.set({
                        propertyName: 'My Home',
                        address: 'Not specified',
                        meterId: newMeterId,
                        createdAt: new Date().toISOString()
                    }).then(() => {
                        return database.ref('meters/' + newMeterId).set({
                            meterId: newMeterId,
                            model: 'ESP32-YF-S201',
                            propertyId: newPropertyRef.key,
                            registeredAt: new Date().toISOString(),
                            lastReading: {
                                flow: 0,
                                volume: 0,
                                pressure: 0,
                                status: 'online',
                                timestamp: new Date().toISOString()
                            }
                        });
                    }).then(() => {
                        currentMeterId = newMeterId;
                        showToast('Meter created! Triggering data...', 'success');
                        sendESP32Data(newMeterId);
                    }).catch((error) => {
                        showToast('Error creating meter: ' + error.message, 'error');
                    });
                    return;
                }
                
                currentMeterId = meterId;
                sendESP32Data(meterId);
            });
        }

        function sendESP32Data(meterId) {
            const flowRate = (Math.random() * 15 + 5).toFixed(2);
            const totalVolume = (Math.random() * 100 + 50).toFixed(2);
            const pressure = (Math.random() * 60 + 20).toFixed(2);
            const timestamp = new Date().toISOString();
            const status = 'online';

            showToast('Sending data to ESP32 device...', 'info');

            const readingData = {
                flow: parseFloat(flowRate),
                flowRate: parseFloat(flowRate),
                volume: parseFloat(totalVolume),
                totalVolume: parseFloat(totalVolume),
                pressure: parseFloat(pressure),
                status: status,
                timestamp: timestamp,
                lastUpdated: timestamp
            };

            database.ref('meters/' + meterId + '/lastReading').update(readingData)
                .then(() => {
                    const today = new Date().toISOString().split('T')[0];
                    const hour = new Date().getHours();
                    return database.ref('meters/' + meterId + '/history/' + today + '/hourly/' + hour).set({
                        flow: parseFloat(flowRate),
                        volume: parseFloat(totalVolume),
                        pressure: parseFloat(pressure),
                        timestamp: timestamp
                    });
                })
                .then(() => {
                    return database.ref('meters/' + meterId + '/lastReading').once('value');
                })
                .then((snapshot) => {
                    const retrievedData = snapshot.val();
                    if (retrievedData) {
                        currentReading = retrievedData;
                        updateDashboardUI(retrievedData);
                        showToast('Data received! Flow: ' + flowRate + ' L/min, Pressure: ' + pressure + ' kPa', 'success');
                        
                        if (parseFloat(flowRate) > 20) {
                            const alertRef = database.ref('alerts/' + currentUser.uid).push();
                            alertRef.set({
                                type: 'high_usage',
                                message: 'High water usage detected: ' + flowRate + ' L/min.',
                                severity: 'warning',
                                timestamp: new Date().toISOString(),
                                isRead: false
                            });
                            showToast('High usage alert triggered!', 'warning');
                        }
                    }
                })
                .catch((error) => {
                    showToast('Error: ' + error.message, 'error');
                });
        }

        // ============================================================
        // PDF REPORT GENERATION - UPDATED FOR FLOW, VOLUME, PRESSURE
        // ============================================================
        function openReportModal() {
            const now = new Date();
            document.getElementById('reportDate').textContent = now.toLocaleDateString('en-US', {
                year: 'numeric', month: 'long', day: 'numeric'
            });
            
            if (currentReading) {
                const flow = currentReading.flow || currentReading.flowRate || 0;
                const volume = currentReading.volume || currentReading.totalVolume || 0;
                const pressure = currentReading.pressure || 0;
                const status = currentReading.status || 'Offline';
                
                document.getElementById('reportFlow').textContent = Number(flow).toFixed(1) + ' L/min';
                document.getElementById('reportVolume').textContent = Number(volume).toFixed(1) + ' L';
                document.getElementById('reportPressure').textContent = Number(pressure).toFixed(1) + ' kPa';
                document.getElementById('reportStatus').textContent = status.toUpperCase();
            } else {
                document.getElementById('reportFlow').textContent = '0.0 L/min';
                document.getElementById('reportVolume').textContent = '0 L';
                document.getElementById('reportPressure').textContent = '0 kPa';
                document.getElementById('reportStatus').textContent = '--';
            }
            
            document.getElementById('reportModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeReportModal() {
            document.getElementById('reportModal').classList.remove('show');
            document.body.style.overflow = 'auto';
        }

        function generatePDFReport() {
            const btn = document.getElementById('generateReportBtn');
            btn.classList.add('loading');
            btn.disabled = true;
            
            try {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF('p', 'mm', 'a4');
                
                const name = userName;
                const email = userEmail;
                const now = new Date();
                const dateStr = now.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
                const timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                
                const flow = currentReading ? (currentReading.flow || currentReading.flowRate || 0) : 0;
                const volume = currentReading ? (currentReading.volume || currentReading.totalVolume || 0) : 0;
                const pressure = currentReading ? (currentReading.pressure || 0) : 0;
                const status = currentReading ? (currentReading.status || 'Offline') : 'Offline';
                
                // Header
                doc.setFillColor(0, 20, 40);
                doc.rect(0, 0, 210, 40, 'F');
                
                doc.setTextColor(255, 255, 255);
                doc.setFontSize(22);
                doc.setFont('helvetica', 'bold');
                doc.text('Smart Water Guardian', 20, 18);
                
                doc.setFontSize(10);
                doc.setTextColor(200, 200, 200);
                doc.text('Water Usage Report', 20, 28);
                
                doc.setTextColor(150, 150, 150);
                doc.setFontSize(9);
                doc.text('Generated: ' + dateStr + ' at ' + timeStr, 20, 38);
                
                // User Info
                doc.setTextColor(0, 0, 0);
                doc.setFontSize(14);
                doc.setFont('helvetica', 'bold');
                doc.text('User Information', 20, 55);
                
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(11);
                doc.text('Name: ' + name, 20, 65);
                doc.text('Email: ' + email, 20, 73);
                doc.text('Report Period: ' + dateStr, 20, 81);
                
                // Summary Box - Updated for Flow, Volume, Pressure
                doc.setFillColor(240, 248, 255);
                doc.roundedRect(20, 90, 170, 55, 3, 3, 'F');
                doc.setDrawColor(0, 100, 200);
                doc.setLineWidth(0.5);
                doc.roundedRect(20, 90, 170, 55, 3, 3, 'S');
                
                doc.setFontSize(10);
                doc.setFont('helvetica', 'bold');
                doc.setTextColor(0, 50, 100);
                doc.text('Current Water Readings', 25, 100);
                
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(11);
                doc.setTextColor(0, 0, 0);
                doc.text('Flow Rate: ' + Number(flow).toFixed(1) + ' L/min', 25, 112);
                doc.text('Total Volume: ' + Number(volume).toFixed(1) + ' L', 110, 112);
                doc.text('Pressure: ' + Number(pressure).toFixed(1) + ' kPa', 25, 124);
                doc.text('Status: ' + status.toUpperCase(), 110, 124);
                doc.text('Active Alerts: ' + alertCount, 25, 136);
                
                // Usage Analytics
                doc.setFontSize(14);
                doc.setFont('helvetica', 'bold');
                doc.setTextColor(0, 0, 0);
                doc.text('Usage Analytics', 20, 160);
                
                const dailyUsage = volume;
                const weeklyUsage = dailyUsage * 7;
                const monthlyUsage = dailyUsage * 30;
                const yearlyUsage = dailyUsage * 365;
                
                const startY = 170;
                const rows = [
                    ['Daily Usage', Number(dailyUsage).toFixed(1) + ' L', '100%'],
                    ['Weekly Usage', Number(weeklyUsage).toFixed(1) + ' L', '700%'],
                    ['Monthly Usage', Number(monthlyUsage).toFixed(1) + ' L', '3000%'],
                    ['Yearly Usage', Number(yearlyUsage).toFixed(1) + ' L', '36500%']
                ];
                
                doc.setFillColor(0, 50, 100);
                doc.rect(20, startY - 5, 170, 8, 'F');
                doc.setTextColor(255, 255, 255);
                doc.setFontSize(9);
                doc.setFont('helvetica', 'bold');
                doc.text('Period', 25, startY + 1);
                doc.text('Volume', 100, startY + 1);
                doc.text('Percentage', 155, startY + 1);
                
                doc.setFont('helvetica', 'normal');
                rows.forEach((row, index) => {
                    const y = startY + 8 + (index * 8);
                    const bgColor = index % 2 === 0 ? [245, 248, 250] : [255, 255, 255];
                    doc.setFillColor(bgColor[0], bgColor[1], bgColor[2]);
                    doc.rect(20, y - 4, 170, 8, 'F');
                    doc.setTextColor(0, 0, 0);
                    doc.setFontSize(9);
                    doc.text(row[0], 25, y + 1);
                    doc.text(row[1], 100, y + 1);
                    doc.text(row[2], 155, y + 1);
                });
                
                // Footer
                const footerY = 270;
                doc.setDrawColor(200, 200, 200);
                doc.setLineWidth(0.5);
                doc.line(20, footerY - 10, 190, footerY - 10);
                
                doc.setTextColor(150, 150, 150);
                doc.setFontSize(8);
                doc.text('Smart Water Guardian - Water Usage Report', 20, footerY + 2);
                doc.text('Generated on ' + dateStr, 20, footerY + 8);
                doc.text('Page 1 of 1', 180, footerY + 2, { align: 'right' });
                
                doc.save('Water_Usage_Report_' + now.toISOString().split('T')[0] + '.pdf');
                
                showToast('PDF report downloaded successfully!', 'success');
                
            } catch (error) {
                console.error('PDF Generation Error:', error);
                showToast('Error generating PDF: ' + error.message, 'error');
            } finally {
                btn.classList.remove('loading');
                btn.disabled = false;
                closeReportModal();
            }
        }

        // ============================================================
        // OTHER FUNCTIONS
        // ============================================================
        function viewHistory() {
            window.location.href = 'history.php';
        }

        function openCalculator() {
            showToast('Bill calculator feature coming soon!', 'info');
        }

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

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                closeReportModal();
            }
        }

        window.triggerESP32 = triggerESP32;
        window.openReportModal = openReportModal;
        window.closeReportModal = closeReportModal;
        window.generatePDFReport = generatePDFReport;
        window.viewHistory = viewHistory;
        window.openCalculator = openCalculator;
        window.logoutUser = logoutUser;
        window.toggleSidebar = toggleSidebar;
        window.toggleTheme = toggleTheme;
        window.showToast = showToast;

        console.log('Dashboard loaded - showing Flow, Volume, Pressure only');
    </script>
</body>
</html>