<?php
// login.php
session_start();
include 'includes/db_connect.php';

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error_login = false;
$error_signup = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['signup'])) {
        // Signup logic
        $user = $_POST['username'];
        $pass = $_POST['password'];
        $confirm = $_POST['confirm_password'];

        if ($pass !== $confirm) {
            $error_signup = "Passwords do not match.";
        } elseif (empty($user) || empty($pass)) {
            $error_signup = "Username and password are required.";
        } else {
            // Check if username exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username");
            $stmt->execute([':username' => $user]);
            if ($stmt->fetch()) {
                $error_signup = "Username already exists.";
            } else {
                // Insert new user with role farmer
                $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (:username, :password, 'farmer')");
                $stmt->execute([':username' => $user, ':password' => $pass]);
                // Auto-login after signup
                $_SESSION['user_id'] = $conn->lastInsertId();
                $_SESSION['role'] = 'farmer';
                header("Location: dashboard.php");
                exit();
            }
        }
    } else {
        // Login logic
        $user = $_POST['username'];
        $pass = $_POST['password'];

        // Query the database for the user (PDO)
        $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = :username");
        $stmt->execute([':username' => $user]);
        $data = $stmt->fetch();

        if ($data) {
            // Compare passwords
            if ($pass === $data['password']) {
                $_SESSION['user_id'] = $data['id'];
                $_SESSION['role'] = $data['role'];
                header("Location: dashboard.php");
                exit();
            } else {
                $error_login = true;
            }
        } else {
            $error_login = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriSense - Login</title>
    <link rel="stylesheet" href="static/style.css?v=<?php echo time(); ?>">
    <script>
        function showTab(tab) {
            document.getElementById('login-tab').style.display = tab === 'login' ? 'block' : 'none';
            document.getElementById('signup-tab').style.display = tab === 'signup' ? 'block' : 'none';
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
        }
    </script>
</head>
<body class="login-body">
    <div class="login-container">
        <h2 style="display: flex; align-items: center; justify-content: center; gap: 10px;">
            <img src="https://unpkg.com/lucide-static@latest/icons/leaf.svg" width="28" class="icon-green"> 
            AgriSense
        </h2>
        <p>Smart Plant Recommendation System</p>

        <div class="tab-buttons" style="margin-top: 20px; display: flex; justify-content: center; gap: 10px;">
            <button type="button" class="tab-button active" onclick="showTab('login')">Login</button>
            <button type="button" class="tab-button" onclick="showTab('signup')">Sign Up</button>
        </div>

        <div id="login-tab">
            <form method="POST" action="index.php">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>

                <?php if ($error_login): ?>
                    <p style="color: #d90429; margin-top: 10px; font-size: 14px;">Invalid credentials. Try again.</p>
                <?php endif; ?>

                <button type="submit" class="btn">Access Dashboard</button>
            </form>
        </div>

        <div id="signup-tab" style="display:none;">
            <form method="POST" action="index.php">
                <input type="hidden" name="signup" value="1">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>

                <?php if (!empty($error_signup)): ?>
                    <p style="color: #d90429; margin-top: 10px; font-size: 14px;"><?php echo $error_signup; ?></p>
                <?php endif; ?>

                <button type="submit" class="btn">Create Account</button>
            </form>
        </div>
    </div>
</body>
</html>