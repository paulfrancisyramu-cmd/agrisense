<?php
/**
 * Simple SMTP Email Sender
 * Supports both Gmail and Mailgun
 * Works without Composer - uses PHP's built-in stream functions
 */

// Load .env file if exists
$env_file = __DIR__ . '/../agrisense.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
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

class SimpleSMTP {
    private $smtp_host;
    private $smtp_port;
    private $username;
    private $password;
    private $from_email;
    private $from_name;
    private $socket;
    private $debug = false;
    
    public function __construct($username, $app_password, $from_email, $from_name = 'AgriSense') {
        // Check if Mailgun is configured, otherwise use Gmail
        $mailgun_domain = getenv('MAILGUN_DOMAIN');
        
        if (!empty($mailgun_domain)) {
            // Use Mailgun SMTP
            $this->smtp_host = 'smtp.mailgun.org';
            $this->smtp_port = 587;
        } else {
            // Use Gmail SMTP
            $this->smtp_host = 'smtp.gmail.com';
            $this->smtp_port = 587;
        }
        
        $this->username = $username;
        $this->password = $app_password;
        $this->from_email = $from_email;
        $this->from_name = $from_name;
    }
    
    public function send($to, $subject, $body) {
        try {
            $this->connect();
            
            $this->sendCommand("EHLO " . gethostname());
            $this->readResponse();
            
            $this->sendCommand("STARTTLS");
            $this->readResponse();
            
            stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            
            $this->sendCommand("EHLO " . gethostname());
            $this->readResponse();
            
            $this->sendCommand("AUTH LOGIN");
            $this->readResponse();
            
            $this->sendCommand(base64_encode($this->username));
            $this->readResponse();
            
            $this->sendCommand(base64_encode($this->password));
            $response = $this->readResponse();
            
            if (strpos($response, '235') === false) {
                throw new Exception("Authentication failed");
            }
            
            $this->sendCommand("MAIL FROM:<{$this->from_email}>");
            $this->readResponse();
            
            $this->sendCommand("RCPT TO:<{$to}>");
            $this->readResponse();
            
            $this->sendCommand("DATA");
            $this->readResponse();
            
            $headers = [
                "From: {$this->from_name} <{$this->from_email}>",
                "To: {$to}",
                "Subject: {$subject}",
                "MIME-Version: 1.0",
                "Content-Type: text/html; charset=UTF-8",
                "Date: " . date('r')
            ];
            
            $message = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.";
            
            fwrite($this->socket, $message . "\r\n");
            $this->readResponse();
            
            $this->sendCommand("QUIT");
            $this->readResponse();
            
            $this->disconnect();
            return true;
            
        } catch (Exception $e) {
            $this->disconnect();
            return false;
        }
    }
    
    private function connect() {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);
        
        $this->socket = stream_socket_client(
            "tcp://{$this->smtp_host}:{$this->smtp_port}",
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT,
            $context
        );
        
        if (!$this->socket) {
            throw new Exception("Could not connect to SMTP server: $errstr ($errno)");
        }
        
        stream_set_timeout($this->socket, 30);
        $this->readResponse();
    }
    
    private function disconnect() {
        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
        }
    }
    
    private function sendCommand($command) {
        fwrite($this->socket, $command . "\r\n");
    }
    
    private function readResponse() {
        $response = '';
        while ($line = fgets($this->socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        return $response;
    }
}

function sendPasswordResetEmail($email, $reset_link) {
    // Check for Resend configuration first (recommended for Render)
    $resend_api_key = getenv('RESEND_API_KEY');
    
    if (!empty($resend_api_key)) {
        return sendViaResend($email, $reset_link);
    }
    
    // Check for Mailgun configuration
    $mailgun_domain = getenv('MAILGUN_DOMAIN');
    $mailgun_api_key = getenv('MAILGUN_API_KEY');
    
    if (!empty($mailgun_domain) && !empty($mailgun_api_key)) {
        return sendViaMailgun($email, $reset_link);
    }
    
    // Fall back to SMTP
    $smtp_username = getenv('GMAIL_USERNAME') ?: getenv('FROM_EMAIL') ?: 'your-email@gmail.com';
    $smtp_app_password = getenv('GMAIL_APP_PASSWORD') ?: 'your-app-password';
    $from_email = $smtp_username;
    $from_name = 'AgriSense';
    
    $mailer = new SimpleSMTP($smtp_username, $smtp_app_password, $from_email, $from_name);
    
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

function sendViaMailgun($email, $reset_link) {
    $domain = getenv('MAILGUN_DOMAIN');
    $api_key = getenv('MAILGUN_API_KEY');
    $from_email = getenv('MAILGUN_FROM') ?: 'noreply@' . $domain;
    
    $subject = 'Reset Your AgriSense Password';
    
    $html = <<<HTML
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
    </div>
</body>
</html>
HTML;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.mailgun.net/v3/$domain/messages");
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, "api:$api_key");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'from' => "AgriSense <$from_email>",
        'to' => $email,
        'subject' => $subject,
        'html' => $html
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $http_code === 200;
}

function sendViaResend($email, $reset_link) {
    $api_key = getenv('RESEND_API_KEY');
    
    $subject = 'Reset Your AgriSense Password';
    
    $html = <<<HTML
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
    </div>
</body>
</html>
HTML;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.resend.com/emails");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'from' => 'AgriSense <noreply@resend.dev>',
        'to' => [$email],
        'subject' => $subject,
        'html' => $html
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $http_code === 200;
}
?>

