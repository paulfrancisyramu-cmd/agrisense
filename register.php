<?php
// register.php
// Create new account

session_start();
include 'includes/db_connect.php';

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    
    // Validation
    if (empty($username)) {
        $error = "Username is required.";
    } elseif (strlen($username) < 3) {
        $error = "Username must be at least 3 characters.";
    } elseif (empty($password)) {
        $error = "Password is required.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if username already exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = :username");
        $check_stmt->execute([':username' => $username]);
        
        if ($check_stmt->fetch()) {
            $error = "Username already exists. Please choose another.";
        } else {
            // Insert new user
            $insert_stmt = $conn->prepare("
                INSERT INTO users (username, password, full_name, role) 
                VALUES (:username, :password, :full_name, 'user')
            ");
            
            try {
                $insert_stmt->execute([
                    ':username' => $username,
                    ':password' => $password,
                    ':full_name' => $full_name ?: $username
                ]);
                
                $success = "Account created successfully! You can now login.";
            } catch (PDOException $e) {
                $error = "Error creating account: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriSense - Create Account</title>
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
        
        .login-link {
            text-align: center;
            margin-top: 15px;
            color: #666;
        }
        
        .login-link a {
            color: #22c55e;
            text-decoration: none;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body class="login-body">
    <div class="login-container">
        <h2 style="display: flex; align-items: center; justify-content: center; gap: 10px;">
            <img src="https://unpkg.com/lucide-static@latest/icons/leaf.svg" width="28" class="icon-green"> 
            AgriSense
        </h2>
        <p>Create New Account</p>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="message"><?php echo htmlspecialchars($success); ?></div>
            <div class="login-link">
                <a href="index.php">← Go to Login</a>
            </div>
        <?php else: ?>
            <form method="POST" action="register.php">
                <input type="text" name="username" placeholder="Username" required>
                <input type="text" name="full_name" placeholder="Full Name (optional)">
                <input type="password" name="password" placeholder="Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <button type="submit" class="btn">Create Account</button>
            </form>
            <div class="login-link">
                Already have an account? <a href="index.php">Login here</a>
            </div>
            <p style="text-align: center; margin-top: 10px; color: #666;">
                Forgot your password? <a href="forgot_password.php" style="color: #22c55e;">Reset it</a>
            </p>
        <?php endif; ?>
    </div>
</body>
</html>

