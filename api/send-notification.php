<?php
/**
 * Smart Water Guardian - Email Notification System
 * Sends emails to EVERY user who registers
 * Uses Gmail SMTP with App Password
 * Gmail: ncubemcliff@gmail.com
 */

header('Content-Type: application/json');

// ==================== CONFIGURATION ====================
// YOUR GMAIL CREDENTIALS
$config = [
    // Gmail SMTP Settings
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_secure' => 'tls',
    'smtp_username' => 'ncubemcliff@gmail.com',  // YOUR GMAIL
    'smtp_password' => 'pyomqucenflrxcwe',        // YOUR APP PASSWORD (without spaces)
    
    // Sender info
    'from_email' => 'ncubemcliff@gmail.com',      // YOUR GMAIL
    'from_name' => 'Smart Water Guardian'
];

// ==================== TRY TO LOAD PHPMailer ====================
$use_phpmailer = false;

// Check if Composer autoload exists
if (file_exists('../vendor/autoload.php')) {
    require_once '../vendor/autoload.php';
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $use_phpmailer = true;
    }
}

// If not found, try manual include
if (!$use_phpmailer) {
    $paths = [
        '../vendor/phpmailer/phpmailer/src/PHPMailer.php',
        '../vendor/phpmailer/phpmailer/src/SMTP.php',
        '../vendor/phpmailer/phpmailer/src/Exception.php',
        '../PHPMailer/PHPMailer.php',
        '../lib/PHPMailer/PHPMailer.php',
        '../includes/PHPMailer.php'
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $baseDir = dirname($path);
            if (file_exists($baseDir . '/SMTP.php')) {
                require_once $baseDir . '/SMTP.php';
            }
            if (file_exists($baseDir . '/Exception.php')) {
                require_once $baseDir . '/Exception.php';
            }
            $use_phpmailer = true;
            break;
        }
    }
}

// If STILL not found, try simple require without namespace
if (!$use_phpmailer) {
    $paths = [
        '../PHPMailer/PHPMailerAutoload.php',
        '../lib/PHPMailer/PHPMailerAutoload.php',
        '../includes/PHPMailerAutoload.php'
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $use_phpmailer = true;
            break;
        }
    }
}

