<?php
/**
 * Smart Water Guardian - Delete User API
 * Deletes user from Firebase Auth and Realtime Database
 * Uses Firebase Admin SDK for complete deletion
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

// Only admins can delete users
if (!isset($_SESSION['user_id']) || !$_SESSION['logged_in']) {
    echo json_encode(['success' => false, 'error' => '❌ Unauthorized']);
    exit();
}

$role = $_SESSION['role'] ?? 'consumer';
if ($role !== 'system_admin' && $role !== 'municipal_admin' && $role !== 'admin') {
    echo json_encode(['success' => false, 'error' => '❌ Admin access required']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['uid']) || empty($data['uid'])) {
    echo json_encode(['success' => false, 'error' => '❌ User ID required']);
    exit();
}

$uid = $data['uid'];

// ============================================================
// LOAD FIREBASE ADMIN SDK
// ============================================================

// Path to your Firebase service account key
// Download from: Firebase Console → Project Settings → Service Accounts → Generate New Private Key
$serviceAccountPath = '../config/firebase-service-account.json';

// Check if service account file exists
if (!file_exists($serviceAccountPath)) {
    echo json_encode([
        'success' => false, 
        'error' => '❌ Firebase service account file not found. Please download from Firebase Console and save to config/firebase-service-account.json'
    ]);
    exit();
}

try {
    // Load the Firebase Admin SDK
    require_once '../vendor/autoload.php';
    
    use Kreait\Firebase\Factory;
    use Kreait\Firebase\Auth;
    use Kreait\Firebase\Exception\Auth\UserNotFound;
    
    // Initialize Firebase Admin SDK
    $factory = (new Factory)
        ->withServiceAccount($serviceAccountPath)
        ->withDatabaseUri('https://smartwaterguardian-default-rtdb.firebaseio.com');
    
    $auth = $factory->createAuth();
    $database = $factory->createDatabase();
    
    // Step 1: Delete from Firebase Authentication
    try {
        $auth->deleteUser($uid);
        $authDeleted = true;
        $authMessage = 'User deleted from Firebase Authentication';
    } catch (UserNotFound $e) {
        // User not found in Auth, but we'll still delete the data
        $authDeleted = false;
        $authMessage = 'User not found in Firebase Authentication (may already be deleted)';
    } catch (Exception $e) {
        $authDeleted = false;
        $authMessage = 'Error deleting from Firebase Auth: ' . $e->getMessage();
    }
    
    // Step 2: Delete user data from Realtime Database
    try {
        // Delete user profile
        $database->getReference('users/' . $uid)->remove();
        
        // Delete alerts
        $database->getReference('alerts/' . $uid)->remove();
        
        // Delete properties
        $database->getReference('properties/' . $uid)->remove();
        
        // Delete thresholds
        $database->getReference('thresholds/' . $uid)->remove();
        
        // Delete bills
        $database->getReference('bills/' . $uid)->remove();
        
        // Delete messages
        $database->getReference('messages/' . $uid)->remove();
        
        // Delete reviews
        $database->getReference('reviews/' . $uid)->remove();
        
        // Delete admin settings
        $database->getReference('admin_settings/' . $uid)->remove();
        
        // Delete user preferences
        $database->getReference('users/' . $uid . '/preferences').remove();
        
        $dbDeleted = true;
        $dbMessage = 'All user data deleted from Realtime Database';
        
    } catch (Exception $e) {
        $dbDeleted = false;
        $dbMessage = 'Error deleting from Realtime Database: ' . $e->getMessage();
    }
    
    // Step 3: Log the action
    $logMessage = "User $uid deleted by admin: " . $_SESSION['email'];
    error_log($logMessage);
    
    // Step 4: Return response
    echo json_encode([
        'success' => true,
        'message' => '✅ User deletion completed',
        'uid' => $uid,
        'auth_deleted' => $authDeleted,
        'auth_message' => $authMessage,
        'db_deleted' => $dbDeleted,
        'db_message' => $dbMessage,
        'admin_email' => $_SESSION['email']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => '❌ Error: ' . $e->getMessage()
    ]);
}
?>