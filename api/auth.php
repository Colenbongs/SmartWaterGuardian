<?php
/**
 * Smart Water Guardian - Authentication API
 * Handles PHP sessions and login with approval check
 * WITH: 2-ATTEMPT WARNING SYSTEM
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// SESSION ATTEMPT TRACKING FUNCTIONS
// ============================================================

function getLoginAttempts() {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
    }
    return (int)$_SESSION['login_attempts'];
}

function incrementLoginAttempts() {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
    }
    $_SESSION['login_attempts']++;
    $_SESSION['last_attempt_time'] = time();
    return $_SESSION['login_attempts'];
}

function resetLoginAttempts() {
    $_SESSION['login_attempts'] = 0;
    unset($_SESSION['blocked_until']);
    unset($_SESSION['last_attempt_time']);
}

function isBlocked() {
    // Check if blocked
    if (isset($_SESSION['blocked_until'])) {
        $blocked_until = (int)$_SESSION['blocked_until'];
        $now = time();
        if ($now < $blocked_until) {
            $remaining = ceil(($blocked_until - $now) / 60);
            return [
                'blocked' => true,
                'remaining_minutes' => $remaining,
                'remaining_seconds' => ($blocked_until - $now)
            ];
        } else {
            // Block expired, reset
            unset($_SESSION['blocked_until']);
            resetLoginAttempts();
            return ['blocked' => false];
        }
    }
    return ['blocked' => false];
}

function blockUser($minutes = 5) {
    $_SESSION['blocked_until'] = time() + ($minutes * 60);
    $_SESSION['login_attempts'] = 0;
}

function getAttemptsRemaining() {
    $attempts = getLoginAttempts();
    $max_attempts = 3;
    $remaining = $max_attempts - $attempts;
    return max(0, $remaining);
}

function shouldShowWarning() {
    // Show warning after 2 failed attempts (1 attempt left before block)
    $attempts = getLoginAttempts();
    return ($attempts >= 2);
}

function getWarningMessage() {
    $remaining = getAttemptsRemaining();
    if ($remaining <= 0) {
        return "⚠️ You have used all login attempts. Your account is temporarily locked.";
    }
    return "⚠️ Warning: You have " . $remaining . " login attempt(s) remaining. Your account will be locked after " . $remaining . " more failed attempt(s).";
}

// ============================================================
// API HANDLER
// ============================================================

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'No data received']);
    exit();
}

$action = $data['action'] ?? '';

switch($action) {
    
    // ==================== SET SESSION ====================
    case 'set_session':
        if (!isset($data['uid']) || !isset($data['email'])) {
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            exit();
        }
        
        // Reset login attempts on successful login
        resetLoginAttempts();
        
        $_SESSION['user_id'] = $data['uid'];
        $_SESSION['email'] = $data['email'];
        $_SESSION['firstName'] = $data['firstName'] ?? '';
        $_SESSION['lastName'] = $data['lastName'] ?? '';
        $_SESSION['role'] = $data['role'] ?? 'consumer';
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        
        require_once '../config/database.php';
        $stmt = $conn->prepare("
            UPDATE users SET last_login = NOW() WHERE firebase_uid = ?
        ");
        $stmt->bind_param("s", $data['uid']);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $data['uid'],
                'email' => $data['email'],
                'firstName' => $_SESSION['firstName'],
                'lastName' => $_SESSION['lastName'],
                'role' => $_SESSION['role']
            ]
        ]);
        break;
    
    // ==================== CHECK SESSION ====================
    case 'check_session':
        if (isset($_SESSION['user_id']) && $_SESSION['logged_in']) {
            echo json_encode([
                'success' => true,
                'logged_in' => true,
                'user' => [
                    'id' => $_SESSION['user_id'],
                    'email' => $_SESSION['email'],
                    'firstName' => $_SESSION['firstName'],
                    'lastName' => $_SESSION['lastName'],
                    'role' => $_SESSION['role']
                ]
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'logged_in' => false
            ]);
        }
        break;
    
    // ==================== LOGIN ATTEMPT ====================
    case 'login_attempt':
        // Check if user is blocked
        $block_status = isBlocked();
        if ($block_status['blocked']) {
            echo json_encode([
                'success' => false,
                'blocked' => true,
                'error' => 'Too many failed attempts. Please wait ' . $block_status['remaining_minutes'] . ' minute(s).',
                'remaining_minutes' => $block_status['remaining_minutes'],
                'remaining_seconds' => $block_status['remaining_seconds'],
                'attempts' => getLoginAttempts()
            ]);
            exit();
        }
        
        // Increment login attempts
        $attempts = incrementLoginAttempts();
        $max_attempts = 3;
        $remaining = $max_attempts - $attempts;
        
        // Check if this is the final attempt (3rd failed)
        if ($attempts >= $max_attempts) {
            // Block the user
            blockUser(5);
            echo json_encode([
                'success' => false,
                'blocked' => true,
                'error' => 'Too many failed attempts. Your account has been locked for 5 minutes.',
                'remaining_minutes' => 5,
                'attempts' => $attempts,
                'max_attempts' => $max_attempts,
                'remaining' => 0,
                'is_final_attempt' => true
            ]);
            exit();
        }
        
        // Check if we should show a warning (2nd failed attempt)
        $show_warning = ($attempts >= 2);
        
        echo json_encode([
            'success' => false,
            'blocked' => false,
            'error' => 'Invalid credentials. Please try again.',
            'attempts' => $attempts,
            'max_attempts' => $max_attempts,
            'remaining' => $remaining,
            'show_warning' => $show_warning,
            'warning_message' => $show_warning ? '⚠️ Warning: You have ' . $remaining . ' attempt(s) remaining. Your account will be locked after ' . $remaining . ' more failed attempt(s).' : null,
            'is_final_attempt' => ($attempts === $max_attempts - 1)
        ]);
        break;
    
    // ==================== GET ATTEMPT STATUS ====================
    case 'get_attempt_status':
        $attempts = getLoginAttempts();
        $max_attempts = 3;
        $remaining = max(0, $max_attempts - $attempts);
        $block_status = isBlocked();
        
        echo json_encode([
            'success' => true,
            'attempts' => $attempts,
            'max_attempts' => $max_attempts,
            'remaining' => $remaining,
            'show_warning' => shouldShowWarning(),
            'warning_message' => shouldShowWarning() ? getWarningMessage() : null,
            'blocked' => $block_status['blocked'],
            'blocked_until' => $block_status['blocked'] ? $block_status['remaining_minutes'] : 0
        ]);
        break;
    
    // ==================== RESET ATTEMPTS ====================
    case 'reset_attempts':
        resetLoginAttempts();
        echo json_encode([
            'success' => true,
            'message' => 'Login attempts reset'
        ]);
        break;
    
    // ==================== LOGOUT ====================
    case 'logout':
        resetLoginAttempts();
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
        break;
    
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>
