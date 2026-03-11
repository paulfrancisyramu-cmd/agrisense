<?php
/**
 * Password Reset Logic
 * Handles token generation, validation, and password updates
 */

require_once 'db_connect.php';

// Load .env file if exists
$env_file = __DIR__ . '/../agrisense.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Skip comments
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!getenv($key)) {
                putenv("$key=$value");
            }
        }
    }
}

/**
 * Generate a secure random token
 * @return string
 */
function generateResetToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Create a password reset token for a user
 * @param int $user_id User ID
 * @return string|false The reset token or false on failure
 */
function createPasswordResetToken($user_id) {
    global $conn;
    
    // Generate new token
    $token = generateResetToken();
    $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour from now
    
    // Update user with reset token
    $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expire = ? WHERE id = ?");
    
    try {
        $stmt->execute([$token, $expires, $user_id]);
        return $token;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get user by email address
 * @param string $email
 * @return array|false User data or false if not found
 */
function getUserByEmail($email) {
    global $conn;
    
    // First try to find by email (if email column exists)
    $stmt = $conn->prepare("SELECT id, username, email FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        return $user;
    }
    
    // Fallback: try to find by username (for backwards compatibility)
    $stmt = $conn->prepare("SELECT id, username, email FROM users WHERE username = ?");
    $stmt->execute([$email]);
    return $stmt->fetch();
}

/**
 * Validate a reset token
 * @param string $token
 * @return int|false User ID if valid, false if invalid/expired
 */
function validateResetToken($token) {
    global $conn;
    
    // Get token from users table
    $stmt = $conn->prepare("
        SELECT id, reset_token_expire 
        FROM users 
        WHERE reset_token = ?
    ");
    $stmt->execute([$token]);
    $result = $stmt->fetch();
    
    if (!$result) {
        // Token not found in database
        return false;
    }
    
    // Use PHP time() for comparison (more reliable than database NOW())
    $expires_timestamp = strtotime($result['reset_token_expire']);
    $now_timestamp = time();
    
    // Check if token has expired
    if ($now_timestamp > $expires_timestamp) {
        // Token has expired
        return false;
    }
    
    return $result['id'];
}

/**
 * Update user password
 * @param int $user_id
 * @param string $new_password
 * @return bool Success status
 */
function updateUserPassword($user_id, $new_password) {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expire = NULL WHERE id = ?");
    
    try {
        $stmt->execute([$new_password, $user_id]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get base URL for generating reset links
 * @return string
 */
function getBaseURL() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script_dir = dirname($_SERVER['SCRIPT_NAME']);
    
    return $protocol . '://' . $host . $script_dir;
}

/**
 * Process forgot password request
 * @param string $email_or_username
 * @return array Result with status and message
 */
function processForgotPassword($email_or_username) {
    $user = getUserByEmail($email_or_username);
    
    if (!$user) {
        // Don't reveal if user exists or not for security
        return [
            'success' => true,
            'message' => 'If an account exists with that email, you will receive a password reset link shortly.'
        ];
    }
    
    // Get base URL
    $base_url = getBaseURL();
    
    // Create reset token
    $token = createPasswordResetToken($user['id']);
    
    if (!$token) {
        return [
            'success' => false,
            'message' => 'Failed to generate reset token. Please try again.'
        ];
    }
    
    // Generate reset link
    $reset_link = $base_url . '/reset_password.php?token=' . $token;
    
    // Determine email to send to
    $send_to = !empty($user['email']) ? $user['email'] : $user['username'];
    
    // Send email
    require_once 'send_email.php';
    $email_sent = sendPasswordResetEmail($send_to, $reset_link);
    
    // Always return success to prevent email enumeration
    // In debug mode, also show the link for testing
    $app_debug = getenv('APP_DEBUG');
    if ($app_debug === 'true' || $app_debug === true) {
        return [
            'success' => true,
            'message' => 'Password reset link sent to your email. (Debug: ' . $reset_link . ')'
        ];
    }
    
    return [
        'success' => true,
        'message' => 'If an account exists with that email, you will receive a password reset link shortly.'
    ];
}

/**
 * Process password reset
 * @param string $token
 * @param string $new_password
 * @return array Result with status and message
 */
function processPasswordReset($token, $new_password) {
    // Validate token
    $user_id = validateResetToken($token);
    
    if (!$user_id) {
        return [
            'success' => false,
            'message' => 'Invalid or expired reset token. Please request a new password reset.'
        ];
    }
    
    // Validate password
    if (strlen($new_password) < 6) {
        return [
            'success' => false,
            'message' => 'Password must be at least 6 characters long.'
        ];
    }
    
    // Update password
    $updated = updateUserPassword($user_id, $new_password);
    
    if (!$updated) {
        return [
            'success' => false,
            'message' => 'Failed to update password. Please try again.'
        ];
    }
    
    return [
        'success' => true,
        'message' => 'Your password has been reset successfully. You can now login with your new password.'
    ];
}
?>

