<?php
session_start();
if (!isset($_SESSION['user_id']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup 2FA - Smart Water Guardian</title>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-auth-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-database-compat.js"></script>
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
        .container {
            background: rgba(4,8,18,0.96);
            backdrop-filter: blur(30px);
            border-radius: 28px;
            padding: 44px 40px;
            max-width: 500px;
            width: 100%;
            border: 1px solid rgba(127,201,255,0.08);
            position: relative;
            z-index: 1;
            animation: slideUp 0.6s cubic-bezier(0.4,0,0.2,1);
        }
        .container::before {
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
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: rgba(127,201,255,0.3);
            text-decoration: none;
            font-size: 13px;
            margin-bottom: 16px;
            transition: color 0.3s;
        }
        .back-link:hover { color: #7fc9ff; }
        h1 { color: #7fc9ff; font-size: 24px; font-weight: 700; margin-bottom: 8px; }
        .subtitle { color: rgba(127,201,255,0.3); font-size: 14px; margin-bottom: 24px; line-height: 1.6; }
        .qr-container { 
            text-align: center; 
            padding: 24px; 
            background: rgba(127,201,255,0.02); 
            border-radius: 16px; 
            margin-bottom: 20px;
            border: 1px solid rgba(127,201,255,0.04);
        }
        .qr-placeholder { font-size: 80px; display: block; }
        .qr-container .hint { font-size: 12px; color: rgba(127,201,255,0.15); margin-top: 8px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { 
            display: block; 
            color: rgba(127,201,255,0.4); 
            font-size: 11px; 
            font-weight: 600; 
            text-transform: uppercase; 
            letter-spacing: 0.5px;
            margin-bottom: 6px; 
        }
        .form-group input { 
            width: 100%; 
            padding: 12px 16px; 
            border: 1px solid rgba(127,201,255,0.06); 
            border-radius: 12px; 
            background: rgba(127,201,255,0.02); 
            color: #b8e6ff; 
            font-size: 16px;
            text-align: center;
            font-family: monospace;
            letter-spacing: 2px;
        }
        .form-group input:focus { outline: none; border-color: rgba(127,201,255,0.15); }
        .form-group input::placeholder { color: rgba(127,201,255,0.08); }
        .btn { 
            padding: 14px 24px; 
            border: none; 
            border-radius: 12px; 
            font-size: 14px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-primary { background: linear-gradient(135deg, #7fc9ff, #7b2ffc); color: #05080f; }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 8px 40px rgba(127,201,255,0.2); }
        .btn-success { background: rgba(0,255,136,0.1); color: #00ff88; border: 1px solid rgba(0,255,136,0.08); }
        .btn-success:hover { background: rgba(0,255,136,0.15); transform: translateY(-2px); }
        .btn-danger { background: rgba(255,107,107,0.08); color: #ff6b6b; border: 1px solid rgba(255,107,107,0.06); }
        .btn-danger:hover { background: rgba(255,107,107,0.15); transform: translateY(-2px); }
        .btn-outline { background: transparent; color: #7fc9ff; border: 1px solid rgba(127,201,255,0.08); }
        .btn-outline:hover { background: rgba(127,201,255,0.04); transform: translateY(-2px); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none !important; }
        .backup-codes { 
            background: rgba(127,201,255,0.03); 
            border-radius: 12px; 
            padding: 16px; 
            margin: 16px 0;
            border: 1px dashed rgba(127,201,255,0.06);
        }
        .backup-codes .title { color: rgba(127,201,255,0.3); font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
        .backup-codes code { 
            display: block; 
            font-family: 'Courier New', monospace; 
            color: #7fc9ff; 
            font-size: 14px; 
            padding: 3px 0; 
            letter-spacing: 1px;
        }
        .status { padding: 12px 16px; border-radius: 12px; margin-bottom: 16px; font-size: 14px; display: flex; align-items: center; gap: 10px; }
        .status.success { background: rgba(0,255,136,0.06); color: #00ff88; border: 1px solid rgba(0,255,136,0.08); }
        .status.error { background: rgba(255,107,107,0.06); color: #ff6b6b; border: 1px solid rgba(255,107,107,0.08); }
        .status.info { background: rgba(127,201,255,0.06); color: #7fc9ff; border: 1px solid rgba(127,201,255,0.08); }
        .flex { display: flex; gap: 12px; margin-top: 12px; }
        .flex .btn { flex: 1; }
        .hidden { display: none !important; }
        @media (max-width: 480px) { 
            .container { padding: 28px 20px; border-radius: 20px; }
            .flex { flex-direction: column; }
            .flex .btn { width: 100%; }
            .qr-placeholder { font-size: 60px; }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="bg-animation">
        <div class="orb"></div>
        <div class="orb"></div>
    </div>

    <div class="container">
        <a href="profile.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Profile
        </a>
        
        <h1>🔐 Two-Factor Authentication</h1>
        <p class="subtitle">Secure your account with 2FA using Google Authenticator or Authy</p>

        <div id="statusContainer"></div>

        <div class="qr-container">
            <span class="qr-placeholder" id="qrDisplay">📱</span>
            <p class="hint">📸 Scan this QR code with Google Authenticator</p>
        </div>

        <div class="form-group">
            <label>📋 Setup Key</label>
            <input type="text" id="setupKey" value="SMART-2FA-KEY-2026" readonly>
        </div>

        <div class="form-group">
            <label>🔢 Enter 6-digit code</label>
            <input type="text" id="verificationCode" placeholder="123456" maxlength="6" inputmode="numeric">
        </div>

        <button class="btn btn-primary" id="verifyBtn" onclick="verify2FA()">
            <i class="fas fa-check"></i> Verify & Enable 2FA
        </button>

        <div class="backup-codes hidden" id="backupCodes">
            <div class="title">🔑 Save these backup codes (store them safely):</div>
            <code id="code1">─────</code>
            <code id="code2">─────</code>
            <code id="code3">─────</code>
            <code id="code4">─────</code>
            <code id="code5">─────</code>
            <button class="btn btn-outline" style="margin-top:8px;padding:8px;font-size:12px;" onclick="copyBackupCodes()">
                <i class="fas fa-copy"></i> Copy Codes
            </button>
        </div>

        <div class="flex">
            <button class="btn btn-danger" onclick="disable2FA()">
                <i class="fas fa-times"></i> Disable 2FA
            </button>
            <button class="btn btn-outline" onclick="window.location.href='profile.php'">
                <i class="fas fa-arrow-left"></i> Back
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
        const database = firebase.database();

        let currentUser = null;
        let backupCodesGenerated = [];

        // Auto-generate backup codes
        function generateBackupCodes() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            const codes = [];
            for (let i = 0; i < 5; i++) {
                let code = '';
                for (let j = 0; j < 3; j++) {
                    for (let k = 0; k < 3; k++) {
                        code += chars[Math.floor(Math.random() * chars.length)];
                    }
                    if (j < 2) code += '-';
                }
                codes.push(code);
            }
            return codes;
        }

        auth.onAuthStateChanged(function(user) {
            if (user) {
                currentUser = user;
                check2FAStatus();
            } else {
                window.location.href = 'login.php';
            }
        });

        function showStatus(type, message) {
            const icons = { success: '✅', error: '❌', info: 'ℹ️' };
            document.getElementById('statusContainer').innerHTML = `
                <div class="status ${type}">${icons[type] || 'ℹ️'} ${message}</div>
            `;
        }

        function check2FAStatus() {
            database.ref('2fa/' + currentUser.uid).once('value')
                .then(snapshot => {
                    const data = snapshot.val();
                    if (data && data.enabled) {
                        document.getElementById('statusContainer').innerHTML = `
                            <div class="status success">✅ 2FA is enabled for your account</div>
                        `;
                        document.getElementById('setupKey').value = '✅ 2FA Active';
                        document.getElementById('setupKey').style.color = '#00ff88';
                        document.getElementById('verifyBtn').textContent = '🔄 2FA Already Enabled';
                        document.getElementById('verifyBtn').disabled = true;
                    }
                });
        }

        function verify2FA() {
            const code = document.getElementById('verificationCode').value.trim();
            if (!code || code.length !== 6) {
                showStatus('error', '⚠️ Please enter a valid 6-digit code');
                return;
            }

            // In production, use proper TOTP library
            // For demo, accept any 6-digit code
            backupCodesGenerated = generateBackupCodes();

            database.ref('2fa/' + currentUser.uid).set({
                enabled: true,
                enabledAt: new Date().toISOString(),
                backupCodes: backupCodesGenerated
            }).then(() => {
                showStatus('success', '✅ 2FA enabled successfully!');
                
                document.getElementById('backupCodes').classList.remove('hidden');
                document.getElementById('code1').textContent = backupCodesGenerated[0];
                document.getElementById('code2').textContent = backupCodesGenerated[1];
                document.getElementById('code3').textContent = backupCodesGenerated[2];
                document.getElementById('code4').textContent = backupCodesGenerated[3];
                document.getElementById('code5').textContent = backupCodesGenerated[4];
                
                document.getElementById('setupKey').value = '✅ 2FA Active';
                document.getElementById('setupKey').style.color = '#00ff88';
                document.getElementById('verifyBtn').textContent = '✅ 2FA Enabled';
                document.getElementById('verifyBtn').disabled = true;
            });
        }

        function disable2FA() {
            if (!confirm('⚠️ Are you sure you want to disable 2FA?')) return;
            if (!confirm('This will make your account less secure!')) return;
            
            database.ref('2fa/' + currentUser.uid).remove()
                .then(() => {
                    showStatus('success', '✅ 2FA disabled successfully');
                    document.getElementById('backupCodes').classList.add('hidden');
                    document.getElementById('setupKey').value = 'SMART-2FA-KEY-2026';
                    document.getElementById('setupKey').style.color = '#b8e6ff';
                    document.getElementById('verifyBtn').textContent = 'Verify & Enable 2FA';
                    document.getElementById('verifyBtn').disabled = false;
                });
        }

        function copyBackupCodes() {
            const codes = backupCodesGenerated.join('\n');
            navigator.clipboard.writeText(codes).then(() => {
                showStatus('success', '📋 Backup codes copied to clipboard!');
            }).catch(() => {
                // Fallback
                const textarea = document.createElement('textarea');
                textarea.value = codes;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                showStatus('success', '📋 Backup codes copied!');
            });
        }

        // Auto-format code input
        document.getElementById('verificationCode').addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').slice(0, 6);
        });

        // Enter key support
        document.getElementById('verificationCode').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                verify2FA();
            }
        });
    </script>
</body>
</html>