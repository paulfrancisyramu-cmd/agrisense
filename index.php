<?php
// login.php
session_start();
include 'includes/db_connect.php';

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    // Query the database for the user (PDO)
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = :username");
    $stmt->execute([':username' => $user]);
    $data = $stmt->fetch();

    if ($data) {
        // Compare passwords
        if ($pass === $data['password']) {
            $_SESSION['user_id'] = $data['id'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = true;
        }
    } else {
        $error = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AgriSense - Login</title>
    <link rel="stylesheet" href="static/style.css?v=<?php echo time(); ?>">
</head>
<body class="login-body">
    <div class="login-container">
        <h2 style="display: flex; align-items: center; justify-content: center; gap: 10px;">
            <img src="https://unpkg.com/lucide-static@latest/icons/leaf.svg" width="28" class="icon-green"> 
            AgriSense
        </h2>
        <p>Smart Plant Recommendation System</p>
        
        <form method="POST" action="index.php">
            <input type="text" name="username" placeholder="Username or Farm ID" required>
            <input type="password" name="password" placeholder="Password" required>
            
            <?php if ($error): ?>
                <p style="color: #d90429; margin-top: 10px; font-size: 14px;">Invalid credentials. Try again.</p>
            <?php endif; ?>
            
            <button type="submit" class="btn">Access Dashboard</button>
        </form>
    </div>
</body>
</html>