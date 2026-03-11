<?php
// index.php - Login, Signup, and Forgot Password
session_start();
include 'includes/db_connect.php';
include 'includes/email_helper.php';

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error_login = false;
$error_signup = '';
$error_forgot = '';
$success_message = '';

// Handle POST requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ------------------- SIGNUP -------------------
    if (isset($_POST['signup'])) {
        $fullName = trim($_POST['full_name']);
        $user = trim($_POST['username']);
        $email = trim($_POST['email']);
        $pass = $_POST['password'];
        $confirm = $_POST['confirm_password'];

        if ($pass !== $confirm) {
            $error_signup = "Passwords do not match.";
        } elseif (empty($fullName) || empty($user) || empty($pass) || empty($email)) {
            $error_signup = "Full name, username, email, and password are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_signup = "Please enter a valid email address.";
        } else {
            // Check if username exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username");
            $stmt->execute([':username' => $user]);
            if ($stmt->fetch()) {
                $error_signup = "Username already exists.";
            } else {
                // Check if email exists
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
                $stmt->execute([':email' => $email]);
                if ($stmt->fetch()) {
                    $error_signup = "Email already registered.";
                } else {
                    // Insert new user with role farmer
                    $stmt = $conn->prepare("INSERT INTO users (full_name, username, email, password, role) VALUES (:full_name, :username, :email, :password, 'farmer')");
                    $stmt->execute([
                        ':full_name' => $fullName,
                        ':username' => $user,
                        ':email' => $email,
                        ':password' => $pass
                    ]);

                    $success_message = "Account created! You can now login.";
                    // Clear form for next time
                    $_POST = array();
                }
            }
        }
    }

    // ------------------- LOGIN -------------------
    elseif (isset($_POST['login'])) {
        $user = $_POST['username'];
        $pass = $_POST['password'];

        // Query the database for the user (PDO)
        $stmt = $conn->prepare("SELECT id, password, role, full_name FROM users WHERE username = :username");
        $stmt->execute([':username' => $user]);
        $data = $stmt->fetch();

        if ($data) {
            // Compare passwords
            if ($pass === $data['password']) {
                $_SESSION['user_id'] = $data['id'];
                $_SESSION['role'] = $data['role'];
                $_SESSION['full_name'] = $data['full_name'] ?? '';
                header("Location: dashboard.php");
                exit();
            } else {
                $error_login = true;
            }
        } else {
            $error_login = true;
        }
    }

    // ------------------- FORGOT PASSWORD -------------------
    elseif (isset($_POST['forgot_password'])) {
        $username = trim($_POST['recovery_username']);
        $fullname = trim($_POST['recovery_fullname']);

        if (empty($username) || empty($fullname)) {
            $error_forgot = "Please enter both your username and full name.";
        } else {
            // Check if combination exists
            $stmt = $conn->prepare("SELECT id, username, email FROM users WHERE username = :username AND full_name = :fullname");
            $stmt->execute([':username' => $username, ':fullname' => $fullname]);
            $user = $stmt->fetch();

            if ($user) {
                // Generate reset token (valid for 1 hour)
                $token = bin2hex(random_bytes(32));
                $expire = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Save token to database
                $stmt = $conn->prepare("UPDATE users SET reset_token = :token, reset_token_expire = :expire WHERE id = :id");
                $stmt->execute([':token' => $token, ':expire' => $expire, ':id' => $user['id']]);

                // show user success immediately; we'll send the email after the response finishes
                $success_message = "If the information you provided matches an account, a password reset link has been sent to the email on file. Please check your inbox (and spam).";

                // schedule actual email send in shutdown function so the page can render quickly
                register_shutdown_function(function() use ($user, $token) {
                    sendPasswordResetEmail($user['email'], $user['username'], $token);
                });
            } else {
                $error_forgot = "No account found matching that information.";
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
    <title>AgriSense - Login</title>
    <link rel="stylesheet" href="static/style.css?v=<?php echo time(); ?>">
    <script>
        function showTab(tab) {
            document.getElementById('login-tab').style.display = tab === 'login' ? 'block' : 'none';
            document.getElementById('signup-tab').style.display = tab === 'signup' ? 'block' : 'none';
            document.getElementById('forgot-tab').style.display = tab === 'forgot' ? 'block' : 'none';
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
        }

        function showForgotPassword() {
            document.getElementById('login-tab').style.display = 'none';
            document.getElementById('forgot-tab').style.display = 'block';
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
        }
    </script>
    <style>
        .forgot-link {
            display: block;
            text-align: center;
            margin-top: 10px;
            color: #40916c;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
        }
        .forgot-link:hover {
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
        <p>Smart Plant Recommendation System</p>

        <div class="tab-buttons" style="margin-top: 20px; display: flex; justify-content: center; gap: 10px;">
            <button type="button" class="tab-button active" onclick="showTab('login')">Login</button>
            <button type="button" class="tab-button" onclick="showTab('signup')">Sign Up</button>
        </div>

        <!-- Success Message -->
        <?php if (!empty($success_message)): ?>
            <div style="background: #dcfce7; color: #166534; padding: 12px; border-radius: 8px; margin-top: 15px; font-size: 14px; text-align: center;">       
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <!-- LOGIN TAB -->
        <div id="login-tab">
            <form method="POST" action="index.php">
                <input type="hidden" name="login" value="1">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>

                <?php if ($error_login): ?>
                    <p style="color: #d90429; margin-top: 10px; font-size: 14px;">Invalid credentials. Try again.</p>
                <?php endif; ?>

                <button type="submit" class="btn">Access Dashboard</button>
            </form>
            <a class="forgot-link" onclick="showForgotPassword()">Forgot Password?</a>
        </div>

        <!-- SIGNUP TAB -->
        <div id="signup-tab" style="display:none;">
            <form method="POST" action="index.php">
                <input type="hidden" name="signup" value="1">
                <input type="text" name="full_name" placeholder="Full Name" required value="<?php echo $_POST['full_name'] ?? ''; ?>">
                <input type="text" name="username" placeholder="Username" required value="<?php echo $_POST['username'] ?? ''; ?>">
                <input type="email" name="email" placeholder="Email Address" required value="<?php echo $_POST['email'] ?? ''; ?>">
                <input type="password" name="password" placeholder="Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>

                <?php if (!empty($error_signup)): ?>
                    <p style="color: #d90429; margin-top: 10px; font-size: 14px;"><?php echo $error_signup; ?></p>
                <?php endif; ?>

                <button type="submit" class="btn">Create Account</button>
            </form>
        </div>

        <!-- FORGOT PASSWORD TAB -->
        <div id="forgot-tab" style="display:none;">
            <p style="text-align: center; color: #64748b; margin-bottom: 15px; font-size: 14px;">
                Provide your username and full name to receive a password reset link to the email on file.
            </p>
            <form method="POST" action="index.php">
                <input type="hidden" name="forgot_password" value="1">
                <input type="text" name="recovery_username" placeholder="Username" required>
                <input type="text" name="recovery_fullname" placeholder="Full Name" required>

                <?php if (!empty($error_forgot)): ?>
                    <p style="color: #d90429; margin-top: 10px; font-size: 14px;"><?php echo $error_forgot; ?></p>
                <?php endif; ?>

                <button type="submit" class="btn">Send Reset Link</button>
            </form>
            <a class="forgot-link" onclick="showTab('login'); document.querySelectorAll('.tab-button')[0].click();">Back to Login</a>
        </div>
</body>
</html>
