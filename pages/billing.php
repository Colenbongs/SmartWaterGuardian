<?php
/**
 * Smart Water Guardian - Billing & Payment Page
 * View bills with detailed calculations, make payments, payment history
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
$user_email = $_SESSION['email'] ?? '';

// Require database connection
require_once '../config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing - Smart Water Guardian 💰</title>
    
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-auth-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-database-compat.js"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        /* ============================================================
                   BILLING PAGE - PROFESSIONAL WITH LIGHT/DARK MODE
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
        
        body.light-mode .summary-card {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid #e2e8f0;
        }
        
        body.light-mode .summary-card .amount {
            color: #1a365d;
        }
        
        body.light-mode .summary-card .label {
            color: rgba(0, 0, 0, 0.4);
        }
        
        body.light-mode .bill-card {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid #e2e8f0;
        }
        
        body.light-mode .bill-card .bill-info .period {
            color: #1a365d;
        }
        
        body.light-mode .bill-card .bill-info .details {
            color: rgba(0, 0, 0, 0.4);
        }
        
        body.light-mode .bill-card .bill-amount {
            color: #1a365d;
        }
        
        body.light-mode .bill-details .detail-row {
            color: rgba(0, 0, 0, 0.4);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        body.light-mode .bill-details .detail-row .label {
            color: rgba(0, 0, 0, 0.3);
        }
        
        body.light-mode .bill-details .detail-row .value {
            color: rgba(0, 0, 0, 0.6);
        }
        
        body.light-mode .bill-details .detail-row.total .value {
            color: #0066cc;
        }
        
        body.light-mode .modal-content {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid #e2e8f0;
        }
        
        body.light-mode .modal-header h3 {
            color: #1a365d;
        }
        
        body.light-mode .payment-amount .amount {
            color: #1a365d;
        }
        
        body.light-mode .payment-method {
            border-color: #e2e8f0;
            background: rgba(255, 255, 255, 0.5);
        }
        
        body.light-mode .payment-method .name {
            color: rgba(0, 0, 0, 0.5);
        }
        
        body.light-mode .payment-method.selected {
            border-color: #00d4ff;
            background: rgba(0, 212, 255, 0.05);
        }
        
        body.light-mode .menu-toggle {
            color: #1a365d;
        }
        
        body.light-mode .theme-toggle-btn {
            color: rgba(0, 0, 0, 0.4);
            border-top: 1px solid #e2e8f0;
        }
        
        body.light-mode .theme-toggle-btn:hover {
            color: #1a365d;
            background: rgba(0, 0, 0, 0.05);
        }
        
        body.light-mode .bg-animation .orb {
            opacity: 0.08;
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
            opacity: 0.25;
            animation: floatOrb 20s ease-in-out infinite;
        }
        
        .bg-animation .orb:nth-child(1) {
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, #00d4ff, transparent 70%);
            top: -80px;
            right: -80px;
            animation-delay: 0s;
        }
        
        .bg-animation .orb:nth-child(2) {
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, #7b2ffc, transparent 70%);
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
        
        body.light-mode .sidebar-nav .logout-link {
            color: rgba(255, 100, 100, 0.3);
        }
        
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
        
        /* ========== MAIN CONTENT ========== */
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
        
        /* ========== BILLING SUMMARY ========== */
        .billing-summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .summary-card {
            background: rgba(255,255,255,0.03);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid rgba(255,255,255,0.05);
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .summary-card:hover {
            border-color: rgba(0, 212, 255, 0.2);
            transform: translateY(-2px);
        }
        
        .summary-card .amount {
            font-size: 28px;
            font-weight: 700;
            color: white;
            margin-top: 4px;
        }
        
        .summary-card .label {
            font-size: 12px;
            color: rgba(255,255,255,0.3);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .summary-card .amount.green { color: #00ff88; }
        .summary-card .amount.red { color: #ff6b6b; }
        .summary-card .amount.blue { color: #00d4ff; }
        .summary-card .amount.gold { color: #ffd700; }
        
        body.light-mode .summary-card .amount.green { color: #008844; }
        body.light-mode .summary-card .amount.red { color: #cc3333; }
        body.light-mode .summary-card .amount.blue { color: #0066cc; }
        body.light-mode .summary-card .amount.gold { color: #cc9900; }
        
        /* ========== BILL CARDS WITH DETAILS ========== */
        .bill-card {
            background: rgba(255,255,255,0.03);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 20px 24px;
            border: 1px solid rgba(255,255,255,0.05);
            margin-bottom: 14px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .bill-card:hover {
            border-color: rgba(0, 212, 255, 0.2);
            transform: translateX(4px);
        }
        
        .bill-card .bill-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }
        
        .bill-card .bill-info .period {
            font-size: 16px;
            font-weight: 600;
            color: rgba(255,255,255,0.8);
        }
        
        .bill-card .bill-info .details {
            font-size: 13px;
            color: rgba(255,255,255,0.3);
            margin-top: 2px;
        }
        
        .bill-card .bill-amount {
            font-size: 24px;
            font-weight: 700;
            color: white;
        }
        
        .bill-card .bill-status {
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-paid {
            background: rgba(0, 255, 136, 0.15);
            color: #00ff88;
            border: 1px solid rgba(0, 255, 136, 0.1);
        }
        
        .status-unpaid {
            background: rgba(255, 107, 107, 0.15);
            color: #ff6b6b;
            border: 1px solid rgba(255, 107, 107, 0.1);
        }
        
        .status-pending {
            background: rgba(255, 215, 0, 0.15);
            color: #ffd700;
            border: 1px solid rgba(255, 215, 0, 0.1);
        }
        
        body.light-mode .status-paid {
            background: rgba(0, 200, 100, 0.15);
            color: #008844;
            border: 1px solid rgba(0, 200, 100, 0.1);
        }
        
        body.light-mode .status-unpaid {
            background: rgba(200, 50, 50, 0.15);
            color: #cc3333;
            border: 1px solid rgba(200, 50, 50, 0.1);
        }
        
        body.light-mode .status-pending {
            background: rgba(200, 180, 0, 0.15);
            color: #cc9900;
            border: 1px solid rgba(200, 180, 0, 0.1);
        }
        
        .bill-card .btn-pay {
            padding: 8px 24px;
            background: linear-gradient(135deg, #00d4ff, #7b2ffc);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .bill-card .btn-pay:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 212, 255, 0.3);
        }
        
        .bill-card .btn-pay:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        /* ========== BILL DETAILS EXPAND ========== */
        .bill-details {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid rgba(255,255,255,0.05);
            display: none;
        }
        
        .bill-details.open {
            display: block;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .bill-details .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 13px;
            color: rgba(255,255,255,0.5);
            border-bottom: 1px solid rgba(255,255,255,0.02);
        }
        
        .bill-details .detail-row:last-child {
            border-bottom: none;
        }
        
        .bill-details .detail-row .label {
            color: rgba(255,255,255,0.3);
        }
        
        .bill-details .detail-row .value {
            color: rgba(255,255,255,0.7);
            font-weight: 500;
        }
        
        .bill-details .detail-row.total {
            font-size: 16px;
            font-weight: 700;
            padding-top: 10px;
            margin-top: 4px;
            border-top: 2px solid rgba(255,255,255,0.05);
        }
        
        .bill-details .detail-row.total .value {
            color: #ffd700;
        }
        
        body.light-mode .bill-details .detail-row.total .value {
            color: #0066cc;
        }
        
        .bill-details .detail-row .highlight {
            color: #00d4ff;
        }
        
        .bill-details .expand-icon {
            float: right;
            color: rgba(255,255,255,0.2);
            font-size: 14px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .bill-details .expand-icon.rotated {
            transform: rotate(180deg);
        }
        
        .bill-details .click-hint {
            font-size: 12px;
            color: rgba(255,255,255,0.1);
            text-align: center;
            margin-top: 8px;
        }
        
        /* ========== PAYMENT MODAL ========== */
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
        }
        
        body.light-mode .modal-content {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid #e2e8f0;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-header h3 {
            color: white;
            font-size: 20px;
        }
        
        body.light-mode .modal-header h3 {
            color: #1a365d;
        }
        
        .modal-header h3 i {
            color: #ffd700;
            margin-right: 10px;
        }
        
        .modal-close {
            font-size: 24px;
            cursor: pointer;
            color: rgba(255,255,255,0.3);
            transition: all 0.3s ease;
            background: none;
            border: none;
        }
        
        .modal-close:hover {
            color: white;
            transform: rotate(90deg);
        }
        
        body.light-mode .modal-close {
            color: rgba(0, 0, 0, 0.3);
        }
        
        body.light-mode .modal-close:hover {
            color: #1a365d;
        }
        
        .payment-amount {
            text-align: center;
            padding: 20px;
            background: rgba(255,255,255,0.03);
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .payment-amount .amount {
            font-size: 36px;
            font-weight: 700;
            color: white;
        }
        
        body.light-mode .payment-amount .amount {
            color: #1a365d;
        }
        
        .payment-amount .label {
            font-size: 13px;
            color: rgba(255,255,255,0.3);
        }
        
        .payment-methods {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .payment-method {
            padding: 14px;
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.02);
        }
        
        .payment-method:hover {
            border-color: rgba(0, 212, 255, 0.2);
            background: rgba(255,255,255,0.04);
        }
        
        .payment-method.selected {
            border-color: #00d4ff;
            background: rgba(0, 212, 255, 0.06);
        }
        
        body.light-mode .payment-method {
            border-color: #e2e8f0;
            background: rgba(255, 255, 255, 0.5);
        }
        
        body.light-mode .payment-method .name {
            color: rgba(0, 0, 0, 0.5);
        }
        
        body.light-mode .payment-method.selected {
            border-color: #00d4ff;
            background: rgba(0, 212, 255, 0.05);
        }
        
        .payment-method .icon {
            font-size: 32px;
            display: block;
            margin-bottom: 4px;
        }
        
        .payment-method .name {
            font-size: 12px;
            color: rgba(255,255,255,0.6);
        }
        
        .btn-pay-now {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #00d4ff, #7b2ffc);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-pay-now:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 212, 255, 0.3);
        }
        
        .btn-pay-now:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
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
            .billing-summary {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .billing-summary {
                grid-template-columns: 1fr;
            }
            .topbar {
                flex-direction: column;
                text-align: center;
                padding: 16px;
            }
            .topbar-left h2 {
                font-size: 22px;
            }
            .bill-card .bill-header {
                flex-direction: column;
                text-align: center;
            }
            .payment-methods {
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
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="history.php"><i class="fas fa-chart-line"></i> History 📊</a>
            <a href="alerts.php"><i class="fas fa-bell"></i> Alerts 🔔</a>
            <a href="thresholds.php"><i class="fas fa-sliders-h"></i> Thresholds ⚙️</a>
            <a href="reviews.php"><i class="fas fa-star"></i> Reviews ⭐</a>
            <a href="properties.php"><i class="fas fa-home"></i> Properties 🏠</a>
            <a href="billing.php" class="active"><i class="fas fa-credit-card"></i> Billing 💰</a>
            <?php if ($user_role === 'system_admin' || $user_role === 'municipal_admin' || $user_role === 'admin'): ?>
            <a href="admin.php"><i class="fas fa-cog"></i> Admin 🛠️</a>
            <?php endif; ?>
            <a href="profile.php"><i class="fas fa-user"></i> Profile 👤</a>
            
            <!-- Theme Toggle -->
            <button class="theme-toggle-btn" onclick="toggleTheme()" id="themeToggle">
                <i class="fas fa-moon" id="themeIcon"></i>
                <span id="themeLabel">Dark Mode</span>
            </button>
            
            <a href="#" onclick="logoutUser()" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout 🚪</a>
        </nav>
        <div class="sidebar-footer">v2.0.0 • ✦ 2026</div>
    </aside>

    <!-- ========== MAIN CONTENT ========== -->
    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <button class="menu-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h2>💰 Billing & Payments</h2>
                <p>View and pay your water bills</p>
            </div>
            <div class="topbar-right">
                <span class="date-display">
                    <i class="far fa-calendar-alt"></i> 
                    <?php echo date('l, F j, Y'); ?>
                </span>
            </div>
        </header>

        <!-- Billing Summary -->
        <div class="billing-summary" id="billingSummary">
            <div class="summary-card">
                <div class="label">Total Outstanding</div>
                <div class="amount red" id="totalOutstanding">R 0.00</div>
            </div>
            <div class="summary-card">
                <div class="label">Total Paid</div>
                <div class="amount green" id="totalPaid">R 0.00</div>
            </div>
            <div class="summary-card">
                <div class="label">Current Month</div>
                <div class="amount blue" id="currentMonth">R 0.00</div>
            </div>
            <div class="summary-card">
                <div class="label">Due Date</div>
                <div class="amount gold" id="dueDate">--</div>
            </div>
        </div>

        <!-- Bills List -->
        <div id="billsContainer">
            <div style="text-align:center;padding:40px;color:rgba(255,255,255,0.3);">
                <i class="fas fa-spinner fa-spin" style="font-size:24px;"></i>
                <p style="margin-top:12px;">Loading bills...</p>
            </div>
        </div>
    </main>

    <!-- ========== PAYMENT MODAL ========== -->
    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-credit-card"></i> Make Payment</h3>
                <button class="modal-close" onclick="closePaymentModal()">&times;</button>
            </div>
            <div class="payment-amount">
                <div class="label">Amount Due</div>
                <div class="amount" id="payAmount">R 0.00</div>
            </div>
            <div class="payment-methods">
                <div class="payment-method selected" onclick="selectPaymentMethod('payfast')">
                    <span class="icon">⚡</span>
                    <span class="name">PayFast</span>
                </div>
                <div class="payment-method" onclick="selectPaymentMethod('yoco')">
                    <span class="icon">💳</span>
                    <span class="name">Yoco</span>
                </div>
                <div class="payment-method" onclick="selectPaymentMethod('ozow')">
                    <span class="icon">🏦</span>
                    <span class="name">Ozow</span>
                </div>
                <div class="payment-method" onclick="selectPaymentMethod('bank')">
                    <span class="icon">🏛️</span>
                    <span class="name">Bank Transfer</span>
                </div>
            </div>
            <input type="hidden" id="paymentBillId" value="">
            <button class="btn-pay-now" id="payNowBtn" onclick="processPayment()">
                <i class="fas fa-lock"></i> Pay Now
            </button>
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
        let bills = [];
        let selectedMethod = 'payfast';
        let currentBillId = null;
        let billGenerated = false;

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
                console.log('✅ User logged in:', user.uid);
                loadBills();
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
                const badge = document.getElementById('alertBadge');
                if (badge) badge.textContent = count;
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
            }, 5000);
        }

        // ============================================================
        // TARIFF CALCULATION ENGINE
        // ============================================================
        function calculateBill(usageInKL) {
            const tiers = [
                { min: 0, max: 6, rate: 18.50, label: 'Tier 1 (0-6 kL)' },
                { min: 6, max: 20, rate: 25.00, label: 'Tier 2 (6-20 kL)' },
                { min: 20, max: 40, rate: 35.00, label: 'Tier 3 (20-40 kL)' },
                { min: 40, max: Infinity, rate: 45.00, label: 'Tier 4 (40+ kL)' }
            ];
            
            let total = 0;
            let breakdown = [];
            let remaining = usageInKL;
            
            for (let i = 0; i < tiers.length; i++) {
                const tier = tiers[i];
                const prevMax = i > 0 ? tiers[i-1].max : 0;
                const tierVolume = Math.min(remaining, tier.max - prevMax);
                
                if (tierVolume > 0) {
                    const tierCost = tierVolume * tier.rate;
                    total += tierCost;
                    breakdown.push({
                        label: tier.label,
                        volume: tierVolume,
                        rate: tier.rate,
                        cost: tierCost
                    });
                    remaining -= tierVolume;
                }
                
                if (remaining <= 0) break;
            }
            
            const vat = total * 0.15;
            const grandTotal = total + vat;
            
            return {
                subtotal: total,
                vat: vat,
                total: grandTotal,
                breakdown: breakdown,
                usage: usageInKL
            };
        }

        // ============================================================
        // LOAD BILLS
        // ============================================================
        function loadBills() {
            const billsRef = database.ref('bills/' + currentUser.uid);
            billsRef.on('value', function(snapshot) {
                const data = snapshot.val();
                bills = [];
                if (data) {
                    for (let id in data) {
                        bills.push({
                            id: id,
                            ...data[id]
                        });
                    }
                }
                bills.sort((a, b) => new Date(b.date) - new Date(a.date));
                
                if (bills.length === 0 && !billGenerated) {
                    billGenerated = true;
                    generateBill(currentUser.uid);
                } else {
                    renderBills(bills);
                    updateSummary(bills);
                }
            });
        }

        // ============================================================
        // GENERATE BILL WITH DETAILED CALCULATIONS
        // ============================================================
        function generateBill(uid) {
            const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            const currentMonth = months[new Date().getMonth()];
            const year = new Date().getFullYear();
            
            const billRef = database.ref('bills/' + uid);
            billRef.orderByChild('month').equalTo(currentMonth).once('value')
                .then((snapshot) => {
                    if (snapshot.exists()) {
                        console.log('✅ Bill already exists for this month');
                        billGenerated = true;
                        return;
                    }
                    
                    const usage = Math.round((Math.random() * 20 + 5) * 100) / 100;
                    const calculation = calculateBill(usage);
                    
                    const newBill = {
                        month: currentMonth,
                        year: year,
                        usage: usage,
                        usageDisplay: usage + ' kL',
                        subtotal: calculation.subtotal,
                        vat: calculation.vat,
                        amount: calculation.total,
                        breakdown: calculation.breakdown,
                        date: new Date().toISOString(),
                        status: 'unpaid',
                        dueDate: new Date(new Date().setDate(new Date().getDate() + 30)).toISOString(),
                        invoiceNumber: 'INV-' + year + '-' + String(months.indexOf(currentMonth) + 1).padStart(2, '0') + '-' + String(Math.floor(Math.random() * 10000)).padStart(4, '0')
                    };
                    
                    billRef.push(newBill);
                    console.log('✅ Bill generated for', currentMonth);
                    billGenerated = true;
                });
        }

        // ============================================================
        // RENDER BILLS WITH DETAILS
        // ============================================================
        function renderBills(bills) {
            const container = document.getElementById('billsContainer');
            
            if (!bills || bills.length === 0) {
                container.innerHTML = `
                    <div style="text-align:center;padding:60px 20px;background:rgba(255,255,255,0.03);border-radius:12px;border:1px solid rgba(255,255,255,0.05);">
                        <span style="font-size:48px;display:block;margin-bottom:16px;">💰</span>
                        <h3 style="color:rgba(255,255,255,0.8);font-size:20px;">No Bills Yet</h3>
                        <p style="color:rgba(255,255,255,0.3);margin-top:8px;">Generating your first bill...</p>
                    </div>
                `;
                return;
            }

            let html = '';
            bills.forEach((bill) => {
                const statusClass = bill.status === 'paid' ? 'status-paid' : (bill.status === 'pending' ? 'status-pending' : 'status-unpaid');
                const statusText = bill.status === 'paid' ? '✅ Paid' : (bill.status === 'pending' ? '⏳ Pending' : '❌ Unpaid');
                const isPaid = bill.status === 'paid';
                const dueDate = bill.dueDate ? new Date(bill.dueDate).toLocaleDateString('en-ZA', { day: '2-digit', month: 'short', year: 'numeric' }) : 'N/A';
                const amount = bill.amount || 0;
                const subtotal = bill.subtotal || (amount / 1.15);
                const vat = bill.vat || (amount - subtotal);
                const usage = bill.usage || 0;
                const breakdown = bill.breakdown || [{ label: 'Tier 1', volume: usage, rate: 18.50, cost: subtotal }];
                const invoiceNo = bill.invoiceNumber || 'INV-' + Date.now();
                
                let daysUntilDue = '';
                if (bill.dueDate && !isPaid) {
                    const now = new Date();
                    const due = new Date(bill.dueDate);
                    const diffDays = Math.ceil((due - now) / (1000 * 60 * 60 * 24));
                    if (diffDays > 0) {
                        daysUntilDue = `<span style="color:rgba(255,255,255,0.3);font-size:12px;">${diffDays} days until due</span>`;
                    } else if (diffDays === 0) {
                        daysUntilDue = `<span style="color:#ffd700;font-size:12px;">⚠️ Due today!</span>`;
                    } else {
                        daysUntilDue = `<span style="color:#ff6b6b;font-size:12px;">🔴 ${Math.abs(diffDays)} days overdue</span>`;
                    }
                }
                
                let breakdownRows = '';
                if (breakdown && breakdown.length > 0) {
                    breakdown.forEach(tier => {
                        breakdownRows += `
                            <div class="detail-row">
                                <span class="label">${tier.label || 'Tier'}</span>
                                <span class="value">${tier.volume ? tier.volume.toFixed(2) : '0.00'} kL × R${(tier.rate || 0).toFixed(2)} = R${(tier.cost || 0).toFixed(2)}</span>
                            </div>
                        `;
                    });
                }
                
                html += `
                    <div class="bill-card" onclick="toggleDetails('${bill.id}')">
                        <div class="bill-header">
                            <div class="bill-info">
                                <div class="period">${bill.month} ${bill.year}</div>
                                <div class="details">
                                    📊 Usage: ${typeof usage === 'number' ? usage.toFixed(2) : usage} kL • 
                                    📅 Due: ${dueDate}
                                    ${daysUntilDue ? ' • ' + daysUntilDue : ''}
                                </div>
                            </div>
                            <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                                <div class="bill-amount">R ${amount.toFixed(2)}</div>
                                <span class="bill-status ${statusClass}">${statusText}</span>
                                ${!isPaid ? `<button class="btn-pay" onclick="event.stopPropagation();openPaymentModal('${bill.id}', ${amount})">💳 Pay Now</button>` : ''}
                                <i class="fas fa-chevron-down expand-icon" id="expandIcon_${bill.id}" onclick="event.stopPropagation();toggleDetails('${bill.id}')"></i>
                            </div>
                        </div>
                        
                        <div class="bill-details" id="details_${bill.id}">
                            <div style="margin-bottom:10px;display:flex;justify-content:space-between;font-size:13px;color:rgba(255,255,255,0.2);">
                                <span>📄 ${invoiceNo}</span>
                                <span>💰 Billing Breakdown</span>
                            </div>
                            
                            ${breakdownRows}
                            
                            <div class="detail-row">
                                <span class="label">💰 Subtotal</span>
                                <span class="value">R ${(subtotal || 0).toFixed(2)}</span>
                            </div>
                            <div class="detail-row">
                                <span class="label">📊 VAT (15%)</span>
                                <span class="value">R ${(vat || 0).toFixed(2)}</span>
                            </div>
                            <div class="detail-row total">
                                <span class="label">💎 Total Amount</span>
                                <span class="value">R ${amount.toFixed(2)}</span>
                            </div>
                            
                            <div style="margin-top:12px;padding:12px;background:rgba(0,212,255,0.03);border-radius:8px;border:1px solid rgba(0,212,255,0.05);">
                                <div style="display:flex;justify-content:space-between;font-size:12px;color:rgba(255,255,255,0.2);">
                                    <span>💧 Water Usage: ${typeof usage === 'number' ? usage.toFixed(2) : usage} kL</span>
                                    <span>💰 Effective Rate: R ${(amount / (usage || 1)).toFixed(2)}/kL</span>
                                </div>
                            </div>
                            
                            ${!isPaid ? `
                            <div style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap;">
                                <button onclick="event.stopPropagation();openPaymentModal('${bill.id}', ${amount})" style="flex:1;padding:10px;background:linear-gradient(135deg,#00d4ff,#7b2ffc);color:white;border:none;border-radius:8px;font-weight:600;cursor:pointer;font-size:14px;">
                                    💳 Pay Now
                                </button>
                                <button onclick="event.stopPropagation();downloadInvoice('${bill.id}')" style="padding:10px 20px;background:rgba(255,255,255,0.05);color:rgba(255,255,255,0.4);border:1px solid rgba(255,255,255,0.05);border-radius:8px;font-weight:500;cursor:pointer;font-size:14px;">
                                    📄 Download PDF
                                </button>
                            </div>
                            ` : `
                            <div style="margin-top:12px;text-align:center;padding:8px;background:rgba(0,255,136,0.03);border-radius:8px;border:1px solid rgba(0,255,136,0.05);">
                                <span style="color:rgba(0,255,136,0.3);font-size:13px;">✅ Paid on ${bill.paidAt ? new Date(bill.paidAt).toLocaleDateString() : 'N/A'}</span>
                            </div>
                            `}
                            
                            <div class="click-hint">💡 Click anywhere on the bill to expand/collapse details</div>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
        }

        // ============================================================
        // TOGGLE BILL DETAILS
        // ============================================================
        function toggleDetails(billId) {
            const details = document.getElementById('details_' + billId);
            const icon = document.getElementById('expandIcon_' + billId);
            if (details) {
                details.classList.toggle('open');
                if (icon) {
                    icon.classList.toggle('rotated');
                }
            }
        }

        // ============================================================
        // DOWNLOAD INVOICE PDF
        // ============================================================
        function downloadInvoice(billId) {
            showToast('📄 Invoice PDF download coming soon! API integration in progress.', 'info');
        }

        // ============================================================
        // UPDATE SUMMARY
        // ============================================================
        function updateSummary(bills) {
            let totalOutstanding = 0;
            let totalPaid = 0;
            let currentMonthAmount = 0;
            let dueDate = '--';
            
            bills.forEach(bill => {
                if (bill.status === 'unpaid' || bill.status === 'pending') {
                    totalOutstanding += bill.amount || 0;
                } else if (bill.status === 'paid') {
                    totalPaid += bill.amount || 0;
                }
                
                const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                const currentMonth = months[new Date().getMonth()];
                if (bill.month === currentMonth && bill.status !== 'paid') {
                    currentMonthAmount = bill.amount || 0;
                }
            });
            
            const unpaid = bills.filter(b => b.status !== 'paid' && b.dueDate);
            if (unpaid.length > 0) {
                const nearest = unpaid.sort((a, b) => new Date(a.dueDate) - new Date(b.dueDate))[0];
                dueDate = new Date(nearest.dueDate).toLocaleDateString('en-ZA', { day: '2-digit', month: 'short', year: 'numeric' });
            }
            
            document.getElementById('totalOutstanding').textContent = 'R ' + totalOutstanding.toFixed(2);
            document.getElementById('totalPaid').textContent = 'R ' + totalPaid.toFixed(2);
            document.getElementById('currentMonth').textContent = 'R ' + currentMonthAmount.toFixed(2);
            document.getElementById('dueDate').textContent = dueDate;
        }

        // ============================================================
        // PAYMENT MODAL
        // ============================================================
        function openPaymentModal(billId, amount) {
            currentBillId = billId;
            document.getElementById('payAmount').textContent = 'R ' + amount.toFixed(2);
            document.getElementById('paymentModal').classList.add('show');
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').classList.remove('show');
        }

        function selectPaymentMethod(method) {
            selectedMethod = method;
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
            });
            event.target.closest('.payment-method').classList.add('selected');
        }

        // ============================================================
        // PROCESS PAYMENT
        // ============================================================
        function processPayment() {
            if (!currentBillId) {
                showToast('⚠️ No bill selected', 'error');
                return;
            }
            
            const btn = document.getElementById('payNowBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            setTimeout(() => {
                const billRef = database.ref('bills/' + currentUser.uid + '/' + currentBillId);
                billRef.update({
                    status: 'paid',
                    paymentMethod: selectedMethod,
                    paidAt: new Date().toISOString()
                }).then(() => {
                    showToast('✅ Payment successful! 🎉', 'success');
                    closePaymentModal();
                    loadBills();
                }).catch((error) => {
                    showToast('❌ Payment failed: ' + error.message, 'error');
                }).finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-lock"></i> Pay Now';
                });
            }, 2000);
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                closePaymentModal();
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
        // EXPOSE FUNCTIONS
        // ============================================================
        window.toggleSidebar = toggleSidebar;
        window.logoutUser = logoutUser;
        window.openPaymentModal = openPaymentModal;
        window.closePaymentModal = closePaymentModal;
        window.selectPaymentMethod = selectPaymentMethod;
        window.processPayment = processPayment;
        window.showToast = showToast;
        window.toggleDetails = toggleDetails;
        window.downloadInvoice = downloadInvoice;
        window.toggleTheme = toggleTheme;

        console.log('💰 Billing page loaded with detailed calculations!');
        console.log('📊 Tariff: Tiered pricing (0-6 kL: R18.50, 6-20 kL: R25.00, 20-40 kL: R35.00, 40+ kL: R45.00)');
    </script>
</body>
</html>