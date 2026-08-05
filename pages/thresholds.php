<?php
/**
 * Smart Water Guardian - Thresholds Page
 * Manage alert thresholds for water usage - Professional Design with Light/Dark Mode
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

$user_id = $_SESSION['user_id'] ?? '';
$first_name = $_SESSION['firstName'] ?? 'User';
$last_name = $_SESSION['lastName'] ?? '';
$user_role = $_SESSION['role'] ?? 'consumer';
$full_name = trim($first_name . ' ' . $last_name);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thresholds - Smart Water Guardian ⚙️</title>
    
    <!-- Firebase SDK -->
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-auth-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-database-compat.js"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        /* ============================================================
                   THRESHOLDS PAGE - PROFESSIONAL WITH LIGHT/DARK MODE
                   ============================================================ */
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
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
            -webkit-text-fill-color: #1a365d;
            background: none;
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
        
        body.light-mode .threshold-card {
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid #e2e8f0;
        }
        
        body.light-mode .threshold-card:hover {
            border-color: rgba(0, 212, 255, 0.3);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05);
        }
        
        body.light-mode .threshold-card .title {
            color: #1a365d;
        }
        
        body.light-mode .threshold-card .description {
            color: rgba(0, 0, 0, 0.4);
        }
        
        body.light-mode .threshold-card .value-display {
            background: linear-gradient(135deg, #0066cc, #4a00a0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        body.light-mode .threshold-card .value-display .unit {
            -webkit-text-fill-color: rgba(0, 0, 0, 0.3);
            color: rgba(0, 0, 0, 0.3);
        }
        
        body.light-mode .threshold-card .actions {
            border-top: 1px solid #e2e8f0;
        }
        
        body.light-mode .btn-edit {
            background: rgba(0, 100, 200, 0.08);
            color: #0066cc;
        }
        
        body.light-mode .btn-edit:hover {
            background: rgba(0, 100, 200, 0.15);
        }
        
        body.light-mode .btn-toggle {
            background: rgba(200, 180, 0, 0.08);
            color: #cc9900;
        }
        
        body.light-mode .btn-toggle:hover {
            background: rgba(200, 180, 0, 0.15);
        }
        
        body.light-mode .btn-delete {
            background: rgba(200, 50, 50, 0.08);
            color: #cc3333;
        }
        
        body.light-mode .btn-delete:hover {
            background: rgba(200, 50, 50, 0.15);
        }
        
        body.light-mode .status-active {
            background: rgba(0, 200, 100, 0.08);
            color: #008844;
            border: 1px solid rgba(0, 200, 100, 0.05);
        }
        
        body.light-mode .status-inactive {
            background: rgba(200, 50, 50, 0.08);
            color: #cc3333;
            border: 1px solid rgba(200, 50, 50, 0.05);
        }
        
        body.light-mode .empty-state {
            background: rgba(255, 255, 255, 0.6);
            border: 1px solid #e2e8f0;
        }
        
        body.light-mode .empty-state h3 {
            color: #1a365d;
        }
        
        body.light-mode .empty-state p {
            color: rgba(0, 0, 0, 0.3);
        }
        
        body.light-mode .modal-content {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid #e2e8f0;
        }
        
        body.light-mode .modal-content h2 {
            color: #1a365d;
        }
        
        body.light-mode .modal-content .form-group label {
            color: rgba(0, 0, 0, 0.6);
        }
        
        body.light-mode .modal-content .form-group input,
        body.light-mode .modal-content .form-group select {
            background: rgba(255, 255, 255, 0.8);
            color: #1a365d;
            border: 1px solid #e2e8f0;
        }
        
        body.light-mode .modal-content .form-group input:focus,
        body.light-mode .modal-content .form-group select:focus {
            border-color: rgba(0, 100, 200, 0.3);
        }
        
        body.light-mode .modal-content .form-group input::placeholder {
            color: rgba(0, 0, 0, 0.2);
        }
        
        body.light-mode .modal-content .close {
            color: rgba(0, 0, 0, 0.3);
        }
        
        body.light-mode .modal-content .close:hover {
            color: #1a365d;
        }
        
        body.light-mode .btn-cancel {
            background: rgba(0, 0, 0, 0.03);
            color: rgba(0, 0, 0, 0.4);
            border: 1px solid #e2e8f0;
        }
        
        body.light-mode .btn-cancel:hover {
            background: rgba(0, 0, 0, 0.06);
            color: #1a365d;
        }
        
        body.light-mode .btn-add {
            color: white;
        }
        
        body.light-mode .btn-add-empty {
            color: white;
        }
        
        body.light-mode .thresholds-header h3 {
            color: #1a365d;
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
        
        /* ========== ANIMATED BACKGROUND ========== */
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
        
        .bg-animation .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.3;
            animation: floatOrb 20s ease-in-out infinite;
        }
        
        .bg-animation .orb:nth-child(1) {
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, #00d4ff, transparent 70%);
            top: -80px;
            left: -80px;
            animation-delay: 0s;
        }
        
        .bg-animation .orb:nth-child(2) {
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, #7b2ffc, transparent 70%);
            bottom: -50px;
            right: -50px;
            animation-delay: -7s;
        }
        
        @keyframes floatOrb {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(50px, -30px) scale(1.1); }
            50% { transform: translate(-20px, 40px) scale(0.9); }
            75% { transform: translate(30px, -20px) scale(1.05); }
        }
        
        /* ========== SIDEBAR ========== */
        .sidebar {
            width: 260px;
            background: rgba(10, 14, 26, 0.85);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(0, 212, 255, 0.08);
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
        .sidebar::-webkit-scrollbar-thumb { background: rgba(0, 212, 255, 0.3); border-radius: 10px; }
        
        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 0 24px 24px;
            border-bottom: 1px solid rgba(0, 212, 255, 0.08);
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
            50% { box-shadow: 0 0 60px rgba(0, 212, 255, 0.6), 0 0 120px rgba(123, 47, 252, 0.2); }
        }
        
        .sidebar-brand .brand-text {
            font-size: 20px;
            font-weight: 800;
            background: linear-gradient(135deg, #00d4ff, #7b2ffc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .sidebar-nav {
            padding: 16px 12px;
        }
        
        .sidebar-nav .nav-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: rgba(255,255,255,0.2);
            padding: 12px 12px 8px;
            font-weight: 600;
        }
        
        body.light-mode .sidebar-nav .nav-label {
            color: rgba(0, 0, 0, 0.2);
        }
        
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: rgba(255, 255, 255, 0.5);
            text-decoration: none;
            transition: all 0.3s ease;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            position: relative;
        }
        
        .sidebar-nav a:hover {
            color: white;
            background: rgba(0, 212, 255, 0.08);
        }
        
        .sidebar-nav a.active {
            color: white;
            background: linear-gradient(135deg, rgba(0, 212, 255, 0.15), rgba(123, 47, 252, 0.1));
            box-shadow: 0 0 30px rgba(0, 212, 255, 0.05);
        }
        
        body.light-mode .sidebar-nav a.active {
            color: #1a365d;
            background: linear-gradient(135deg, rgba(0, 212, 255, 0.1), rgba(123, 47, 252, 0.05));
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
        
        .sidebar-nav .logout-link {
            margin-top: 12px;
            border-top: 1px solid rgba(255,255,255,0.05);
            padding-top: 12px;
            color: rgba(255, 100, 100, 0.5);
        }
        .sidebar-nav .logout-link:hover { color: #ff6b6b; background: rgba(255, 100, 100, 0.08); }
        
        .sidebar-footer {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            padding: 0 24px;
            font-size: 11px;
            color: rgba(255, 255, 255, 0.15);
            text-align: center;
            letter-spacing: 1px;
        }
        
        /* ========== MAIN CONTENT ========== */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 28px 36px 40px;
            min-height: 100vh;
            position: relative;
            z-index: 1;
        }
        
        /* ========== TOP BAR ========== */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
            padding: 16px 24px;
            background: rgba(255,255,255,0.03);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            border: 1px solid rgba(255,255,255,0.05);
            flex-wrap: wrap;
            gap: 12px;
            transition: background 0.3s ease;
        }
        
        .topbar-left h2 {
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(135deg, #00d4ff, #7b2ffc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .topbar-left p {
            color: rgba(255,255,255,0.4);
            font-size: 14px;
        }
        
        .topbar-right .date-display {
            color: rgba(255,255,255,0.6);
            font-size: 13px;
            padding: 6px 16px;
            background: rgba(255,255,255,0.05);
            border-radius: 50px;
            border: 1px solid rgba(255,255,255,0.05);
        }
        
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: white;
            cursor: pointer;
        }
        
        body.light-mode .menu-toggle {
            color: #1a365d;
        }
        
        /* ========== THRESHOLDS HEADER ========== */
        .thresholds-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 24px;
        }
        
        .thresholds-header h3 {
            color: rgba(255,255,255,0.7);
            font-size: 16px;
            font-weight: 600;
        }
        
        .thresholds-header h3 i {
            color: #00d4ff;
            margin-right: 8px;
        }
        
        .btn-add {
            padding: 10px 24px;
            background: linear-gradient(135deg, #00d4ff, #7b2ffc);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 212, 255, 0.3);
        }
        
        /* ========== THRESHOLDS GRID ========== */
        .threshold-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .threshold-card {
            background: rgba(255,255,255,0.03);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 24px;
            border: 1px solid rgba(255,255,255,0.05);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .threshold-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(0, 212, 255, 0.03), transparent);
            opacity: 0;
            transition: opacity 0.4s ease;
        }
        
        .threshold-card:hover::before {
            opacity: 1;
        }
        
        .threshold-card:hover {
            border-color: rgba(0, 212, 255, 0.2);
            transform: translateY(-4px);
            box-shadow: 0 10px 40px rgba(0, 212, 255, 0.05);
        }
        
        .threshold-card .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        
        .threshold-card .header .icon {
            font-size: 36px;
        }
        
        .threshold-card .header .status {
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .status-active {
            background: rgba(0, 255, 136, 0.15);
            color: #00ff88;
            border: 1px solid rgba(0, 255, 136, 0.1);
        }
        
        .status-inactive {
            background: rgba(255, 107, 107, 0.15);
            color: #ff6b6b;
            border: 1px solid rgba(255, 107, 107, 0.1);
        }
        
        .threshold-card .title {
            font-size: 16px;
            font-weight: 600;
            color: rgba(255,255,255,0.8);
        }
        
        .threshold-card .value-display {
            font-size: 34px;
            font-weight: 700;
            color: white;
            margin: 10px 0 6px;
            background: linear-gradient(135deg, #00d4ff, #7b2ffc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .threshold-card .value-display .unit {
            font-size: 16px;
            font-weight: 400;
            color: rgba(255,255,255,0.3);
            -webkit-text-fill-color: rgba(255,255,255,0.3);
        }
        
        .threshold-card .description {
            color: rgba(255,255,255,0.4);
            font-size: 13px;
            margin-bottom: 14px;
        }
        
        .threshold-card .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            padding-top: 14px;
            border-top: 1px solid rgba(255,255,255,0.05);
        }
        
        .threshold-card .actions button {
            padding: 6px 16px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-edit {
            background: rgba(0, 212, 255, 0.12);
            color: #00d4ff;
        }
        .btn-edit:hover { background: rgba(0, 212, 255, 0.2); }
        
        .btn-toggle {
            background: rgba(255, 215, 0, 0.12);
            color: #ffd700;
        }
        .btn-toggle:hover { background: rgba(255, 215, 0, 0.2); }
        
        .btn-delete {
            background: rgba(255, 107, 107, 0.12);
            color: #ff6b6b;
        }
        .btn-delete:hover { background: rgba(255, 107, 107, 0.2); }
        
        /* ========== EMPTY STATE ========== */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: rgba(255,255,255,0.03);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            border: 1px solid rgba(255,255,255,0.05);
            grid-column: 1 / -1;
        }
        
        .empty-state .icon {
            font-size: 80px;
            margin-bottom: 20px;
            display: block;
        }
        
        .empty-state h3 {
            color: rgba(255,255,255,0.8);
            font-size: 24px;
            margin-bottom: 8px;
        }
        
        .empty-state p {
            color: rgba(255,255,255,0.3);
            font-size: 16px;
        }
        
        .btn-add-empty {
            margin-top: 16px;
            padding: 12px 32px;
            background: linear-gradient(135deg, #00d4ff, #7b2ffc);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .btn-add-empty:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 212, 255, 0.3);
        }
        
        /* ========== MODAL ========== */
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
        
        .modal.show {
            display: flex;
            animation: modalFadeIn 0.3s ease;
        }
        
        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.9) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        
        .modal-content {
            background: rgba(20, 25, 45, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            padding: 32px;
            max-width: 480px;
            width: 100%;
            border: 1px solid rgba(0, 212, 255, 0.1);
            box-shadow: 0 0 60px rgba(0, 212, 255, 0.05);
            transition: background 0.3s ease;
        }
        
        .modal-content .close {
            float: right;
            font-size: 28px;
            cursor: pointer;
            color: rgba(255,255,255,0.3);
            transition: all 0.3s ease;
            background: none;
            border: none;
        }
        
        .modal-content .close:hover {
            color: white;
            transform: rotate(90deg);
        }
        
        body.light-mode .modal-content .close {
            color: rgba(0, 0, 0, 0.3);
        }
        
        body.light-mode .modal-content .close:hover {
            color: #1a365d;
        }
        
        .modal-content h2 {
            color: white;
            margin-bottom: 20px;
            font-size: 22px;
        }
        
        .modal-content h2 i {
            color: #00d4ff;
            margin-right: 10px;
        }
        
        body.light-mode .modal-content h2 {
            color: #1a365d;
        }
        
        .modal-content .form-group {
            margin-bottom: 16px;
        }
        
        .modal-content .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: rgba(255,255,255,0.8);
            margin-bottom: 4px;
        }
        
        body.light-mode .modal-content .form-group label {
            color: rgba(0, 0, 0, 0.6);
        }
        
        .modal-content .form-group input,
        .modal-content .form-group select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.05);
            color: white;
        }
        
        body.light-mode .modal-content .form-group input,
        body.light-mode .modal-content .form-group select {
            background: rgba(255, 255, 255, 0.8);
            color: #1a365d;
            border: 1px solid #e2e8f0;
        }
        
        .modal-content .form-group input:focus,
        .modal-content .form-group select:focus {
            outline: none;
            border-color: #00d4ff;
            box-shadow: 0 0 30px rgba(0, 212, 255, 0.05);
        }
        
        body.light-mode .modal-content .form-group input:focus,
        body.light-mode .modal-content .form-group select:focus {
            border-color: rgba(0, 100, 200, 0.3);
        }
        
        .modal-content .form-group input::placeholder {
            color: rgba(255,255,255,0.2);
        }
        
        body.light-mode .modal-content .form-group input::placeholder {
            color: rgba(0, 0, 0, 0.2);
        }
        
        .modal-content .form-group .helper {
            font-size: 12px;
            color: rgba(255,255,255,0.3);
            margin-top: 4px;
        }
        
        body.light-mode .modal-content .form-group .helper {
            color: rgba(0, 0, 0, 0.3);
        }
        
        .btn-submit {
            padding: 12px 30px;
            background: linear-gradient(135deg, #00d4ff, #7b2ffc);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 212, 255, 0.3);
        }
        
        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-cancel {
            padding: 12px 30px;
            background: rgba(255,255,255,0.05);
            color: rgba(255,255,255,0.5);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 8px;
            width: 100%;
        }
        
        .btn-cancel:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        body.light-mode .btn-cancel {
            background: rgba(0, 0, 0, 0.03);
            color: rgba(0, 0, 0, 0.4);
            border: 1px solid #e2e8f0;
        }
        
        body.light-mode .btn-cancel:hover {
            background: rgba(0, 0, 0, 0.06);
            color: #1a365d;
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
            color: white;
            font-weight: 500;
            font-size: 14px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.3);
            animation: slideInRight 0.3s ease;
            min-width: 250px;
            backdrop-filter: blur(10px);
        }
        
        .toast-success { background: rgba(0, 255, 136, 0.2); border: 1px solid #00ff88; color: #00ff88; }
        .toast-error { background: rgba(255, 107, 107, 0.2); border: 1px solid #ff6b6b; color: #ff6b6b; }
        .toast-info { background: rgba(0, 212, 255, 0.2); border: 1px solid #00d4ff; color: #00d4ff; }
        .toast-warning { background: rgba(255, 215, 0, 0.2); border: 1px solid #ffd700; color: #ffd700; }
        
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(50px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        /* ========== RESPONSIVE ========== */
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
        }
        
        @media (max-width: 768px) {
            .topbar {
                flex-direction: column;
                text-align: center;
                padding: 16px;
            }
            .topbar-left h2 {
                font-size: 22px;
            }
            .thresholds-header {
                flex-direction: column;
            }
            .thresholds-header .btn-add {
                width: 100%;
                text-align: center;
            }
            .threshold-grid {
                grid-template-columns: 1fr;
            }
            .modal-content {
                padding: 20px;
                margin: 10px;
            }
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
            <div class="logo-icon"><i class="fas fa-water"></i></div>
            <div class="brand-text">Smart<span style="-webkit-text-fill-color:rgba(255,255,255,0.3);">Water</span></div>
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
            <a href="thresholds.php" class="active">
                <i class="fas fa-sliders-h"></i> Thresholds ⚙️
            </a>
            <a href="reviews.php">
                <i class="fas fa-star"></i> Reviews ⭐
            </a>
            <a href="properties.php">
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

    <!-- ========== MAIN CONTENT ========== -->
    <main class="main-content">
        <!-- Top Bar -->
        <header class="topbar">
            <div class="topbar-left">
                <button class="menu-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h2>⚙️ Alert Thresholds</h2>
                <p>Configure your water usage alert settings</p>
            </div>
            <div class="topbar-right">
                <span class="date-display">
                    <i class="far fa-calendar-alt"></i> 
                    <?php echo date('l, F j, Y'); ?>
                </span>
            </div>
        </header>

        <!-- Thresholds Header -->
        <div class="thresholds-header">
            <h3><i class="fas fa-sliders-h"></i> Your Thresholds</h3>
            <button class="btn-add" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Add Threshold ➕
            </button>
        </div>

        <!-- Thresholds Grid -->
        <div class="threshold-grid" id="thresholdsContainer">
            <div class="empty-state" id="loadingThresholds">
                <span class="icon">⏳</span>
                <h3>Loading thresholds...</h3>
                <p>Please wait while we fetch your settings.</p>
            </div>
        </div>
    </main>

    <!-- ========== MODAL ========== -->
    <div id="thresholdModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2><i class="fas fa-sliders-h"></i> <span id="modalTitle">Add Threshold</span></h2>
            <form id="thresholdForm">
                <input type="hidden" id="thresholdId" value="">
                
                <div class="form-group">
                    <label for="thresholdType">Threshold Type 📊</label>
                    <select id="thresholdType" required>
                        <option value="daily_limit">Daily Limit</option>
                        <option value="hourly_limit">Hourly Limit</option>
                        <option value="leak_duration">Leak Duration</option>
                        <option value="flow_rate">Flow Rate</option>
                        <option value="pressure">Pressure</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="thresholdValue">Threshold Value 🔢</label>
                    <input type="number" id="thresholdValue" step="0.1" required placeholder="Enter value">
                    <div class="helper" id="unitHelper">📏 Units: L/day</div>
                </div>
                
                <button type="submit" class="btn-submit" id="submitBtn">
                    <i class="fas fa-save"></i> Save Threshold 💾
                </button>
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <script>
        // ==================== FIREBASE CONFIG ====================
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
        let allThresholds = [];
        let editingId = null;

        // ==================== THEME MANAGEMENT ====================
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

        // ==================== THRESHOLD TYPES CONFIG ====================
        const thresholdTypes = {
            'daily_limit': {
                label: 'Daily Limit',
                icon: '📊',
                unit: 'L/day',
                description: 'Alert when daily usage exceeds this limit',
                default: 1000
            },
            'hourly_limit': {
                label: 'Hourly Limit',
                icon: '⏰',
                unit: 'L/hour',
                description: 'Alert when hourly usage exceeds this limit',
                default: 100
            },
            'leak_duration': {
                label: 'Leak Duration',
                icon: '💧',
                unit: 'hours',
                description: 'Alert when continuous flow exceeds this duration',
                default: 2
            },
            'flow_rate': {
                label: 'Flow Rate',
                icon: '🌊',
                unit: 'L/min',
                description: 'Alert when flow rate exceeds this value',
                default: 20
            },
            'pressure': {
                label: 'Pressure',
                icon: '📏',
                unit: 'kPa',
                description: 'Alert when pressure exceeds this value',
                default: 500
            }
        };

        // ==================== AUTH CHECK ====================
        auth.onAuthStateChanged(function(user) {
            if (user) {
                currentUser = user;
                console.log('✅ User logged in:', user.uid);
                loadThresholds();
                updateAlertBadge();
            } else {
                window.location.href = 'login.php';
            }
        });

        // ==================== UPDATE ALERT BADGE ====================
        function updateAlertBadge() {
            if (!currentUser) return;
            const alertsRef = database.ref('alerts/' + currentUser.uid);
            alertsRef.on('value', function(snapshot) {
                const data = snapshot.val();
                const count = data ? Object.keys(data).filter(key => !data[key].isRead).length : 0;
                document.getElementById('alertBadge').textContent = count;
            });
        }

        // ==================== TOAST NOTIFICATIONS ====================
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

        // ==================== LOAD THRESHOLDS ====================
        function loadThresholds() {
            const thresholdsRef = database.ref('thresholds/' + currentUser.uid);
            thresholdsRef.on('value', function(snapshot) {
                const data = snapshot.val();
                allThresholds = [];
                if (data) {
                    for (let id in data) {
                        allThresholds.push({
                            id: id,
                            ...data[id]
                        });
                    }
                }
                renderThresholds(allThresholds);
            });
        }

        // ==================== RENDER THRESHOLDS ====================
        function renderThresholds(thresholds) {
            const container = document.getElementById('thresholdsContainer');
            
            if (!thresholds || thresholds.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <span class="icon">⚙️</span>
                        <h3>No Thresholds Set</h3>
                        <p>Configure alert thresholds to monitor your water usage.</p>
                        <button class="btn-add-empty" onclick="openAddModal()">
                            <i class="fas fa-plus"></i> Add Your First Threshold
                        </button>
                    </div>
                `;
                return;
            }

            let html = '';
            thresholds.forEach(threshold => {
                const typeInfo = thresholdTypes[threshold.thresholdType] || {
                    label: threshold.thresholdType || 'Unknown',
                    icon: '⚙️',
                    unit: '',
                    description: 'Custom threshold'
                };
                
                const isActive = threshold.isActive !== false;
                const statusClass = isActive ? 'status-active' : 'status-inactive';
                const statusText = isActive ? '✅ Active' : '❌ Inactive';
                
                html += `
                    <div class="threshold-card" style="border-left: 4px solid ${isActive ? '#00ff88' : '#ff6b6b'};">
                        <div class="header">
                            <span class="icon">${typeInfo.icon}</span>
                            <span class="status ${statusClass}">${statusText}</span>
                        </div>
                        <div class="title">${typeInfo.label}</div>
                        <div class="value-display">
                            ${threshold.thresholdValue || 0} 
                            <span class="unit">${typeInfo.unit}</span>
                        </div>
                        <div class="description">${typeInfo.description}</div>
                        <div class="actions">
                            <button class="btn-edit" onclick="editThreshold('${threshold.id}')">
                                ✏️ Edit
                            </button>
                            <button class="btn-toggle" onclick="toggleThreshold('${threshold.id}', ${!isActive})">
                                ${isActive ? '⏸️ Disable' : '▶️ Enable'}
                            </button>
                            <button class="btn-delete" onclick="deleteThreshold('${threshold.id}')">
                                🗑️ Delete
                            </button>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
        }

        // ==================== OPEN ADD MODAL ====================
        function openAddModal() {
            editingId = null;
            document.getElementById('modalTitle').textContent = 'Add Threshold';
            document.getElementById('thresholdId').value = '';
            document.getElementById('thresholdType').value = 'daily_limit';
            document.getElementById('thresholdValue').value = '';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Save Threshold 💾';
            updateUnitHelper('daily_limit');
            document.getElementById('thresholdModal').classList.add('show');
        }

        // ==================== EDIT THRESHOLD ====================
        function editThreshold(id) {
            const threshold = allThresholds.find(t => t.id === id);
            if (!threshold) {
                showToast('⚠️ Threshold not found', 'error');
                return;
            }
            
            editingId = id;
            document.getElementById('modalTitle').textContent = 'Edit Threshold';
            document.getElementById('thresholdId').value = id;
            document.getElementById('thresholdType').value = threshold.thresholdType || 'daily_limit';
            document.getElementById('thresholdValue').value = threshold.thresholdValue || '';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Update Threshold 💾';
            updateUnitHelper(threshold.thresholdType || 'daily_limit');
            document.getElementById('thresholdModal').classList.add('show');
        }

        // ==================== UPDATE UNIT HELPER ====================
        function updateUnitHelper(type) {
            const info = thresholdTypes[type];
            if (info) {
                document.getElementById('unitHelper').textContent = '📏 Units: ' + info.unit;
            } else {
                document.getElementById('unitHelper').textContent = '📏 Units: N/A';
            }
        }

        // ==================== TOGGLE THRESHOLD ====================
        function toggleThreshold(id, newStatus) {
            const action = newStatus ? 'enable' : 'disable';
            if (!confirm(`⚠️ Are you sure you want to ${action} this threshold?`)) return;
            
            database.ref('thresholds/' + currentUser.uid + '/' + id).update({
                isActive: newStatus
            }).then(() => {
                showToast(`✅ Threshold ${action}d successfully!`, 'success');
            }).catch((error) => {
                showToast('❌ ' + error.message, 'error');
            });
        }

        // ==================== DELETE THRESHOLD ====================
        function deleteThreshold(id) {
            if (!confirm('🗑️ Are you sure you want to delete this threshold?')) return;
            
            database.ref('thresholds/' + currentUser.uid + '/' + id).remove()
                .then(() => {
                    showToast('🗑️ Threshold deleted successfully!', 'success');
                })
                .catch((error) => {
                    showToast('❌ ' + error.message, 'error');
                });
        }

        // ==================== CLOSE MODAL ====================
        function closeModal() {
            document.getElementById('thresholdModal').classList.remove('show');
        }

        // Close modal on outside click
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        }

        // ==================== UPDATE UNIT ON TYPE CHANGE ====================
        document.getElementById('thresholdType').addEventListener('change', function() {
            updateUnitHelper(this.value);
        });

        // ==================== SUBMIT FORM ====================
        document.getElementById('thresholdForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const id = document.getElementById('thresholdId').value;
            const thresholdType = document.getElementById('thresholdType').value;
            const thresholdValue = parseFloat(document.getElementById('thresholdValue').value);
            
            if (!thresholdValue || thresholdValue <= 0) {
                showToast('⚠️ Please enter a valid threshold value', 'warning');
                return;
            }
            
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            
            try {
                const data = {
                    thresholdType: thresholdType,
                    thresholdValue: thresholdValue,
                    isActive: true,
                    updatedAt: new Date().toISOString()
                };
                
                if (id) {
                    await database.ref('thresholds/' + currentUser.uid + '/' + id).update(data);
                    showToast('✅ Threshold updated successfully!', 'success');
                } else {
                    data.createdAt = new Date().toISOString();
                    await database.ref('thresholds/' + currentUser.uid).push(data);
                    showToast('✅ Threshold added successfully! 🎉', 'success');
                }
                
                closeModal();
                document.getElementById('thresholdForm').reset();
                
            } catch (error) {
                showToast('❌ Error: ' + error.message, 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = id ? '<i class="fas fa-save"></i> Update Threshold 💾' : '<i class="fas fa-save"></i> Save Threshold 💾';
            }
        });

        // ==================== SIDEBAR TOGGLE ====================
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        // ==================== LOGOUT ====================
        function logoutUser() {
            if (confirm('Are you sure you want to logout? 👋')) {
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

        // ==================== EXPOSE FUNCTIONS ====================
        window.toggleSidebar = toggleSidebar;
        window.logoutUser = logoutUser;
        window.openAddModal = openAddModal;
        window.editThreshold = editThreshold;
        window.toggleThreshold = toggleThreshold;
        window.deleteThreshold = deleteThreshold;
        window.closeModal = closeModal;
        window.showToast = showToast;
        window.toggleTheme = toggleTheme;

        console.log('⚙️ Thresholds page loaded with light/dark mode!');
    </script>
</body>
</html>