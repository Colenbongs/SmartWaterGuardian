<?php
/**
 * Smart Water Guardian - Reviews Page
 * User reviews with admin delete, comments, and light/dark mode
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
$is_admin = ($user_role === 'system_admin' || $user_role === 'municipal_admin' || $user_role === 'admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews - Smart Water Guardian ⭐</title>
    
    <!-- Firebase SDK -->
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-auth-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-database-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-storage-compat.js"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        /* ============================================================
                   REVIEWS PAGE - WITH LIGHT/DARK MODE
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
        
        body.light-mode .rating-summary {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid #e2e8f0;
        }
        
        body.light-mode .rating-count {
            color: rgba(0, 0, 0, 0.4);
        }
        
        body.light-mode .rating-bar span:first-child {
            color: rgba(0, 0, 0, 0.5);
        }
        
        body.light-mode .rating-bar span:last-child {
            color: rgba(0, 0, 0, 0.4);
        }
        
        body.light-mode .bar-bg {
            background: rgba(0, 0, 0, 0.05);
        }
        
        body.light-mode .review-card {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid #e2e8f0;
        }
        
        body.light-mode .review-card:hover {
            border-color: rgba(0, 212, 255, 0.3);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05);
        }
        
        body.light-mode .review-card .user-info .name {
            color: #1a365d;
        }
        
        body.light-mode .review-card .user-info .date {
            color: rgba(0, 0, 0, 0.3);
        }
        
        body.light-mode .review-card .title {
            color: #1a365d;
        }
        
        body.light-mode .review-card .comment {
            color: rgba(0, 0, 0, 0.6);
        }
        
        body.light-mode .review-card .footer {
            border-top: 1px solid #e2e8f0;
            color: rgba(0, 0, 0, 0.4);
        }
        
        body.light-mode .review-card .footer .helpful-btn {
            background: rgba(0, 0, 0, 0.03);
            color: rgba(0, 0, 0, 0.4);
        }
        
        body.light-mode .review-card .footer .helpful-btn:hover {
            background: rgba(0, 212, 255, 0.1);
            color: #0066cc;
        }
        
        body.light-mode .review-card .edit-badge {
            color: rgba(0, 0, 0, 0.2);
            background: rgba(0, 0, 0, 0.03);
        }
        
        body.light-mode .btn-edit-review {
            background: rgba(0, 100, 200, 0.1);
            color: #0066cc;
        }
        
        body.light-mode .btn-edit-review:hover {
            background: rgba(0, 100, 200, 0.2);
        }
        
        body.light-mode .btn-delete-review {
            background: rgba(200, 50, 50, 0.1);
            color: #cc3333;
        }
        
        body.light-mode .btn-delete-review:hover {
            background: rgba(200, 50, 50, 0.2);
        }
        
        body.light-mode .btn-admin-delete {
            background: rgba(200, 50, 50, 0.1);
            color: #cc3333;
        }
        
        body.light-mode .btn-admin-delete:hover {
            background: rgba(200, 50, 50, 0.2);
        }
        
        body.light-mode .no-reviews {
            background: rgba(255, 255, 255, 0.6);
            border: 1px solid #e2e8f0;
        }
        
        body.light-mode .no-reviews h3 {
            color: #1a365d;
        }
        
        body.light-mode .no-reviews p {
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
        body.light-mode .modal-content .form-group textarea {
            background: rgba(255, 255, 255, 0.8);
            color: #1a365d;
            border: 1px solid #e2e8f0;
        }
        
        body.light-mode .modal-content .form-group input:focus,
        body.light-mode .modal-content .form-group textarea:focus {
            border-color: rgba(0, 100, 200, 0.3);
        }
        
        body.light-mode .modal-content .form-group input::placeholder,
        body.light-mode .modal-content .form-group textarea::placeholder {
            color: rgba(0, 0, 0, 0.2);
        }
        
        body.light-mode .modal-content .close {
            color: rgba(0, 0, 0, 0.3);
        }
        
        body.light-mode .modal-content .close:hover {
            color: #1a365d;
        }
        
        body.light-mode .my-reviews-toggle {
            color: rgba(0, 0, 0, 0.4);
            border: 1px solid #e2e8f0;
        }
        
        body.light-mode .my-reviews-toggle:hover {
            background: rgba(0, 0, 0, 0.03);
            color: #1a365d;
        }
        
        body.light-mode .my-reviews-toggle.active {
            background: rgba(0, 212, 255, 0.08);
            border-color: rgba(0, 212, 255, 0.2);
            color: #0066cc;
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
        
        /* ========== RATING SUMMARY ========== */
        .rating-summary {
            background: rgba(255,255,255,0.03);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 32px;
            border: 1px solid rgba(255,255,255,0.05);
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 40px;
            margin-bottom: 24px;
            transition: background 0.3s ease;
        }
        
        .rating-overall {
            text-align: center;
        }
        
        .rating-number {
            font-size: 56px;
            font-weight: 800;
            background: linear-gradient(135deg, #00d4ff, #7b2ffc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        body.light-mode .rating-number {
            background: linear-gradient(135deg, #0066cc, #4a00a0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .rating-stars {
            color: #ffd700;
            font-size: 20px;
            margin: 4px 0;
        }
        
        .rating-count {
            color: rgba(255,255,255,0.4);
            font-size: 14px;
        }
        
        .rating-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 6px;
        }
        
        .rating-bar span:first-child {
            font-size: 13px;
            font-weight: 500;
            min-width: 50px;
            color: rgba(255,255,255,0.5);
        }
        
        .rating-bar span:last-child {
            font-size: 13px;
            color: rgba(255,255,255,0.4);
            min-width: 40px;
        }
        
        .bar-bg {
            flex: 1;
            height: 8px;
            background: rgba(255,255,255,0.05);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #00d4ff, #7b2ffc);
            border-radius: 4px;
            transition: width 0.5s ease;
        }
        
        body.light-mode .bar-fill {
            background: linear-gradient(90deg, #0066cc, #4a00a0);
        }
        
        /* ========== REVIEWS HEADER ========== */
        .reviews-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .reviews-header h3 {
            color: rgba(255,255,255,0.7);
            font-size: 16px;
            font-weight: 600;
        }
        
        .reviews-header h3 i {
            color: #00d4ff;
            margin-right: 8px;
        }
        
        .btn-write {
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
        
        .btn-write:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 212, 255, 0.3);
        }
        
        .my-reviews-toggle {
            padding: 10px 20px;
            background: rgba(255,255,255,0.05);
            color: rgba(255,255,255,0.6);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .my-reviews-toggle:hover {
            background: rgba(255,255,255,0.08);
            color: white;
        }
        
        .my-reviews-toggle.active {
            background: rgba(0, 212, 255, 0.15);
            border-color: rgba(0, 212, 255, 0.3);
            color: #00d4ff;
        }
        
        /* ========== REVIEW CARDS ========== */
        .review-card {
            background: rgba(255,255,255,0.03);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 20px 24px;
            border: 1px solid rgba(255,255,255,0.05);
            margin-bottom: 14px;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .review-card:hover {
            border-color: rgba(0, 212, 255, 0.2);
            transform: translateX(4px);
            box-shadow: 0 4px 30px rgba(0, 212, 255, 0.05);
        }
        
        .review-card .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 8px;
        }
        
        .review-card .user {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .review-card .avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, #00d4ff, #7b2ffc);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 600;
            box-shadow: 0 0 20px rgba(0, 212, 255, 0.2);
            overflow: hidden;
        }
        
        .review-card .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .review-card .user-info .name {
            font-weight: 600;
            color: rgba(255,255,255,0.9);
        }
        
        .review-card .user-info .date {
            font-size: 13px;
            color: rgba(255,255,255,0.3);
        }
        
        .review-card .stars {
            color: #ffd700;
            font-size: 16px;
        }
        
        .review-card .title {
            font-size: 18px;
            font-weight: 600;
            color: rgba(255,255,255,0.8);
            margin: 8px 0 4px;
        }
        
        .review-card .comment {
            color: rgba(255,255,255,0.6);
            line-height: 1.7;
            font-size: 15px;
        }
        
        .review-card .footer {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid rgba(255,255,255,0.05);
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 14px;
            color: rgba(255,255,255,0.4);
            flex-wrap: wrap;
        }
        
        .review-card .footer .helpful-btn {
            padding: 4px 16px;
            border: none;
            border-radius: 6px;
            background: rgba(255,255,255,0.05);
            color: rgba(255,255,255,0.5);
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 13px;
        }
        
        .review-card .footer .helpful-btn:hover {
            background: rgba(0, 212, 255, 0.15);
            color: #00d4ff;
        }
        
        .review-card .footer .review-actions {
            margin-left: auto;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .review-card .footer .review-actions button {
            padding: 4px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-edit-review {
            background: rgba(0, 212, 255, 0.1);
            color: #00d4ff;
        }
        .btn-edit-review:hover {
            background: rgba(0, 212, 255, 0.2);
        }
        
        .btn-delete-review {
            background: rgba(255, 107, 107, 0.1);
            color: #ff6b6b;
        }
        .btn-delete-review:hover {
            background: rgba(255, 107, 107, 0.2);
        }
        
        .btn-admin-delete {
            background: rgba(255, 107, 107, 0.15);
            color: #ff6b6b;
            border: 1px solid rgba(255, 107, 107, 0.08);
        }
        .btn-admin-delete:hover {
            background: rgba(255, 107, 107, 0.25);
        }
        
        .admin-badge {
            font-size: 9px;
            color: #00d4ff;
            background: rgba(0, 212, 255, 0.08);
            padding: 2px 8px;
            border-radius: 10px;
            margin-left: 8px;
        }
        
        .review-card .edit-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            font-size: 10px;
            color: rgba(255,255,255,0.2);
            background: rgba(255,255,255,0.05);
            padding: 2px 10px;
            border-radius: 12px;
        }
        
        /* ========== NO REVIEWS ========== */
        .no-reviews {
            text-align: center;
            padding: 60px 20px;
            background: rgba(255,255,255,0.03);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.05);
        }
        
        .no-reviews .icon {
            font-size: 64px;
            margin-bottom: 16px;
            display: block;
        }
        
        .no-reviews h3 {
            color: rgba(255,255,255,0.8);
            font-size: 24px;
            margin-bottom: 8px;
        }
        
        .no-reviews p {
            color: rgba(255,255,255,0.3);
            font-size: 16px;
        }
        
        .no-reviews .btn-write-empty {
            margin-top: 16px;
            padding: 10px 30px;
            background: linear-gradient(135deg, #00d4ff, #7b2ffc);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .no-reviews .btn-write-empty:hover {
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
            max-width: 500px;
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
        
        .modal-content h2 {
            color: white;
            margin-bottom: 16px;
            font-size: 22px;
        }
        
        .modal-content h2 i {
            color: #ffd700;
            margin-right: 10px;
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
        
        .modal-content .form-group input,
        .modal-content .form-group textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.05);
            color: white;
        }
        
        .modal-content .form-group input:focus,
        .modal-content .form-group textarea:focus {
            outline: none;
            border-color: #00d4ff;
            box-shadow: 0 0 30px rgba(0, 212, 255, 0.05);
        }
        
        .modal-content .form-group input::placeholder,
        .modal-content .form-group textarea::placeholder {
            color: rgba(255,255,255,0.2);
        }
        
        .modal-content .form-group textarea {
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
        }
        
        .star-rating {
            display: flex;
            gap: 8px;
            margin: 8px 0;
        }
        
        .star-rating .star {
            font-size: 36px;
            color: rgba(255,255,255,0.1);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .star-rating .star:hover {
            transform: scale(1.15);
        }
        
        .star-rating .star.active {
            color: #ffd700;
            text-shadow: 0 0 30px rgba(255, 215, 0, 0.3);
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
            .rating-summary {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            .topbar {
                flex-direction: column;
                text-align: center;
                padding: 16px;
            }
            .topbar-left h2 {
                font-size: 22px;
            }
            .reviews-header {
                flex-direction: column;
            }
            .reviews-header .btn-write {
                width: 100%;
                text-align: center;
            }
            .review-card .header {
                flex-direction: column;
            }
            .modal-content {
                padding: 20px;
                margin: 10px;
            }
            .review-card .footer .review-actions {
                margin-left: 0;
                width: 100%;
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

   <!-- ========== SIDEBAR - DYNAMIC ========== -->
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
            <!-- ===== ADMIN SIDEBAR ===== -->
            <a href="admin.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'admin.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> Admin 🛠️
            </a>
            <a href="alerts.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'alerts.php' ? 'active' : ''; ?>">
                <i class="fas fa-bell"></i> Alerts 🔔
                <span class="nav-badge" id="alertBadge">0</span>
            </a>
            <a href="reviews.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'reviews.php' ? 'active' : ''; ?>">
                <i class="fas fa-star"></i> Reviews ⭐
            </a>
            <a href="profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i> Profile 👤
            </a>
            
        <?php else: ?>
            <!-- ===== CONSUMER SIDEBAR ===== -->
            <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="history.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'history.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i> History 📊
            </a>
            <a href="alerts.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'alerts.php' ? 'active' : ''; ?>">
                <i class="fas fa-bell"></i> Alerts 🔔
                <span class="nav-badge" id="alertBadge">0</span>
            </a>
            <a href="thresholds.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'thresholds.php' ? 'active' : ''; ?>">
                <i class="fas fa-sliders-h"></i> Thresholds ⚙️
            </a>
            <a href="reviews.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'reviews.php' ? 'active' : ''; ?>">
                <i class="fas fa-star"></i> Reviews ⭐
            </a>
            <a href="properties.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'properties.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Properties 🏠
            </a>
            <a href="billing.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'billing.php' ? 'active' : ''; ?>">
                <i class="fas fa-credit-card"></i> Billing 💰
            </a>
            <a href="profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i> Profile 👤
            </a>
            
        <?php endif; ?>
        
        <!-- Theme Toggle - Visible to ALL -->
        <button class="theme-toggle-btn" onclick="toggleTheme()" id="themeToggle">
            <i class="fas fa-moon" id="themeIcon"></i>
            <span id="themeLabel">Dark Mode</span>
        </button>
        
        <!-- Logout - Visible to ALL -->
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
                <h2>⭐ Reviews</h2>
                <p>See what our community is saying</p>
            </div>
            <div class="topbar-right">
                <span class="date-display">
                    <i class="far fa-calendar-alt"></i> 
                    <?php echo date('l, F j, Y'); ?>
                </span>
            </div>
        </header>

        <!-- Rating Summary -->
        <section class="rating-summary" id="ratingSummary">
            <div class="rating-overall">
                <div class="rating-number" id="avgRating">0.0</div>
                <div class="rating-stars" id="avgStars">
                    <i class="far fa-star"></i>
                    <i class="far fa-star"></i>
                    <i class="far fa-star"></i>
                    <i class="far fa-star"></i>
                    <i class="far fa-star"></i>
                </div>
                <div class="rating-count" id="reviewCount">0 reviews</div>
            </div>
            <div class="rating-bars" id="ratingBars">
                <div class="rating-bar">
                    <span>5 ★</span>
                    <div class="bar-bg"><div class="bar-fill" style="width:0%;"></div></div>
                    <span id="p5">0%</span>
                </div>
                <div class="rating-bar">
                    <span>4 ★</span>
                    <div class="bar-bg"><div class="bar-fill" style="width:0%;"></div></div>
                    <span id="p4">0%</span>
                </div>
                <div class="rating-bar">
                    <span>3 ★</span>
                    <div class="bar-bg"><div class="bar-fill" style="width:0%;"></div></div>
                    <span id="p3">0%</span>
                </div>
                <div class="rating-bar">
                    <span>2 ★</span>
                    <div class="bar-bg"><div class="bar-fill" style="width:0%;"></div></div>
                    <span id="p2">0%</span>
                </div>
                <div class="rating-bar">
                    <span>1 ★</span>
                    <div class="bar-bg"><div class="bar-fill" style="width:0%;"></div></div>
                    <span id="p1">0%</span>
                </div>
            </div>
        </section>

        <!-- Reviews Header -->
        <div class="reviews-header">
            <h3><i class="fas fa-star"></i> Recent Reviews</h3>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <button class="my-reviews-toggle" id="myReviewsToggle" onclick="toggleMyReviews()">
                    <i class="fas fa-user"></i> My Reviews
                </button>
                <button class="btn-write" onclick="openReviewModal()">
                    <i class="fas fa-plus"></i> Write a Review ✍️
                </button>
            </div>
        </div>

        <!-- Reviews List -->
        <div id="reviewsContainer">
            <div class="no-reviews" id="loadingReviews">
                <span class="icon">⏳</span>
                <h3>Loading reviews...</h3>
                <p>Please wait while we fetch the reviews.</p>
            </div>
        </div>
    </main>

    <!-- ========== REVIEW MODAL ========== -->
    <div id="reviewModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeReviewModal()">&times;</span>
            <h2><i class="fas fa-star"></i> <span id="modalTitle">Write a Review</span></h2>
            <form id="reviewForm">
                <input type="hidden" id="editReviewId" value="">
                <div class="form-group">
                    <label>Rating ⭐</label>
                    <div class="star-rating" id="starRating">
                        <span class="star active" data-value="1" onclick="setRating(1)">★</span>
                        <span class="star active" data-value="2" onclick="setRating(2)">★</span>
                        <span class="star active" data-value="3" onclick="setRating(3)">★</span>
                        <span class="star active" data-value="4" onclick="setRating(4)">★</span>
                        <span class="star active" data-value="5" onclick="setRating(5)">★</span>
                    </div>
                    <input type="hidden" id="ratingValue" value="5">
                </div>
                <div class="form-group">
                    <label>Review Title 📝</label>
                    <input type="text" id="reviewTitle" placeholder="Summarize your experience" required>
                </div>
                <div class="form-group">
                    <label>Your Review 📋</label>
                    <textarea id="reviewText" placeholder="Share your experience with Smart Water Guardian..." required></textarea>
                </div>
                <button type="submit" class="btn-submit" id="submitReviewBtn">
                    <i class="fas fa-paper-plane"></i> <span id="submitBtnText">Submit Review 🚀</span>
                </button>
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
        const storage = firebase.storage();

        let currentUser = null;
        let allReviews = [];
        let userReviews = [];
        let selectedRating = 5;
        let editingReviewId = null;
        let showingMyReviews = false;
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
                console.log('✅ User logged in:', user.uid);
                console.log('👑 Is Admin:', isAdmin);
                loadReviews();
                updateAlertBadge();
                loadUserAvatar();
            } else {
                window.location.href = 'login.php';
            }
        });

        // ==================== LOAD USER AVATAR ====================
        function loadUserAvatar() {
            if (!currentUser) return;
            const avatarRef = storage.ref('avatars/' + currentUser.uid + '.jpg');
            avatarRef.getDownloadURL().then(url => {
                document.querySelectorAll('.avatar').forEach(el => {
                    if (el.querySelector('img')) {
                        el.querySelector('img').src = url;
                    } else {
                        el.innerHTML = `<img src="${url}" alt="Avatar">`;
                    }
                });
            }).catch(() => {});
        }

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

        // ==================== LOAD REVIEWS ====================
        function loadReviews() {
            const reviewsRef = database.ref('reviews');
            reviewsRef.on('value', function(snapshot) {
                const data = snapshot.val();
                allReviews = [];
                userReviews = [];
                if (data) {
                    for (let id in data) {
                        const review = {
                            id: id,
                            ...data[id]
                        };
                        allReviews.push(review);
                        if (review.userId === currentUser.uid) {
                            userReviews.push(review);
                        }
                    }
                }
                allReviews.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));
                userReviews.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));
                renderReviews(showingMyReviews ? userReviews : allReviews);
                updateRatingSummary(allReviews);
            });
        }

        // ==================== RENDER REVIEWS ====================
        function renderReviews(reviews) {
            const container = document.getElementById('reviewsContainer');
            
            if (!reviews || reviews.length === 0) {
                const message = showingMyReviews ? 
                    'You haven\'t written any reviews yet 📝' :
                    'No reviews yet. Be the first to share your experience!';
                container.innerHTML = `
                    <div class="no-reviews">
                        <span class="icon">📝</span>
                        <h3>${showingMyReviews ? 'No Reviews Yet' : 'No Reviews Yet'}</h3>
                        <p>${message}</p>
                        ${showingMyReviews ? 
                            `<button class="btn-write-empty" onclick="openReviewModal()">✍️ Write Your First Review</button>` :
                            `<button class="btn-write-empty" onclick="openReviewModal()">✍️ Write a Review</button>`
                        }
                    </div>
                `;
                return;
            }

            let html = '';
            reviews.forEach(review => {
                const user = review.userName || 'Anonymous';
                const userInitial = user.charAt(0).toUpperCase();
                const date = review.timestamp ? new Date(review.timestamp).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                }) : 'Recently';
                const isOwnReview = review.userId === currentUser.uid;
                const canAdminDelete = isAdmin && !isOwnReview;
                
                let starsHtml = '';
                for (let i = 1; i <= 5; i++) {
                    starsHtml += `<i class="fas fa-star" style="color:${i <= review.rating ? '#ffd700' : 'rgba(255,255,255,0.1)'};"></i>`;
                }
                
                html += `
                    <div class="review-card" id="review-${review.id}">
                        ${isOwnReview ? '<span class="edit-badge">✏️ Your Review</span>' : ''}
                        ${canAdminDelete ? '<span class="edit-badge" style="color:#ff6b6b;">🛠️ Admin</span>' : ''}
                        <div class="header">
                            <div class="user">
                                <div class="avatar">${userInitial}</div>
                                <div class="user-info">
                                    <div class="name">${user} ${isOwnReview ? '👤' : ''} ${canAdminDelete ? '<span class="admin-badge">Admin</span>' : ''}</div>
                                    <div class="date">📅 ${date}</div>
                                </div>
                            </div>
                            <div class="stars">${starsHtml}</div>
                        </div>
                        ${review.title ? `<div class="title">${review.title}</div>` : ''}
                        <div class="comment">${review.comment || ''}</div>
                        <div class="footer">
                            <span>👍 ${review.helpfulCount || 0} people found this helpful</span>
                            <button class="helpful-btn" onclick="markHelpful('${review.id}')">
                                👍 Helpful
                            </button>
                            <div class="review-actions">
                                ${isOwnReview ? `
                                    <button class="btn-edit-review" onclick="editReview('${review.id}')">
                                        ✏️ Edit
                                    </button>
                                    <button class="btn-delete-review" onclick="deleteReview('${review.id}')">
                                        🗑️ Delete
                                    </button>
                                ` : ''}
                                ${canAdminDelete ? `
                                    <button class="btn-admin-delete" onclick="adminDeleteReview('${review.id}', '${user}')">
                                        🗑️ Delete (Admin)
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
        }

        // ==================== TOGGLE MY REVIEWS ====================
        function toggleMyReviews() {
            showingMyReviews = !showingMyReviews;
            const toggle = document.getElementById('myReviewsToggle');
            if (showingMyReviews) {
                toggle.classList.add('active');
                toggle.innerHTML = '<i class="fas fa-globe"></i> All Reviews';
                renderReviews(userReviews);
            } else {
                toggle.classList.remove('active');
                toggle.innerHTML = '<i class="fas fa-user"></i> My Reviews';
                renderReviews(allReviews);
            }
        }

        // ==================== UPDATE RATING SUMMARY ====================
        function updateRatingSummary(reviews) {
            if (!reviews || reviews.length === 0) {
                document.getElementById('avgRating').textContent = '0.0';
                document.getElementById('reviewCount').textContent = '0 reviews';
                document.getElementById('p5').textContent = '0%';
                document.getElementById('p4').textContent = '0%';
                document.getElementById('p3').textContent = '0%';
                document.getElementById('p2').textContent = '0%';
                document.getElementById('p1').textContent = '0%';
                
                document.getElementById('avgStars').innerHTML = `
                    <i class="far fa-star"></i>
                    <i class="far fa-star"></i>
                    <i class="far fa-star"></i>
                    <i class="far fa-star"></i>
                    <i class="far fa-star"></i>
                `;
                return;
            }

            const total = reviews.length;
            let sum = 0;
            const counts = {1: 0, 2: 0, 3: 0, 4: 0, 5: 0};
            
            reviews.forEach(r => {
                sum += r.rating || 0;
                if (r.rating >= 1 && r.rating <= 5) {
                    counts[r.rating]++;
                }
            });
            
            const avg = sum / total;
            document.getElementById('avgRating').textContent = avg.toFixed(1);
            document.getElementById('reviewCount').textContent = `${total} reviews`;
            
            const fullStars = Math.floor(avg);
            const hasHalf = avg - fullStars >= 0.5;
            let starsHtml = '';
            for (let i = 1; i <= 5; i++) {
                if (i <= fullStars) {
                    starsHtml += '<i class="fas fa-star"></i>';
                } else if (hasHalf && i === fullStars + 1) {
                    starsHtml += '<i class="fas fa-star-half-alt"></i>';
                } else {
                    starsHtml += '<i class="far fa-star"></i>';
                }
            }
            document.getElementById('avgStars').innerHTML = starsHtml;
            
            for (let i = 1; i <= 5; i++) {
                const percent = (counts[i] / total) * 100;
                document.getElementById(`p${i}`).textContent = percent.toFixed(1) + '%';
                const bars = document.querySelectorAll('.bar-fill');
                if (bars[5 - i]) {
                    bars[5 - i].style.width = percent + '%';
                }
            }
        }

        // ==================== STAR RATING ====================
        function setRating(rating) {
            selectedRating = rating;
            document.getElementById('ratingValue').value = rating;
            const stars = document.querySelectorAll('.star-rating .star');
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
        }

        // ==================== REVIEW MODAL ====================
        function openReviewModal() {
            editingReviewId = null;
            document.getElementById('modalTitle').textContent = 'Write a Review';
            document.getElementById('submitBtnText').textContent = 'Submit Review 🚀';
            document.getElementById('editReviewId').value = '';
            document.getElementById('reviewTitle').value = '';
            document.getElementById('reviewText').value = '';
            setRating(5);
            document.getElementById('reviewModal').classList.add('show');
        }

        function closeReviewModal() {
            document.getElementById('reviewModal').classList.remove('show');
        }

        // ==================== EDIT REVIEW ====================
        function editReview(reviewId) {
            const review = allReviews.find(r => r.id === reviewId);
            if (!review) {
                showToast('⚠️ Review not found', 'error');
                return;
            }
            
            editingReviewId = reviewId;
            document.getElementById('modalTitle').textContent = 'Edit Your Review';
            document.getElementById('submitBtnText').textContent = 'Update Review 🔄';
            document.getElementById('editReviewId').value = reviewId;
            document.getElementById('reviewTitle').value = review.title || '';
            document.getElementById('reviewText').value = review.comment || '';
            setRating(review.rating || 5);
            document.getElementById('reviewModal').classList.add('show');
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                closeReviewModal();
            }
        }

        // ==================== SUBMIT / UPDATE REVIEW ====================
        document.getElementById('reviewForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const title = document.getElementById('reviewTitle').value.trim();
            const comment = document.getElementById('reviewText').value.trim();
            const rating = parseInt(document.getElementById('ratingValue').value);
            const editId = document.getElementById('editReviewId').value;
            
            if (!title || !comment) {
                showToast('⚠️ Please fill in all fields', 'warning');
                return;
            }
            
            if (comment.length < 10) {
                showToast('⚠️ Review must be at least 10 characters', 'warning');
                return;
            }
            
            const btn = document.getElementById('submitReviewBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
            
            try {
                const userRef = database.ref('users/' + currentUser.uid);
                const snapshot = await userRef.once('value');
                const userData = snapshot.val();
                const userName = userData ? `${userData.firstName || ''} ${userData.lastName || ''}`.trim() || 'Anonymous' : 'Anonymous';
                
                if (editId) {
                    await database.ref('reviews/' + editId).update({
                        rating: rating,
                        title: title,
                        comment: comment,
                        updatedAt: new Date().toISOString()
                    });
                    showToast('✅ Review updated successfully! 🔄', 'success');
                } else {
                    const reviewRef = database.ref('reviews').push();
                    await reviewRef.set({
                        userId: currentUser.uid,
                        userName: userName,
                        rating: rating,
                        title: title,
                        comment: comment,
                        helpfulCount: 0,
                        timestamp: new Date().toISOString(),
                        isApproved: true
                    });
                    showToast('✅ Review submitted successfully! 🎉', 'success');
                }
                
                closeReviewModal();
                document.getElementById('reviewForm').reset();
                setRating(5);
                
            } catch (error) {
                showToast('❌ Error: ' + error.message, 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane"></i> <span id="submitBtnText">' + (editId ? 'Update Review 🔄' : 'Submit Review 🚀') + '</span>';
            }
        });

        // ==================== DELETE REVIEW (User) ====================
        function deleteReview(reviewId) {
            if (!confirm('🗑️ Are you sure you want to delete your review?')) return;
            
            database.ref('reviews/' + reviewId).remove()
                .then(() => {
                    showToast('🗑️ Review deleted successfully!', 'success');
                })
                .catch((error) => {
                    showToast('❌ Error deleting review: ' + error.message, 'error');
                });
        }

        // ==================== ADMIN DELETE REVIEW ====================
        function adminDeleteReview(reviewId, userName) {
            if (!confirm(`🗑️ Are you sure you want to delete ${userName}'s review? This cannot be undone!`)) return;
            if (!confirm('⚠️ Confirm: This will permanently delete this review from the system.')) return;
            
            database.ref('reviews/' + reviewId).remove()
                .then(() => {
                    showToast(`🗑️ Review by ${userName} deleted by admin!`, 'success');
                    // Log admin action
                    console.log(`🗑️ Admin ${currentUser.email} deleted review ${reviewId} by ${userName}`);
                })
                .catch((error) => {
                    showToast('❌ Error deleting review: ' + error.message, 'error');
                });
        }

        // ==================== MARK HELPFUL ====================
        function markHelpful(reviewId) {
            const reviewRef = database.ref('reviews/' + reviewId);
            reviewRef.transaction(function(currentData) {
                if (currentData) {
                    currentData.helpfulCount = (currentData.helpfulCount || 0) + 1;
                }
                return currentData;
            }).then(() => {
                showToast('👍 Thanks for your feedback!', 'success');
            }).catch((error) => {
                showToast('❌ Error: ' + error.message, 'error');
            });
        }

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
        window.setRating = setRating;
        window.openReviewModal = openReviewModal;
        window.closeReviewModal = closeReviewModal;
        window.markHelpful = markHelpful;
        window.showToast = showToast;
        window.editReview = editReview;
        window.deleteReview = deleteReview;
        window.adminDeleteReview = adminDeleteReview;
        window.toggleMyReviews = toggleMyReviews;
        window.toggleTheme = toggleTheme;

        console.log('⭐ Reviews page loaded with admin delete and theme support!');
        console.log('👑 Admin mode:', isAdmin);
    </script>
</body>
</html>