// ==================== EMAIL TEMPLATES ====================
function getEmailTemplate($type, $data) {
    $name = htmlspecialchars($data['name'] ?? 'User');
    $baseUrl = 'https://smartwater.co.za';
    
    $templates = [
        'pending_approval' => [
            'subject' => 'Account Pending Approval - Smart Water Guardian',
            'html' => '
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Account Pending Approval</title>
                    <style>
                        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #f4f8fc; padding: 20px; margin: 0; }
                        .container { max-width: 580px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 40px 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); }
                        .header { text-align: center; padding-bottom: 24px; border-bottom: 2px solid #eef4f8; }
                        .logo { font-size: 26px; font-weight: 800; color: #0055aa; letter-spacing: -0.5px; }
                        .logo span { color: #003366; }
                        .logo-sub { font-size: 13px; color: #6a7a8a; margin-top: 2px; }
                        .content { padding: 24px 0; }
                        .content h2 { font-size: 22px; color: #0a1e2f; margin: 0 0 12px 0; }
                        .content p { font-size: 15px; line-height: 1.6; color: #3a4a5a; margin: 0 0 16px 0; }
                        .status-box { background: #f8f4e8; border-left: 4px solid #d4a017; padding: 16px 20px; border-radius: 8px; margin: 16px 0; }
                        .status-box .label { font-weight: 700; color: #8a6a00; font-size: 13px; }
                        .status-box p { margin: 4px 0 0; color: #556677; font-size: 14px; }
                        .btn { display: inline-block; padding: 12px 32px; background: #0055aa; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 15px; }
                        .btn:hover { background: #003d7a; }
                        .footer { text-align: center; font-size: 12px; color: #8899aa; border-top: 1px solid #eef4f8; padding-top: 20px; margin-top: 20px; }
                        .footer a { color: #0055aa; text-decoration: none; }
                        @media (max-width: 480px) {
                            .container { padding: 24px 18px; }
                            .content h2 { font-size: 19px; }
                            .content p { font-size: 14px; }
                            .btn { padding: 10px 24px; font-size: 14px; }
                        }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <div class="logo">Smart<span>Water</span></div>
                            <div class="logo-sub">Guardian System</div>
                        </div>
                        <div class="content">
                            <h2>Hello ' . $name . ',</h2>
                            <p>Thank you for registering with Smart Water Guardian. We are excited to have you on board!</p>
                            <div class="status-box">
                                <div class="label">ACCOUNT STATUS: PENDING APPROVAL</div>
                                <p>Your account requires administrator approval before you can access the system.</p>
                            </div>
                            <p>You will receive a confirmation email once your account has been approved. This usually takes 24-48 hours.</p>
                            <p style="margin: 20px 0; text-align: center;">
                                <a href="' . $baseUrl . '/login.php" class="btn">Go to Login</a>
                            </p>
                            <p style="font-size: 13px; color: #6a7a8a;">If you did not create this account, please ignore this email.</p>
                        </div>
                        <div class="footer">
                            <p>&copy; 2026 Smart Water Guardian. All rights reserved.</p>
                            <p><a href="' . $baseUrl . '">' . $baseUrl . '</a></p>
                        </div>
                    </div>
                </body>
                </html>
            ',
            'text' => 'Hello ' . $name . ',

Thank you for registering with Smart Water Guardian.

ACCOUNT STATUS: PENDING APPROVAL
Your account requires administrator approval before you can access the system.

You will receive a confirmation email once your account has been approved.

Login at: ' . $baseUrl . '/login.php

If you did not create this account, please ignore this email.

---
Smart Water Guardian
' . $baseUrl
        ],
        
        'pending_approval_reminder' => [
            'subject' => 'Reminder: Account Pending Approval - Smart Water Guardian',
            'html' => '
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Pending Approval Reminder</title>
                    <style>
                        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #f4f8fc; padding: 20px; margin: 0; }
                        .container { max-width: 580px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 40px 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); }
                        .header { text-align: center; padding-bottom: 24px; border-bottom: 2px solid #eef4f8; }
                        .logo { font-size: 26px; font-weight: 800; color: #0055aa; letter-spacing: -0.5px; }
                        .logo span { color: #003366; }
                        .logo-sub { font-size: 13px; color: #6a7a8a; margin-top: 2px; }
                        .content { padding: 24px 0; }
                        .content h2 { font-size: 22px; color: #0a1e2f; margin: 0 0 12px 0; }
                        .content p { font-size: 15px; line-height: 1.6; color: #3a4a5a; margin: 0 0 16px 0; }
                        .status-box { background: #f8f4e8; border-left: 4px solid #d4a017; padding: 16px 20px; border-radius: 8px; margin: 16px 0; }
                        .status-box .label { font-weight: 700; color: #8a6a00; font-size: 13px; }
                        .footer { text-align: center; font-size: 12px; color: #8899aa; border-top: 1px solid #eef4f8; padding-top: 20px; margin-top: 20px; }
                        .footer a { color: #0055aa; text-decoration: none; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <div class="logo">Smart<span>Water</span></div>
                            <div class="logo-sub">Guardian System</div>
                        </div>
                        <div class="content">
                            <h2>Hello ' . $name . ',</h2>
                            <p>We noticed you tried to login, but your account is still pending approval.</p>
                            <div class="status-box">
                                <div class="label">ACCOUNT STATUS: PENDING APPROVAL</div>
                                <p>An administrator has not yet approved your account.</p>
                            </div>
                            <p>Please wait for the approval email. If you have any questions, contact support.</p>
                        </div>
                        <div class="footer">
                            <p>&copy; 2026 Smart Water Guardian. All rights reserved.</p>
                            <p><a href="' . $baseUrl . '">' . $baseUrl . '</a></p>
                        </div>
                    </div>
                </body>
                </html>
            ',
            'text' => 'Hello ' . $name . ',

We noticed you tried to login, but your account is still pending approval.

ACCOUNT STATUS: PENDING APPROVAL
An administrator has not yet approved your account.

Please wait for the approval email. If you have any questions, contact support.

---
Smart Water Guardian
' . $baseUrl
        ],
        
        'account_approved' => [
            'subject' => 'Account Approved - Welcome to Smart Water Guardian!',
            'html' => '
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Account Approved</title>
                    <style>
                        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #f4f8fc; padding: 20px; margin: 0; }
                        .container { max-width: 580px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 40px 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); }
                        .header { text-align: center; padding-bottom: 24px; border-bottom: 2px solid #eef4f8; }
                        .logo { font-size: 26px; font-weight: 800; color: #0055aa; letter-spacing: -0.5px; }
                        .logo span { color: #003366; }
                        .logo-sub { font-size: 13px; color: #6a7a8a; margin-top: 2px; }
                        .content { padding: 24px 0; }
                        .content h2 { font-size: 22px; color: #0a1e2f; margin: 0 0 12px 0; }
                        .content p { font-size: 15px; line-height: 1.6; color: #3a4a5a; margin: 0 0 16px 0; }
                        .status-box { background: #e8f8f0; border-left: 4px solid #00aa66; padding: 16px 20px; border-radius: 8px; margin: 16px 0; }
                        .status-box .label { font-weight: 700; color: #008855; font-size: 13px; }
                        .btn { display: inline-block; padding: 12px 32px; background: #0055aa; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 15px; }
                        .btn:hover { background: #003d7a; }
                        .footer { text-align: center; font-size: 12px; color: #8899aa; border-top: 1px solid #eef4f8; padding-top: 20px; margin-top: 20px; }
                        .footer a { color: #0055aa; text-decoration: none; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <div class="logo">Smart<span>Water</span></div>
                            <div class="logo-sub">Guardian System</div>
                        </div>
                        <div class="content">
                            <h2>Congratulations ' . $name . '!</h2>
                            <p>Your Smart Water Guardian account has been approved!</p>
                            <div class="status-box">
                                <div class="label">ACCOUNT STATUS: APPROVED</div>
                                <p>You can now login and start monitoring your water usage.</p>
                            </div>
                            <p style="margin: 20px 0; text-align: center;">
                                <a href="' . $baseUrl . '/login.php" class="btn">Login to Your Account</a>
                            </p>
                            <p style="font-size: 13px; color: #6a7a8a;">Welcome to the community! Start tracking your water usage today.</p>
                        </div>
                        <div class="footer">
                            <p>&copy; 2026 Smart Water Guardian. All rights reserved.</p>
                            <p><a href="' . $baseUrl . '">' . $baseUrl . '</a></p>
                        </div>
                    </div>
                </body>
                </html>
            ',
            'text' => 'Congratulations ' . $name . '!

Your Smart Water Guardian account has been approved!

ACCOUNT STATUS: APPROVED
You can now login and start monitoring your water usage.

Login at: ' . $baseUrl . '/login.php

Welcome to the community!

---
Smart Water Guardian
' . $baseUrl
        ],
        
        'account_rejected' => [
            'subject' => 'Account Registration Update - Smart Water Guardian',
            'html' => '
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Account Rejected</title>
                    <style>
                        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #f4f8fc; padding: 20px; margin: 0; }
                        .container { max-width: 580px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 40px 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); }
                        .header { text-align: center; padding-bottom: 24px; border-bottom: 2px solid #eef4f8; }
                        .logo { font-size: 26px; font-weight: 800; color: #0055aa; letter-spacing: -0.5px; }
                        .logo span { color: #003366; }
                        .logo-sub { font-size: 13px; color: #6a7a8a; margin-top: 2px; }
                        .content { padding: 24px 0; }
                        .content h2 { font-size: 22px; color: #0a1e2f; margin: 0 0 12px 0; }
                        .content p { font-size: 15px; line-height: 1.6; color: #3a4a5a; margin: 0 0 16px 0; }
                        .status-box { background: #f8e8e8; border-left: 4px solid #cc3333; padding: 16px 20px; border-radius: 8px; margin: 16px 0; }
                        .status-box .label { font-weight: 700; color: #992222; font-size: 13px; }
                        .footer { text-align: center; font-size: 12px; color: #8899aa; border-top: 1px solid #eef4f8; padding-top: 20px; margin-top: 20px; }
                        .footer a { color: #0055aa; text-decoration: none; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <div class="logo">Smart<span>Water</span></div>
                            <div class="logo-sub">Guardian System</div>
                        </div>
                        <div class="content">
                            <h2>Hello ' . $name . ',</h2>
                            <p>We regret to inform you that your account registration has been rejected.</p>
                            <div class="status-box">
                                <div class="label">ACCOUNT STATUS: REJECTED</div>
                                <p>' . htmlspecialchars($data['reason'] ?? 'Please contact support for more information.') . '</p>
                            </div>
                            <p>If you believe this is an error, please contact our support team.</p>
                        </div>
                        <div class="footer">
                            <p>&copy; 2026 Smart Water Guardian. All rights reserved.</p>
                            <p><a href="' . $baseUrl . '">' . $baseUrl . '</a></p>
                        </div>
                    </div>
                </body>
                </html>
            ',
            'text' => 'Hello ' . $name . ',

We regret to inform you that your account registration has been rejected.

ACCOUNT STATUS: REJECTED
' . ($data['reason'] ?? 'Please contact support for more information.') . '

If you believe this is an error, please contact our support team.

---
Smart Water Guardian
' . $baseUrl
        ],
        
        'bill_reminder' => [
            'subject' => 'Payment Reminder - Smart Water Guardian Bill Due',
            'html' => '
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Payment Reminder</title>
                    <style>
                        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #f4f8fc; padding: 20px; margin: 0; }
                        .container { max-width: 580px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 40px 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); }
                        .header { text-align: center; padding-bottom: 24px; border-bottom: 2px solid #eef4f8; }
                        .logo { font-size: 26px; font-weight: 800; color: #0055aa; letter-spacing: -0.5px; }
                        .logo span { color: #003366; }
                        .logo-sub { font-size: 13px; color: #6a7a8a; margin-top: 2px; }
                        .content { padding: 24px 0; }
                        .content h2 { font-size: 22px; color: #0a1e2f; margin: 0 0 12px 0; }
                        .content p { font-size: 15px; line-height: 1.6; color: #3a4a5a; margin: 0 0 16px 0; }
                        .bill-box { background: #f8f4e8; border-left: 4px solid #cc8833; padding: 16px 20px; border-radius: 8px; margin: 16px 0; }
                        .amount { font-size: 24px; font-weight: 700; color: #cc3333; }
                        .footer { text-align: center; font-size: 12px; color: #8899aa; border-top: 1px solid #eef4f8; padding-top: 20px; margin-top: 20px; }
                        .footer a { color: #0055aa; text-decoration: none; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <div class="logo">Smart<span>Water</span></div>
                            <div class="logo-sub">Guardian System</div>
                        </div>
                        <div class="content">
                            <h2>Hello ' . $name . ',</h2>
                            <p>This is a reminder that your water bill is due.</p>
                            <div class="bill-box">
                                <p><strong>Month:</strong> ' . htmlspecialchars($data['month'] ?? 'Current') . '</p>
                                <p><strong>Amount Due:</strong> <span class="amount">R ' . number_format($data['amount'] ?? 0, 2) . '</span></p>
                                <p><strong>Due Date:</strong> ' . htmlspecialchars($data['due_date'] ?? 'Immediate') . '</p>
                            </div>
                            ' . ($data['custom_message'] ? '<p style="color: #556677;">' . htmlspecialchars($data['custom_message']) . '</p>' : '') . '
                            <p>Please ensure payment is made to avoid service interruption.</p>
                        </div>
                        <div class="footer">
                            <p>&copy; 2026 Smart Water Guardian. All rights reserved.</p>
                            <p><a href="' . $baseUrl . '">' . $baseUrl . '</a></p>
                        </div>
                    </div>
                </body>
                </html>
            ',
            'text' => 'Hello ' . $name . ',

This is a reminder that your water bill is due.

Month: ' . ($data['month'] ?? 'Current') . '
Amount Due: R ' . number_format($data['amount'] ?? 0, 2) . '
Due Date: ' . ($data['due_date'] ?? 'Immediate') . '

' . ($data['custom_message'] ?? '') . '

Please ensure payment is made to avoid service interruption.

---
Smart Water Guardian
' . $baseUrl
        ],
        
        'account_deleted' => [
            'subject' => 'Account Deleted - Smart Water Guardian',
            'html' => '
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Account Deleted</title>
                    <style>
                        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #f4f8fc; padding: 20px; margin: 0; }
                        .container { max-width: 580px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 40px 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); }
                        .header { text-align: center; padding-bottom: 24px; border-bottom: 2px solid #eef4f8; }
                        .logo { font-size: 26px; font-weight: 800; color: #0055aa; letter-spacing: -0.5px; }
                        .logo span { color: #003366; }
                        .logo-sub { font-size: 13px; color: #6a7a8a; margin-top: 2px; }
                        .content { padding: 24px 0; }
                        .content h2 { font-size: 22px; color: #0a1e2f; margin: 0 0 12px 0; }
                        .content p { font-size: 15px; line-height: 1.6; color: #3a4a5a; margin: 0 0 16px 0; }
                        .status-box { background: #f8e8e8; border-left: 4px solid #cc3333; padding: 16px 20px; border-radius: 8px; margin: 16px 0; }
                        .status-box .label { font-weight: 700; color: #992222; font-size: 13px; }
                        .footer { text-align: center; font-size: 12px; color: #8899aa; border-top: 1px solid #eef4f8; padding-top: 20px; margin-top: 20px; }
                        .footer a { color: #0055aa; text-decoration: none; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <div class="logo">Smart<span>Water</span></div>
                            <div class="logo-sub">Guardian System</div>
                        </div>
                        <div class="content">
                            <h2>Hello ' . $name . ',</h2>
                            <p>Your Smart Water Guardian account has been deleted by an administrator.</p>
                            <div class="status-box">
                                <div class="label">ACCOUNT STATUS: DELETED</div>
                                <p>All your data has been permanently removed from our system.</p>
                            </div>
                            <p>If you have any questions, please contact our support team.</p>
                        </div>
                        <div class="footer">
                            <p>&copy; 2026 Smart Water Guardian. All rights reserved.</p>
                            <p><a href="' . $baseUrl . '">' . $baseUrl . '</a></p>
                        </div>
                    </div>
                </body>
                </html>
            ',
            'text' => 'Hello ' . $name . ',

Your Smart Water Guardian account has been deleted by an administrator.

ACCOUNT STATUS: DELETED
All your data has been permanently removed from our system.

If you have any questions, please contact our support team.

---
Smart Water Guardian
' . $baseUrl
        ],
        
        'admin_message' => [
            'subject' => $data['subject'] ?? 'New Message from Smart Water Guardian',
            'html' => '
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>New Message</title>
                    <style>
                        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #f4f8fc; padding: 20px; margin: 0; }
                        .container { max-width: 580px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 40px 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); }
                        .header { text-align: center; padding-bottom: 24px; border-bottom: 2px solid #eef4f8; }
                        .logo { font-size: 26px; font-weight: 800; color: #0055aa; letter-spacing: -0.5px; }
                        .logo span { color: #003366; }
                        .logo-sub { font-size: 13px; color: #6a7a8a; margin-top: 2px; }
                        .content { padding: 24px 0; }
                        .content h2 { font-size: 22px; color: #0a1e2f; margin: 0 0 12px 0; }
                        .content p { font-size: 15px; line-height: 1.6; color: #3a4a5a; margin: 0 0 16px 0; }
                        .message-box { background: #f0f8ff; padding: 16px 20px; border-radius: 8px; margin: 16px 0; }
                        .message-box p { margin: 0; white-space: pre-wrap; }
                        .footer { text-align: center; font-size: 12px; color: #8899aa; border-top: 1px solid #eef4f8; padding-top: 20px; margin-top: 20px; }
                        .footer a { color: #0055aa; text-decoration: none; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <div class="logo">Smart<span>Water</span></div>
                            <div class="logo-sub">Guardian System</div>
                        </div>
                        <div class="content">
                            <h2>Hello ' . $name . ',</h2>
                            <p><strong>Subject:</strong> ' . htmlspecialchars($data['subject'] ?? 'New Message') . '</p>
                            <div class="message-box">
                                <p>' . nl2br(htmlspecialchars($data['message'] ?? '')) . '</p>
                            </div>
                            <p style="font-size: 13px; color: #6a7a8a;">You can view this message in your dashboard.</p>
                        </div>
                        <div class="footer">
                            <p>&copy; 2026 Smart Water Guardian. All rights reserved.</p>
                            <p><a href="' . $baseUrl . '">' . $baseUrl . '</a></p>
                        </div>
                    </div>
                </body>
                </html>
            ',
            'text' => 'Hello ' . $name . ',

Subject: ' . ($data['subject'] ?? 'New Message') . '

' . ($data['message'] ?? '') . '

You can view this message in your dashboard.

---
Smart Water Guardian
' . $baseUrl
        ]
    ];
    
    return $templates[$type] ?? null;
}

// ==================== SEND EMAIL USING PHPMailer ====================
function sendEmailPHPMailer($to, $subject, $htmlBody, $textBody = '') {
    global $config;
    
    try {
        // Check if PHPMailer class exists
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer') && !class_exists('PHPMailer')) {
            error_log('PHPMailer class not found');
            return false;
        }
        
        // Use the appropriate class name
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        } else {
            $mail = new PHPMailer(true);
        }
        
        // Server settings
        $mail->SMTPDebug = 0; // Set to 2 for testing
        $mail->isSMTP();
        $mail->Host = $config['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['smtp_username'];
        $mail->Password = $config['smtp_password'];
        $mail->SMTPSecure = $config['smtp_secure'];
        $mail->Port = $config['smtp_port'];
        $mail->CharSet = 'UTF-8';
        
        // Recipients
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($to);
        $mail->addReplyTo($config['from_email'], $config['from_name']);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $textBody ?: strip_tags($htmlBody);
        
        // Send
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log('PHPMailer Error: ' . $e->getMessage());
        return false;
    } catch (Exception $e) {
        error_log('PHPMailer General Error: ' . $e->getMessage());
        return false;
    }
}

// ==================== SEND EMAIL USING PHP MAIL (Fallback) ====================
function sendEmailPHP($to, $subject, $htmlBody, $textBody = '') {
    global $config;
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . $config['from_name'] . " <" . $config['from_email'] . ">\r\n";
    $headers .= "Reply-To: " . $config['from_email'] . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    
    return mail($to, $subject, $htmlBody, $headers);
}

// ==================== MAIN SEND FUNCTION ====================
function sendEmail($to, $subject, $htmlBody, $textBody = '') {
    global $use_phpmailer;
    
    // Try PHPMailer first
    if ($use_phpmailer) {
        $result = sendEmailPHPMailer($to, $subject, $htmlBody, $textBody);
        if ($result) {
            error_log("Email sent via PHPMailer to: $to");
            return true;
        }
        error_log("PHPMailer failed, trying fallback...");
    }
    
    // Fallback to PHP mail()
    error_log("Attempting fallback PHP mail() to: $to");
    return sendEmailPHP($to, $subject, $htmlBody, $textBody);
}

// ==================== API HANDLER ====================
$input = json_decode(file_get_contents('php://input'), true);
$type = $input['type'] ?? '';
$email = $input['email'] ?? '';
$data = $input;

// Validate input
if (empty($email) || empty($type)) {
    echo json_encode([
        'success' => false, 
        'error' => 'Email and type are required'
    ]);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid email address'
    ]);
    exit;
}

// Get template
$template = getEmailTemplate($type, $data);
if (!$template) {
    echo json_encode([
        'success' => false, 
        'error' => 'Invalid email type: ' . $type
    ]);
    exit;
}

// Send email
$success = sendEmail(
    $email,
    $template['subject'],
    $template['html'],
    $template['text'] ?? ''
);

// Log the attempt for debugging
error_log("Email sent to: $email, Type: $type, Success: " . ($success ? 'Yes' : 'No'));

// Return response
echo json_encode([
    'success' => $success,
    'message' => $success ? 'Email sent successfully' : 'Failed to send email',
    'type' => $type,
    'to' => $email,
    'method' => $use_phpmailer ? 'PHPMailer' : 'PHP mail()'
]);