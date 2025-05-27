
SESSION_LIFETIME,
        'path' => '/',
        'domain' => '',
        'secure' => !empty($_SERVER['HTTPS']), // Secure in HTTPS only
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    // Start the session
    session_start();
    
    // Regenerate session ID periodically to prevent session fixation attacks
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 1800) {
        // Regenerate session ID every 30 minutes
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

/**
 * Create a new user session
 * 
 * @param array $user User data
 * @param bool $remember Whether to remember the user
 */
function createUserSession($user, $remember = false) {
    // Set session data
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['last_login'] = time();
    
    // Update last login in database
    updateRecord('users', 
        ['last_login' => date('Y-m-d H:i:s')], 
        'id = ?', 
        [$user['id']]
    );
    
    // Set remember me cookie if requested
    if ($remember) {
        $token = generateToken(64);
        $tokenHash = hashPassword($token);
        
        // Store token in database
        $data = [
            'user_id' => $user['id'],
            'token' => $tokenHash,
            'expires_at' => date('Y-m-d H:i:s', time() + COOKIE_LIFETIME)
        ];
        insertRecord('remember_tokens', $data);
        
        // Set cookie
        setcookie('remember_token', $user['id'] . ':' . $token, [
            'expires' => time() + COOKIE_LIFETIME,
            'path' => '/',
            'domain' => '',
            'secure' => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    
    // Log activity
    logActivity('login', 'User logged in');
}

/**
 * Check for remember me cookie and log user in
 * 
 * @return bool True if user was logged in from cookie, false otherwise
 */
function checkRememberMeCookie() {
    if (isset($_COOKIE['remember_token']) && !isLoggedIn()) {
        list($userId, $token) = explode(':', $_COOKIE['remember_token'], 2);
        
        // Get token from database
        $sql = "SELECT u.*, rt.token FROM users u 
                JOIN remember_tokens rt ON u.id = rt.user_id 
                WHERE u.id = ? AND rt.expires_at > ?";
        $user = getRecord($sql, [$userId, date('Y-m-d H:i:s')]);
        
        if ($user && verifyPassword($token, $user['token'])) {
            // Valid token, log user in
            createUserSession($user);
            return true;
        }
        
        // Invalid token, clear cookie
        setcookie('remember_token', '', time() - 3600);
    }
    
    return false;
}

/**
 * End user session and clear cookies
 */
function destroyUserSession() {
    // Clear remember me token if exists
    if (isset($_COOKIE['remember_token'])) {
        list($userId, $token) = explode(':', $_COOKIE['remember_token'], 2);
        
        // Delete token from database
        deleteRecord('remember_tokens', 'user_id = ?', [$userId]);
        
        // Clear cookie
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    // Log activity before destroying session
    if (isLoggedIn()) {
        logActivity('logout', 'User logged out');
    }
    
    // Clear all session data
    $_SESSION = [];
    
    // Destroy the session
    session_destroy();
}

// Initialize session when this file is included
initSession();

// Check for remember me cookie
checkRememberMeCookie();

?>