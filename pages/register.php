<?php
/**
 * Smart Water Guardian - Registration Page
 * Professional Design with Firebase + MySQL Sync
 * Consumer & Admin Registration with Pending Approval
 * Includes: Full validation, error handling, 3-attempt lockout
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['user_id']) && $_SESSION['logged_in']) {
    header('Location: dashboard.php');
    exit();
}

// Initialize registration attempts counter
if (!isset($_SESSION['reg_attempts'])) {
    $_SESSION['reg_attempts'] = 0;
}
if (!isset($_SESSION['reg_blocked_until'])) {
    $_SESSION['reg_blocked_until'] = null;
}

// Check if user is blocked
$is_blocked = false;
$block_message = '';
if ($_SESSION['reg_blocked_until'] && time() < $_SESSION['reg_blocked_until']) {
    $is_blocked = true;
    $remaining = ceil(($_SESSION['reg_blocked_until'] - time()) / 60);
    $block_message = 'Too many failed registration attempts. Please try again in ' . $remaining . ' minute(s).';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Smart Water Guardian</title>
    
    <!-- Firebase SDK -->
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-auth-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-database-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-storage-compat.js"></script>
    
    <!-- Font Awesome -->
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 20px;
            position: relative;
            overflow-y: auto;
        }
        
        .bg-image {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 0;
            background: 
                linear-gradient(135deg, rgba(240, 248, 255, 0.85) 0%, rgba(220, 240, 255, 0.75) 50%, rgba(230, 245, 255, 0.85) 100%),
                url('https://images.unsplash.com/photo-1541701494587-cb58502866ab?w=1600&q=80') center/cover no-repeat;
            background-attachment: fixed;
        }
        
        .droplets {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 0;
            pointer-events: none;
            overflow: hidden;
        }
        
        .droplet {
            position: absolute;
            border-radius: 50%;
            animation: dropletFall linear infinite;
            opacity: 0;
        }
        
        .droplet:nth-child(1) { left: 5%; width: 6px; height: 6px; background: radial-gradient(circle, rgba(0, 100, 200, 0.3), rgba(0, 100, 200, 0.05)); box-shadow: 0 0 20px rgba(0, 100, 200, 0.15); animation-duration: 14s; animation-delay: 0s; }
        .droplet:nth-child(2) { left: 15%; width: 8px; height: 8px; background: radial-gradient(circle, rgba(0, 80, 180, 0.25), rgba(0, 80, 180, 0.05)); box-shadow: 0 0 25px rgba(0, 80, 180, 0.15); animation-duration: 18s; animation-delay: 3s; }
        .droplet:nth-child(3) { left: 25%; width: 4px; height: 4px; background: radial-gradient(circle, rgba(0, 100, 200, 0.4), rgba(0, 100, 200, 0.05)); box-shadow: 0 0 15px rgba(0, 100, 200, 0.2); animation-duration: 12s; animation-delay: 5s; }
        .droplet:nth-child(4) { left: 35%; width: 10px; height: 10px; background: radial-gradient(circle, rgba(0, 60, 150, 0.2), rgba(0, 60, 150, 0.03)); box-shadow: 0 0 30px rgba(0, 60, 150, 0.1); animation-duration: 20s; animation-delay: 2s; }
        .droplet:nth-child(5) { left: 45%; width: 5px; height: 5px; background: radial-gradient(circle, rgba(0, 90, 190, 0.35), rgba(0, 90, 190, 0.05)); box-shadow: 0 0 18px rgba(0, 90, 190, 0.15); animation-duration: 16s; animation-delay: 4s; }
        .droplet:nth-child(6) { left: 55%; width: 7px; height: 7px; background: radial-gradient(circle, rgba(0, 80, 180, 0.3), rgba(0, 80, 180, 0.05)); box-shadow: 0 0 22px rgba(0, 80, 180, 0.15); animation-duration: 13s; animation-delay: 6s; }
        .droplet:nth-child(7) { left: 65%; width: 9px; height: 9px; background: radial-gradient(circle, rgba(0, 60, 150, 0.25), rgba(0, 60, 150, 0.03)); box-shadow: 0 0 28px rgba(0, 60, 150, 0.1); animation-duration: 19s; animation-delay: 1s; }
        .droplet:nth-child(8) { left: 75%; width: 4px; height: 4px; background: radial-gradient(circle, rgba(0, 100, 200, 0.4), rgba(0, 100, 200, 0.05)); box-shadow: 0 0 16px rgba(0, 100, 200, 0.2); animation-duration: 15s; animation-delay: 5s; }
        .droplet:nth-child(9) { left: 85%; width: 8px; height: 8px; background: radial-gradient(circle, rgba(0, 80, 180, 0.3), rgba(0, 80, 180, 0.05)); box-shadow: 0 0 24px rgba(0, 80, 180, 0.15); animation-duration: 17s; animation-delay: 3s; }
        .droplet:nth-child(10) { left: 95%; width: 5px; height: 5px; background: radial-gradient(circle, rgba(0, 90, 190, 0.35), rgba(0, 90, 190, 0.05)); box-shadow: 0 0 18px rgba(0, 90, 190, 0.15); animation-duration: 14s; animation-delay: 7s; }
        
        @keyframes dropletFall {
            0% { transform: translateY(-100px) scale(1) rotate(0deg); opacity: 0; }
            10% { opacity: 1; }
            50% { transform: translateY(50vh) scale(0.8) rotate(180deg); opacity: 0.8; }
            90% { opacity: 0.6; }
            100% { transform: translateY(110vh) scale(0.3) rotate(360deg); opacity: 0; }
        }
        
        .register-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 560px;
            animation: slideUp 0.8s cubic-bezier(0.4, 0, 0.2, 1);
            margin: 10px 0;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(50px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .register-card {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border-radius: 28px;
            padding: 36px 40px 32px;
            border: 2px solid rgba(0, 80, 180, 0.12);
            box-shadow: 
                0 0 40px rgba(0, 80, 180, 0.04),
                0 0 80px rgba(0, 80, 180, 0.02),
                inset 0 0 40px rgba(0, 80, 180, 0.02);
            position: relative;
            transition: all 0.5s ease;
            max-height: 85vh;
            overflow-y: auto;
        }
        
        .register-card::-webkit-scrollbar {
            width: 4px;
        }
        .register-card::-webkit-scrollbar-track {
            background: rgba(0, 80, 180, 0.02);
            border-radius: 10px;
        }
        .register-card::-webkit-scrollbar-thumb {
            background: rgba(0, 80, 180, 0.15);
            border-radius: 10px;
        }
        
        .register-card::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            border-radius: 28px;
            background: linear-gradient(135deg, rgba(0, 80, 180, 0.15), transparent 40%, rgba(0, 60, 150, 0.08));
            z-index: -1;
            animation: borderGlow 4s ease-in-out infinite alternate;
        }
        
        @keyframes borderGlow {
            0% { opacity: 0.5; }
            50% { opacity: 1; }
            100% { opacity: 0.5; }
        }
        
        .register-card:hover {
            border-color: rgba(0, 80, 180, 0.2);
            box-shadow: 
                0 0 60px rgba(0, 80, 180, 0.06),
                0 0 120px rgba(0, 80, 180, 0.03),
                inset 0 0 60px rgba(0, 80, 180, 0.03);
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #2a4a6a;
            margin-bottom: 16px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: fit-content;
        }
        
        .back-link:hover {
            color: #004488;
            transform: translateX(-4px);
        }
        
        .back-link i {
            transition: transform 0.3s ease;
        }
        
        .back-link:hover i {
            transform: translateX(-4px);
        }
        
        .register-header {
            text-align: left;
            margin-bottom: 24px;
        }
        
        .register-logo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0055aa, #003366);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 0 12px 0;
            font-size: 26px;
            color: white;
            box-shadow: 0 0 40px rgba(0, 80, 180, 0.15);
            animation: pulseLogo 3s ease-in-out infinite;
            position: relative;
        }
        
        @keyframes pulseLogo {
            0%, 100% { box-shadow: 0 0 40px rgba(0, 80, 180, 0.15); }
            50% { box-shadow: 0 0 60px rgba(0, 80, 180, 0.25); }
        }
        
        .register-header h1 {
            font-size: 26px;
            font-weight: 800;
            color: #001a33;
            letter-spacing: -0.5px;
            line-height: 1.2;
        }
        
        .register-header h1 .highlight {
            color: #004488;
        }
        
        .register-header p {
            color: #2a4a6a;
            font-size: 14px;
            font-weight: 500;
            margin-top: 2px;
        }
        
        .alert {
            padding: 10px 14px;
            border-radius: 10px;
            margin-bottom: 12px;
            font-size: 13px;
            display: none;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            animation: slideDown 0.3s ease;
        }
        
        .alert.show {
            display: flex;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert-success {
            background: rgba(0, 160, 80, 0.06);
            color: #005588;
            border: 1px solid rgba(0, 160, 80, 0.05);
        }
        
        .alert-danger {
            background: rgba(200, 50, 50, 0.08);
            color: #992222;
            border: 1px solid rgba(200, 50, 50, 0.06);
        }
        
        .alert-warning {
            background: rgba(200, 160, 0, 0.08);
            color: #8a6a00;
            border: 1px solid rgba(200, 160, 0, 0.06);
        }
        
        .alert-info {
            background: rgba(0, 100, 200, 0.05);
            color: #004488;
            border: 1px solid rgba(0, 100, 200, 0.04);
        }
        
        .alert i {
            font-size: 16px;
        }
        
        .form-group {
            margin-bottom: 16px;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: #1a3a5c;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            text-align: left;
        }
        
        .form-group label i {
            margin-right: 6px;
            color: #3a6a9a;
        }
        
        .form-group label .required {
            color: #cc3333;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid rgba(0, 60, 120, 0.08);
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.06);
            color: #001a33;
            font-weight: 500;
            text-align: left;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: rgba(0, 80, 180, 0.25);
            background: rgba(255, 255, 255, 0.12);
            box-shadow: 0 0 30px rgba(0, 80, 180, 0.04);
        }
        
        .form-group input::placeholder {
            color: #4a6a8a;
            font-weight: 400;
            font-size: 13px;
        }
        
        .form-group input.error {
            border-color: rgba(200, 50, 50, 0.2);
            background: rgba(200, 50, 50, 0.03);
        }
        
        .form-group input.success {
            border-color: rgba(0, 160, 80, 0.15);
            background: rgba(0, 160, 80, 0.03);
        }
        
        .form-group .input-error-text {
            font-size: 11px;
            color: #cc3333;
            margin-top: 4px;
            display: none;
            font-weight: 500;
        }
        
        .form-group .input-error-text.show {
            display: block;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }
        
        .form-helper {
            font-size: 11px;
            color: #4a6a8a;
            margin-top: 4px;
            font-weight: 400;
            text-align: left;
        }
        
        .form-helper i {
            margin-right: 4px;
        }
        
        .form-helper.error {
            color: #cc3333;
        }
        
        .form-helper.success {
            color: #006644;
        }
        
        .password-wrapper {
            position: relative;
        }
        
        .password-wrapper input {
            padding-right: 44px;
        }
        
        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #3a5a7a;
            cursor: pointer;
            font-size: 16px;
            padding: 4px;
            transition: color 0.3s ease;
        }
        
        .toggle-password:hover {
            color: #004488;
        }
        
        .password-strength {
            margin-top: 8px;
        }
        
        .strength-bar {
            width: 100%;
            height: 4px;
            background: rgba(0, 40, 80, 0.05);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .strength-fill {
            height: 100%;
            transition: width 0.3s ease;
            border-radius: 4px;
        }
        
        .strength-text {
            display: block;
            font-size: 11px;
            margin-top: 4px;
            font-weight: 500;
            color: #4a6a8a;
            text-align: left;
        }
        
        .role-selection {
            display: flex;
            gap: 12px;
            margin: 4px 0;
        }
        
        .role-card {
            flex: 1;
            border: 2px solid rgba(0, 80, 180, 0.08);
            border-radius: 14px;
            padding: 14px 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(255, 255, 255, 0.06);
            position: relative;
        }
        
        .role-card:hover {
            border-color: rgba(0, 80, 180, 0.12);
            background: rgba(255, 255, 255, 0.08);
        }
        
        .role-card.selected {
            border-color: rgba(0, 80, 180, 0.2);
            background: rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 30px rgba(0, 80, 180, 0.03);
        }
        
        .role-card.selected::after {
            content: '✓';
            position: absolute;
            top: -8px;
            right: -8px;
            width: 22px;
            height: 22px;
            background: linear-gradient(135deg, #0055aa, #003366);
            border-radius: 50%;
            color: white;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            box-shadow: 0 0 20px rgba(0, 80, 180, 0.15);
        }
        
        .role-card .role-icon {
            font-size: 28px;
            margin-bottom: 4px;
            display: block;
        }
        
        .role-card .role-name {
            font-size: 14px;
            font-weight: 700;
            color: #1a3a5c;
        }
        
        .role-card .role-desc {
            font-size: 11px;
            color: #3a5a7a;
            margin-top: 2px;
        }
        
        .role-card input[type="radio"] {
            display: none;
        }
        
        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin: 14px 0;
            text-align: left;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            min-width: 18px;
            margin-top: 2px;
            accent-color: #004488;
            cursor: pointer;
        }
        
        .checkbox-group label {
            font-size: 13px;
            color: #1a3a5c;
            cursor: pointer;
            line-height: 1.5;
            font-weight: 500;
            text-align: left;
        }
        
        .checkbox-group label a {
            color: #004488;
            text-decoration: none;
            font-weight: 600;
        }
        
        .checkbox-group label a:hover {
            color: #003366;
        }
        
        .checkbox-group .checkbox-error {
            font-size: 11px;
            color: #cc3333;
            display: none;
            margin-top: 4px;
        }
        
        .checkbox-group .checkbox-error.show {
            display: block;
        }
        
        .btn-register {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #0055aa, #003366);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 30px rgba(0, 80, 180, 0.12);
        }
        
        .btn-register::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: conic-gradient(
                from 0deg,
                transparent 0%,
                rgba(255, 255, 255, 0.06) 25%,
                transparent 50%,
                rgba(255, 255, 255, 0.06) 75%,
                transparent 100%
            );
            animation: rotateGlow 6s linear infinite;
        }
        
        @keyframes rotateGlow {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 50px rgba(0, 80, 180, 0.2);
        }
        
        .btn-register:active {
            transform: scale(0.98);
        }
        
        .btn-register:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-register .spinner {
            display: none;
            animation: spin 1s linear infinite;
        }
        
        .btn-register.loading .spinner {
            display: inline-block;
        }
        
        .btn-register.loading .btn-text {
            display: none;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .register-footer {
            text-align: center;
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px solid rgba(0, 60, 120, 0.06);
        }
        
        .register-footer p {
            color: #1a3a5c;
            font-size: 13px;
            font-weight: 500;
        }
        
        .register-footer a {
            color: #004488;
            font-weight: 700;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .register-footer a:hover {
            color: #003366;
        }
        
        .website-link {
            text-align: center;
            margin-top: 10px;
            font-size: 11px;
            color: #3a5a7a;
            letter-spacing: 2px;
            text-transform: uppercase;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        
        .website-link:hover {
            color: #1a3a5c;
        }
        
        /* ============================================================ */
        /* PENDING APPROVAL OVERLAY - CLEAN LIGHT DESIGN                 */
        /* ============================================================ */
        .pending-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .pending-overlay.show {
            display: flex;
            animation: pendingFadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @keyframes pendingFadeIn {
            from { opacity: 0; transform: scale(0.96); }
            to { opacity: 1; transform: scale(1); }
        }
        
        .pending-modal {
            background: #ffffff;
            border-radius: 24px;
            padding: 44px 40px 38px;
            max-width: 420px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(0, 0, 0, 0.02);
            text-align: center;
            animation: pendingBounceIn 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @keyframes pendingBounceIn {
            0% { transform: scale(0.92) translateY(12px); opacity: 0; }
            60% { transform: scale(1.02) translateY(-4px); }
            100% { transform: scale(1) translateY(0); opacity: 1; }
        }
        
        .pending-icon-wrapper {
            display: flex;
            justify-content: center;
            margin-bottom: 18px;
        }
        
        .pending-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f0f7ff, #e6f0fa);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            color: #0055aa;
            position: relative;
        }
        
        .pending-icon::after {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(0, 85, 170, 0.08), transparent 60%);
            z-index: -1;
        }
        
        .pending-modal h2 {
            font-size: 24px;
            font-weight: 700;
            color: #0a1e2f;
            margin: 0 0 4px 0;
            letter-spacing: -0.3px;
        }
        
        .pending-subtitle {
            font-size: 15px;
            font-weight: 600;
            color: #b8860b;
            margin: 0 0 18px 0;
            background: rgba(184, 134, 11, 0.06);
            padding: 6px 16px;
            border-radius: 20px;
            display: inline-block;
        }
        
        .pending-divider {
            width: 40px;
            height: 3px;
            background: linear-gradient(90deg, #0055aa, #0077cc);
            border-radius: 4px;
            margin: 0 auto 18px auto;
        }
        
        .pending-message {
            font-size: 14px;
            color: #3a4a5a;
            line-height: 1.6;
            margin: 0 0 16px 0;
        }
        
        .pending-email-box {
            background: #f5f9fe;
            border: 1px solid #e8f0fa;
            border-radius: 12px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin: 0 0 12px 0;
            color: #1a3a5c;
            font-size: 13px;
            font-weight: 500;
        }
        
        .pending-email-box i {
            color: #0055aa;
            font-size: 16px;
        }
        
        .pending-note {
            font-size: 12px;
            color: #7a8a9a;
            margin: 0 0 22px 0;
            font-weight: 400;
        }
        
        .pending-btn-login {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 32px;
            background: linear-gradient(135deg, #0055aa, #003d7a);
            color: #ffffff;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 16px rgba(0, 85, 170, 0.15);
        }
        
        .pending-btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 85, 170, 0.2);
            background: linear-gradient(135deg, #0066cc, #004488);
        }
        
        .pending-btn-login:active {
            transform: scale(0.97);
        }
        
        .pending-btn-login i {
            font-size: 14px;
        }
        
        /* Blocked overlay */
        .blocked-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(12px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .blocked-overlay.show {
            display: flex;
            animation: pendingFadeIn 0.4s ease;
        }
        
        .blocked-modal {
            background: #ffffff;
            border-radius: 24px;
            padding: 40px 36px;
            max-width: 400px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            animation: pendingBounceIn 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .blocked-modal .blocked-icon {
            font-size: 48px;
            color: #cc3333;
            margin-bottom: 16px;
            display: block;
        }
        
        .blocked-modal h2 {
            font-size: 22px;
            color: #0a1e2f;
            margin-bottom: 8px;
        }
        
        .blocked-modal p {
            color: #3a4a5a;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 16px;
        }
        
        .blocked-modal .blocked-timer {
            background: #f5f5f5;
            padding: 10px 16px;
            border-radius: 10px;
            font-size: 13px;
            color: #1a3a5c;
            font-weight: 500;
            margin-bottom: 16px;
        }
        
        .blocked-modal .btn-retry {
            padding: 10px 32px;
            background: #0055aa;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .blocked-modal .btn-retry:hover {
            background: #003d7a;
            transform: translateY(-2px);
        }
        
        .blocked-modal .btn-retry:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            transform: none;
        }
        
        /* ============================================================ */
        /* RESPONSIVE                                                   */
        /* ============================================================ */
        @media (max-width: 480px) {
            .register-card { padding: 24px 18px; border-radius: 20px; max-height: 90vh; }
            .form-row { grid-template-columns: 1fr; gap: 0; }
            .register-header h1 { font-size: 22px; }
            .role-selection { flex-direction: column; gap: 8px; }
            .role-card { padding: 12px 14px; }
            .register-logo { width: 50px; height: 50px; font-size: 22px; }
            .pending-modal { padding: 28px 20px; margin: 16px; }
            .blocked-modal { padding: 28px 20px; margin: 16px; }
            .back-link { font-size: 12px; }
        }
        
        @media (max-height: 700px) {
            .register-card { padding: 20px 24px; max-height: 92vh; }
            .register-logo { width: 44px; height: 44px; font-size: 18px; margin-bottom: 8px; }
            .register-header { margin-bottom: 14px; }
            .register-header h1 { font-size: 20px; }
            .form-group { margin-bottom: 10px; }
            .form-group input { padding: 9px 14px; font-size: 13px; }
            .btn-register { padding: 11px; font-size: 14px; }
            .register-footer { margin-top: 12px; padding-top: 10px; }
            .website-link { margin-top: 6px; }
            .back-link { margin-bottom: 10px; font-size: 12px; }
            .pending-modal { padding: 24px 20px; }
            .pending-icon { width: 54px; height: 54px; font-size: 24px; }
        }
    </style>
</head>
<body>
    <div class="bg-image"></div>
    
    <div class="droplets">
        <div class="droplet"></div>
        <div class="droplet"></div>
        <div class="droplet"></div>
        <div class="droplet"></div>
        <div class="droplet"></div>
        <div class="droplet"></div>
        <div class="droplet"></div>
        <div class="droplet"></div>
        <div class="droplet"></div>
        <div class="droplet"></div>
    </div>

    <div class="register-container">
        <a href="../index.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>
        
        <div class="register-card">
            <div class="register-header">
                <div class="register-logo">
                    <i class="fas fa-water"></i>
                </div>
                <h1>Create <span class="highlight">Account</span></h1>
                <p>Join the Smart Water Guardian community</p>
            </div>
            
            <!-- Alerts -->
            <div id="alert-success" class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span id="successMessage">Account created successfully!</span>
            </div>
            <div id="alert-error" class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span id="errorMessage">An error occurred</span>
            </div>
            <div id="alert-warning" class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <span id="warningMessage">Please check your input</span>
            </div>
            <div id="alert-info" class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <span id="infoMessage">Loading...</span>
            </div>
            
            <?php if ($is_blocked): ?>
            <div class="alert alert-danger show" style="display:flex;margin-bottom:16px;">
                <i class="fas fa-ban"></i>
                <span><?php echo htmlspecialchars($block_message); ?></span>
            </div>
            <?php endif; ?>
            
            <!-- Registration Form -->
            <form id="registerForm" novalidate>
                <!-- Name Fields -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="firstName"><i class="fas fa-user"></i> First Name <span class="required">*</span></label>
                        <input type="text" id="firstName" placeholder="e.g., Sipho" required maxlength="50">
                        <div class="input-error-text" id="firstNameError">First name must be at least 2 characters</div>
                    </div>
                    <div class="form-group">
                        <label for="lastName"><i class="fas fa-user"></i> Last Name <span class="required">*</span></label>
                        <input type="text" id="lastName" placeholder="e.g., Zulu" required maxlength="50">
                        <div class="input-error-text" id="lastNameError">Last name must be at least 2 characters</div>
                    </div>
                </div>
                
                <!-- Email -->
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email Address <span class="required">*</span></label>
                    <input type="email" id="email" placeholder="sipho@example.com" required>
                    <div class="input-error-text" id="emailError">Please enter a valid email address</div>
                </div>
                
                <!-- Password -->
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password <span class="required">*</span></label>
                    <div class="password-wrapper">
                        <input type="password" id="password" placeholder="Min 8 characters" required minlength="8">
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <i class="fas fa-eye" id="passwordIcon"></i>
                        </button>
                    </div>
                    <div class="password-strength">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill" style="width:0%;"></div>
                        </div>
                        <span class="strength-text" id="strengthText">Enter a password</span>
                    </div>
                    <div class="input-error-text" id="passwordError">Password must have at least 8 characters, an uppercase, lowercase, number and special character</div>
                </div>
                
                <!-- Confirm Password -->
                <div class="form-group">
                    <label for="confirmPassword"><i class="fas fa-check-circle"></i> Confirm Password <span class="required">*</span></label>
                    <div class="password-wrapper">
                        <input type="password" id="confirmPassword" placeholder="Confirm your password" required>
                        <button type="button" class="toggle-password" onclick="toggleConfirmPassword()">
                            <i class="fas fa-eye" id="confirmIcon"></i>
                        </button>
                    </div>
                    <div class="form-helper" id="confirmHelper"><i class="fas fa-info-circle"></i> Re-enter your password</div>
                    <div class="input-error-text" id="confirmError">Passwords do not match</div>
                </div>
                
                <!-- Phone -->
                <div class="form-group">
                    <label for="phone"><i class="fas fa-phone"></i> Phone Number <span class="required">*</span></label>
                    <input type="tel" id="phone" placeholder="082 123 4567" required maxlength="15">
                    <div class="input-error-text" id="phoneError">Please enter a valid 10-digit phone number</div>
                </div>
                
                <!-- Address -->
                <div class="form-group">
                    <label for="address"><i class="fas fa-home"></i> Address <span class="required">*</span></label>
                    <input type="text" id="address" placeholder="123 Vilakazi Street, Soweto" required>
                    <div class="input-error-text" id="addressError">Please enter a valid address (minimum 5 characters)</div>
                </div>
                
                <!-- Meter Number -->
                <div class="form-group">
                    <label for="meterNumber"><i class="fas fa-qrcode"></i> Meter Number <span class="required">*</span></label>
                    <input type="text" id="meterNumber" placeholder="MTR-2026-0001" required>
                    <div class="meter-format" style="font-size:11px;color:#4a6a8a;margin-top:4px;">
                        <i class="fas fa-info-circle"></i> Format: MTR-YYYY-XXXX or any 10-15 digit number
                    </div>
                    <div class="input-error-text" id="meterError">Please enter a valid meter number</div>
                </div>
                
                <!-- Role Selection -->
                <div class="form-group">
                    <label><i class="fas fa-user-tag"></i> Account Type <span class="required">*</span></label>
                    <div class="role-selection">
                        <label class="role-card selected" id="role-consumer" onclick="selectRole('consumer')">
                            <input type="radio" name="role" value="consumer" checked>
                            <span class="role-icon">Home</span>
                            <span class="role-name">Consumer</span>
                            <span class="role-desc">Homeowner tracking water usage</span>
                        </label>
                        <label class="role-card" id="role-admin" onclick="selectRole('admin')">
                            <input type="radio" name="role" value="admin">
                            <span class="role-icon">Admin</span>
                            <span class="role-name">Admin</span>
                            <span class="role-desc">Municipal/System administrator</span>
                        </label>
                    </div>
                    <div class="form-helper"><i class="fas fa-info-circle"></i> Select your account type</div>
                </div>
                
                <!-- Terms -->
                <div class="checkbox-group">
                    <input type="checkbox" id="terms" required>
                    <label for="terms">
                        I agree to the <a href="#" onclick="alert('Terms of Service coming soon!')">Terms of Service</a> 
                        and <a href="#" onclick="alert('Privacy Policy coming soon!')">Privacy Policy</a>
                        <span style="color:#cc3333;">*</span>
                    </label>
                </div>
                <div class="checkbox-group">
                    <div class="input-error-text" id="termsError">Please agree to the Terms and Privacy Policy</div>
                </div>
                
                <button type="submit" class="btn-register" id="registerBtn" <?php echo $is_blocked ? 'disabled' : ''; ?>>
                    <span class="btn-text"><i class="fas fa-user-plus"></i> Create Account</span>
                    <span class="spinner"><i class="fas fa-spinner"></i></span>
                </button>
            </form>
            
            <div class="register-footer">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
            
            <div class="website-link">
                WWW.SMARTWATER.CO.ZA
            </div>
        </div>
    </div>

    <!-- PENDING APPROVAL OVERLAY - CLEAN LIGHT DESIGN -->
    <div class="pending-overlay" id="pendingOverlay">
        <div class="pending-modal">
            <div class="pending-icon-wrapper">
                <div class="pending-icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
            <h2>Registration Submitted</h2>
            <p class="pending-subtitle">Your account is pending approval</p>
            <div class="pending-divider"></div>
            <p class="pending-message">You will receive an email notification once your account has been approved by an administrator.</p>
            <div class="pending-email-box">
                <i class="fas fa-envelope"></i>
                <span>Check your email for confirmation</span>
            </div>
            <p class="pending-note">You can login once your account is approved.</p>
            <button class="pending-btn-login" onclick="goToLogin()">
                <i class="fas fa-arrow-left"></i> Back to Login
            </button>
        </div>
    </div>

    <!-- BLOCKED OVERLAY -->
    <div class="blocked-overlay" id="blockedOverlay">
        <div class="blocked-modal">
            <span class="blocked-icon"><i class="fas fa-ban"></i></span>
            <h2>Account Locked</h2>
            <p>Too many failed registration attempts. Your account has been temporarily locked for security purposes.</p>
            <div class="blocked-timer" id="blockedTimer">Please wait <span id="countdownDisplay">5</span> minutes</div>
            <button class="btn-retry" id="retryBtn" onclick="location.reload()" disabled>Retry</button>
        </div>
    </div>

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

        let selectedRole = 'consumer';
        let registrationAttempts = 0;
        const MAX_ATTEMPTS = 3;

        // ==================== CHECK IF BLOCKED ====================
        function checkIfBlocked() {
            const blockedUntil = sessionStorage.getItem('reg_blocked_until');
            if (blockedUntil) {
                const blockTime = parseInt(blockedUntil);
                const now = Date.now();
                if (now < blockTime) {
                    const remaining = Math.ceil((blockTime - now) / 60000);
                    document.getElementById('blockedOverlay').classList.add('show');
                    document.getElementById('countdownDisplay').textContent = remaining;
                    startCountdown(blockTime);
                    return true;
                } else {
                    sessionStorage.removeItem('reg_blocked_until');
                    sessionStorage.removeItem('reg_attempts');
                }
            }
            return false;
        }

        function startCountdown(blockTime) {
            const interval = setInterval(function() {
                const now = Date.now();
                const remaining = Math.ceil((blockTime - now) / 60000);
                if (remaining <= 0) {
                    clearInterval(interval);
                    document.getElementById('blockedOverlay').classList.remove('show');
                    sessionStorage.removeItem('reg_blocked_until');
                    sessionStorage.removeItem('reg_attempts');
                    document.getElementById('registerBtn').disabled = false;
                } else {
                    document.getElementById('countdownDisplay').textContent = remaining;
                }
            }, 10000);
        }

        // ==================== ROLE SELECTION ====================
        function selectRole(role) {
            selectedRole = role;
            document.querySelectorAll('.role-card').forEach(card => {
                card.classList.remove('selected');
            });
            if (role === 'consumer') {
                document.getElementById('role-consumer').classList.add('selected');
                document.querySelector('input[name="role"][value="consumer"]').checked = true;
            } else {
                document.getElementById('role-admin').classList.add('selected');
                document.querySelector('input[name="role"][value="admin"]').checked = true;
            }
        }

        // ==================== TOGGLE PASSWORD ====================
        function togglePassword() {
            const password = document.getElementById('password');
            const icon = document.getElementById('passwordIcon');
            if (password.type === 'password') {
                password.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                password.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }

        function toggleConfirmPassword() {
            const password = document.getElementById('confirmPassword');
            const icon = document.getElementById('confirmIcon');
            if (password.type === 'password') {
                password.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                password.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }

        // ==================== SHOW ALERT ====================
        function showAlert(type, message) {
            const alerts = {
                success: document.getElementById('alert-success'),
                error: document.getElementById('alert-error'),
                warning: document.getElementById('alert-warning'),
                info: document.getElementById('alert-info')
            };
            
            Object.values(alerts).forEach(el => el.classList.remove('show'));
            
            if (alerts[type]) {
                const msgEl = document.getElementById(type === 'success' ? 'successMessage' : 
                                                       type === 'error' ? 'errorMessage' : 
                                                       type === 'warning' ? 'warningMessage' : 'infoMessage');
                if (msgEl) msgEl.textContent = message;
                alerts[type].classList.add('show');
                
                setTimeout(() => {
                    alerts[type].classList.remove('show');
                }, 6000);
            }
        }

        // ==================== GO TO LOGIN ====================
        function goToLogin() {
            window.location.href = 'login.php';
        }

        // ==================== VALIDATION FUNCTIONS ====================
        function validateEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        function validatePhone(phone) {
            const clean = phone.replace(/[^0-9]/g, '');
            return clean.length === 10;
        }

        function validateMeterNumber(meter) {
            const clean = meter.replace(/[^A-Za-z0-9]/g, '');
            if (/^MTR-\d{4}-\d{4}$/.test(meter)) return true;
            if (/^\d{3,4}-\d{4}-\d{4}$/.test(meter)) return true;
            if (/^\d{8,20}$/.test(clean)) return true;
            if (clean.length >= 8 && clean.length <= 20) return true;
            return false;
        }

        function validatePassword(password) {
            const errors = [];
            if (password.length < 8) errors.push('at least 8 characters');
            if (!/[A-Z]/.test(password)) errors.push('an uppercase letter');
            if (!/[a-z]/.test(password)) errors.push('a lowercase letter');
            if (!/[0-9]/.test(password)) errors.push('a number');
            if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) errors.push('a special character');
            return errors;
        }

        // ==================== PASSWORD STRENGTH ====================
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirmPassword');
        const strengthFill = document.getElementById('strengthFill');
        const strengthText = document.getElementById('strengthText');
        const confirmHelper = document.getElementById('confirmHelper');

        function getPasswordStrength(password) {
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]+/)) strength++;
            if (password.match(/[A-Z]+/)) strength++;
            if (password.match(/[0-9]+/)) strength++;
            if (password.match(/[!@#$%^&*(),.?":{}|<>]+/)) strength++;
            return strength;
        }

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const strength = getPasswordStrength(password);
            const percent = (strength / 5) * 100;
            const colors = ['#cc3333', '#cc8833', '#ccaa33', '#33aa66', '#006644'];
            const labels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
            
            strengthFill.style.width = percent + '%';
            strengthFill.style.background = colors[strength - 1] || 'rgba(0,40,80,0.05)';
            strengthText.textContent = strength > 0 ? labels[strength - 1] : 'Enter a password';
            strengthText.style.color = colors[strength - 1] || '#4a6a8a';
            
            // Hide/show password error
            const passwordError = document.getElementById('passwordError');
            if (password.length > 0 && strength < 3) {
                passwordError.classList.add('show');
            } else {
                passwordError.classList.remove('show');
            }
            
            if (confirmInput.value) checkPasswordMatch();
        });

        confirmInput.addEventListener('input', checkPasswordMatch);

        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirm = confirmInput.value;
            const confirmError = document.getElementById('confirmError');
            
            if (confirm.length === 0) {
                confirmHelper.innerHTML = '<i class="fas fa-info-circle"></i> Re-enter your password';
                confirmHelper.className = 'form-helper';
                confirmInput.className = '';
                confirmError.classList.remove('show');
                return;
            }
            
            if (password === confirm) {
                confirmHelper.innerHTML = '<i class="fas fa-check-circle"></i> Passwords match';
                confirmHelper.className = 'form-helper success';
                confirmInput.className = 'success';
                confirmError.classList.remove('show');
            } else {
                confirmHelper.innerHTML = '<i class="fas fa-exclamation-circle"></i> Passwords do not match';
                confirmHelper.className = 'form-helper error';
                confirmInput.className = 'error';
                confirmError.classList.add('show');
            }
        }

        // ==================== REAL-TIME VALIDATION ====================
        document.getElementById('firstName').addEventListener('blur', function() {
            const error = document.getElementById('firstNameError');
            if (this.value.trim().length >= 2) {
                this.className = 'success';
                error.classList.remove('show');
            } else if (this.value.trim().length > 0) {
                this.className = 'error';
                error.classList.add('show');
            }
        });

        document.getElementById('firstName').addEventListener('input', function() {
            if (this.value.trim().length >= 2) {
                this.className = 'success';
                document.getElementById('firstNameError').classList.remove('show');
            }
        });

        document.getElementById('lastName').addEventListener('blur', function() {
            const error = document.getElementById('lastNameError');
            if (this.value.trim().length >= 2) {
                this.className = 'success';
                error.classList.remove('show');
            } else if (this.value.trim().length > 0) {
                this.className = 'error';
                error.classList.add('show');
            }
        });

        document.getElementById('lastName').addEventListener('input', function() {
            if (this.value.trim().length >= 2) {
                this.className = 'success';
                document.getElementById('lastNameError').classList.remove('show');
            }
        });

        document.getElementById('email').addEventListener('blur', function() {
            const error = document.getElementById('emailError');
            if (this.value && validateEmail(this.value)) {
                this.className = 'success';
                error.classList.remove('show');
            } else if (this.value) {
                this.className = 'error';
                error.classList.add('show');
            }
        });

        document.getElementById('email').addEventListener('input', function() {
            if (this.value && validateEmail(this.value)) {
                this.className = 'success';
                document.getElementById('emailError').classList.remove('show');
            }
        });

        document.getElementById('phone').addEventListener('blur', function() {
            const error = document.getElementById('phoneError');
            const clean = this.value.replace(/[^0-9]/g, '');
            if (this.value && clean.length === 10) {
                this.className = 'success';
                error.classList.remove('show');
            } else if (this.value) {
                this.className = 'error';
                error.classList.add('show');
            }
        });

        document.getElementById('phone').addEventListener('input', function() {
            const clean = this.value.replace(/[^0-9]/g, '');
            if (clean.length === 10) {
                this.className = 'success';
                document.getElementById('phoneError').classList.remove('show');
            }
        });

        document.getElementById('address').addEventListener('blur', function() {
            const error = document.getElementById('addressError');
            if (this.value.trim().length >= 5) {
                this.className = 'success';
                error.classList.remove('show');
            } else if (this.value.trim().length > 0) {
                this.className = 'error';
                error.classList.add('show');
            }
        });

        document.getElementById('address').addEventListener('input', function() {
            if (this.value.trim().length >= 5) {
                this.className = 'success';
                document.getElementById('addressError').classList.remove('show');
            }
        });

        document.getElementById('meterNumber').addEventListener('blur', function() {
            const error = document.getElementById('meterError');
            const val = this.value.toUpperCase();
            if (this.value && validateMeterNumber(val)) {
                this.className = 'success';
                error.classList.remove('show');
            } else if (this.value) {
                this.className = 'error';
                error.classList.add('show');
            }
        });

        document.getElementById('meterNumber').addEventListener('input', function() {
            let val = this.value.toUpperCase();
            if (val.startsWith('MTR')) {
                val = val.replace(/[^A-Z0-9]/g, '');
                if (val.length > 3 && val.length <= 7) {
                    val = val.substring(0, 3) + '-' + val.substring(3);
                } else if (val.length > 7 && val.length <= 11) {
                    val = val.substring(0, 3) + '-' + val.substring(4, 8) + '-' + val.substring(8);
                } else if (val.length > 11) {
                    val = val.substring(0, 15);
                }
                this.value = val;
            }
            if (this.value && validateMeterNumber(this.value)) {
                this.className = 'success';
                document.getElementById('meterError').classList.remove('show');
            }
        });

        // ==================== SEND PENDING APPROVAL EMAIL ====================
        async function sendPendingApprovalEmail(userEmail, firstName) {
            try {
                const response = await fetch('../api/send-notification.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        email: userEmail,
                        type: 'pending_approval',
                        name: firstName || 'User'
                    })
                });
                const result = await response.json();
                return result.success;
            } catch (error) {
                console.warn('Email notification failed:', error);
                return false;
            }
        }

        // ==================== REGISTER FORM ====================
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Check if blocked
            if (checkIfBlocked()) {
                return;
            }
            
            const firstName = document.getElementById('firstName').value.trim();
            const lastName = document.getElementById('lastName').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirmPassword').value;
            const phone = document.getElementById('phone').value.trim();
            const address = document.getElementById('address').value.trim();
            const meterNumber = document.getElementById('meterNumber').value.trim().toUpperCase();
            const role = document.querySelector('input[name="role"]:checked').value;
            const terms = document.getElementById('terms').checked;
            
            let hasError = false;
            
            // ==================== FIELD VALIDATION WITH ERROR MESSAGES ====================
            // First Name
            if (!firstName || firstName.length < 2) {
                document.getElementById('firstName').className = 'error';
                document.getElementById('firstNameError').classList.add('show');
                hasError = true;
            }
            
            // Last Name
            if (!lastName || lastName.length < 2) {
                document.getElementById('lastName').className = 'error';
                document.getElementById('lastNameError').classList.add('show');
                hasError = true;
            }
            
            // Email
            if (!email || !validateEmail(email)) {
                document.getElementById('email').className = 'error';
                document.getElementById('emailError').classList.add('show');
                hasError = true;
            }
            
            // Password
            const passwordErrors = validatePassword(password);
            if (passwordErrors.length > 0) {
                document.getElementById('password').className = 'error';
                document.getElementById('passwordError').classList.add('show');
                hasError = true;
            }
            
            // Confirm Password
            if (password !== confirm || confirm.length === 0) {
                document.getElementById('confirmPassword').className = 'error';
                document.getElementById('confirmError').classList.add('show');
                hasError = true;
            }
            
            // Phone
            if (!phone || !validatePhone(phone)) {
                document.getElementById('phone').className = 'error';
                document.getElementById('phoneError').classList.add('show');
                hasError = true;
            }
            
            // Address
            if (!address || address.length < 5) {
                document.getElementById('address').className = 'error';
                document.getElementById('addressError').classList.add('show');
                hasError = true;
            }
            
            // Meter Number
            if (!meterNumber || !validateMeterNumber(meterNumber)) {
                document.getElementById('meterNumber').className = 'error';
                document.getElementById('meterError').classList.add('show');
                hasError = true;
            }
            
            // Terms
            if (!terms) {
                document.getElementById('termsError').classList.add('show');
                hasError = true;
            }
            
            if (hasError) {
                showAlert('warning', 'Please fix all errors before continuing.');
                // Scroll to first error
                const firstError = document.querySelector('.form-group input.error, .form-group .input-error-text.show');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return;
            }
            
            // ==================== REGISTRATION ====================
            const btn = document.getElementById('registerBtn');
            btn.classList.add('loading');
            btn.disabled = true;
            
            try {
                // Increment attempts
                registrationAttempts = parseInt(sessionStorage.getItem('reg_attempts') || '0') + 1;
                sessionStorage.setItem('reg_attempts', registrationAttempts);
                
                let userCredential;
                try {
                    userCredential = await auth.createUserWithEmailAndPassword(email, password);
                } catch (authError) {
                    let message = authError.message;
                    switch (authError.code) {
                        case 'auth/email-already-in-use':
                            message = 'This email is already registered. Please login instead.';
                            break;
                        case 'auth/invalid-email':
                            message = 'Please enter a valid email address.';
                            break;
                        case 'auth/weak-password':
                            message = 'Password is too weak. Please use at least 8 characters.';
                            break;
                        case 'auth/network-request-failed':
                            message = 'Network error. Please check your internet connection.';
                            break;
                        default:
                            message = authError.message;
                    }
                    showAlert('error', message);
                    
                    // Check if max attempts reached
                    if (registrationAttempts >= MAX_ATTEMPTS) {
                        const blockTime = Date.now() + (5 * 60 * 1000); // 5 minutes
                        sessionStorage.setItem('reg_blocked_until', blockTime);
                        document.getElementById('blockedOverlay').classList.add('show');
                        document.getElementById('countdownDisplay').textContent = 5;
                        startCountdown(blockTime);
                        btn.disabled = true;
                    }
                    
                    btn.classList.remove('loading');
                    btn.disabled = false;
                    return;
                }
                
                // Reset attempts on success
                sessionStorage.removeItem('reg_attempts');
                
                const user = userCredential.user;
                console.log('Firebase Auth: User created with UID:', user.uid);
                
                const isAdmin = role === 'admin';
                const userRole = isAdmin ? 'system_admin' : 'consumer';
                const isApproved = isAdmin ? true : false;
                
                const userData = {
                    firstName: firstName,
                    lastName: lastName,
                    email: email,
                    phone: phone,
                    address: address,
                    meterNumber: meterNumber,
                    role: userRole,
                    is_approved: isApproved,
                    createdAt: new Date().toISOString(),
                    isActive: true
                };
                
                try {
                    await database.ref('users/' + user.uid).set(userData);
                    console.log('Firebase Realtime: User profile saved');
                } catch (dbError) {
                    console.error('Database error:', dbError);
                    showAlert('error', 'Failed to save user data. Please try again.');
                    btn.classList.remove('loading');
                    btn.disabled = false;
                    return;
                }
                
                if (isAdmin) {
                    try {
                        await database.ref('admin_settings/' + user.uid).set({
                            isSuperAdmin: true,
                            municipality: address || 'System Administrator',
                            createdAt: new Date().toISOString()
                        });
                        console.log('Firebase Realtime: Admin settings saved');
                    } catch (adminError) {
                        console.warn('Admin settings error:', adminError);
                    }
                } else {
                    try {
                        // Consumer setup
                        const propertyRef = database.ref('properties/' + user.uid).push();
                        await propertyRef.set({
                            propertyName: 'My Home',
                            address: address,
                            meterId: meterNumber,
                            createdAt: new Date().toISOString()
                        });
                        console.log('Firebase Realtime: Property saved');
                        
                        // Create thresholds
                        const thresholds = [
                            { type: 'daily_limit', value: 1000 },
                            { type: 'leak_duration', value: 2 },
                            { type: 'flow_rate', value: 20 }
                        ];
                        
                        for (const t of thresholds) {
                            await database.ref('thresholds/' + user.uid).push({
                                thresholdType: t.type,
                                thresholdValue: t.value,
                                isActive: true,
                                createdAt: new Date().toISOString()
                            });
                        }
                        console.log('Firebase Realtime: Thresholds saved');
                        
                        // Register meter
                        await database.ref('meters/' + meterNumber).set({
                            meterId: meterNumber,
                            model: 'ESP32-YF-S201',
                            propertyId: propertyRef.key,
                            registeredAt: new Date().toISOString(),
                            lastReading: {
                                status: 'offline',
                                battery: 100,
                                flow: 0,
                                volume: 0,
                                timestamp: new Date().toISOString()
                            }
                        });
                        console.log('Firebase Realtime: Device registered');
                        
                        // PENDING APPROVAL ALERT
                        const pendingMessage = 'Your account is pending approval. You will receive an email once approved.';
                        await database.ref('alerts/' + user.uid).push({
                            type: 'system',
                            message: pendingMessage,
                            severity: 'info',
                            timestamp: new Date().toISOString(),
                            isRead: false
                        });
                        console.log('Firebase Realtime: Pending approval alert saved');
                    } catch (consumerError) {
                        console.warn('Consumer setup error:', consumerError);
                    }
                }
                
                // ==================== SEND PENDING APPROVAL EMAIL ====================
                if (!isAdmin) {
                    try {
                        await sendPendingApprovalEmail(email, firstName);
                        console.log('Pending approval email sent to:', email);
                    } catch (emailError) {
                        console.warn('Email sending failed:', emailError);
                    }
                } else {
                    try {
                        await database.ref('alerts/' + user.uid).push({
                            type: 'system',
                            message: 'Welcome Admin! You have full access to manage the system.',
                            severity: 'info',
                            timestamp: new Date().toISOString(),
                            isRead: false
                        });
                    } catch (alertError) {
                        console.warn('Admin alert error:', alertError);
                    }
                }
                
                // ==================== SYNC TO MYSQL ====================
                try {
                    const mysqlResponse = await fetch('../api/users.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            firebase_uid: user.uid,
                            email: email,
                            first_name: firstName,
                            last_name: lastName,
                            phone: phone,
                            address: address,
                            meter_number: meterNumber,
                            role: userRole,
                            is_approved: isApproved ? 1 : 0
                        })
                    });
                    const mysqlResult = await mysqlResponse.json();
                    if (mysqlResult.success) {
                        console.log('MySQL: User saved successfully!');
                    } else {
                        console.warn('MySQL Error:', mysqlResult.error);
                    }
                } catch (mysqlError) {
                    console.warn('MySQL Error:', mysqlError.message);
                }
                
                // ==================== SHOW APPROPRIATE OVERLAY ====================
                btn.classList.remove('loading');
                btn.disabled = false;
                
                if (isAdmin) {
                    showAlert('success', 'Admin account created! Redirecting to admin panel...');
                    setTimeout(() => {
                        window.location.href = 'admin.php';
                    }, 1500);
                } else {
                    document.getElementById('pendingOverlay').classList.add('show');
                }
                
            } catch (error) {
                console.error('Registration error:', error);
                showAlert('error', 'Registration failed. Please try again later.');
                btn.classList.remove('loading');
                btn.disabled = false;
            }
        });

        // ==================== CHECK BLOCKED ON LOAD ====================
        document.addEventListener('DOMContentLoaded', function() {
            if (checkIfBlocked()) {
                document.getElementById('registerBtn').disabled = true;
            }
        });

        // ==================== EXPOSE FUNCTIONS ====================
        window.togglePassword = togglePassword;
        window.toggleConfirmPassword = toggleConfirmPassword;
        window.showAlert = showAlert;
        window.goToLogin = goToLogin;
        window.selectRole = selectRole;
        
        console.log('Registration page loaded with full validation and lockout!');
        console.log('Max attempts: ' + MAX_ATTEMPTS);
    </script>
</body>
</html>