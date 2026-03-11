<?php
// forgot_password.php
session_start();
include 'includes/db_connect.php';
include 'includes/password_reset.php';

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$message = '';
$error = '';
$email = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Please enter your email address or username.';
    } else {
        $result = processForgotPassword($email);
        
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
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
        .login-body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #1b4332 0%, #2d6a4f 50%, #40916c 100%);
            padding: 20px;
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 420px;
        }
        
        .login-container h2 {
            color: #1b4332;
            text-align: center;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .login-container p {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-size: 14px;
            color: #1b4332;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s, box-shadow 0.3s;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #40916c;
            box-shadow: 0 0 0 3px rgba(64, 145, 108, 0.15);
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #40916c 0%, #2d6a4f 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(45, 106, 79, 0.4);
        }
        
        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #40916c;
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .icon-green {
            filter: brightness(0) saturate(100%) invert(42%) sepia(26%) saturate(1291%) hue-rotate(118deg) brightness(93%) contrast(86%);
        }
    </style>
</head>
<body class="login-body">
    <div class="login-container">
        <h2>
            <img src="https://unpkg.com/lucide-static@latest/icons/leaf.svg" width="32" class="icon-green"> 
            AgriSense
        </h2>
        <p>Reset Your Password</p>

        <?php if (!empty($message)): ?>
            <div class="message success">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <a href="index.php" class="btn" style="text-decoration: none; display: block; text-align: center; padding: 14px;">
                Back to Login
            </a>
        <?php else: ?>
            <?php if (!empty($error)): ?>
                <div class="message error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="forgot_password.php">
                <div class="form-group">
                    <label for="email">Email Address or Username</label>
                    <input type="text" 
                           id="email" 
                           name="email" 
                           placeholder="Enter your email or username" 
                           value="<?php echo htmlspecialchars($email); ?>"
                           required>
                </div>

                <button type="submit" class="btn">Send Reset Link</button>
            </form>

            <a href="index.php" class="back-link">← Back to Login</a>
        <?php endif; ?>
    </div>
</body>
</html>

