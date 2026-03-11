<?php
/**
 * Email Helper Functions for AgriSense
 * Uses PHP mail() function with sendmail
 */

function sendPasswordResetEmail($email, $username, $reset_token) {
    $from_email = 'agrisensenagcarlanlgu@gmail.com';
    $from_name = 'AgriSense System';
    
    // Build reset link
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $reset_link = $protocol . '://' . $host . '/agrisense/reset_password.php?token=' . $reset_token;
    
    $subject = 'AgriSense - Password Reset Request';
    
    $message = "
    <html>
    <head>
        <title>Password Reset - AgriSense</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #40916c; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .button { display: inline-block; padding: 12px 24px; background: #40916c; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>AgriSense Password Reset</h1>
            </div>
            <div class='content'>
                <p>Hello <strong>$username</strong>,</p>
                <p>We received a request to reset your password. Click the button below to create a new password:</p>
                <p style='text-align: center;'>
                    <a href='$reset_link' class='button'>Reset Password</a>
                </p>
                <p>Or copy and paste this link into your browser:</p>
                <p style='word-break: break-all; color: #40916c;'>$reset_link</p>
                <p><strong>Note:</strong> This link will expire in 1 hour for security purposes.</p>
                <p>If you did not request a password reset, please ignore this email and your password will remain unchanged.</p>
            </div>
            <div class='footer'>
                <p>This is an automated message from AgriSense - Nagcarlan Agricultural Office</p>
            </div>
    </body>
    </html>
    ";
    
    // Email headers
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type:text/html;charset=UTF-8\r\n";
    $headers .= "From: $from_name <$from_email>\r\n";
    $headers .= "Reply-To: $from_email\r\n";
    
    // Send email using mail()
    $result = mail($email, $subject, $message, $headers);
    
    return $result;
}
?>
