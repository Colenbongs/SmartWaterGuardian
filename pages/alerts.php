<?php
/**
 * Smart Water Guardian - Alerts & Messages Page
 * Users can send messages to admin with persistent message history
 * Full light/dark mode support
 * Delete messages functionality
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
$user_email = $_SESSION['email'] ?? '';
$user_role = $_SESSION['role'] ?? 'consumer';
$full_name = trim($first_name . ' ' . $last_name);
$is_admin = ($user_role === 'system_admin' || $user_role === 'municipal_admin' || $user_role === 'admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerts & Messages - Smart Water Guardian</title>
    
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-auth-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-database-compat.js"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        /* ============================================================
                   ALERTS PAGE - PROFESSIONAL WITH LIGHT/DARK MODE
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
        
        body.light-mode .compose-section {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid #e2e8f0;
        }
        
        body.light-mode .compose-section h3 {
            color: #1a365d;
        }
        
        body.light-mode .compose-section .form-group label {
            color: rgba(0, 0, 0, 0.4);
        }
        
        body.light-mode .compose-section .form-group input,
        body.light-mode .compose-section .form-group textarea,
        body.light-mode .compose-section .form-group select {
            background: rgba(255, 255, 255, 0.8);
            color: #1a365d;
            border: 1px solid #e2e8f0;
        }
        
        body.light-mode .compose-section .form-group input:focus,
        body.light-mode .compose-section .form-group textarea:focus {
            border-color: rgba(0, 100, 200, 0.3);
        }
        
        body.light-mode .compose-section .form-group input::placeholder,
        body.light-mode .compose-section .form-group textarea::placeholder {
            color: rgba(0, 0, 0, 0.2);
        }
        
        body.light-mode .compose-section .form-group select option {
            background: white;
            color: #1a365d;
        }
        
        body.light-mode .messages-section {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid #e2e8f0;
        }
        
        body.light-mode .messages-section h3 {
            color: #1a365d;
        }
        
        body.light-mode .messages-section .filter-tab {
            color: rgba(0, 0, 0, 0.3);
            background: rgba(0, 0, 0, 0.02);
        }
        
        body.light-mode .messages-section .filter-tab:hover {
            color: #1a365d;
            background: rgba(0, 0, 0, 0.04);
        }
        
        body.light-mode .messages-section .filter-tab.active {
            color: white;
            background: linear-gradient(135deg, #0066cc, #4a00a0);
        }
        
        body.light-mode .message-item {
            border: 1px solid #e2e8f0;
        }
        
        body.light-mode .message-item:hover {
            border-color: rgba(0, 100, 200, 0.2);
            background: rgba(0, 0, 0, 0.02);
        }
        
        body.light-mode .message-item.unread {
            border-left: 3px solid #cc3333;
            background: rgba(200, 50, 50, 0.03);
        }
        
        body.light-mode .message-item .header .from {
            color: #0066cc;
        }
        
        body.light-mode .message-item .header .from .from-name {
            color: #4a00a0;
        }
        
        body.light-mode .message-item .header .date {
            color: rgba(0, 0, 0, 0.2);
        }
        
        body.light-mode .message-item .subject {
            color: #1a365d;
        }
        
        body.light-mode .message-item .preview {
            color: rgba(0, 0, 0, 0.3);
        }
        
        body.light-mode .message-item .badge-status {
            background: rgba(0, 0, 0, 0.03);
        }
        
        body.light-mode .badge-unread {
            background: rgba(200, 50, 50, 0.1);
            color: #cc3333;
        }
        
        body.light-mode .badge-read {
            background: rgba(0, 0, 0, 0.03);
            color: rgba(0, 0, 0, 0.3);
        }
        
        body.light-mode .badge-reply {
            background: rgba(0, 200, 100, 0.08);
            color: #008844;
        }
        
        body.light-mode .empty-state {
            color: rgba(0, 0, 0, 0.2);
        }
        
        body.light-mode .empty-state h4 {
            color: rgba(0, 0, 0, 0.4);
        }
        
        body.light-mode .modal-content {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid #e2e8f0;
        }
        
        body.light-mode .modal-header h3 {
            color: #1a365d;
        }
        
        body.light-mode .modal-close {
            color: rgba(0, 0, 0, 0.3);
        }
        
        body.light-mode .modal-close:hover {
            color: #1a365d;
        }
        
        body.light-mode .modal-body .message-info {
            background: rgba(0, 0, 0, 0.02);
            border-left: 3px solid #0066cc;
        }
        
        body.light-mode .modal-body .message-info .label {
            color: rgba(0, 0, 0, 0.2);
        }
        
        body.light-mode .modal-body .message-info .text {
            color: #1a365d;
        }
        
        body.light-mode .modal-body textarea {
            background: rgba(255, 255, 255, 0.8);
            color: #1a365d;
            border: 1px solid #e2e8f0;
        }
        
        body.light-mode .modal-body textarea:focus {
            border-color: rgba(0, 100, 200, 0.3);
        }
        
        body.light-mode .modal-body textarea::placeholder {
            color: rgba(0, 0, 0, 0.2);
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
        
        body.light-mode .compose-section .btn-send {
            color: white;
        }
        
        body.light-mode .modal-body .btn-send {
            color: white;
        }
        
        body.light-mode .message-item .actions .btn-delete {
            color: #cc3333;
            background: rgba(200, 50, 50, 0.08);
        }
        
        body.light-mode .message-item .actions .btn-delete:hover {
            background: rgba(200, 50, 50, 0.15);
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
        
        /* ========== SIDEBAR ========== */
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
        
        .topbar-right .date-display {
            color: rgba(127, 201, 255, 0.25);
            font-size: 13px;
            padding: 6px 16px;
            background: rgba(127, 201, 255, 0.03);
            border-radius: 50px;
            border: 1px solid rgba(127, 201, 255, 0.04);
        }
        
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: #7fc9ff;
            cursor: pointer;
        }
        
        /* ========== MESSAGES LAYOUT ========== */
        .messages-layout {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 24px;
        }
        
        /* ========== COMPOSE MESSAGE ========== */
        .compose-section {
            background: rgba(255,255,255,0.02);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 24px;
            border: 1px solid rgba(127, 201, 255, 0.04);
            transition: background 0.3s ease;
        }
        
        .compose-section h3 {
            color: #7fc9ff;
            font-size: 18px;
            margin-bottom: 16px;
        }
        
        .compose-section h3 i {
            margin-right: 8px;
        }
        
        .compose-section .form-group {
            margin-bottom: 16px;
        }
        
        .compose-section .form-group label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            color: rgba(127, 201, 255, 0.4);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .compose-section .form-group label i {
            margin-right: 6px;
        }
        
        .compose-section .form-group input,
        .compose-section .form-group textarea,
        .compose-section .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid rgba(127, 201, 255, 0.06);
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: rgba(127, 201, 255, 0.02);
            color: #b8e6ff;
        }
        
        .compose-section .form-group input:focus,
        .compose-section .form-group textarea:focus {
            outline: none;
            border-color: rgba(127, 201, 255, 0.15);
            background: rgba(127, 201, 255, 0.04);
        }
        
        .compose-section .form-group input::placeholder,
        .compose-section .form-group textarea::placeholder {
            color: rgba(127, 201, 255, 0.08);
        }
        
        .compose-section .form-group textarea {
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
        }
        
        .compose-section .form-group select option {
            background: #040812;
            color: #b8e6ff;
        }
        
        .compose-section .btn-send {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #7fc9ff, #7b2ffc);
            color: #05080f;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .compose-section .btn-send:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 40px rgba(127, 201, 255, 0.08);
        }
        
        .compose-section .btn-send:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            transform: none;
        }
        
        .compose-section .btn-send .spinner {
            display: none;
            animation: spin 1s linear infinite;
        }
        
        .compose-section .btn-send.loading .spinner {
            display: inline-block;
        }
        
        .compose-section .btn-send.loading .btn-text {
            display: none;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* ========== MESSAGES LIST ========== */
        .messages-section {
            background: rgba(255,255,255,0.02);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 24px;
            border: 1px solid rgba(127, 201, 255, 0.04);
            max-height: 600px;
            overflow-y: auto;
            transition: background 0.3s ease;
        }
        
        .messages-section::-webkit-scrollbar {
            width: 4px;
        }
        
        .messages-section::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .messages-section::-webkit-scrollbar-thumb {
            background: rgba(127, 201, 255, 0.1);
            border-radius: 10px;
        }
        
        .messages-section h3 {
            color: #7fc9ff;
            font-size: 18px;
            margin-bottom: 16px;
        }
        
        .messages-section h3 i {
            margin-right: 8px;
        }
        
        .messages-section .filter-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }
        
        .messages-section .filter-tab {
            padding: 6px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            color: rgba(127, 201, 255, 0.3);
            background: rgba(127, 201, 255, 0.03);
            transition: all 0.3s ease;
        }
        
        .messages-section .filter-tab:hover {
            color: #7fc9ff;
            background: rgba(127, 201, 255, 0.05);
        }
        
        .messages-section .filter-tab.active {
            color: #05080f;
            background: linear-gradient(135deg, #7fc9ff, #7b2ffc);
        }
        
        .messages-section .filter-tab .count {
            font-size: 10px;
            margin-left: 4px;
            opacity: 0.6;
        }
        
        /* ========== MESSAGE ITEMS ========== */
        .message-item {
            padding: 14px 16px;
            border-radius: 12px;
            border: 1px solid rgba(127, 201, 255, 0.04);
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }
        
        .message-item:hover {
            border-color: rgba(127, 201, 255, 0.1);
            background: rgba(127, 201, 255, 0.02);
        }
        
        .message-item.unread {
            border-left: 3px solid #ff6b6b;
            background: rgba(255, 107, 107, 0.03);
        }
        
        .message-item .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .message-item .header .from {
            font-weight: 600;
            color: #7fc9ff;
        }
        
        .message-item .header .from .from-name {
            color: #7b2ffc;
        }
        
        .message-item .header .date {
            font-size: 11px;
            color: rgba(127, 201, 255, 0.2);
        }
        
        .message-item .subject {
            font-weight: 500;
            color: rgba(255,255,255,0.8);
            margin: 4px 0;
        }
        
        .message-item .preview {
            font-size: 13px;
            color: rgba(127, 201, 255, 0.3);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .message-item .badge-status {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 2px 10px;
            border-radius: 12px;
            letter-spacing: 0.5px;
        }
        
        .badge-unread {
            background: rgba(255, 107, 107, 0.15);
            color: #ff6b6b;
        }
        
        .badge-read {
            background: rgba(127, 201, 255, 0.05);
            color: rgba(127, 201, 255, 0.3);
        }
        
        .badge-reply {
            background: rgba(0, 255, 136, 0.08);
            color: #00ff88;
        }
        
        /* ========== MESSAGE ACTIONS ========== */
        .message-item .actions {
            display: flex;
            gap: 6px;
            margin-top: 8px;
            flex-wrap: wrap;
        }
        
        .message-item .actions .btn-action {
            padding: 4px 12px;
            border: none;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .message-item .actions .btn-reply {
            background: rgba(127, 201, 255, 0.08);
            color: #7fc9ff;
        }
        
        .message-item .actions .btn-reply:hover {
            background: rgba(127, 201, 255, 0.15);
        }
        
        .message-item .actions .btn-delete {
            background: rgba(255, 107, 107, 0.08);
            color: #ff6b6b;
        }
        
        .message-item .actions .btn-delete:hover {
            background: rgba(255, 107, 107, 0.15);
        }
        
        .message-item .actions .btn-read {
            background: rgba(0, 255, 136, 0.08);
            color: #00ff88;
        }
        
        .message-item .actions .btn-read:hover {
            background: rgba(0, 255, 136, 0.15);
        }
        
        /* ========== EMPTY STATE ========== */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: rgba(127, 201, 255, 0.2);
        }
        
        .empty-state .icon {
            font-size: 48px;
            margin-bottom: 12px;
            display: block;
        }
        
        .empty-state h4 {
            color: rgba(127, 201, 255, 0.4);
            font-size: 16px;
            margin-bottom: 4px;
        }
        
        /* ========== REPLY MODAL ========== */
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
            transition: background 0.3s ease;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        
        .modal-header h3 {
            color: #7fc9ff;
            font-size: 20px;
        }
        
        .modal-header h3 i {
            margin-right: 10px;
        }
        
        .modal-close {
            font-size: 24px;
            cursor: pointer;
            color: rgba(127, 201, 255, 0.2);
            transition: all 0.3s ease;
            background: none;
            border: none;
        }
        
        .modal-close:hover {
            color: #7fc9ff;
            transform: rotate(90deg);
        }
        
        .modal-body .message-info {
            background: rgba(127, 201, 255, 0.02);
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 14px;
            border-left: 3px solid #7fc9ff;
        }
        
        .modal-body .message-info .label {
            color: rgba(127, 201, 255, 0.2);
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .modal-body .message-info .text {
            color: rgba(255,255,255,0.8);
            font-size: 14px;
            margin-top: 2px;
        }
        
        .modal-body textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid rgba(127, 201, 255, 0.06);
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: rgba(127, 201, 255, 0.02);
            color: #b8e6ff;
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
        }
        
        .modal-body textarea:focus {
            outline: none;
            border-color: rgba(127, 201, 255, 0.15);
        }
        
        .modal-body textarea::placeholder {
            color: rgba(127, 201, 255, 0.08);
        }
        
        .modal-body .btn-send {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #7fc9ff, #7b2ffc);
            color: #05080f;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 12px;
        }
        
        .modal-body .btn-send:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(127, 201, 255, 0.08);
        }
        
        .modal-body .btn-send:disabled {
            opacity: 0.4;
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
        
        .toast-success { background: rgba(0, 255, 136, 0.1); border: 1px solid #00ff88; color: #00ff88; }
        .toast-error { background: rgba(255, 107, 107, 0.1); border: 1px solid #ff6b6b; color: #ff6b6b; }
        .toast-info { background: rgba(127, 201, 255, 0.08); border: 1px solid #7fc9ff; color: #7fc9ff; }
        .toast-warning { background: rgba(255, 215, 0, 0.08); border: 1px solid #ffd700; color: #ffd700; }
        
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
            .messages-layout {
                grid-template-columns: 1fr;
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
            .modal-content {
                padding: 20px;
                margin: 10px;
            }
            .messages-section {
                max-height: 400px;
            }
            .message-item .header {
                flex-direction: column;
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
            <div class="logo-icon">
                <i class="fas fa-water"></i>
            </div>
            <div class="brand-text">Smart<span>Water</span></div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-label">Main Menu</div>
            
            <?php if ($user_role === 'system_admin' || $user_role === 'municipal_admin' || $user_role === 'admin'): ?>
                <!-- Admin Sidebar -->
                <a href="admin.php">
                    <i class="fas fa-cog"></i> Admin
                </a>
                <a href="alerts.php" class="active">
                    <i class="fas fa-bell"></i> Alerts
                    <span class="nav-badge" id="alertBadge">0</span>
                </a>
                <a href="reviews.php">
                    <i class="fas fa-star"></i> Reviews
                </a>
                <a href="profile.php">
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
                <a href="alerts.php" class="active">
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
                <a href="profile.php">
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
        <header class="topbar">
            <div class="topbar-left">
                <button class="menu-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h2>Alerts & Messages</h2>
                <p><?php echo $is_admin ? 'Manage user messages' : 'View alerts and communicate with admin'; ?></p>
            </div>
            <div class="topbar-right">
                <span class="date-display">
                    <i class="far fa-calendar-alt"></i> 
                    <?php echo date('l, F j, Y'); ?>
                </span>
            </div>
        </header>

        <!-- Messages Layout -->
        <div class="messages-layout">
            <!-- ========== COMPOSE MESSAGE - Only for Consumers ========== -->
            <?php if (!$is_admin): ?>
            <div class="compose-section">
                <h3><i class="fas fa-pen"></i> Send Message to Admin</h3>
                <form id="composeForm">
                    <div class="form-group">
                        <label for="msgSubject"><i class="fas fa-tag"></i> Subject</label>
                        <input type="text" id="msgSubject" placeholder="Enter message subject" required>
                    </div>
                    <div class="form-group">
                        <label for="msgCategory"><i class="fas fa-folder"></i> Category</label>
                        <select id="msgCategory">
                            <option value="general">General Inquiry</option>
                            <option value="billing">Billing Question</option>
                            <option value="technical">Technical Issue</option>
                            <option value="feedback">Feedback</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="msgBody"><i class="fas fa-comment"></i> Message</label>
                        <textarea id="msgBody" placeholder="Type your message to admin here..." required></textarea>
                    </div>
                    <button type="submit" class="btn-send" id="sendMsgBtn">
                        <span class="btn-text"><i class="fas fa-paper-plane"></i> Send Message</span>
                        <span class="spinner"><i class="fas fa-spinner"></i></span>
                    </button>
                </form>
            </div>
            <?php else: ?>
            <div class="compose-section">
                <h3><i class="fas fa-user-cog"></i> Admin Console</h3>
                <p style="color:rgba(127,201,255,0.3);font-size:14px;margin-bottom:16px;">
                    View and reply to messages from users below.
                </p>
                <div style="background:rgba(127,201,255,0.02);padding:16px;border-radius:12px;border:1px solid rgba(127,201,255,0.04);">
                    <p style="color:rgba(127,201,255,0.2);font-size:12px;">
                        <i class="fas fa-info-circle"></i> As an admin, you can reply to user messages from the right panel.
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <!-- ========== MESSAGES LIST ========== -->
            <div class="messages-section">
                <h3><i class="fas fa-inbox"></i> <?php echo $is_admin ? 'User Messages' : 'Your Messages'; ?></h3>
                <div class="filter-tabs">
                    <button class="filter-tab active" data-filter="all" onclick="filterMessages('all')">
                        All <span class="count" id="countAll">0</span>
                    </button>
                    <button class="filter-tab" data-filter="unread" onclick="filterMessages('unread')">
                        Unread <span class="count" id="countUnread">0</span>
                    </button>
                    <?php if (!$is_admin): ?>
                    <button class="filter-tab" data-filter="sent" onclick="filterMessages('sent')">
                        Sent <span class="count" id="countSent">0</span>
                    </button>
                    <?php endif; ?>
                </div>
                <div id="messagesList">
                    <div class="empty-state">
                        <span class="icon">📭</span>
                        <h4>No messages</h4>
                        <p><?php echo $is_admin ? 'No messages from users yet' : 'Send a message to admin or wait for replies'; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- ========== REPLY MODAL ========== -->
    <div id="replyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-reply"></i> Reply to Message</h3>
                <button class="modal-close" onclick="closeReplyModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="message-info">
                    <div class="label">Replying to:</div>
                    <div class="text" id="replyToInfo">Admin</div>
                </div>
                <div class="message-info">
                    <div class="label">Original Message:</div>
                    <div class="text" id="originalMessage">Loading...</div>
                </div>
                <textarea id="replyMessage" placeholder="Type your reply here..."></textarea>
                <input type="hidden" id="replyToId" value="">
                <input type="hidden" id="replyToAdminUid" value="">
                <button class="btn-send" id="sendReplyBtn" onclick="sendReply()">
                    <i class="fas fa-paper-plane"></i> Send Reply
                </button>
            </div>
        </div>
    </div>

    <!-- ========== DELETE CONFIRM MODAL ========== -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="color:#ff6b6b;"><i class="fas fa-trash"></i> Delete Message</h3>
                <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p style="color:rgba(255,255,255,0.6);margin-bottom:16px;">
                    Are you sure you want to delete this message? This action cannot be undone.
                </p>
                <div style="background:rgba(255,107,107,0.05);padding:12px 16px;border-radius:8px;border-left:3px solid #ff6b6b;margin-bottom:16px;">
                    <p style="color:rgba(255,255,255,0.4);font-size:13px;" id="deleteMessagePreview">Loading...</p>
                </div>
                <div style="display:flex;gap:12px;">
                    <button class="btn-send" style="background:linear-gradient(135deg,#ff6b6b,#e53e3e);" onclick="confirmDeleteMessage()">
                        <i class="fas fa-trash"></i> Delete Permanently
                    </button>
                    <button class="btn-send" style="background:rgba(127,201,255,0.08);color:rgba(127,201,255,0.3);" onclick="closeDeleteModal()">
                        Cancel
                    </button>
                </div>
                <input type="hidden" id="deleteMessageId" value="">
                <input type="hidden" id="deleteMessagePath" value="">
            </div>
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
        let allMessages = [];
        let currentFilter = 'all';
        let currentUserId = '<?php echo $user_id; ?>';
        let currentUserEmail = '<?php echo $user_email; ?>';
        let currentUserName = '<?php echo $full_name; ?>';
        let adminUidCache = null;
        let isAdmin = <?php echo $is_admin ? 'true' : 'false'; ?>;

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

        // ==================== AUTH CHECK ====================
        auth.onAuthStateChanged(function(user) {
            if (user) {
                currentUser = user;
                console.log('User logged in:', user.uid);
                if (isAdmin) {
                    adminUidCache = user.uid;
                    loadAdminMessages();
                } else {
                    findAdminUid().then(() => {
                        loadMessages();
                    });
                }
                updateAlertBadge();
            } else {
                window.location.href = 'login.php';
            }
        });

        // ==================== FIND ADMIN UID ====================
        function findAdminUid() {
            return new Promise((resolve) => {
                if (adminUidCache) {
                    resolve(adminUidCache);
                    return;
                }
                
                const usersRef = database.ref('users');
                usersRef.orderByChild('role').equalTo('system_admin').once('value')
                    .then((snapshot) => {
                        const data = snapshot.val();
                        if (data) {
                            for (let uid in data) {
                                if (data[uid].role === 'system_admin' || data[uid].role === 'municipal_admin') {
                                    adminUidCache = uid;
                                    console.log('Found admin UID:', uid);
                                    resolve(uid);
                                    return;
                                }
                            }
                        }
                        adminUidCache = 'admin';
                        resolve('admin');
                    })
                    .catch(() => {
                        adminUidCache = 'admin';
                        resolve('admin');
                    });
            });
        }

        // ==================== UPDATE BADGE ====================
        function updateAlertBadge() {
            if (!currentUser) return;
            const alertsRef = database.ref('alerts/' + currentUser.uid);
            alertsRef.on('value', function(snapshot) {
                const data = snapshot.val();
                const count = data ? Object.keys(data).filter(key => !data[key].isRead).length : 0;
                document.getElementById('alertBadge').textContent = count;
            });
        }

        // ==================== TOAST ====================
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

        // ==================== LOAD MESSAGES (Consumer) ====================
        function loadMessages() {
            const messagesRef = database.ref('messages/' + currentUser.uid);
            messagesRef.on('value', function(snapshot) {
                const data = snapshot.val();
                allMessages = [];
                if (data) {
                    for (let id in data) {
                        allMessages.push({
                            id: id,
                            ...data[id]
                        });
                    }
                }
                allMessages.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));
                renderMessages(allMessages);
                updateCounts(allMessages);
            });
        }

        // ==================== LOAD ADMIN MESSAGES ====================
        function loadAdminMessages() {
            const messagesRef = database.ref('messages/' + currentUser.uid);
            messagesRef.on('value', function(snapshot) {
                const data = snapshot.val();
                allMessages = [];
                if (data) {
                    for (let id in data) {
                        const msg = data[id];
                        // Only show messages FROM users (not admin self)
                        if (msg.fromUid !== currentUser.uid && msg.from !== currentUserEmail) {
                            allMessages.push({
                                id: id,
                                ...msg
                            });
                        }
                    }
                }
                allMessages.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));
                renderMessages(allMessages);
                updateCounts(allMessages);
            });
        }

        // ==================== RENDER MESSAGES ====================
        function renderMessages(messages) {
            const container = document.getElementById('messagesList');
            
            let filtered = messages;
            if (currentFilter === 'unread') {
                filtered = messages.filter(m => !m.isRead);
            } else if (currentFilter === 'sent') {
                filtered = messages.filter(m => m.from === currentUserEmail || m.fromUid === currentUser.uid);
            }
            
            if (!filtered || filtered.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <span class="icon">📭</span>
                        <h4>No messages</h4>
                        <p>${isAdmin ? 'No messages from users yet' : 'Send a message to admin or wait for replies'}</p>
                    </div>
                `;
                return;
            }

            let html = '';
            filtered.forEach(msg => {
                const isFromAdmin = msg.from === 'admin@smartwater.com' || msg.fromUid === adminUidCache;
                const isRead = msg.isRead || false;
                const isReply = msg.isReply || false;
                let fromName = isFromAdmin ? 'Admin' : (msg.fromName || 'You');
                
                if (isAdmin) {
                    fromName = msg.fromName || msg.from || 'User';
                }
                
                const date = msg.timestamp ? new Date(msg.timestamp).toLocaleString() : 'Recently';
                const messageId = msg.id;
                
                html += `
                    <div class="message-item ${!isRead ? 'unread' : ''}">
                        <div class="header">
                            <span class="from">
                                <span class="from-name">${fromName}</span>
                                ${isFromAdmin ? 'Admin' : ''}
                            </span>
                            <span class="date">${date}</span>
                        </div>
                        <div class="subject">${msg.subject || 'Message'}</div>
                        <div class="preview">${msg.message || ''}</div>
                        <div style="margin-top:8px;display:flex;gap:8px;flex-wrap:wrap;">
                            ${!isRead ? '<span class="badge-status badge-unread">Unread</span>' : '<span class="badge-status badge-read">Read</span>'}
                            ${isReply ? '<span class="badge-status badge-reply">Reply</span>' : ''}
                        </div>
                        <div class="actions">
                            ${isAdmin ? `<button class="btn-action btn-reply" onclick="openReplyModal('${messageId}', '${msg.fromUid || msg.from}', '${fromName}', '${msg.message}')"><i class="fas fa-reply"></i> Reply</button>` : ''}
                            ${!isAdmin && isFromAdmin ? `<button class="btn-action btn-reply" onclick="openReplyModal('${messageId}', '${msg.fromUid || msg.from}', '${fromName}', '${msg.message}')"><i class="fas fa-reply"></i> Reply</button>` : ''}
                            <button class="btn-action btn-delete" onclick="openDeleteModal('${messageId}')"><i class="fas fa-trash"></i> Delete</button>
                            ${!isRead ? `<button class="btn-action btn-read" onclick="markAsRead('${messageId}')"><i class="fas fa-check"></i> Mark Read</button>` : ''}
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
        }

        // ==================== UPDATE COUNTS ====================
        function updateCounts(messages) {
            const total = messages.length;
            const unread = messages.filter(m => !m.isRead).length;
            const sent = messages.filter(m => m.from === currentUserEmail || m.fromUid === currentUser.uid).length;
            
            document.getElementById('countAll').textContent = total;
            document.getElementById('countUnread').textContent = unread;
            document.getElementById('countSent').textContent = sent;
        }

        // ==================== FILTER MESSAGES ====================
        function filterMessages(filter) {
            currentFilter = filter;
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active');
                if (tab.dataset.filter === filter) {
                    tab.classList.add('active');
                }
            });
            renderMessages(allMessages);
        }

        // ==================== MARK AS READ ====================
        function markAsRead(messageId) {
            const msg = allMessages.find(m => m.id === messageId);
            if (!msg) return;
            
            // Determine which path to update
            const path = isAdmin ? 'messages/' + currentUser.uid + '/' + messageId : 'messages/' + currentUser.uid + '/' + messageId;
            
            database.ref(path).update({
                isRead: true,
                readAt: new Date().toISOString()
            }).then(() => {
                showToast('Message marked as read', 'success');
                if (isAdmin) {
                    loadAdminMessages();
                } else {
                    loadMessages();
                }
            }).catch((error) => {
                showToast('Error: ' + error.message, 'error');
            });
        }

        // ==================== OPEN DELETE MODAL ====================
        function openDeleteModal(messageId) {
            const msg = allMessages.find(m => m.id === messageId);
            if (!msg) {
                showToast('Message not found', 'error');
                return;
            }
            
            document.getElementById('deleteMessageId').value = messageId;
            document.getElementById('deleteMessagePreview').textContent = (msg.subject || 'Message') + ': ' + (msg.message || '').substring(0, 100) + '...';
            document.getElementById('deleteModal').classList.add('show');
        }

        // ==================== CLOSE DELETE MODAL ====================
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('show');
            document.getElementById('deleteMessageId').value = '';
            document.getElementById('deleteMessagePreview').textContent = 'Loading...';
        }

        // ==================== CONFIRM DELETE MESSAGE ====================
        function confirmDeleteMessage() {
            const messageId = document.getElementById('deleteMessageId').value;
            if (!messageId) {
                showToast('No message selected', 'error');
                return;
            }
            
            // Determine which path to delete from
            // For admin: delete from admin's messages
            // For consumer: delete from consumer's messages
            const path = isAdmin ? 'messages/' + currentUser.uid + '/' + messageId : 'messages/' + currentUser.uid + '/' + messageId;
            
            // Also need to delete from the other user's messages if it's a conversation
            // For simplicity, we delete from the current user's inbox
            // The other user's copy will remain (they can delete their own copy)
            
            database.ref(path).remove()
                .then(() => {
                    showToast('Message deleted successfully', 'success');
                    closeDeleteModal();
                    if (isAdmin) {
                        loadAdminMessages();
                    } else {
                        loadMessages();
                    }
                })
                .catch((error) => {
                    showToast('Error deleting message: ' + error.message, 'error');
                });
        }

        // ==================== SEND MESSAGE TO ADMIN ====================
        document.getElementById('composeForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const subject = document.getElementById('msgSubject').value.trim();
            const category = document.getElementById('msgCategory').value;
            const message = document.getElementById('msgBody').value.trim();
            
            if (!subject || !message) {
                showToast('Please fill in all fields', 'warning');
                return;
            }
            
            if (message.length < 5) {
                showToast('Message must be at least 5 characters', 'warning');
                return;
            }
            
            const btn = document.getElementById('sendMsgBtn');
            btn.classList.add('loading');
            btn.disabled = true;
            
            const adminUid = adminUidCache || 'admin';
            
            // Send to admin's messages
            const msgRef = database.ref('messages/' + adminUid).push();
            
            msgRef.set({
                from: currentUserEmail,
                fromUid: currentUser.uid,
                fromName: currentUserName,
                subject: '[' + category.toUpperCase() + '] ' + subject,
                message: message,
                timestamp: new Date().toISOString(),
                isRead: false,
                isFromUser: true,
                category: category
            })
            .then(() => {
                // Save to user's sent messages
                return database.ref('messages/' + currentUser.uid).push({
                    from: currentUserEmail,
                    fromUid: currentUser.uid,
                    fromName: currentUserName,
                    subject: '[' + category.toUpperCase() + '] ' + subject,
                    message: message,
                    timestamp: new Date().toISOString(),
                    isRead: true,
                    isFromUser: true,
                    category: category,
                    sent: true
                });
            })
            .then(() => {
                showToast('Message sent to Admin!', 'success');
                document.getElementById('composeForm').reset();
                loadMessages();
            })
            .catch((error) => {
                showToast('Failed to send message: ' + error.message, 'error');
            })
            .finally(() => {
                btn.classList.remove('loading');
                btn.disabled = false;
            });
        });

        // ==================== OPEN REPLY MODAL ====================
        function openReplyModal(messageId, fromUid, fromName, originalMessage) {
            document.getElementById('replyToId').value = messageId;
            document.getElementById('replyToAdminUid').value = fromUid || adminUidCache || 'admin';
            document.getElementById('replyToInfo').textContent = fromName || 'User';
            document.getElementById('originalMessage').textContent = originalMessage || 'No message content';
            document.getElementById('replyMessage').value = '';
            document.getElementById('replyModal').classList.add('show');
        }

        // ==================== CLOSE REPLY MODAL ====================
        function closeReplyModal() {
            document.getElementById('replyModal').classList.remove('show');
        }

        // ==================== SEND REPLY ====================
        function sendReply() {
            const messageId = document.getElementById('replyToId').value;
            const targetUid = document.getElementById('replyToAdminUid').value;
            const replyText = document.getElementById('replyMessage').value.trim();
            
            if (!replyText) {
                showToast('Please type your reply', 'warning');
                return;
            }
            
            if (replyText.length < 3) {
                showToast('Reply must be at least 3 characters', 'warning');
                return;
            }
            
            const btn = document.getElementById('sendReplyBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            
            // Save reply to target user's messages
            const replyRef = database.ref('messages/' + targetUid).push();
            replyRef.set({
                from: currentUserEmail,
                fromUid: currentUser.uid,
                fromName: currentUserName,
                subject: 'Re: Your message',
                message: replyText,
                timestamp: new Date().toISOString(),
                isRead: false,
                isReply: true,
                originalMessageId: messageId
            })
            .then(() => {
                // Also save to sender's sent messages
                return database.ref('messages/' + currentUser.uid).push({
                    from: currentUserEmail,
                    fromUid: currentUser.uid,
                    fromName: currentUserName,
                    subject: 'Re: Your message',
                    message: replyText,
                    timestamp: new Date().toISOString(),
                    isRead: true,
                    isReply: true,
                    originalMessageId: messageId,
                    sent: true
                });
            })
            .then(() => {
                // Mark original as having reply
                if (messageId && !isAdmin) {
                    return database.ref('messages/' + currentUser.uid + '/' + messageId).update({
                        hasReply: true,
                        replyTimestamp: new Date().toISOString()
                    });
                }
            })
            .then(() => {
                showToast('Reply sent!', 'success');
                closeReplyModal();
                if (isAdmin) {
                    loadAdminMessages();
                } else {
                    loadMessages();
                }
            })
            .catch((error) => {
                showToast('Failed to send reply: ' + error.message, 'error');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Reply';
            });
        }

        // Close modal on outside click
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        }

        // ==================== SIDEBAR TOGGLE ====================
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        // ==================== LOGOUT ====================
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

        // ==================== EXPOSE FUNCTIONS ====================
        window.toggleSidebar = toggleSidebar;
        window.logoutUser = logoutUser;
        window.filterMessages = filterMessages;
        window.openReplyModal = openReplyModal;
        window.closeReplyModal = closeReplyModal;
        window.sendReply = sendReply;
        window.showToast = showToast;
        window.toggleTheme = toggleTheme;
        window.openDeleteModal = openDeleteModal;
        window.closeDeleteModal = closeDeleteModal;
        window.confirmDeleteMessage = confirmDeleteMessage;
        window.markAsRead = markAsRead;

        console.log('Alerts & Messages page loaded with theme support and delete functionality!');
        console.log('Is Admin:', isAdmin);
    </script>
</body>
</html>