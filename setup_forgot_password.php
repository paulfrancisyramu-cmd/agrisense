<?php
// setup_forgot_password.php
// Run this file to set up the database for forgot password feature
// Access this file in your browser on Render: https://your-app.onrender.com/setup_forgot_password.php

include 'includes/db_connect.php';

echo "<h1>Setting up Forgot Password Database...</h1>";

try {
    // Add email column to users table (if not exists)
    $conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS email VARCHAR(255) DEFAULT NULL");
    echo "<p>✓ Added 'email' column to users table</p>";
    
    // Create password_reset_tokens table
    $sql = "
        CREATE TABLE IF NOT EXISTS password_reset_tokens (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            token VARCHAR(64) NOT NULL UNIQUE,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NOT NULL,
            used BOOLEAN DEFAULT FALSE
        )
    ";
    $conn->exec($sql);
    echo "<p>✓ Created 'password_reset_tokens' table</p>";
    
    // Create indexes
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_password_reset_tokens_token ON password_reset_tokens(token)");
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_password_reset_tokens_user_id ON password_reset_tokens(user_id)");
    echo "<p>✓ Created indexes for faster lookups</p>";
    
    echo "<h2 style='color: green;'>Setup Complete!</h2>";
    echo "<p>You can now use the forgot password feature:</p>";
    echo "<ol>";
    echo "<li>Go to <a href='forgot_password.php'>forgot_password.php</a></li>";
    echo "<li>Enter your username (e.g., 'admin')</li>";
    echo "<li>Copy the reset link that appears on screen</li>";
    echo "<li>Open the link and set a new password</li>";
    echo "</ol>";
    echo "<p><a href='index.php'>Go to Login Page</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

