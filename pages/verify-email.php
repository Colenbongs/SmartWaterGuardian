<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - Smart Water Guardian</title>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-auth-compat.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #05080f;
            padding: 20px;
        }
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
        .verify-card {
            background: rgba(4,8,18,0.96);
            backdrop-filter: blur(30px);
            border-radius: 28px;
            padding: 48px;
            max-width: 480px;
            width: 100%;
            border: 1px solid rgba(127,201,255,0.08);
            text-align: center;
            position: relative;
            z-index: 1;
            animation: slideUp 0.6s cubic-bezier(0.4,0,0.2,1);
        }
        .verify-card::before {
            content: '';
            position: absolute;
            top: -1px; left: -1px; right: -1px; bottom: -1px;
            border-radius: 28px;
            background: linear-gradient(135deg, rgba(127,201,255,0.05), transparent 50%, rgba(123,47,252,0.03));
            z-index: -1;
            pointer-events: none;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .verify-icon { font-size: 72px; margin-bottom: 16px; display: block; }
        .verify-card h1 { color: #7fc9ff; font-size: 28px; font-weight: 800; margin-bottom: 8px; }
        .verify-card .subtitle { color: rgba(127,201,255,0.3); font-size: 15px; line-height: 1.6; margin-bottom: 24px; }
        .btn {
            padding: 14px 32px;
            border: none;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            justify-content: center;
            width: 100%;
        }
        .btn-primary { background: linear-gradient(135deg, #7fc9ff, #7b2ffc); color: #05080f; }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 8px 40px rgba(127,201,255,0.2); }
        .btn-outline { background: transparent; color: #7fc9ff; border: 1px solid rgba(127,201,255,0.12); }
        .btn-outline:hover { background: rgba(127,201,255,0.04); transform: translateY(-2px); }
        .btn-success { background: rgba(0,255,136,0.1); color: #00ff88; border: 1px solid rgba(0,255,136,0.08); }
        .btn-success:hover { background: rgba(0,255,136,0.15); transform: translateY(-2px); }
        .status { padding: 14px 18px; border-radius: 12px; margin-bottom: 16px; font-size: 14px; text-align: left; display: flex; align-items: center; gap: 10px; }
        .status.success { background: rgba(0,255,136,0.06); color: #00ff88; border: 1px solid rgba(0,255,136,0.08); }
        .status.error { background: rgba(255,107,107,0.06); color: #ff6b6b; border: 1px solid rgba(255,107,107,0.08); }
        .status.info { background: rgba(127,201,255,0.06); color: #7fc9ff; border: 1px solid rgba(127,201,255,0.08); }
        .status.warning { background: rgba(255,215,0,0.06); color: #ffd700; border: 1px solid rgba(255,215,0,0.08); }
        .mt-12 { margin-top: 12px; }
        .flex { display: flex; gap: 12px; }
        .flex .btn { width: 50%; }
        .hidden { display: none !important; }
        .email-display { 
            background: rgba(127,201,255,0.04);
            padding: 8px 16px;
            border-radius: 8px;
            font-family: monospace;
            color: #7fc9ff;
            font-size: 14px;
            display: inline-block;
            margin: 8px 0 16px;
        }
        @media (max-width: 480px) { 
            .verify-card { padding: 28px 20px; border-radius: 20px; }
            .flex { flex-direction: column; }
            .flex .btn { width: 100%; }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="bg-animation">
        <div class="orb"></div>
        <div class="orb"></div>
    </div>

    <div class="verify-card">
        <span class="verify-icon" id="statusIcon">📧</span>
        <h1 id="statusTitle">Verify Your Email</h1>
        <p class="subtitle" id="statusMessage">We've sent a verification link to your email. Please check your inbox.</p>
        
        <div class="email-display" id="emailDisplay">user@example.com</div>
        
        <div id="statusContainer"></div>
        
        <button class="btn btn-primary" id="resendBtn" onclick="resendVerification()">
            <i class="fas fa-redo"></i> Resend Verification
        </button>
        
        <div class="flex mt-12">
            <button class="btn btn-outline" onclick="window.location.href='login.php'">
                <i class="fas fa-sign-in-alt"></i> Back to Login
            </button>
            <button class="btn btn-outline" onclick="window.location.href='dashboard.php'">
                <i class="fas fa-home"></i> Go to Dashboard
            </button>
        </div>
    </div>

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

        let currentUser = null;
        let verificationAttempts = 0;
        const maxAttempts = 5;

        // Check auth state
        auth.onAuthStateChanged(async function(user) {
            if (user) {
                currentUser = user;
                document.getElementById('emailDisplay').textContent = user.email;
                
                // Check if email is already verified
                if (user.emailVerified) {
                    showVerified();
                    return;
                }
                
                // Check if verification email was sent recently
                const lastSent = localStorage.getItem('last_verification_sent');
                if (lastSent && (Date.now() - parseInt(lastSent)) < 60000) {
                    showStatus('info', '📧 Verification email already sent. Please check your inbox.');
                } else {
                    // Auto-send verification
                    await sendVerification();
                }
                
                // Start checking for verification
                checkVerificationStatus();
            } else {
                window.location.href = 'login.php';
            }
        });

        function showVerified() {
            document.getElementById('statusIcon').textContent = '✅';
            document.getElementById('statusTitle').textContent = 'Email Verified!';
            document.getElementById('statusMessage').textContent = 'Your email has been verified. You can now access all features.';
            document.getElementById('resendBtn').style.display = 'none';
            document.getElementById('statusContainer').innerHTML = `
                <div class="status success">✅ Your email is verified!</div>
                <button class="btn btn-success mt-12" onclick="window.location.href='dashboard.php'">
                    <i class="fas fa-home"></i> Go to Dashboard
                </button>
            `;
        }

        function showStatus(type, message) {
            const icons = {
                success: '✅',
                error: '❌',
                info: 'ℹ️',
                warning: '⚠️'
            };
            document.getElementById('statusContainer').innerHTML = `
                <div class="status ${type}">${icons[type] || 'ℹ️'} ${message}</div>
            `;
        }

        async function sendVerification() {
            if (!currentUser) return;
            try {
                await currentUser.sendEmailVerification();
                localStorage.setItem('last_verification_sent', Date.now().toString());
                showStatus('info', '📧 Verification email sent to ' + currentUser.email);
            } catch (error) {
                showStatus('error', '❌ Failed to send verification: ' + error.message);
            }
        }

        async function resendVerification() {
            if (!currentUser) {
                window.location.href = 'login.php';
                return;
            }
            
            verificationAttempts++;
            if (verificationAttempts > maxAttempts) {
                showStatus('warning', '⚠️ Too many attempts. Please wait a few minutes.');
                return;
            }
            
            await sendVerification();
            showStatus('info', '📧 Verification email resent! Check your inbox.');
            
            // Reset attempts after 5 minutes
            setTimeout(() => { verificationAttempts = 0; }, 300000);
        }

        function checkVerificationStatus() {
            let checkCount = 0;
            const maxChecks = 30; // Check for 30 seconds
            
            const interval = setInterval(async () => {
                checkCount++;
                if (checkCount > maxChecks) {
                    clearInterval(interval);
                    if (!currentUser.emailVerified) {
                        showStatus('warning', '⏳ Still waiting for verification. Click "Resend" if needed.');
                    }
                    return;
                }
                
                if (currentUser) {
                    await currentUser.reload();
                    if (currentUser.emailVerified) {
                        clearInterval(interval);
                        showVerified();
                    }
                }
            }, 1000);
        }
    </script>
</body>
</html>