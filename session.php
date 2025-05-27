<?php
/**
 * Session Management Functions
 * * Handles session initialization and security
 * * @author Dr. Ahmed AL-sadi
 * @version 1.0
 */

// Adjust path as per your final structure
require_once '../config.php'; 
// functions.php includes db_connect.php, and db_connect.php includes config.php.
// So, functions.php should be enough if it correctly requires db_connect.
// And utility functions like generateToken, hashPassword, verifyPassword, updateRecord, insertRecord, deleteRecord, logActivity
// are expected to be in functions.php
require_once '../functions.php';


/**
 * Initialize session with secure settings
 */
function initSession() {
    // Set session cookie parameters
    session_set_cookie_params([
        'lifetime' => defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 3600, //
        'path' => '/',
        'domain' => '', // Set your domain if needed
        'secure' => !empty($_SERVER['HTTPS']), 
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Regenerate session ID periodically
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 1800) { // 30 minutes
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

/**
 * Create a new user session
 */
function createUserSession($user, $remember = false) {
    $_SESSION['user_id'] = $user['id']; // [cite: 5]
    $_SESSION['username'] = $user['username']; // [cite: 5]
    $_SESSION['user_role'] = $user['role']; // [cite: 5]
    $_SESSION['last_login_timestamp'] = time(); // Use a different name to avoid confusion with db field
    
    updateRecord('Users', 
        ['last_login' => date('Y-m-d H:i:s')], // [cite: 5]
        'id = ?', 
        [$user['id']]
    );
    
    if ($remember) {
        $token = generateToken(64); // from functions.php
        $tokenHash = hashPassword($token); // from functions.php
        
        $expiresAt = time() + (defined('COOKIE_LIFETIME') ? COOKIE_LIFETIME : 604800); //
        insertRecord('UserSessions', [ // Using UserSessions table from SQL Schema [cite: 7]
            'user_id' => $user['id'],
            'session_token' => $tokenHash, // Store hashed token
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'expires_at' => date('Y-m-d H:i:s', $expiresAt)
        ]);
        
        setcookie('remember_token', $user['id'] . ':' . $token, [
            'expires' => $expiresAt,
            'path' => '/',
            'domain' => '', // Set your domain if needed
            'secure' => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    
    logActivity('login', 'User logged in', $user['id']); // from functions.php
}

/**
 * Check for remember me cookie and log user in
 */
function checkRememberMeCookie() {
    if (isset($_COOKIE['remember_token']) && !isLoggedIn()) {
        list($userId, $token) = explode(':', $_COOKIE['remember_token'], 2);
        
        if (empty($userId) || empty($token)) return false;

        $userId = (int)$userId;

        // Get token from UserSessions table
        $sql = "SELECT us.session_token, u.* FROM UserSessions us
                JOIN Users u ON u.id = us.user_id 
                WHERE us.user_id = ? AND us.expires_at > ? 
                ORDER BY us.created_at DESC LIMIT 1"; // Get the latest valid token
        
        $sessionRecord = getRecord($sql, [$userId, date('Y-m-d H:i:s')]);
        
        if ($sessionRecord && verifyPassword($token, $sessionRecord['session_token'])) {
            // Valid token, log user in
            // Regenerate a new remember me token for security (optional but good practice)
            // For simplicity, just creating the session here
            $userSql = "SELECT * FROM Users WHERE id = ? AND is_active = TRUE"; // [cite: 5]
            $user = getRecord($userSql, [$userId]);
            if ($user) {
                createUserSession($user, true); // Re-issue remember me to extend or refresh
                return true;
            }
        }
        
        // Invalid or expired token, clear cookie and DB entry
        if ($userId > 0) {
             deleteRecord('UserSessions', 'user_id = ? AND session_token = ?', [$userId, hashPassword($token)]); // Delete specific token if known
        }
        setcookie('remember_token', '', time() - 3600, '/');
    }
    return false;
}

/**
 * End user session and clear cookies
 */
function destroyUserSession() {
    if (isLoggedIn()) {
        logActivity('logout', 'User logged out', $_SESSION['user_id']);
        
        // Clear remember me token if exists
        if (isset($_COOKIE['remember_token'])) {
            list($userId, $token) = explode(':', $_COOKIE['remember_token'], 2);
            if (!empty($userId) && !empty($token)) {
                 $userId = (int) $userId;
                 // Delete the specific token from UserSessions if it matches
                 // For simplicity, we can delete all for the user or the specific one if we hashed the cookie token to find it.
                 // $hashedToken = hashPassword($token);
                 // deleteRecord('UserSessions', 'user_id = ? AND session_token = ?', [$userId, $hashedToken]);
                 // Or more broadly:
                 deleteRecord('UserSessions', 'user_id = ?', [$userId]);
            }
        }
    }
    
    setcookie('remember_token', '', time() - 3600, '/'); // Clear cookie
    
    $_SESSION = []; // Clear all session variables
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    if (session_status() !== PHP_SESSION_NONE) {
        session_destroy(); // Destroy the session
    }
}

// Initialize session when this file is included
initSession();

// Check for remember me cookie only if not already logged in
if (!isLoggedIn()) {
    checkRememberMeCookie();
}
?>
