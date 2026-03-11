<?php
// reset_password.php - Handle password reset with token
session_start();
include 'includes/db_connect.php';

$error = '';
$success = '';

// Check if token is provided
if (!isset($_GET['token']) || empty($_GET['token'])) {
    $error = "Invalid reset link. Please request a new password reset.";
} else {
    $token = $_GET['token'];
    
    // Verify token exists and not expired
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE reset_token = :token AND reset_token_expire > NOW()");
    $stmt->execute([':token' => $token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $error = "Invalid or expired reset link. Please request a new password reset.";
    } elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
        $new_password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($new_password !== $confirm_password) {
            $error = "Passwords do not match.";
        } elseif (strlen($new_password) < 4) {
            $error = "Password must be at least 4 characters.";
        } else {
            // Update password and clear token
            $stmt = $conn->prepare("UPDATE users SET password = :password, reset_token = NULL, reset_token_expire = NULL WHERE id = :id");
            $stmt->execute([':password' => $new_password, ':id' => $user['id']]);
            
            $success = "Password reset successful! You can now <a href='index.php'>login</a> with your new password.";
        }
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
        body {
            background: linear-gradient(135deg, #1b4332 0%, #2d6a4f 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 20px;
        }
        .reset-container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
        }
        .reset-container h2 {
            text-align: center;
            color: #1b4332;
            margin-bottom: 10px;
        }
        .reset-container p {
            text-align: center;
            color: #64748b;
            margin-bottom: 25px;
        }
        .reset-container input {
            width: 100%;
            padding: 14px;
            margin-bottom: 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            box-sizing: border-box;
        }
        .reset-container input:focus {
            border-color: #40916c;
            outline: none;
        }
        .btn {
            width: 100%;
            padding: 14px;
            background: #40916c;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }
        .btn:hover {
            background: #2d6a4f;
        }
        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
        }
        .success {
            background: #dcfce7;
            color: #166534;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #40916c;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <h2 style="display: flex; align-items: center; justify-content: center; gap: 10px;">
            <img src="https://unpkg.com/lucide-static@latest/icons/leaf.svg" width="28" style="filter: invert(36%) sepia(62%) saturate(464%) hue-rotate(105deg) brightness(94%) contrast(84%);"> 
            AgriSense
        </h2>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
            <a href="index.php" class="back-link">Back to Login</a>
        <?php elseif ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php else: ?>
            <p>Reset password for: <strong><?php echo htmlspecialchars($user['username']); ?></strong></p>
            
            <form method="POST" action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>">
                <input type="password" name="password" placeholder="New Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
                <button type="submit" class="btn">Reset Password</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
</parameter>
</create_file>
