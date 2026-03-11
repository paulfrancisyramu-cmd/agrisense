<?php
// forgot_password.php
// Request password reset form

session_start();
include 'includes/db_connect.php';

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$message = '';
$error = '';
$reset_link = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    
    if (empty($username)) {
        $error = "Please enter your username.";
    } else {
        // Check if user exists
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate unique token
            $token = bin2hex(random_bytes(32));
            
            // Set expiry time (30 minutes from now)
            $expires_at = date('Y-m-d H:i:s', strtotime('+30 minutes'));
            
            // Insert token into password_reset_tokens table
            $insert_stmt = $conn->prepare("
                INSERT INTO password_reset_tokens (user_id, token, expires_at) 
                VALUES (:user_id, :token, :expires_at)
            ");
            
            try {
                $insert_stmt->execute([
                    ':user_id' => $user['id'],
                    ':token' => $token,
                    ':expires_at' => $expires_at
                ]);
                
                // Get the base URL - works on Render
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $base_path = dirname($_SERVER['REQUEST_URI']);
                if ($base_path === '/' || $base_path === '\\') {
                    $base_path = '';
                }
                
                // Build reset link
                $reset_link = $protocol . '://' . $host . $base_path . '/reset_password.php?token=' . $token . '&user=' . urlencode($username);
                
                $message = "Password reset link generated successfully!";
            } catch (PDOException $e) {
                $error = "Error generating reset token: " . $e->getMessage();
            }
        } else {
            // Don't reveal if username exists for security
            $message = "If the username exists, a reset link has been generated.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriSense - Forgot Password</title>
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
        
        input[type="text"] {
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
        
        .reset-link-box {
            background: #f0f9ff;
            border: 1px solid #7dd3fc;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            word-break: break-all;
        }
        
        .reset-link-box strong {
            color: #0369a1;
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
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($reset_link): ?>
            <div class="reset-link-box">
                <strong>Copy this link to reset your password:</strong><br><br>
                <a href="<?php echo htmlspecialchars($reset_link); ?>"><?php echo htmlspecialchars($reset_link); ?></a>
                <br><br>
                <small><strong>Note:</strong> This link will expire in 30 minutes.</small>
            </div>
        <?php else: ?>
            <form method="POST" action="forgot_password.php">
                <input type="text" name="username" placeholder="Enter your username" required>
                <button type="submit" class="btn">Generate Reset Link</button>
            </form>
        <?php endif; ?>
        
        <a href="index.php" class="back-link">← Back to Login</a>
    </div>
</body>
</html>

