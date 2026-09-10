<?php
/**
 * Smart Water Guardian - Email Notification System
 * FIXED: SMTP configuration with better error handling
 * Gmail: ncubemcliff@gmail.com
 */

header('Content-Type: application/json');

// ==================== CONFIGURATION ====================
$config = [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_secure' => 'tls',
    'smtp_username' => 'ncubemcliff@gmail.com',
    'smtp_password' => 'pyomqucenflrxcwe', // App Password
    'from_email' => 'ncubemcliff@gmail.com',
    'from_name' => 'Smart Water Guardian'
];

// ==================== TRY TO LOAD PHPMailer ====================
$use_phpmailer = false;

// Check if Composer autoload exists
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $use_phpmailer = true;
    }
}

// If not found, try manual include
if (!$use_phpmailer) {
    $paths = [
        __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php',
        __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php',
        __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php',
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $use_phpmailer = true;
            break;
        }
    }
}

// ==================== FALLBACK TO PHP MAIL ====================
function sendMailFallback($to, $subject, $htmlBody, $textBody = '') {
    global $config;
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . $config['from_name'] . " <" . $config['from_email'] . ">\r\n";
    $headers .= "Reply-To: " . $config['from_email'] . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    
    return mail($to, $subject, $htmlBody, $headers);
}

// ==================== SEND EMAIL FUNCTION ====================
function sendEmail($to, $subject, $htmlBody, $textBody = '') {
    global $config, $use_phpmailer;
    
    if ($use_phpmailer) {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
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
        }
    }
    
    // Fallback to PHP mail()
    return sendMailFallback($to, $subject, $htmlBody, $textBody);
}

// ==================== EMAIL TEMPLATES ====================
function getEmailTemplate($type, $data) {
    $name = htmlspecialchars($data['name'] ?? 'User');
    $baseUrl = 'https://smartwater.co.za';
    
    // ... (email templates remain the same as in your original file)
    // Keep the existing templates for: pending_approval, account_approved, etc.
    
    // For brevity, this is a placeholder. Use your existing templates.
    return [
        'subject' => 'Subject',
        'html' => '<html><body>Hello ' . $name . '</body></html>',
        'text' => 'Hello ' . $name
    ];
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

// Log the attempt
error_log("Email sent to: $email, Type: $type, Success: " . ($success ? 'Yes' : 'No'));

// Return response
echo json_encode([
    'success' => $success,
    'message' => $success ? 'Email sent successfully' : 'Failed to send email',
    'type' => $type,
    'to' => $email,
    'method' => $use_phpmailer ? 'PHPMailer' : 'PHP mail()'
]);
