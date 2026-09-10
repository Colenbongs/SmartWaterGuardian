<?php
/**
 * Smart Water Guardian - Admin Dashboard
 * Complete admin panel with user, device, property management
 * Pending Approvals shown first
 * Self-protection: Admin cannot delete, disable, or message themselves
 * Users count: Only approved users
 * Devices: Linked to real owners via properties
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}

// Check if user has admin privileges
$user_role = $_SESSION['role'] ?? 'consumer';
if ($user_role !== 'system_admin' && $user_role !== 'municipal_admin' && $user_role !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

$admin_name = $_SESSION['firstName'] ?? 'Admin';
$admin_email = $_SESSION['email'] ?? '';
$admin_role = $user_role;
$admin_uid = $_SESSION['user_id'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Smart Water Guardian</title>
    
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-auth-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-database-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-storage-compat.js"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
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
        
        body.light-mode .stat-card {
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid #e2e8f0;
        }
        
        body.light-mode .stat-card .stat-number {
            color: #1a365d;
        }
        
        body.light-mode .stat-card .stat-content h3 {
            color: rgba(0, 0, 0, 0.3);
        }
        
        body.light-mode .stat-card .stat-sub {
            color: rgba(0, 0, 0, 0.2);
        }
        
        body.light-mode .admin-tabs {
            background: rgba(255, 255, 255, 0.6);
            border: 1px solid #e2e8f0;
        }
        
        body.light-mode .admin-tab {
            color: rgba(0, 0, 0, 0.3);
        }
        
        body.light-mode .admin-tab:hover {
            color: #1a365d;
            background: rgba(0, 0, 0, 0.03);
        }
        
        body.light-mode .admin-tab.active {
            color: #1a365d;
            background: linear-gradient(135deg, rgba(0, 212, 255, 0.08), rgba(123, 47, 252, 0.05));
        }
        
        body.light-mode .table-section {
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid #e2e8f0;
        }
        
        body.light-mode .table-section .header h3 {
            color: #1a365d;
        }
        
        body.light-mode .table-section th {
            color: rgba(0, 0, 0, 0.3);
            background: rgba(0, 0, 0, 0.02);
        }
        
        body.light-mode .table-section td {
            color: rgba(0, 0, 0, 0.7);
            border-bottom: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        body.light-mode .table-section tr:hover td {
            background: rgba(0, 0, 0, 0.02);
        }
        
        body.light-mode .search-bar input,
        body.light-mode .search-bar select {
            background: rgba(255, 255, 255, 0.8);
            color: #1a365d;
            border: 1px solid #e2e8f0;
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
        
        body.light-mode .modal-content .form-group input,
        body.light-mode .modal-content .form-group textarea {
            background: rgba(255, 255, 255, 0.8);
            color: #1a365d;
            border: 1px solid #e2e8f0;
        }
        
        body.light-mode .modal-content .form-group input:focus,
        body.light-mode .modal-content .form-group textarea:focus {
            border-color: rgba(0, 100, 200, 0.3);
        }
        
        body.light-mode .modal-content .close {
            color: rgba(0, 0, 0, 0.3);
        }
        
        body.light-mode .modal-content .close:hover {
            color: #1a365d;
        }
        
        body.light-mode .btn-cancel {
            color: rgba(0, 0, 0, 0.3);
            border: 1px solid #e2e8f0;
        }
        
        body.light-mode .btn-cancel:hover {
            background: rgba(0, 0, 0, 0.05);
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
        
        body.light-mode .chart-container {
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid #e2e8f0;
        }
        
        body.light-mode .chart-container h3 {
            color: #1a365d;
        }
        
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
            filter: blur(120px);
            opacity: 0.12;
            animation: floatOrb 20s ease-in-out infinite;
        }
        
        .bg-animation .orb:nth-child(1) {
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, #7fc9ff, transparent 70%);
            top: -200px;
            right: -100px;
            animation-delay: 0s;
        }
        
        .bg-animation .orb:nth-child(2) {
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, #7b2ffc, transparent 70%);
            bottom: -200px;
            left: -100px;
            animation-delay: -7s;
        }
        
        @keyframes floatOrb {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(60px, -40px) scale(1.1); }
            50% { transform: translate(-30px, 50px) scale(0.9); }
            75% { transform: translate(40px, -30px) scale(1.05); }
        }
        
        .sidebar {
            width: 260px;
            background: rgba(4, 8, 18, 0.95);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(127, 201, 255, 0.04);
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
        .sidebar::-webkit-scrollbar-thumb { background: rgba(127, 201, 255, 0.1); border-radius: 10px; }
        
        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 0 24px 24px;
            border-bottom: 1px solid rgba(127, 201, 255, 0.04);
        }
        
        .sidebar-brand .logo-icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: linear-gradient(135deg, #7fc9ff, #7b2ffc);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: #05080f;
            box-shadow: 0 0 30px rgba(127, 201, 255, 0.05);
            animation: pulseGlow 3s ease-in-out infinite;
        }
        
        @keyframes pulseGlow {
            0%, 100% { box-shadow: 0 0 30px rgba(127, 201, 255, 0.05); }
            50% { box-shadow: 0 0 60px rgba(127, 201, 255, 0.08); }
        }
        
        .sidebar-brand .brand-text {
            font-size: 20px;
            font-weight: 800;
            color: #7fc9ff;
        }
        
        .sidebar-nav {
            padding: 16px 12px;
        }
        
        .sidebar-nav .nav-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: rgba(127, 201, 255, 0.15);
            padding: 12px 12px 8px;
            font-weight: 600;
        }
        
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: rgba(127, 201, 255, 0.35);
            text-decoration: none;
            transition: all 0.3s ease;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            position: relative;
        }
        
        .sidebar-nav a:hover {
            color: #7fc9ff;
            background: rgba(127, 201, 255, 0.04);
        }
        
        .sidebar-nav a.active {
            color: #7fc9ff;
            background: rgba(127, 201, 255, 0.06);
            box-shadow: 0 0 30px rgba(127, 201, 255, 0.02);
        }
        
        .sidebar-nav a.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 20%;
            height: 60%;
            width: 3px;
            background: linear-gradient(180deg, #7fc9ff, #7b2ffc);
            border-radius: 0 4px 4px 0;
        }
        
        .sidebar-nav a i { width: 20px; text-align: center; font-size: 16px; }
        .sidebar-nav a .nav-badge {
            margin-left: auto;
            background: rgba(127, 201, 255, 0.08);
            color: #7fc9ff;
            font-size: 10px;
            padding: 2px 10px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .sidebar-nav .logout-link {
            margin-top: 12px;
            border-top: 1px solid rgba(127, 201, 255, 0.04);
            padding-top: 12px;
            color: rgba(255, 100, 100, 0.3);
        }
        .sidebar-nav .logout-link:hover { color: #ff6b6b; background: rgba(255, 100, 100, 0.05); }
        
        .sidebar-footer {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            padding: 0 24px;
            font-size: 11px;
            color: rgba(127, 201, 255, 0.08);
            text-align: center;
            letter-spacing: 1px;
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
            background: rgba(255,255,255,0.02);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            border: 1px solid rgba(127, 201, 255, 0.04);
            flex-wrap: wrap;
            gap: 12px;
            transition: background 0.3s ease;
        }
        
        .topbar-left h2 {
            font-size: 24px;
            font-weight: 700;
            color: #7fc9ff;
        }
        
        .topbar-left p {
            color: rgba(127, 201, 255, 0.25);
            font-size: 14px;
        }
        
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        
        .date-display {
            color: rgba(127, 201, 255, 0.25);
            font-size: 13px;
            padding: 6px 16px;
            background: rgba(127, 201, 255, 0.03);
            border-radius: 50px;
            border: 1px solid rgba(127, 201, 255, 0.04);
        }
        
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #00ff88;
            display: inline-block;
            animation: pulseDot 2s infinite;
            box-shadow: 0 0 20px rgba(0, 255, 136, 0.15);
        }
        
        @keyframes pulseDot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.8); }
        }
        
        .status-text {
            font-size: 13px;
            font-weight: 500;
            color: rgba(0, 255, 136, 0.6);
        }
        
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: #7fc9ff;
            cursor: pointer;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }
        
        .stat-card {
            background: rgba(255,255,255,0.02);
            backdrop-filter: blur(10px);
            border-radius: 14px;
            padding: 18px 20px;
            border: 1px solid rgba(127, 201, 255, 0.04);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            border-color: rgba(127, 201, 255, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(127, 201, 255, 0.02);
        }
        
        .stat-card .stat-icon {
            font-size: 24px;
            display: block;
            margin-bottom: 6px;
        }
        
        .stat-card .stat-content h3 {
            font-size: 10px;
            font-weight: 600;
            color: rgba(127, 201, 255, 0.3);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-card .stat-number {
            font-size: 26px;
            font-weight: 700;
            color: white;
        }
        
        .stat-card .stat-sub {
            font-size: 11px;
            color: rgba(127, 201, 255, 0.2);
            margin-top: 2px;
        }
        
        .chart-container {
            background: rgba(255,255,255,0.02);
            backdrop-filter: blur(10px);
            border-radius: 14px;
            padding: 20px;
            border: 1px solid rgba(127, 201, 255, 0.04);
            margin-bottom: 24px;
        }
        
        .chart-container h3 {
            color: #7fc9ff;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 16px;
        }
        
        .chart-container h3 i {
            margin-right: 8px;
        }
        
        .chart-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        
        .chart-box {
            height: 280px;
        }
        
        .admin-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            flex-wrap: wrap;
            background: rgba(255,255,255,0.02);
            backdrop-filter: blur(10px);
            padding: 6px;
            border-radius: 12px;
            border: 1px solid rgba(127, 201, 255, 0.04);
        }
        
        .admin-tab {
            padding: 10px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            color: rgba(127, 201, 255, 0.3);
            background: transparent;
            transition: all 0.3s ease;
        }
        
        .admin-tab:hover {
            color: #7fc9ff;
            background: rgba(127, 201, 255, 0.04);
        }
        
        .admin-tab.active {
            background: linear-gradient(135deg, rgba(127, 201, 255, 0.08), rgba(123, 47, 252, 0.05));
            color: #7fc9ff;
        }
        
        .admin-tab i {
            margin-right: 8px;
        }
        
        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .table-section {
            background: rgba(255,255,255,0.02);
            backdrop-filter: blur(10px);
            border-radius: 14px;
            padding: 20px;
            border: 1px solid rgba(127, 201, 255, 0.04);
            overflow-x: auto;
        }
        
        .table-section .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 12px;
        }
        
        .table-section .header h3 {
            font-size: 16px;
            color: #7fc9ff;
        }
        
        .search-bar {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .search-bar input,
        .search-bar select {
            padding: 8px 14px;
            border: 1px solid rgba(127, 201, 255, 0.06);
            border-radius: 8px;
            font-size: 13px;
            background: rgba(127, 201, 255, 0.02);
            color: #b8e6ff;
            transition: all 0.3s ease;
        }
        
        .search-bar input:focus,
        .search-bar select:focus {
            outline: none;
            border-color: rgba(127, 201, 255, 0.15);
        }
        
        .search-bar input::placeholder {
            color: rgba(127, 201, 255, 0.08);
        }
        
        .search-bar select option {
            background: #040812;
            color: #b8e6ff;
        }
        
        .btn {
            padding: 8px 18px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #7fc9ff, #7b2ffc);
            color: #05080f;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(127, 201, 255, 0.08);
        }
        
        .btn-danger {
            background: rgba(255, 107, 107, 0.1);
            color: #ff6b6b;
            border: 1px solid rgba(255, 107, 107, 0.08);
        }
        
        .btn-danger:hover {
            background: rgba(255, 107, 107, 0.15);
        }
        
        .btn-success {
            background: rgba(0, 255, 136, 0.1);
            color: #00ff88;
            border: 1px solid rgba(0, 255, 136, 0.08);
        }
        
        .btn-success:hover {
            background: rgba(0, 255, 136, 0.15);
        }
        
        .btn-warning {
            background: rgba(255, 215, 0, 0.1);
            color: #ffd700;
            border: 1px solid rgba(255, 215, 0, 0.08);
        }
        
        .btn-warning:hover {
            background: rgba(255, 215, 0, 0.15);
        }
        
        .table-section table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        
        .table-section th {
            text-align: left;
            padding: 10px 12px;
            background: rgba(127, 201, 255, 0.02);
            border-bottom: 1px solid rgba(127, 201, 255, 0.04);
            font-weight: 600;
            color: rgba(127, 201, 255, 0.3);
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .table-section td {
            padding: 10px 12px;
            border-bottom: 1px solid rgba(127, 201, 255, 0.02);
            color: rgba(255,255,255,0.7);
            vertical-align: middle;
        }
        
        .table-section tr:hover td {
            background: rgba(127, 201, 255, 0.02);
        }
        
        .table-section .actions {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
        }
        
        .table-section .actions button {
            padding: 4px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-edit {
            background: rgba(127, 201, 255, 0.08);
            color: #7fc9ff;
        }
        .btn-edit:hover { background: rgba(127, 201, 255, 0.15); }
        
        .btn-delete {
            background: rgba(255, 107, 107, 0.08);
            color: #ff6b6b;
        }
        .btn-delete:hover { background: rgba(255, 107, 107, 0.15); }
        
        .btn-message {
            background: rgba(127, 201, 255, 0.06);
            color: #7fc9ff;
        }
        .btn-message:hover { background: rgba(127, 201, 255, 0.12); }
        
        .btn-disable {
            background: rgba(255, 107, 107, 0.08);
            color: #ff6b6b;
        }
        .btn-disable:hover { background: rgba(255, 107, 107, 0.15); }
        
        .btn-enable {
            background: rgba(0, 255, 136, 0.08);
            color: #00ff88;
        }
        .btn-enable:hover { background: rgba(0, 255, 136, 0.15); }
        
        .status-badge {
            padding: 2px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 500;
        }
        
        .status-active { background: rgba(0, 255, 136, 0.08); color: #00ff88; }
        .status-inactive { background: rgba(255, 107, 107, 0.08); color: #ff6b6b; }
        .status-online { background: rgba(0, 255, 136, 0.08); color: #00ff88; }
        .status-offline { background: rgba(255, 107, 107, 0.08); color: #ff6b6b; }
        
        .role-badge {
            padding: 2px 10px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 500;
        }
        .role-system_admin { background: rgba(127, 201, 255, 0.1); color: #7fc9ff; }
        .role-municipal_admin { background: rgba(123, 47, 252, 0.1); color: #7b2ffc; }
        .role-consumer { background: rgba(0, 255, 136, 0.08); color: #00ff88; }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
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
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        
        .modal-content {
            background: rgba(4, 8, 18, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            padding: 32px;
            max-width: 500px;
            width: 100%;
            border: 1px solid rgba(127, 201, 255, 0.06);
            box-shadow: 0 0 60px rgba(127, 201, 255, 0.02);
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-content .close {
            float: right;
            font-size: 28px;
            cursor: pointer;
            color: rgba(127, 201, 255, 0.2);
            transition: all 0.3s ease;
            background: none;
            border: none;
        }
        
        .modal-content .close:hover {
            color: #7fc9ff;
            transform: rotate(90deg);
        }
        
        .modal-content h2 {
            color: #7fc9ff;
            margin-bottom: 16px;
            font-size: 22px;
        }
        
        .modal-content .form-group {
            margin-bottom: 16px;
        }
        
        .modal-content .form-group label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            color: rgba(127, 201, 255, 0.4);
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .modal-content .form-group input,
        .modal-content .form-group textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid rgba(127, 201, 255, 0.06);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: rgba(127, 201, 255, 0.02);
            color: #b8e6ff;
        }
        
        .modal-content .form-group input:focus,
        .modal-content .form-group textarea:focus {
            outline: none;
            border-color: rgba(127, 201, 255, 0.15);
        }
        
        .modal-content .form-group textarea {
            resize: vertical;
            min-height: 80px;
            font-family: inherit;
        }
        
        .modal-content .btn-submit {
            padding: 10px 30px;
            background: linear-gradient(135deg, #7fc9ff, #7b2ffc);
            color: #05080f;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .modal-content .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(127, 201, 255, 0.08);
        }
        
        .modal-content .btn-cancel {
            padding: 10px 30px;
            background: rgba(127, 201, 255, 0.04);
            color: rgba(127, 201, 255, 0.3);
            border: 1px solid rgba(127, 201, 255, 0.06);
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-left: 8px;
        }
        
        .modal-content .btn-cancel:hover {
            background: rgba(127, 201, 255, 0.08);
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
        
        .toast-success { background: rgba(0, 255, 136, 0.1); border: 1px solid #00ff88; color: #00ff88; }
        .toast-error { background: rgba(255, 107, 107, 0.1); border: 1px solid #ff6b6b; color: #ff6b6b; }
        .toast-info { background: rgba(127, 201, 255, 0.08); border: 1px solid #7fc9ff; color: #7fc9ff; }
        .toast-warning { background: rgba(255, 215, 0, 0.08); border: 1px solid #ffd700; color: #ffd700; }
        
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(50px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        @media (max-width: 1200px) {
            .stats-cards {
                grid-template-columns: repeat(2, 1fr);
            }
            .chart-grid {
                grid-template-columns: 1fr;
            }
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
            .stats-cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .stats-cards {
                grid-template-columns: 1fr;
            }
            .admin-tabs {
                flex-direction: column;
            }
            .admin-tab {
                text-align: center;
            }
            .search-bar {
                flex-direction: column;
                width: 100%;
            }
            .search-bar input {
                width: 100%;
                min-width: unset;
            }
            .topbar {
                flex-direction: column;
                text-align: center;
                padding: 16px;
            }
            .topbar-left h2 {
                font-size: 22px;
            }
            .modal-content {
                padding: 20px;
                margin: 10px;
            }
            .chart-box {
                height: 200px;
            }
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
            <div class="brand-text">Smart<span style="color:rgba(127,201,255,0.2);">Water</span></div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-label">Main Menu</div>
            <a href="admin.php" class="active">
                <i class="fas fa-cog"></i> Admin
            </a>
            <a href="alerts.php">
                <i class="fas fa-bell"></i> Alerts
                <span class="nav-badge" id="alertBadge">0</span>
            </a>
            <a href="reviews.php">
                <i class="fas fa-star"></i> Reviews
            </a>
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

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <button class="menu-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h2>Admin Dashboard</h2>
                <p>Welcome back, <?php echo htmlspecialchars($admin_name); ?>!</p>
            </div>
            <div class="topbar-right">
                <button onclick="simulateESP32Data()" class="btn btn-warning" style="background: rgba(255, 215, 0, 0.1); border: 1px solid #ffd700; color: #ffd700; border-radius: 50px; padding: 6px 16px; margin-right: 15px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-microchip"></i> Trigger ESP32
                </button>
                
                <span class="date-display">
                    <i class="far fa-calendar-alt"></i> 
                    <?php echo date('l, F j, Y'); ?>
                </span>
                <span class="status-dot"></span>
                <span class="status-text">System Online</span>
            </div>
        </header>

        <!-- Stats Cards -->
        <section class="stats-cards" id="statsContainer">
            <div class="stat-card">
                <span class="stat-icon"><i class="fas fa-users"></i></span>
                <div class="stat-content">
                    <h3>Total Users</h3>
                    <div class="stat-number" id="totalUsers">0</div>
                    <div class="stat-sub" id="approvedUsers">Approved: 0</div>
                </div>
            </div>
            <div class="stat-card">
                <span class="stat-icon"><i class="fas fa-home"></i></span>
                <div class="stat-content">
                    <h3>Properties</h3>
                    <div class="stat-number" id="totalProperties">0</div>
                    <div class="stat-sub">Total registered</div>
                </div>
            </div>
            <div class="stat-card">
                <span class="stat-icon"><i class="fas fa-microchip"></i></span>
                <div class="stat-content">
                    <h3>Devices</h3>
                    <div class="stat-number" id="totalDevices">0</div>
                    <div class="stat-sub" id="onlineDevices">Online: 0</div>
                </div>
            </div>
        </section>

        <!-- Admin Tabs -->
        <div class="admin-tabs">
            <button class="admin-tab active" data-tab="approvals">
                <i class="fas fa-check-circle"></i> Approvals
                <span class="nav-badge" id="approvalBadge">0</span>
            </button>
            <button class="admin-tab" data-tab="users">
                <i class="fas fa-users"></i> Users
            </button>
            <button class="admin-tab" data-tab="properties">
                <i class="fas fa-home"></i> Properties
            </button>
            <button class="admin-tab" data-tab="devices">
                <i class="fas fa-microchip"></i> Devices
            </button>
            <button class="admin-tab" data-tab="alerts">
                <i class="fas fa-bell"></i> Alerts
            </button>
        </div>

        <!-- APPROVALS TAB -->
        <div id="tab-approvals" class="tab-content active">
            <div class="table-section">
                <div class="header">
                    <h3>Pending Approvals</h3>
                    <div class="search-bar">
                        <input type="text" id="approvalSearch" placeholder="Search..." onkeyup="filterApprovals()">
                    </div>
                </div>
                <div id="approvalsTableContainer">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th>Role</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="approvalsTableBody">
                            <tr><td colspan="7" style="text-align:center;padding:40px;color:rgba(127,201,255,0.15);">
                                <i class="fas fa-spinner fa-spin"></i> Loading pending approvals...
                            </td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- USERS TAB -->
        <div id="tab-users" class="tab-content">
            <div class="table-section">
                <div class="header">
                    <h3>All Users (Approved Only)</h3>
                    <div class="search-bar">
                        <input type="text" id="userSearch" placeholder="Search users..." onkeyup="filterUsers()">
                        <select id="userRoleFilter" onchange="filterUsers()">
                            <option value="all">All Roles</option>
                            <option value="consumer">Consumer</option>
                            <option value="system_admin">Admin</option>
                            <option value="municipal_admin">Municipal Admin</option>
                        </select>
                    </div>
                </div>
                <div id="usersTableContainer">
                    <table>
                        <thead>
                            <tr>
                                <th>Avatar</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Meter</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <tr><td colspan="7" style="text-align:center;padding:40px;color:rgba(127,201,255,0.15);">
                                <i class="fas fa-spinner fa-spin"></i> Loading users...
                            </td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- PROPERTIES TAB -->
        <div id="tab-properties" class="tab-content">
            <div class="table-section">
                <div class="header">
                    <h3>Properties</h3>
                    <div class="search-bar">
                        <input type="text" id="propertySearch" placeholder="Search properties..." onkeyup="filterProperties()">
                    </div>
                </div>
                <div id="propertiesTableContainer">
                    <table>
                        <thead>
                            <tr>
                                <th>Property Name</th>
                                <th>Address</th>
                                <th>Meter ID</th>
                                <th>Owner</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="propertiesTableBody">
                            <tr><td colspan="6" style="text-align:center;padding:40px;color:rgba(127,201,255,0.15);">
                                <i class="fas fa-spinner fa-spin"></i> Loading properties...
                            </td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- DEVICES TAB -->
        <div id="tab-devices" class="tab-content">
            <div class="table-section">
                <div class="header">
                    <h3>All Devices</h3>
                    <div class="search-bar">
                        <input type="text" id="deviceSearch" placeholder="Search devices..." onkeyup="filterDevices()">
                        <select id="deviceStatusFilter" onchange="filterDevices()">
                            <option value="all">All Status</option>
                            <option value="online">Online</option>
                            <option value="offline">Offline</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                </div>
                <div id="devicesTableContainer">
                    <table>
                        <thead>
                            <tr>
                                <th>Meter ID</th>
                                <th>Model</th>
                                <th>Property</th>
                                <th>Owner</th>
                                <th>Status</th>
                                <th>Battery</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="devicesTableBody">
                            <tr><td colspan="7" style="text-align:center;padding:40px;color:rgba(127,201,255,0.15);">
                                <i class="fas fa-spinner fa-spin"></i> Loading devices...
                            </td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ALERTS TAB -->
        <div id="tab-alerts" class="tab-content">
            <div class="table-section">
                <div class="header">
                    <h3>System Alerts</h3>
                    <div class="search-bar">
                        <select id="alertSeverityFilter" onchange="filterAlerts()">
                            <option value="all">All Severity</option>
                            <option value="critical">Critical</option>
                            <option value="warning">Warning</option>
                            <option value="info">Info</option>
                        </select>
                    </div>
                </div>
                <div id="alertsTableContainer">
                    <table>
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Message</th>
                                <th>Severity</th>
                                <th>User</th>
                                <th>Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="alertsTableBody">
                            <tr><td colspan="6" style="text-align:center;padding:40px;color:rgba(127,201,255,0.15);">
                                <i class="fas fa-spinner fa-spin"></i> Loading alerts...
                            </td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="chart-container" style="margin-top: 24px;">
            <h3><i class="fas fa-chart-bar"></i> Water Usage Analytics</h3>
            <div class="chart-grid">
                <div class="chart-box">
                    <canvas id="usageChart"></canvas>
                </div>
                <div class="chart-box">
                    <canvas id="userChart"></canvas>
                </div>
            </div>
        </div>
    </main>

    <div id="messageModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('messageModal')">&times;</span>
            <h2>Send Message</h2>
            <form id="messageForm">
                <div class="form-group">
                    <label>To:</label>
                    <input type="text" id="messageRecipient" readonly>
                </div>
                <div class="form-group">
                    <label>Subject:</label>
                    <input type="text" id="messageSubject" placeholder="Enter subject" required>
                </div>
                <div class="form-group">
                    <label>Message:</label>
                    <textarea id="messageBody" placeholder="Type your message here..." required></textarea>
                </div>
                <button type="submit" class="btn-submit">Send Message</button>
                <button type="button" class="btn-cancel" onclick="closeModal('messageModal')">Cancel</button>
            </form>
        </div>
    </div>

    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('deleteModal')">&times;</span>
            <h2 style="color:#ff6b6b;">Delete User</h2>
            <p style="color:rgba(255,255,255,0.6);margin-bottom:20px;">
                Are you sure you want to delete <strong id="deleteUserName"></strong>?<br>
                This action cannot be undone and will permanently remove:
            </p>
            <ul style="color:rgba(255,255,255,0.4);margin-bottom:20px;padding-left:20px;">
                <li>All user data</li>
                <li>Properties and devices</li>
                <li>Alerts and messages</li>
                <li>Reviews</li>
            </ul>
            <p style="color:rgba(255,255,255,0.3);font-size:13px;margin-bottom:16px;">
                A notification email will be sent to the user.
            </p>
            <button class="btn-submit" style="background:linear-gradient(135deg,#ff6b6b,#e53e3e);" onclick="confirmDelete()">
                Yes, Delete User
            </button>
            <button type="button" class="btn-cancel" onclick="closeModal('deleteModal')">Cancel</button>
        </div>
    </div>

    <div class="toast-container" id="toastContainer"></div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        const storage = firebase.storage();

        // ============================================================
        // GLOBAL VARIABLES
        // ============================================================
        let allUsers = [];
        let allProperties = [];
        let allDevices = [];
        let allAlerts = [];
        let currentUser = null;
        let deleteTargetUid = null;
        let usageChart = null;
        let userChart = null;
        const ADMIN_UID = '<?php echo $admin_uid; ?>';

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
        // AUTH STATE
        // ============================================================
        auth.onAuthStateChanged(function(user) {
            if (user) {
                currentUser = user;
                console.log('Admin logged in:', user.uid);
                loadAllData();
                updateAlertBadge();
            } else {
                window.location.href = 'login.php';
            }
        });

        // ============================================================
        // ALERT BADGE
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
        // TOAST NOTIFICATIONS
        // ============================================================
        function showToast(message, type) {
            type = type || 'success';
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = 'toast toast-' + type;
            toast.textContent = message;
            container.appendChild(toast);
            setTimeout(function() {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.3s ease';
                setTimeout(function() { toast.remove(); }, 300);
            }, 5000);
        }

        // ============================================================
        // LOAD ALL DATA FROM FIREBASE
        // ============================================================
        function loadAllData() {
            loadUsers();
            loadProperties();
            loadDevices();
            loadAlerts();
        }

        // ============================================================
        // LOAD USERS FROM FIREBASE
        // ============================================================
        function loadUsers() {
            const usersRef = database.ref('users');
            usersRef.on('value', function(snapshot) {
                const data = snapshot.val();
                allUsers = [];
                if (data) {
                    for (let uid in data) {
                        allUsers.push({
                            uid: uid,
                            ...data[uid]
                        });
                    }
                }
                
                // Load avatars
                allUsers.forEach(function(user) {
                    if (user.uid) {
                        var avatarRef = storage.ref('avatars/' + user.uid + '.jpg');
                        avatarRef.getDownloadURL().then(function(url) {
                            user.avatarUrl = url;
                            renderUsers(getApprovedUsers());
                        }).catch(function() {
                            renderUsers(getApprovedUsers());
                        });
                    }
                });
                
                renderUsers(getApprovedUsers());
                updateStats();
                updateCharts();
                loadPendingApprovals();
            });
        }

        // ============================================================
        // GET APPROVED USERS ONLY
        // ============================================================
        function getApprovedUsers() {
            return allUsers.filter(function(user) {
                return user.is_approved === true;
            });
        }

        // ============================================================
        // RENDER USERS (Only Approved)
        // ============================================================
        function renderUsers(users) {
            var tbody = document.getElementById('usersTableBody');
            if (!users || users.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px;color:rgba(127,201,255,0.15);">No approved users found</td></tr>';
                return;
            }
            
            var html = '';
            users.forEach(function(user) {
                var roleClass = user.role || 'consumer';
                var statusClass = user.is_active !== false ? 'active' : 'inactive';
                var statusText = user.is_active !== false ? 'Active' : 'Inactive';
                var meterNum = user.meterNumber || 'N/A';
                var avatarHtml = user.avatarUrl ? 
                    '<img src="' + user.avatarUrl + '" style="width:32px;height:32px;border-radius:50%;object-fit:cover;">' :
                    '<div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#7fc9ff,#7b2ffc);display:flex;align-items:center;justify-content:center;color:white;font-weight:700;">' + (user.firstName || 'U').charAt(0) + '</div>';
                
                var isSelf = (user.uid === ADMIN_UID);
                
                html += `
                    <tr>
                        <td>${avatarHtml}</td>
                        <td><strong>${user.firstName || ''} ${user.lastName || ''} ${isSelf ? '<span style="color:#7fc9ff;font-size:10px;"> (You)</span>' : ''}</strong></td>
                        <td>${user.email || ''}</td>
                        <td><span style="color:#7fc9ff;font-family:monospace;font-size:12px;">${meterNum}</span></td>
                        <td><span class="role-badge role-${roleClass}">${roleClass.replace('_', ' ').toUpperCase()}</span></td>
                        <td><span class="status-badge status-${statusClass}">${statusText}</span></td>
                        <td>
                            <div class="actions">
                                <button class="btn-message" onclick="openMessageModal('${user.uid}', '${user.email}')" ${isSelf ? 'disabled style="opacity:0.3;cursor:not-allowed;"' : ''}>
                                    Message
                                </button>
                                <button class="btn-${user.is_active !== false ? 'disable' : 'enable'}" 
                                        onclick="toggleUser('${user.uid}', ${user.is_active !== false ? '0' : '1'})"
                                        ${isSelf ? 'disabled style="opacity:0.3;cursor:not-allowed;"' : ''}>
                                    ${user.is_active !== false ? 'Disable' : 'Enable'}
                                </button>
                                <button class="btn-delete" onclick="openDeleteModal('${user.uid}', '${user.firstName || ''} ${user.lastName || ''}')" 
                                        ${isSelf ? 'disabled style="opacity:0.3;cursor:not-allowed;"' : ''}>
                                    Delete
                                </button>
                                ${isSelf ? '<span style="color:rgba(127,201,255,0.2);font-size:10px;margin-left:4px;">(Cannot modify self)</span>' : ''}
                            </div>
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }

        // ============================================================
        // LOAD PENDING APPROVALS
        // ============================================================
        function loadPendingApprovals() {
            var pending = allUsers.filter(function(user) {
                var isAdmin = user.role === 'system_admin' || user.role === 'municipal_admin' || user.role === 'admin';
                return user.is_approved === false && !isAdmin;
            });
            
            allPendingApprovals = pending;
            renderApprovals(pending);
            document.getElementById('approvalBadge').textContent = pending.length;
        }

        // ============================================================
        // RENDER APPROVALS
        // ============================================================
        function renderApprovals(users) {
            var tbody = document.getElementById('approvalsTableBody');
            if (!users || users.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px;color:rgba(127,201,255,0.15);">No pending approvals</td></tr>';
                return;
            }
            
            var html = '';
            users.forEach(function(user) {
                var roleClass = user.role || 'consumer';
                var date = user.createdAt ? new Date(user.createdAt).toLocaleDateString() : 'N/A';
                
                html += `
                    <tr>
                        <td><strong>${user.firstName || ''} ${user.lastName || ''}</strong></td>
                        <td>${user.email || ''}</td>
                        <td>${user.phone || 'N/A'}</td>
                        <td>${user.address || 'N/A'}</td>
                        <td><span class="role-badge role-${roleClass}">${roleClass.replace('_', ' ').toUpperCase()}</span></td>
                        <td>${date}</td>
                        <td>
                            <div class="actions">
                                <button class="btn-success" onclick="approveUser('${user.uid}')">
                                    Approve
                                </button>
                                <button class="btn-delete" onclick="rejectUser('${user.uid}')">
                                    Reject
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }

        // ============================================================
        // APPROVE USER
        // ============================================================
        function approveUser(uid) {
            if (!confirm('Approve this user? They will receive an email notification.')) return;
            
            database.ref('users/' + uid).update({
                is_approved: true,
                approved_at: new Date().toISOString()
            }).then(function() {
                return database.ref('users/' + uid).once('value');
            }).then(function(snapshot) {
                var userData = snapshot.val();
                if (userData && userData.email) {
                    return fetch('../api/send-notification.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            email: userData.email,
                            type: 'account_approved',
                            name: userData.firstName || 'User'
                        })
                    });
                }
            }).then(function() {
                showToast('User approved! Approval email sent.', 'success');
                loadPendingApprovals();
                loadUsers();
            }).catch(function(error) {
                showToast('Error: ' + error.message, 'error');
            });
        }

        // ============================================================
        // REJECT USER
        // ============================================================
        function rejectUser(uid) {
            if (!confirm('Reject this user? They will receive a notification.')) return;
            
            var reason = prompt('Reason for rejection (optional):');
            
            database.ref('users/' + uid).once('value').then(function(snapshot) {
                var userData = snapshot.val();
                if (userData && userData.email) {
                    return fetch('../api/send-notification.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            email: userData.email,
                            type: 'account_rejected',
                            name: userData.firstName || 'User',
                            reason: reason || 'Not specified'
                        })
                    });
                }
            }).then(function() {
                return database.ref('users/' + uid).remove();
            }).then(function() {
                showToast('User rejected. Notification sent.', 'success');
                loadPendingApprovals();
                loadUsers();
            }).catch(function(error) {
                showToast('Error: ' + error.message, 'error');
            });
        }

        // ============================================================
        // FILTER APPROVALS
        // ============================================================
        function filterApprovals() {
            var search = document.getElementById('approvalSearch').value.toLowerCase();
            var filtered = allPendingApprovals;
            if (search) {
                filtered = filtered.filter(function(u) {
                    return (u.first_name || '').toLowerCase().includes(search) ||
                           (u.last_name || '').toLowerCase().includes(search) ||
                           (u.email || '').toLowerCase().includes(search);
                });
            }
            renderApprovals(filtered);
        }

        // ============================================================
        // LOAD PROPERTIES
        // ============================================================
        function loadProperties() {
            var propertiesRef = database.ref('properties');
            propertiesRef.on('value', function(snapshot) {
                var data = snapshot.val();
                allProperties = [];
                if (data) {
                    for (var uid in data) {
                        var userProps = data[uid];
                        if (userProps) {
                            for (var propId in userProps) {
                                var owner = allUsers.find(function(u) { return u.uid === uid; });
                                if (owner && owner.is_approved === true) {
                                    allProperties.push({
                                        id: propId,
                                        ownerId: uid,
                                        ownerName: (owner.firstName || '') + ' ' + (owner.lastName || ''),
                                        ...userProps[propId]
                                    });
                                }
                            }
                        }
                    }
                }
                renderProperties(allProperties);
                updateStats();
            });
        }

        // ============================================================
        // RENDER PROPERTIES
        // ============================================================
        function renderProperties(properties) {
            var tbody = document.getElementById('propertiesTableBody');
            if (!properties || properties.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:40px;color:rgba(127,201,255,0.15);">No properties found</td></tr>';
                return;
            }
            
            var html = '';
            properties.forEach(function(prop) {
                html += `
                    <tr>
                        <td><strong>${prop.propertyName || 'Unnamed'}</strong></td>
                        <td>${prop.address || 'No address'}</td>
                        <td><span style="color:#7fc9ff;font-family:monospace;">${prop.meterId || 'No meter'}</span></td>
                        <td>${prop.ownerName}</td>
                        <td><span class="status-badge ${prop.is_active !== false ? 'status-active' : 'status-inactive'}">${prop.is_active !== false ? 'Active' : 'Inactive'}</span></td>
                        <td>
                            <div class="actions">
                                <button class="btn-delete" onclick="deleteProperty('${prop.id}', '${prop.ownerId}')">Delete</button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }

        // ============================================================
        // LOAD DEVICES - LINKED TO REAL OWNERS VIA PROPERTIES
        // ============================================================
        function loadDevices() {
            var devicesRef = database.ref('meters');
            devicesRef.on('value', function(snapshot) {
                var data = snapshot.val();
                allDevices = [];
                if (data) {
                    for (var meterId in data) {
                        var device = data[meterId];
                        var propertyName = 'Unassigned';
                        var ownerName = 'Unknown';
                        var ownerUid = null;
                        
                        // Find property and owner by matching propertyId
                        if (device.propertyId) {
                            var foundProperty = null;
                            for (var i = 0; i < allProperties.length; i++) {
                                var p = allProperties[i];
                                if (p.id === device.propertyId) {
                                    foundProperty = p;
                                    break;
                                }
                            }
                            
                            if (foundProperty) {
                                propertyName = foundProperty.propertyName || 'Unnamed';
                                ownerName = foundProperty.ownerName || 'Unknown';
                                ownerUid = foundProperty.ownerId;
                            }
                        }
                        
                        // Only include devices where owner is approved OR device is unassigned
                        var ownerApproved = false;
                        if (ownerUid) {
                            var owner = allUsers.find(function(u) { return u.uid === ownerUid; });
                            if (owner && owner.is_approved === true) {
                                ownerApproved = true;
                            }
                        }
                        
                        // Include device if owner is approved OR it's unassigned
                        if (ownerApproved || propertyName === 'Unassigned') {
                            allDevices.push({
                                meterId: meterId,
                                propertyName: propertyName,
                                ownerName: ownerName,
                                ownerUid: ownerUid,
                                ...device
                            });
                        }
                    }
                }
                renderDevices(allDevices);
                updateStats();
            });
        }

        // ============================================================
        // RENDER DEVICES
        // ============================================================
        function renderDevices(devices) {
            var tbody = document.getElementById('devicesTableBody');
            if (!devices || devices.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px;color:rgba(127,201,255,0.15);">No devices registered</td></tr>';
                return;
            }
            
            var html = '';
            devices.forEach(function(device) {
                var reading = device.lastReading || {};
                var status = reading.status || 'offline';
                var battery = reading.battery !== undefined ? Number(reading.battery).toFixed(0) : '--';
                var displayBattery = battery + '%';
                var ownerDisplay = device.ownerName || 'Unknown';
                
                html += `
                    <tr>
                        <td><span style="color:#7fc9ff;font-family:monospace;">${device.meterId}</span></td>
                        <td>${device.model || 'ESP32'}</td>
                        <td>${device.propertyName}</td>
                        <td>${ownerDisplay}</td>
                        <td><span class="status-badge status-${status}">${status.toUpperCase()}</span></td>
                        <td>${displayBattery}</td>
                        <td>
                            <div class="actions">
                                <button class="btn-delete" onclick="deleteDevice('${device.meterId}')">Delete</button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }

        // ============================================================
        // LOAD ALERTS
        // ============================================================
        function loadAlerts() {
            var alertsRef = database.ref('alerts');
            alertsRef.on('value', function(snapshot) {
                var data = snapshot.val();
                allAlerts = [];
                if (data) {
                    for (var uid in data) {
                        var userAlerts = data[uid];
                        if (userAlerts) {
                            for (var alertId in userAlerts) {
                                var user = allUsers.find(function(u) { return u.uid === uid; });
                                if (user && user.is_approved === true) {
                                    allAlerts.push({
                                        id: alertId,
                                        userId: uid,
                                        userEmail: user ? user.email : uid,
                                        ...userAlerts[alertId]
                                    });
                                }
                            }
                        }
                    }
                }
                allAlerts.sort(function(a, b) { return new Date(b.timestamp) - new Date(a.timestamp); });
                renderAlerts(allAlerts);
                updateStats();
            });
        }

        // ============================================================
        // RENDER ALERTS
        // ============================================================
        function renderAlerts(alerts) {
            var tbody = document.getElementById('alertsTableBody');
            if (!alerts || alerts.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:40px;color:rgba(127,201,255,0.15);">No alerts</td></tr>';
                return;
            }
            
            var html = '';
            alerts.slice(0, 50).forEach(function(alert) {
                var severityColors = {
                    critical: 'status-overdue',
                    warning: 'status-warning',
                    info: 'status-active'
                };
                var severityClass = severityColors[alert.severity] || 'status-active';
                var severityLabel = alert.severity || 'info';
                
                html += `
                    <tr>
                        <td>${alert.type || 'Notification'}</td>
                        <td>${alert.message || ''}</td>
                        <td><span class="status-badge ${severityClass}">${severityLabel.toUpperCase()}</span></td>
                        <td>${alert.userEmail}</td>
                        <td>${alert.timestamp ? new Date(alert.timestamp).toLocaleString() : 'N/A'}</td>
                        <td>
                            <div class="actions">
                                <button class="btn-success" onclick="resolveAlert('${alert.id}', '${alert.userId}')">Resolve</button>
                                <button class="btn-delete" onclick="deleteAlert('${alert.id}', '${alert.userId}')">Delete</button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }

        // ============================================================
        // UPDATE STATS
        // ============================================================
        function updateStats() {
            var approvedUsers = getApprovedUsers();
            document.getElementById('totalUsers').textContent = approvedUsers.length;
            document.getElementById('approvedUsers').textContent = 'Approved: ' + approvedUsers.length;
            document.getElementById('totalProperties').textContent = allProperties.length;
            document.getElementById('totalDevices').textContent = allDevices.length;
            
            var online = allDevices.filter(function(d) {
                var reading = d.lastReading || {};
                return reading.status === 'online';
            }).length;
            document.getElementById('onlineDevices').textContent = 'Online: ' + online;
        }

        // ============================================================
        // UPDATE CHARTS
        // ============================================================
        function updateCharts() {
            var usageCtx = document.getElementById('usageChart').getContext('2d');
            if (usageChart) usageChart.destroy();
            
            var approvedUsers = getApprovedUsers();
            var userNames = approvedUsers.slice(0, 8).map(function(u) { return u.firstName || 'User'; });
            var usageData = approvedUsers.slice(0, 8).map(function() { return Math.floor(Math.random() * 500 + 100); });
            
            if (userNames.length === 0) {
                userNames = ['No Users'];
                usageData = [0];
            }
            
            usageChart = new Chart(usageCtx, {
                type: 'bar',
                data: {
                    labels: userNames,
                    datasets: [{
                        label: 'Water Usage (L)',
                        data: usageData,
                        backgroundColor: ['#7fc9ff', '#7b2ffc', '#00ff88', '#ffd700', '#ff6b6b', '#00d4ff', '#7b2ffc', '#7fc9ff'],
                        borderColor: 'transparent',
                        borderRadius: 8,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { labels: { color: 'rgba(255,255,255,0.5)' } },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.y + ' Liters';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(255,255,255,0.03)' },
                            ticks: { color: 'rgba(255,255,255,0.3)' }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: 'rgba(255,255,255,0.3)' }
                        }
                    }
                }
            });

            var userCtx = document.getElementById('userChart').getContext('2d');
            if (userChart) userChart.destroy();
            
            var consumerCount = approvedUsers.filter(function(u) { return u.role === 'consumer' || !u.role; }).length;
            var adminCount = approvedUsers.filter(function(u) { return u.role === 'system_admin' || u.role === 'municipal_admin' || u.role === 'admin'; }).length;
            
            userChart = new Chart(userCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Consumers', 'Admins'],
                    datasets: [{
                        data: [consumerCount || 1, adminCount || 0],
                        backgroundColor: ['#00d4ff', '#7b2ffc'],
                        borderColor: 'transparent',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { color: 'rgba(255,255,255,0.5)', padding: 16 }
                        }
                    }
                }
            });
        }

        // ============================================================
        // USER MANAGEMENT FUNCTIONS (With Self-Protection)
        // ============================================================
        function toggleUser(uid, status) {
            if (uid === ADMIN_UID) {
                showToast('You cannot disable your own account.', 'warning');
                return;
            }
            
            var action = status ? 'enable' : 'disable';
            if (!confirm('Are you sure you want to ' + action + ' this user?')) return;
            
            database.ref('users/' + uid).update({
                is_active: status === 1
            }).then(function() {
                showToast('User ' + action + 'd successfully!', 'success');
                var user = allUsers.find(function(u) { return u.uid === uid; });
                if (user && user.email) {
                    fetch('../api/send-notification.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            email: user.email,
                            type: 'account_status',
                            name: user.firstName || 'User',
                            status: status === 1 ? 'enabled' : 'disabled'
                        })
                    });
                }
            }).catch(function(error) {
                showToast('Error: ' + error.message, 'error');
            });
        }

        function openDeleteModal(uid, name) {
            if (uid === ADMIN_UID) {
                showToast('You cannot delete your own account.', 'warning');
                return;
            }
            deleteTargetUid = uid;
            document.getElementById('deleteUserName').textContent = name;
            document.getElementById('deleteModal').classList.add('show');
        }

        function confirmDelete() {
            if (!deleteTargetUid) return;
            
            var uid = deleteTargetUid;
            var user = allUsers.find(function(u) { return u.uid === uid; });
            
            if (user && user.email) {
                fetch('../api/send-notification.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        email: user.email,
                        type: 'account_deleted',
                        name: user.firstName || 'User'
                    })
                }).catch(function() {});
            }
            
            Promise.all([
                database.ref('users/' + uid).remove(),
                database.ref('alerts/' + uid).remove(),
                database.ref('properties/' + uid).remove(),
                database.ref('thresholds/' + uid).remove(),
                database.ref('meters/' + uid).remove(),
                database.ref('reviews/' + uid).remove(),
                database.ref('messages/' + uid).remove()
            ]).then(function() {
                showToast('User and all data deleted! Notification sent to user.', 'success');
                closeModal('deleteModal');
                deleteTargetUid = null;
                loadUsers();
                loadDevices();
            }).catch(function(error) {
                showToast('Error: ' + error.message, 'error');
            });
        }

        function deleteProperty(propId, ownerId) {
            if (!confirm('Delete this property?')) return;
            database.ref('properties/' + ownerId + '/' + propId).remove()
                .then(function() { showToast('Property deleted!', 'success'); })
                .catch(function(error) { showToast('Error: ' + error.message, 'error'); });
        }

        function deleteDevice(meterId) {
            if (!confirm('Delete this device?')) return;
            database.ref('meters/' + meterId).remove()
                .then(function() { showToast('Device deleted!', 'success'); })
                .catch(function(error) { showToast('Error: ' + error.message, 'error'); });
        }

        function resolveAlert(alertId, userId) {
            database.ref('alerts/' + userId + '/' + alertId).update({
                resolved_at: new Date().toISOString()
            }).then(function() {
                showToast('Alert resolved!', 'success');
            }).catch(function(error) {
                showToast('Error: ' + error.message, 'error');
            });
        }

        function deleteAlert(alertId, userId) {
            if (!confirm('Delete this alert?')) return;
            database.ref('alerts/' + userId + '/' + alertId).remove()
                .then(function() { showToast('Alert deleted!', 'success'); })
                .catch(function(error) { showToast('Error: ' + error.message, 'error'); });
        }

        function openMessageModal(uid, email) {
            if (uid === ADMIN_UID) {
                showToast('You cannot send a message to yourself.', 'warning');
                return;
            }
            
            document.getElementById('messageRecipient').value = email;
            document.getElementById('messageSubject').value = '';
            document.getElementById('messageBody').value = '';
            document.getElementById('messageModal').classList.add('show');
            
            document.getElementById('messageForm').onsubmit = function(e) {
                e.preventDefault();
                var subject = document.getElementById('messageSubject').value;
                var body = document.getElementById('messageBody').value;
                
                if (!subject || !body) {
                    showToast('Please fill in all fields', 'warning');
                    return;
                }
                
                var msgRef = database.ref('messages/' + uid).push();
                msgRef.set({
                    from: '<?php echo $admin_email; ?>',
                    fromName: '<?php echo $admin_name; ?>',
                    subject: subject,
                    message: body,
                    timestamp: new Date().toISOString(),
                    isRead: false
                }).then(function() {
                    var user = allUsers.find(function(u) { return u.uid === uid; });
                    if (user && user.email) {
                        fetch('../api/send-notification.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                email: user.email,
                                type: 'admin_message',
                                name: user.firstName || 'User',
                                subject: subject,
                                message: body
                            })
                        });
                    }
                    showToast('Message sent!', 'success');
                    closeModal('messageModal');
                }).catch(function(error) {
                    showToast('Error: ' + error.message, 'error');
                });
            };
        }

        function filterUsers() {
            var search = document.getElementById('userSearch').value.toLowerCase();
            var roleFilter = document.getElementById('userRoleFilter').value;
            
            var filtered = getApprovedUsers();
            if (search) {
                filtered = filtered.filter(function(u) {
                    return (u.firstName || '').toLowerCase().includes(search) ||
                           (u.lastName || '').toLowerCase().includes(search) ||
                           (u.email || '').toLowerCase().includes(search);
                });
            }
            if (roleFilter !== 'all') {
                filtered = filtered.filter(function(u) { return u.role === roleFilter; });
            }
            renderUsers(filtered);
        }

        function filterProperties() {
            var search = document.getElementById('propertySearch').value.toLowerCase();
            var filtered = allProperties;
            if (search) {
                filtered = filtered.filter(function(p) {
                    return (p.propertyName || '').toLowerCase().includes(search) ||
                           (p.address || '').toLowerCase().includes(search) ||
                           (p.meterId || '').toLowerCase().includes(search);
                });
            }
            renderProperties(filtered);
        }

        function filterDevices() {
            var search = document.getElementById('deviceSearch').value.toLowerCase();
            var statusFilter = document.getElementById('deviceStatusFilter').value;
            
            var filtered = allDevices;
            if (search) {
                filtered = filtered.filter(function(d) {
                    return (d.meterId || '').toLowerCase().includes(search) ||
                           (d.model || '').toLowerCase().includes(search) ||
                           (d.ownerName || '').toLowerCase().includes(search);
                });
            }
            if (statusFilter !== 'all') {
                filtered = filtered.filter(function(d) {
                    var reading = d.lastReading || {};
                    return reading.status === statusFilter;
                });
            }
            renderDevices(filtered);
        }

        function filterAlerts() {
            var severityFilter = document.getElementById('alertSeverityFilter').value;
            var filtered = allAlerts;
            if (severityFilter !== 'all') {
                filtered = filtered.filter(function(a) { return a.severity === severityFilter; });
            }
            renderAlerts(filtered);
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('show');
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        }

        document.querySelectorAll('.admin-tab').forEach(function(tab) {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.admin-tab').forEach(function(t) { t.classList.remove('active'); });
                this.classList.add('active');
                
                var tabId = this.dataset.tab;
                document.querySelectorAll('.tab-content').forEach(function(content) { content.classList.remove('active'); });
                document.getElementById('tab-' + tabId).classList.add('active');
            });
        });

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

        // ============================================================
        // SIMULATE ESP32 DATA - CREATES REAL DEVICE WITH OWNER
        // ============================================================
        function simulateESP32Data() {
            if (!currentUser) {
                showToast('No user logged in. Please refresh.', 'error');
                return;
            }

            // Check if there are any approved users with properties
            var approvedUsers = getApprovedUsers();
            var usersWithProperties = [];
            
            approvedUsers.forEach(function(user) {
                var hasProperty = allProperties.some(function(p) { return p.ownerId === user.uid; });
                if (hasProperty) {
                    usersWithProperties.push(user);
                }
            });

            // If no users with properties, create one for the current admin
            if (usersWithProperties.length === 0) {
                showToast('No users with properties found. Creating property for you...', 'info');
                
                var propertyRef = database.ref('properties/' + currentUser.uid).push();
                var meterId = 'MTR-' + Date.now();
                var testProperty = {
                    propertyName: 'Admin Property',
                    address: '123 Admin Street, City',
                    meterId: meterId,
                    createdAt: new Date().toISOString()
                };
                
                propertyRef.set(testProperty).then(function() {
                    var deviceRef = database.ref('meters/' + meterId);
                    return deviceRef.set({
                        meterId: meterId,
                        model: 'ESP32-YF-S201',
                        propertyId: propertyRef.key,
                        registeredAt: new Date().toISOString(),
                        lastReading: {
                            flow: 12.5,
                            volume: 350.2,
                            battery: 85,
                            status: 'online',
                            timestamp: new Date().toISOString()
                        }
                    });
                }).then(function() {
                    showToast('Device created and linked to your account!', 'success');
                    loadAllData();
                }).catch(function(error) {
                    showToast('Error creating device: ' + error.message, 'error');
                });
                return;
            }

            // Pick a random user with a property
            var randomUser = usersWithProperties[Math.floor(Math.random() * usersWithProperties.length)];
            var userProperties = allProperties.filter(function(p) { return p.ownerId === randomUser.uid; });
            
            if (userProperties.length === 0) {
                showToast('No properties found for user.', 'warning');
                return;
            }

            var randomProperty = userProperties[Math.floor(Math.random() * userProperties.length)];
            var meterId = randomProperty.meterId || 'MTR-' + Date.now();
            
            // Check if device exists, if not create it
            database.ref('meters/' + meterId).once('value').then(function(snapshot) {
                if (!snapshot.exists()) {
                    // Create device
                    return database.ref('meters/' + meterId).set({
                        meterId: meterId,
                        model: 'ESP32-YF-S201',
                        propertyId: randomProperty.id,
                        registeredAt: new Date().toISOString(),
                        lastReading: {
                            flow: 12.5,
                            volume: 350.2,
                            battery: 85,
                            status: 'online',
                            timestamp: new Date().toISOString()
                        }
                    });
                }
                return snapshot.ref;
            }).then(function() {
                // Update the reading
                var flowRate = (Math.random() * 15 + 10).toFixed(2);
                var totalVolume = (Math.random() * 100 + 50).toFixed(2);
                var timestamp = new Date().toISOString();
                var battery = Math.floor(Math.random() * 20) + 80;
                
                return database.ref('meters/' + meterId + '/lastReading').update({
                    flowRate: parseFloat(flowRate),
                    totalVolume: parseFloat(totalVolume),
                    status: 'online',
                    battery: battery,
                    lastUpdated: timestamp
                });
            }).then(function() {
                showToast('Data sent to device ' + meterId + '! Flow: ' + flowRate + ' L/min', 'success');
                loadAllData();
            }).catch(function(error) {
                showToast('Hardware Simulation failed: ' + error.message, 'error');
            });
        }

        // ============================================================
        // EXPOSE FUNCTIONS
        // ============================================================
        window.toggleSidebar = toggleSidebar;
        window.logoutUser = logoutUser;
        window.showToast = showToast;
        window.openMessageModal = openMessageModal;
        window.closeModal = closeModal;
        window.filterUsers = filterUsers;
        window.filterProperties = filterProperties;
        window.filterDevices = filterDevices;
        window.filterAlerts = filterAlerts;
        window.toggleUser = toggleUser;
        window.openDeleteModal = openDeleteModal;
        window.confirmDelete = confirmDelete;
        window.deleteProperty = deleteProperty;
        window.deleteDevice = deleteDevice;
        window.resolveAlert = resolveAlert;
        window.deleteAlert = deleteAlert;
        window.toggleTheme = toggleTheme;
        window.simulateESP32Data = simulateESP32Data;
        window.approveUser = approveUser;
        window.rejectUser = rejectUser;
        window.filterApprovals = filterApprovals;
        window.loadPendingApprovals = loadPendingApprovals;
        window.getApprovedUsers = getApprovedUsers;

        console.log('Admin dashboard loaded with Firebase users!');
        console.log('Self-protection enabled: Admin cannot delete/disable/message themselves');
        console.log('Users count shows approved users only');
        console.log('Devices table includes Owner column with real names from Firebase');
        console.log('Devices are linked to real owners via properties');
    </script>
</body>
</html>