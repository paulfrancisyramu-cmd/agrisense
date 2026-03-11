<?php
// reset_password.php
// Reset password using token

session_start();
include 'includes/db_connect.php';

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';
$token = $_GET['token'] ?? '';
$username = $_GET['user'] ?? '';

// Validate token parameter
if (empty($token) || empty($username)) {
    $error = "Invalid reset link. Please request a new password reset.";
} else {
    // Check if token is valid and not expired (using your existing schema)
    $stmt = $conn->prepare("
        SELECT prt.id, prt.user_id, prt.expires_at, u.username 
        FROM password_reset_tokens prt
        JOIN users u ON prt.user_id = u.id
        WHERE prt.token = :token AND u.username = :username
    ");
    $stmt->execute([':token' => $token, ':username' => $username]);
    $reset_request = $stmt->fetch();
    
    if (!$reset_request) {
        $error = "Invalid reset link. Please request a new password reset.";
    } elseif (strtotime($reset_request['expires_at']) < time()) {
        $error = "This reset link has expired. Please request a new password reset.";
    }
}

// Handle password reset form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($error)) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($new_password)) {
        $error = "Please enter a new password.";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (!$reset_request) {
        $error = "Invalid request. Please start over.";
    } else {
        // Update the user's password
        $update_stmt = $conn->prepare("UPDATE users SET password = :password WHERE id = :user_id");
        $update_stmt->execute([
            ':password' => $new_password,
            ':user_id' => $reset_request['user_id']
        ]);
        
        // Delete the used token
        $delete_stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE id = :id");
        $delete_stmt->execute([':id' => $reset_request['id']]);
        
        $success = "Password reset successfully! You can now login with your new password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriSense - Reset Password</title>
    <link rel="stylesheet" href="static/style.css?v=<?php echo time(); ?>">
    <style>
        .login-container {
            max-width: 450px;
            margin: 100px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .login-body {
            background: linear-gradient(135deg, #22c55e 0%, #15803d 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background: #22c55e;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }
        
        .btn:hover {
            background: #16a34a;
        }
        
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            margin: 8px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        
        .message {
            padding: 12px;
            margin: 15px 0;
            border-radius: 5px;
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }
        
        .error {
            padding: 12px;
            margin: 15px 0;
            border-radius: 5px;
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        .success {
            padding: 12px;
            margin: 15px 0;
            border-radius: 5px;
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #666;
            text-decoration: none;
        }
        
        .back-link:hover {
            color: #22c55e;
        }
        
        .icon-green {
            color: #22c55e;
        }
    </style>
</head>
<body class="login-body">
    <div class="login-container">
        <h2 style="display: flex; align-items: center; justify-content: center; gap: 10px;">
            <img src="https://unpkg.com/lucide-static@latest/icons/leaf.svg" width="28" class="icon-green"> 
            AgriSense
        </h2>
        <p>Reset Your Password</p>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <a href="forgot_password.php" class="back-link">← Request New Reset Link</a>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
            <a href="index.php" class="back-link">← Go to Login</a>
        <?php endif; ?>
        
        <?php if (!$error && !$success): ?>
            <form method="POST" action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>&user=<?php echo htmlspecialchars($username); ?>">
                <input type="password" name="new_password" placeholder="Enter new password" required>
                <input type="password" name="confirm_password" placeholder="Confirm new password" required>
                <button type="submit" class="btn">Reset Password</button>
            </form>
            <a href="index.php" class="back-link">← Back to Login</a>
        <?php endif; ?>
    </div>
</body>
</html>

