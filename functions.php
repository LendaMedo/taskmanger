$value) {
            $data[$key] = sanitizeInput($value);
        }
        return $data;
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate email address format
 * 
 * @param string $email Email address to validate
 * @return bool True if valid, false otherwise
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Hash password securely using password_hash
 * 
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hashPassword($password) {
    return password_hash($password . HASH_SALT, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password against hash
 * 
 * @param string $password Plain text password
 * @param string $hash Hashed password
 * @return bool True if password matches hash, false otherwise
 */
function verifyPassword($password, $hash) {
    return password_verify($password . HASH_SALT, $hash);
}

/**
 * Generate a random token
 * 
 * @param int $length Length of token
 * @return string Random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Format date for display
 * 
 * @param string $date Date string
 * @param string $format Output format (default: Y-m-d)
 * @return string Formatted date
 */
function formatDate($date, $format = 'Y-m-d') {
    $timestamp = strtotime($date);
    return date($format, $timestamp);
}

/**
 * Redirect to URL
 * 
 * @param string $url URL to redirect to
 * @param array $flash Flash message to set (optional)
 */
function redirect($url, $flash = null) {
    if ($flash !== null && isset($flash['type'], $flash['message'])) {
        $_SESSION['flash'] = $flash;
    }
    
    header("Location: $url");
    exit;
}

/**
 * Display flash message
 * 
 * @return string HTML for flash message, or empty string if no message
 */
function displayFlash() {
    if (isset($_SESSION['flash'])) {
        $type = $_SESSION['flash']['type'];
        $message = $_SESSION['flash']['message'];
        
        unset($_SESSION['flash']);
        
        $alertClass = '';
        switch ($type) {
            case 'success':
                $alertClass = 'alert-success';
                break;
            case 'error':
                $alertClass = 'alert-danger';
                break;
            case 'warning':
                $alertClass = 'alert-warning';
                break;
            case 'info':
                $alertClass = 'alert-info';
                break;
        }
        
        return "
$message
";
    }
    
    return '';
}

/**
 * Check if user is logged in
 * 
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Get current user ID
 * 
 * @return int|null User ID if logged in, null otherwise
 */
function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

/**
 * Check if user has specified role
 * 
 * @param string|array $role Role or roles to check
 * @return bool True if user has role, false otherwise
 */
function hasRole($role) {
    if (!isset($_SESSION['user_role'])) {
        return false;
    }
    
    if (is_array($role)) {
        return in_array($_SESSION['user_role'], $role);
    }
    
    return $_SESSION['user_role'] === $role;
}

/**
 * Log activity
 * 
 * @param string $action Action performed
 * @param string $details Details about the action
 * @param int|null $userId User ID (default: current user)
 */
function logActivity($action, $details = '', $userId = null) {
    if ($userId === null) {
        $userId = getCurrentUserId();
    }
    
    $data = [
        'user_id' => $userId,
        'action' => $action,
        'details' => $details,
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    insertRecord('activity_log', $data);
}

/**
 * Calculate task completion percentage based on subtasks
 * 
 * @param int $taskId Major task ID
 * @return int Percentage (0-100)
 */
function calculateTaskCompletion($taskId) {
    $sql = "SELECT COUNT(*) as total, SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed 
            FROM SubTasks WHERE major_task_id = ?";
    $result = getRecord($sql, [$taskId]);
    
    if ($result && $result['total'] > 0) {
        return round(($result['completed'] / $result['total']) * 100);
    }
    
    return 0;
}

/**
 * Format bytes to human-readable size
 * 
 * @param int $bytes Number of bytes
 * @return string Human-readable size
 */
function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    
    while ($bytes >= 1024 && $i < 4) {
        $bytes /= 1024;
        $i++;
    }
    
    return round($bytes, 2) . ' ' . $units[$i];
}

?>