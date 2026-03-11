<?php
/**
 * Email Sender using Postmark API
 * Works without Composer - uses PHP's cURL
 */

// Load .env file if exists (for local development)
$env_file = __DIR__ . '/../agrisense.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Skip comments
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!getenv($key)) {
                putenv("$key=$value");
            }
        }
    }
}

class PostmarkSender {
    private $api_token;
    private $from_email;
    private $from_name;
    
    public function __construct($api_token, $from_email, $from_name = 'AgriSense') {
        $this->api_token = $api_token;
        $this->from_email = $from_email;
        $this->from_name = $from_name;
    }
    
    /**
     * Send email via Postmark API
     * @param string $to Email to send to
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @return bool Success status
     */
    public function send($to, $subject, $body) {
        $url = "https://api.postmarkapp.com/email";
        
        $post_data = json_encode([
            'From' => "{$this->from_name} <{$this->from_email}>",
            'To' => $to,
            'Subject' => $subject,
            'HtmlBody' => $body,
            'MessageStream' => 'outbound'
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/json',
            'X-Postmark-API-Token: ' . $this->api_token
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        // Check for success (201 = created, 200 = ok)
        return in_array($http_code, [200, 201]) && isset($result['ErrorCode']) && $result['ErrorCode'] === 0;
    }
}

/**
 * Send password reset email via Postmark
 * @param string $email User's email address
 * @param string $reset_link Full URL to reset password page
 * @return bool Success status
 */
function sendPasswordResetEmail($email, $reset_link) {
    // Load Postmark configuration from environment
    $api_token = getenv('POSTMARK_API_TOKEN');
    $from_email = getenv('POSTMARK_FROM_EMAIL');
    $from_name = getenv('POSTMARK_FROM_NAME') ?: 'AgriSense';
    
    // Check if Postmark is configured
    if (empty($api_token) || empty($from_email)) {
        error_log("Postmark not configured. Please add POSTMARK_API_TOKEN and POSTMARK_FROM_EMAIL to .env");
        return false;
    }
    
    // Initialize mailer
    $mailer = new PostmarkSender($api_token, $from_email, $from_name);
    
    // Email subject and body
    $subject = 'Reset Your AgriSense Password';
    
    $body = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #40916c 0%, #2d6a4f 100%); padding: 30px; border-radius: 10px 10px 0 0;">
        <h1 style="color: white; margin: 0; display: flex; align-items: center; gap: 10px;">
            🌱 AgriSense
        </h1>
    </div>
    
    <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #e0e0e0;">
        <h2 style="color: #1b4332; margin-top: 0;">Password Reset Request</h2>
        
        <p>We received a request to reset your AgriSense password. Click the button below to create a new password:</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{$reset_link}" style="background: #40916c; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">
                Reset Password
            </a>
        </div>
        
        <p style="font-size: 14px; color: #666;">
            Or copy and paste this link into your browser:<br>
            <span style="color: #40916c; word-break: break-all;">{$reset_link}</span>
        </p>
        
        <div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107;">
            <strong>⚠️ Important:</strong> This link will expire in 1 hour for security purposes.
        </div>
        
        <p style="font-size: 14px; color: #666;">
            If you didn't request a password reset, please ignore this email. Your password will remain unchanged.
        </p>
        
        <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">
        
        <p style="font-size: 12px; color: #999; text-align: center;">
            This is an automated email from AgriSense. Please do not reply to this message.
        </p>
    </div>
</body>
</html>
HTML;
    
    return $mailer->send($email, $subject, $body);
}
?>
