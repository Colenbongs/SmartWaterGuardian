<?php
/**
 * Smart Water Guardian - Login Page
 * Full login with approval checking, email notifications, session management
 * Includes Google Sign-In
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Smart Water Guardian</title>
    
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-auth-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-database-compat.js"></script>
    
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
            padding: 40px 20px;
            position: relative;
            overflow: hidden;
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
        
        .droplet:nth-child(1) { left: 5%; width: 6px; height: 6px; background: radial-gradient(circle, rgba(0, 100, 200, 0.4), rgba(0, 100, 200, 0.05)); box-shadow: 0 0 20px rgba(0, 100, 200, 0.2); animation-duration: 14s; animation-delay: 0s; }
        .droplet:nth-child(2) { left: 15%; width: 8px; height: 8px; background: radial-gradient(circle, rgba(0, 80, 180, 0.3), rgba(0, 80, 180, 0.05)); box-shadow: 0 0 25px rgba(0, 80, 180, 0.2); animation-duration: 18s; animation-delay: 3s; }
        .droplet:nth-child(3) { left: 25%; width: 4px; height: 4px; background: radial-gradient(circle, rgba(0, 100, 200, 0.5), rgba(0, 100, 200, 0.05)); box-shadow: 0 0 15px rgba(0, 100, 200, 0.25); animation-duration: 12s; animation-delay: 5s; }
        .droplet:nth-child(4) { left: 35%; width: 10px; height: 10px; background: radial-gradient(circle, rgba(0, 60, 150, 0.25), rgba(0, 60, 150, 0.03)); box-shadow: 0 0 30px rgba(0, 60, 150, 0.15); animation-duration: 20s; animation-delay: 2s; }
        .droplet:nth-child(5) { left: 45%; width: 5px; height: 5px; background: radial-gradient(circle, rgba(0, 90, 190, 0.4), rgba(0, 90, 190, 0.05)); box-shadow: 0 0 18px rgba(0, 90, 190, 0.2); animation-duration: 16s; animation-delay: 4s; }
        .droplet:nth-child(6) { left: 55%; width: 7px; height: 7px; background: radial-gradient(circle, rgba(0, 80, 180, 0.35), rgba(0, 80, 180, 0.05)); box-shadow: 0 0 22px rgba(0, 80, 180, 0.2); animation-duration: 13s; animation-delay: 6s; }
        .droplet:nth-child(7) { left: 65%; width: 9px; height: 9px; background: radial-gradient(circle, rgba(0, 60, 150, 0.3), rgba(0, 60, 150, 0.03)); box-shadow: 0 0 28px rgba(0, 60, 150, 0.15); animation-duration: 19s; animation-delay: 1s; }
        .droplet:nth-child(8) { left: 75%; width: 4px; height: 4px; background: radial-gradient(circle, rgba(0, 100, 200, 0.5), rgba(0, 100, 200, 0.05)); box-shadow: 0 0 16px rgba(0, 100, 200, 0.25); animation-duration: 15s; animation-delay: 5s; }
        .droplet:nth-child(9) { left: 85%; width: 8px; height: 8px; background: radial-gradient(circle, rgba(0, 80, 180, 0.35), rgba(0, 80, 180, 0.05)); box-shadow: 0 0 24px rgba(0, 80, 180, 0.2); animation-duration: 17s; animation-delay: 3s; }
        .droplet:nth-child(10) { left: 95%; width: 5px; height: 5px; background: radial-gradient(circle, rgba(0, 90, 190, 0.4), rgba(0, 90, 190, 0.05)); box-shadow: 0 0 18px rgba(0, 90, 190, 0.2); animation-duration: 14s; animation-delay: 7s; }
        
        @keyframes dropletFall {
            0% { transform: translateY(-100px) scale(1) rotate(0deg); opacity: 0; }
            10% { opacity: 1; }
            50% { transform: translateY(50vh) scale(0.8) rotate(180deg); opacity: 0.8; }
            90% { opacity: 0.6; }
            100% { transform: translateY(110vh) scale(0.3) rotate(360deg); opacity: 0; }
        }
        
        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 1000px;
            animation: slideUp 0.8s cubic-bezier(0.4, 0, 0.2, 1);
            margin: 20px;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(50px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(30px);
            border-radius: 32px;
            padding: 48px;
            border: 2px solid rgba(0, 80, 180, 0.12);
            box-shadow: 
                0 0 40px rgba(0, 80, 180, 0.04),
                0 0 80px rgba(0, 80, 180, 0.02),
                inset 0 0 40px rgba(0, 80, 180, 0.02);
            display: grid;
            grid-template-columns: 340px 1fr;
            gap: 48px;
            position: relative;
            overflow: hidden;
            min-height: 500px;
        }
        
        .login-card::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            border-radius: 32px;
            background: linear-gradient(135deg, rgba(0, 80, 180, 0.15), transparent 40%, rgba(0, 60, 150, 0.08));
            z-index: -1;
            animation: borderGlow 4s ease-in-out infinite alternate;
        }
        
        @keyframes borderGlow {
            0% { opacity: 0.5; }
            50% { opacity: 1; }
            100% { opacity: 0.5; }
        }
        
        .login-card:hover {
            border-color: rgba(0, 80, 180, 0.2);
            box-shadow: 
                0 0 60px rgba(0, 80, 180, 0.06),
                0 0 120px rgba(0, 80, 180, 0.03),
                inset 0 0 60px rgba(0, 80, 180, 0.03);
        }
        
        .left-panel {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding-right: 40px;
            border-right: 1px solid rgba(0, 60, 120, 0.06);
            position: relative;
            z-index: 1;
        }
        
        .left-panel .panel-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #1a3a5c;
            margin-bottom: 20px;
            font-weight: 700;
        }
        
        .left-panel .panel-label i {
            margin-right: 8px;
            color: #2a5a8a;
        }
        
        .role-option {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px 20px;
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(255, 255, 255, 0.04);
            border: 2px solid transparent;
            margin-bottom: 8px;
            position: relative;
        }
        
        .role-option:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(4px);
            border-color: rgba(0, 80, 180, 0.08);
        }
        
        .role-option.selected {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(0, 80, 180, 0.15);
            box-shadow: 0 0 40px rgba(0, 80, 180, 0.04);
        }
        
        .role-option.selected::before {
            content: '';
            position: absolute;
            left: -2px;
            top: 20%;
            height: 60%;
            width: 3px;
            background: linear-gradient(180deg, #0055aa, #003366);
            border-radius: 0 4px 4px 0;
            box-shadow: 0 0 20px rgba(0, 80, 180, 0.2);
        }
        
        .role-option .role-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }
        
        .role-option .role-icon.consumer {
            background: rgba(0, 80, 180, 0.08);
            color: #004488;
        }
        
        .role-option .role-icon.admin {
            background: rgba(0, 50, 120, 0.08);
            color: #002255;
        }
        
        .role-option.selected .role-icon.consumer {
            background: rgba(0, 80, 180, 0.15);
            box-shadow: 0 0 30px rgba(0, 80, 180, 0.04);
        }
        
        .role-option.selected .role-icon.admin {
            background: rgba(0, 50, 120, 0.15);
            box-shadow: 0 0 30px rgba(0, 50, 120, 0.04);
        }
        
        .role-option .role-info {
            flex: 1;
        }
        
        .role-option .role-info .name {
            font-size: 16px;
            font-weight: 700;
            color: #1a2a3a;
            transition: color 0.3s ease;
        }
        
        .role-option.selected .role-info .name {
            color: #001a33;
        }
        
        .role-option .role-info .desc {
            font-size: 13px;
            color: #3a5a7a;
            margin-top: 2px;
            transition: color 0.3s ease;
        }
        
        .role-option.selected .role-info .desc {
            color: #2a4a6a;
        }
        
        .role-option .check-mark {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0055aa, #003366);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: white;
            opacity: 0;
            transform: scale(0.5);
            transition: all 0.3s ease;
            flex-shrink: 0;
            font-weight: 700;
            box-shadow: 0 0 20px rgba(0, 80, 180, 0.15);
        }
        
        .role-option.selected .check-mark {
            opacity: 1;
            transform: scale(1);
        }
        
        .role-option input[type="radio"] {
            display: none;
        }
        
        .right-panel {
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            z-index: 1;
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
        
        .login-header {
            margin-bottom: 28px;
        }
        
        .login-header h1 {
            font-size: 28px;
            font-weight: 800;
            color: #001a33;
            letter-spacing: -0.5px;
            margin-bottom: 4px;
        }
        
        .login-header h1 span {
            color: #004488;
        }
        
        .login-header p {
            color: #2a4a6a;
            font-size: 14px;
            font-weight: 500;
        }
        
        .alert {
            padding: 10px 14px;
            border-radius: 10px;
            margin-bottom: 14px;
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
        
        .alert-success {
            background: rgba(0, 160, 80, 0.06);
            color: #005588;
            border: 1px solid rgba(0, 160, 80, 0.05);
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
            margin-bottom: 14px;
        }
        
        .form-group label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: #1a3a5c;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        
        .form-group label i {
            margin-right: 6px;
            color: #3a6a9a;
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
        }
        
        .form-group input:focus {
            outline: none;
            border-color: rgba(0, 80, 180, 0.2);
            background: rgba(255, 255, 255, 0.12);
            box-shadow: 0 0 40px rgba(0, 80, 180, 0.03);
        }
        
        .form-group input::placeholder {
            color: #4a6a8a;
            font-weight: 400;
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
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 14px 0 18px;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #1a3a5c;
            cursor: pointer;
            transition: color 0.3s ease;
            font-weight: 500;
        }
        
        .checkbox-label:hover {
            color: #001a33;
        }
        
        .checkbox-label input {
            accent-color: #004488;
            width: 16px;
            height: 16px;
            cursor: pointer;
        }
        
        .forgot-link {
            color: #2a5a8a;
            font-size: 13px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .forgot-link:hover {
            color: #003366;
        }
        
        .divider-container {
            display: flex;
            align-items: center;
            margin: 16px 0;
            gap: 16px;
        }
        
        .divider-line {
            flex: 1;
            height: 1px;
            background: rgba(0, 60, 120, 0.08);
        }
        
        .divider-text {
            font-size: 11px;
            color: #3a5a7a;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            white-space: nowrap;
        }
        
        .btn-login {
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
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 30px rgba(0, 80, 180, 0.12);
        }
        
        .btn-login::before {
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
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 50px rgba(0, 80, 180, 0.2);
        }
        
        .btn-login:active {
            transform: scale(0.98);
        }
        
        .btn-login:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-login .spinner {
            display: none;
            animation: spin 1s linear infinite;
        }
        
        .btn-login.loading .spinner {
            display: inline-block;
        }
        
        .btn-login.loading .btn-text {
            display: none;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* ============================================================ */
        /* GOOGLE SIGN-IN BUTTON                                         */
        /* ============================================================ */
        .btn-google {
            width: 100%;
            padding: 12px;
            background: #ffffff;
            color: #1a2a3a;
            border: 2px solid rgba(0, 60, 120, 0.08);
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
        }
        
        .btn-google:hover {
            border-color: rgba(0, 80, 180, 0.15);
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.04);
            background: #f8fafc;
        }
        
        .btn-google:active {
            transform: scale(0.98);
        }
        
        .btn-google:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-google .google-icon {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-google .google-icon svg {
            width: 20px;
            height: 20px;
        }
        
        .btn-google .spinner {
            display: none;
            animation: spin 1s linear infinite;
        }
        
        .btn-google.loading .spinner {
            display: inline-block;
        }
        
        .btn-google.loading .btn-text {
            display: none;
        }
        
        .btn-google.loading .google-icon {
            display: none;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 20px;
        }
        
        .login-footer p {
            color: #1a3a5c;
            font-size: 13px;
            font-weight: 500;
        }
        
        .login-footer a {
            color: #004488;
            font-weight: 700;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .login-footer a:hover {
            color: #003366;
        }
        
        .website-link {
            text-align: center;
            margin-top: 14px;
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
        
        @media (max-width: 820px) {
            .login-card {
                grid-template-columns: 1fr;
                gap: 32px;
                padding: 32px 28px;
                min-height: auto;
            }
            
            .left-panel {
                padding-right: 0;
                border-right: none;
                border-bottom: 1px solid rgba(0, 60, 120, 0.06);
                padding-bottom: 24px;
            }
            
            .left-panel .panel-label {
                margin-bottom: 12px;
            }
            
            .role-option {
                padding: 12px 16px;
            }
            
            .role-option .role-icon {
                width: 40px;
                height: 40px;
                font-size: 20px;
            }
            
            .login-header h1 {
                font-size: 24px;
            }
        }
        
        @media (max-width: 480px) {
            .login-card {
                padding: 24px 16px;
                border-radius: 24px;
            }
            
            .role-option .role-info .name {
                font-size: 14px;
            }
            
            .role-option .role-info .desc {
                font-size: 12px;
            }
            
            .form-options {
                flex-direction: column;
                gap: 8px;
                align-items: flex-start;
            }
            
            .btn-google {
                font-size: 14px;
                padding: 10px;
            }
        }
        
        @media (max-height: 700px) {
            .login-card {
                padding: 24px 28px;
                min-height: auto;
            }
            
            .left-panel {
                padding-bottom: 16px;
            }
            
            .role-option {
                padding: 10px 14px;
                margin-bottom: 4px;
            }
            
            .role-option .role-icon {
                width: 36px;
                height: 36px;
                font-size: 18px;
            }
            
            .login-header {
                margin-bottom: 16px;
            }
            
            .login-header h1 {
                font-size: 22px;
            }
            
            .form-group {
                margin-bottom: 10px;
            }
            
            .form-group input {
                padding: 10px 14px;
            }
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

    <div class="login-container">
        <div class="login-card">
            
            <!-- Left Panel - Role Selection -->
            <div class="left-panel">
                <div class="panel-label">
                    <i class="fas fa-user-tag"></i> Login as...
                </div>
                
                <label class="role-option selected" id="role-consumer" onclick="selectRole('consumer')">
                    <input type="radio" name="login_role" value="consumer" checked>
                    <div class="role-icon consumer">Home</div>
                    <div class="role-info">
                        <div class="name">Consumer</div>
                        <div class="desc">View water usage and alerts</div>
                    </div>
                    <div class="check-mark">✓</div>
                </label>
                
                <label class="role-option" id="role-admin" onclick="selectRole('admin')">
                    <input type="radio" name="login_role" value="admin">
                    <div class="role-icon admin">Admin</div>
                    <div class="role-info">
                        <div class="name">Admin</div>
                        <div class="desc">Manage system and users</div>
                    </div>
                    <div class="check-mark">✓</div>
                </label>
            </div>
            
            <!-- Right Panel - Login Form -->
            <div class="right-panel">
                <a href="../index.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
                
                <div class="login-header">
                    <h1>Welcome <span>Back</span></h1>
                    <p>Login to your Smart Water Guardian account</p>
                </div>
                
                <div id="alert-error" class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span id="errorMessage">Invalid credentials</span>
                </div>
                <div id="alert-warning" class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span id="warningMessage">Please select your role</span>
                </div>
                <div id="alert-success" class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span id="successMessage">Login successful!</span>
                </div>
                <div id="alert-info" class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <span id="infoMessage">Your account is pending approval</span>
                </div>
                
                <form id="loginForm">
                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                        <input type="email" id="email" placeholder="you@example.com" required autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label for="password"><i class="fas fa-lock"></i> Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" placeholder="Enter your password" required>
                            <button type="button" class="toggle-password" onclick="togglePassword()">
                                <i class="fas fa-eye" id="passwordIcon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-options">
                        <label class="checkbox-label">
                            <input type="checkbox" id="remember">
                            Remember me
                        </label>
                        <a href="#" class="forgot-link" onclick="forgotPassword()">Forgot password?</a>
                    </div>
                    
                    <button type="submit" class="btn-login" id="loginBtn">
                        <span class="btn-text"><i class="fas fa-sign-in-alt"></i> Login</span>
                        <span class="spinner"><i class="fas fa-spinner"></i></span>
                    </button>
                </form>
                
                <!-- Divider -->
                <div class="divider-container">
                    <span class="divider-line"></span>
                    <span class="divider-text">or continue with</span>
                    <span class="divider-line"></span>
                </div>
                
                <!-- Google Sign-In Button -->
                <button class="btn-google" id="googleBtn" onclick="signInWithGoogle()">
                    <span class="google-icon">
                        <svg viewBox="0 0 48 48">
                            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                            <path fill="none" d="M0 0h48v48H0z"/>
                        </svg>
                    </span>
                    <span class="btn-text">Sign in with Google</span>
                    <span class="spinner"><i class="fas fa-spinner"></i></span>
                </button>
                
                <div class="login-footer">
                    <p>Don't have an account? <a href="register.php">Create Account</a></p>
                </div>
                
                <div class="website-link">
                    WWW.SMARTWATER.CO.ZA
                </div>
            </div>
            
        </div>
    </div>

    <script>
        // ============================================================
        // FIREBASE CONFIGURATION
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

        let selectedLoginRole = 'consumer';
        let loginAttempts = 0;
        let googleLoginInProgress = false;

        // ============================================================
        // ROLE SELECTION
        // ============================================================
        function selectRole(role) {
            selectedLoginRole = role;
            document.querySelectorAll('.role-option').forEach(option => {
                option.classList.remove('selected');
            });
            if (role === 'consumer') {
                document.getElementById('role-consumer').classList.add('selected');
                document.querySelector('input[name="login_role"][value="consumer"]').checked = true;
            } else {
                document.getElementById('role-admin').classList.add('selected');
                document.querySelector('input[name="login_role"][value="admin"]').checked = true;
            }
        }

        // ============================================================
        // TOGGLE PASSWORD VISIBILITY
        // ============================================================
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

        // ============================================================
        // VALIDATION HELPERS
        // ============================================================
        function validateEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        // ============================================================
        // ALERT SYSTEM
        // ============================================================
        function showAlert(type, message) {
            const alerts = {
                error: document.getElementById('alert-error'),
                warning: document.getElementById('alert-warning'),
                success: document.getElementById('alert-success'),
                info: document.getElementById('alert-info')
            };
            
            Object.values(alerts).forEach(el => el.classList.remove('show'));
            
            if (alerts[type]) {
                const msgEl = document.getElementById(type === 'error' ? 'errorMessage' : 
                                                       type === 'warning' ? 'warningMessage' : 
                                                       type === 'success' ? 'successMessage' : 
                                                       type === 'info' ? 'infoMessage' : '');
                if (msgEl) msgEl.textContent = message;
                alerts[type].classList.add('show');
                
                const timeout = type === 'info' ? 8000 : 5000;
                setTimeout(() => {
                    alerts[type].classList.remove('show');
                }, timeout);
            }
        }

        // ============================================================
        // CREATE USER DATA IN FIREBASE (if missing)
        // ============================================================
        async function createUserData(uid, email, displayName, photoURL) {
            try {
                const userRef = database.ref('users/' + uid);
                const snapshot = await userRef.once('value');
                
                if (snapshot.exists()) {
                    return snapshot.val();
                }
                
                console.log('User data not found, creating...');
                
                // Check if this is an admin email
                const isAdmin = email === 'admin@smartwater.com' || 
                               email === 'admin@smartwater.co.za' ||
                               email === 'ncubemcliff@gmail.com';
                const role = isAdmin ? 'system_admin' : 'consumer';
                
                const nameParts = displayName ? displayName.split(' ') : ['User', ''];
                const firstName = nameParts[0] || 'User';
                const lastName = nameParts.slice(1).join(' ') || '';
                
                const userData = {
                    firstName: firstName,
                    lastName: lastName,
                    email: email,
                    phone: '',
                    address: isAdmin ? 'System Administrator' : '',
                    role: role,
                    photoURL: photoURL || '',
                    createdAt: new Date().toISOString(),
                    is_approved: isAdmin ? true : false,
                    is_active: true,
                    authProvider: 'google'
                };
                
                await userRef.set(userData);
                console.log('Created user data for:', uid);
                
                if (isAdmin) {
                    await database.ref('admin_settings/' + uid).set({
                        isSuperAdmin: true,
                        municipality: 'System Administrator',
                        createdAt: new Date().toISOString()
                    });
                } else {
                    const propertyRef = database.ref('properties/' + uid).push();
                    await propertyRef.set({
                        propertyName: 'My Home',
                        address: 'Not specified',
                        meterId: 'meter_' + Date.now(),
                        createdAt: new Date().toISOString()
                    });
                    
                    const thresholds = [
                        { type: 'daily_limit', value: 1000 },
                        { type: 'leak_duration', value: 2 },
                        { type: 'flow_rate', value: 20 }
                    ];
                    
                    for (const t of thresholds) {
                        await database.ref('thresholds/' + uid).push({
                            thresholdType: t.type,
                            thresholdValue: t.value,
                            isActive: true,
                            createdAt: new Date().toISOString()
                        });
                    }
                }
                
                const welcomeMessage = isAdmin 
                    ? 'Welcome Admin! You have full access to manage the system.'
                    : 'Welcome to Smart Water Guardian! Your account is pending approval.';
                
                await database.ref('alerts/' + uid).push({
                    type: 'system',
                    message: welcomeMessage,
                    severity: 'info',
                    timestamp: new Date().toISOString(),
                    isRead: false
                });
                
                // Sync to MySQL
                try {
                    const mysqlResponse = await fetch('../api/users.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            firebase_uid: uid,
                            email: email,
                            first_name: firstName,
                            last_name: lastName,
                            phone: '',
                            address: isAdmin ? 'System Administrator' : '',
                            role: role,
                            is_approved: isAdmin ? 1 : 0,
                            photo_url: photoURL || ''
                        })
                    });
                    const mysqlResult = await mysqlResponse.json();
                    if (mysqlResult.success) {
                        console.log('MySQL: User saved successfully!');
                    }
                } catch (mysqlError) {
                    console.warn('MySQL Error:', mysqlError.message);
                }
                
                return userData;
                
            } catch (error) {
                console.error('Error creating user data:', error);
                throw error;
            }
        }

        // ============================================================
        // SEND PENDING APPROVAL REMINDER EMAIL
        // ============================================================
        async function sendPendingReminderEmail(email, firstName) {
            try {
                const response = await fetch('../api/send-notification.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        email: email,
                        type: 'pending_approval_reminder',
                        name: firstName || 'User'
                    })
                });
                const result = await response.json();
                return result.success;
            } catch (error) {
                console.warn('Reminder email failed:', error);
                return false;
            }
        }

        // ============================================================
        // LOGIN FORM HANDLER - WITH FULL APPROVAL CHECK
        // ============================================================
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const role = document.querySelector('input[name="login_role"]:checked').value;
            
            if (!email || !password) {
                showAlert('error', 'Please enter your email and password');
                return;
            }
            
            if (!validateEmail(email)) {
                showAlert('error', 'Please enter a valid email address');
                return;
            }
            
            const btn = document.getElementById('loginBtn');
            btn.classList.add('loading');
            btn.disabled = true;
            
            document.getElementById('alert-error').classList.remove('show');
            document.getElementById('alert-warning').classList.remove('show');
            document.getElementById('alert-info').classList.remove('show');
            document.getElementById('alert-success').classList.remove('show');
            
            try {
                const userCredential = await auth.signInWithEmailAndPassword(email, password);
                await handleSuccessfulLogin(userCredential.user, role);
            } catch (error) {
                handleLoginError(error);
                btn.classList.remove('loading');
                btn.disabled = false;
            }
        });

        // ============================================================
        // GOOGLE SIGN-IN
        // ============================================================
        async function signInWithGoogle() {
            if (googleLoginInProgress) return;
            
            const btn = document.getElementById('googleBtn');
            googleLoginInProgress = true;
            btn.classList.add('loading');
            btn.disabled = true;
            
            document.getElementById('alert-error').classList.remove('show');
            document.getElementById('alert-warning').classList.remove('show');
            document.getElementById('alert-info').classList.remove('show');
            document.getElementById('alert-success').classList.remove('show');
            
            try {
                const provider = new firebase.auth.GoogleAuthProvider();
                provider.setCustomParameters({
                    prompt: 'select_account'
                });
                
                const result = await auth.signInWithPopup(provider);
                const user = result.user;
                
                // Get role from selection
                const role = document.querySelector('input[name="login_role"]:checked').value;
                
                // Get additional user info from Google
                const credential = result.credential;
                const photoURL = user.photoURL || '';
                const displayName = user.displayName || '';
                
                // Handle the login
                await handleSuccessfulLogin(user, role, displayName, photoURL);
                
            } catch (error) {
                console.error('Google Sign-In Error:', error);
                
                let message = error.message;
                if (error.code === 'auth/popup-closed-by-user') {
                    message = 'Sign-in popup was closed. Please try again.';
                } else if (error.code === 'auth/popup-blocked') {
                    message = 'Pop-up was blocked. Please allow pop-ups for this site.';
                } else if (error.code === 'auth/cancelled-popup-request') {
                    message = 'Sign-in was cancelled. Please try again.';
                } else if (error.code === 'auth/network-request-failed') {
                    message = 'Network error. Please check your internet connection.';
                }
                
                showAlert('error', 'Google Sign-In failed: ' + message);
                googleLoginInProgress = false;
                btn.classList.remove('loading');
                btn.disabled = false;
            }
        }

        // ============================================================
        // HANDLE SUCCESSFUL LOGIN (Shared between email and Google)
        // ============================================================
        async function handleSuccessfulLogin(user, selectedRole, displayName, photoURL) {
            try {
                // Get user data from Firebase
                const userRef = database.ref('users/' + user.uid);
                const snapshot = await userRef.once('value');
                let userData = snapshot.val();
                
                // If user data doesn't exist, create it
                if (!userData) {
                    showAlert('info', 'Setting up your account...');
                    userData = await createUserData(user.uid, user.email, displayName || user.displayName, photoURL || user.photoURL);
                }
                
                // Check if account is disabled
                if (userData.isActive === false) {
                    showAlert('error', 'This account has been disabled. Please contact support.');
                    await auth.signOut();
                    return;
                }
                
                // Approval check
                const isAdminUser = userData.role === 'system_admin' || 
                                   userData.role === 'municipal_admin' || 
                                   userData.role === 'admin';
                
                if (!isAdminUser && userData.is_approved === false) {
                    showAlert('info', 'Your account is pending approval. Please check your email for confirmation.');
                    await sendPendingReminderEmail(user.email, userData.firstName);
                    await auth.signOut();
                    return;
                }
                
                // Role verification
                const userRole = userData.role || 'consumer';
                let finalRole = userRole;
                
                if (selectedRole === 'admin') {
                    if (userRole !== 'system_admin' && userRole !== 'municipal_admin' && userRole !== 'admin') {
                        showAlert('error', 'This account does not have admin privileges. Please login as Consumer.');
                        await auth.signOut();
                        return;
                    }
                }
                
                // Set PHP session
                const response = await fetch('../api/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'set_session',
                        uid: user.uid,
                        email: user.email,
                        firstName: userData.firstName || '',
                        lastName: userData.lastName || '',
                        role: finalRole,
                        photoURL: userData.photoURL || ''
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('success', 'Login successful! Redirecting...');
                    
                    await database.ref('users/' + user.uid).update({
                        last_login: new Date().toISOString()
                    });
                    
                    setTimeout(() => {
                        if (finalRole === 'system_admin' || finalRole === 'municipal_admin' || finalRole === 'admin') {
                            window.location.href = 'admin.php';
                        } else {
                            window.location.href = 'dashboard.php';
                        }
                    }, 500);
                } else {
                    throw new Error('Session creation failed');
                }
                
            } catch (error) {
                console.error('Login error:', error);
                showAlert('error', error.message || 'Login failed. Please try again.');
                
                // Reset Google button if it was Google login
                if (document.getElementById('googleBtn').classList.contains('loading')) {
                    document.getElementById('googleBtn').classList.remove('loading');
                    document.getElementById('googleBtn').disabled = false;
                    googleLoginInProgress = false;
                }
                
                // Reset email login button
                document.getElementById('loginBtn').classList.remove('loading');
                document.getElementById('loginBtn').disabled = false;
            }
        }

        // ============================================================
        // HANDLE LOGIN ERROR
        // ============================================================
        function handleLoginError(error) {
            let message = error.message;
            
            switch (error.code) {
                case 'auth/user-not-found':
                    message = 'No account found with this email address. Please register first.';
                    break;
                case 'auth/wrong-password':
                    message = 'Incorrect password. Please try again.';
                    loginAttempts++;
                    if (loginAttempts >= 3) {
                        message += ' Too many failed attempts.';
                    }
                    break;
                case 'auth/too-many-requests':
                    message = 'Too many failed attempts. Please try again later or reset your password.';
                    break;
                case 'auth/user-disabled':
                    message = 'This account has been disabled. Please contact support.';
                    break;
                case 'auth/invalid-email':
                    message = 'Invalid email format.';
                    break;
                case 'auth/network-request-failed':
                    message = 'Network error. Please check your internet connection.';
                    break;
                default:
                    message = error.message || 'Login failed. Please try again.';
            }
            
            showAlert('error', message);
        }

        // ============================================================
        // FORGOT PASSWORD
        // ============================================================
        function forgotPassword() {
            const email = prompt('Enter your email address to reset your password:');
            if (email && validateEmail(email)) {
                auth.sendPasswordResetEmail(email)
                    .then(() => {
                        showAlert('success', 'Password reset email sent! Check your inbox.');
                    })
                    .catch((error) => {
                        let message = error.message;
                        if (error.code === 'auth/user-not-found') {
                            message = 'No account found with this email address.';
                        }
                        showAlert('error', message);
                    });
            } else if (email) {
                showAlert('warning', 'Please enter a valid email address');
            }
        }

        // ============================================================
        // ENTER KEY SUPPORT
        // ============================================================
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const form = document.getElementById('loginForm');
                const active = document.activeElement;
                if (active === document.getElementById('email') || 
                    active === document.getElementById('password')) {
                    form.dispatchEvent(new Event('submit'));
                }
            }
        });

        // ============================================================
        // EXPOSE FUNCTIONS TO GLOBAL SCOPE
        // ============================================================
        window.togglePassword = togglePassword;
        window.forgotPassword = forgotPassword;
        window.selectRole = selectRole;
        window.showAlert = showAlert;
        window.signInWithGoogle = signInWithGoogle;
        
        console.log('Login page loaded with Google Sign-In and full approval checking!');
    </script>
</body>
</html>