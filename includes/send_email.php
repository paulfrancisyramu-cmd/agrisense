<?php
/**
 * Simple Gmail SMTP Email Sender
 * Works without Composer - uses PHP's built-in stream functions
 */

// Load .env file if exists
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

class GmailSMTP {
    private $smtp_host = 'smtp.gmail.com';
    private $smtp_port = 587;
    private $username;
    private $password;
    private $from_email;
    private $from_name;
    private $socket;
    private $debug = false;
    
    public function __construct($username, $app_password, $from_email, $from_name = 'AgriSense') {
        $this->username = $username;
        $this->password = $app_password;
        $this->from_email = $from_email;
        $this->from_name = $from_name;
    }
    
    /**
     * Send email via Gmail SMTP
     * @param string $to Email to send to
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @return bool Success status
     */
    public function send($to, $subject, $body) {
        try {
            // Connect to SMTP server
            $this->connect();
            
            // Say hello to server
            $this->sendCommand("EHLO " . gethostname());
            $this->readResponse();
            
            // Start TLS
            $this->sendCommand("STARTTLS");
            $this->readResponse();
            
            // Enable crypto
            stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            
            // Say hello again after TLS
            $this->sendCommand("EHLO " . gethostname());
            $this->readResponse();
            
            // Authenticate
            $this->sendCommand("AUTH LOGIN");
            $this->readResponse();
            
            $this->sendCommand(base64_encode($this->username));
            $this->readResponse();
            
            $this->sendCommand(base64_encode($this->password));
            $response = $this->readResponse();
            
            if (strpos($response, '235') === false) {
                throw new Exception("Authentication failed. Please check your Gmail App Password.");
            }
            
            // Set from
            $this->sendCommand("MAIL FROM:<{$this->from_email}>");
            $this->readResponse();
            
            // Set to
            $this->sendCommand("RCPT TO:<{$to}>");
            $this->readResponse();
            
            // Send data
            $this->sendCommand("DATA");
            $this->readResponse();
            
            // Build email headers
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
            
            // Quit
            $this->sendCommand("QUIT");
            $this->readResponse();
            
            $this->disconnect();
            return true;
            
        } catch (Exception $e) {
            if ($this->debug) {
                echo "Error: " . $e->getMessage();
            }
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
        if ($this->debug) {
            echo ">> $command\n";
        }
        fwrite($this->socket, $command . "\r\n");
    }
    
    private function readResponse() {
        $response = '';
        while ($line = fgets($this->socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        if ($this->debug) {
            echo "<< $response\n";
        }
        return $response;
    }
}

/**
 * Send password reset email
 * @param string $email User's email address
 * @param string $reset_link Full URL to reset password page
 * @return bool Success status
 */
function sendPasswordResetEmail($email, $reset_link) {
    // Load Gmail configuration from environment
    $smtp_username = getenv('GMAIL_USERNAME') ?: 'your-email@gmail.com';
    $smtp_app_password = getenv('GMAIL_APP_PASSWORD') ?: 'your-app-password';
    $from_email = getenv('FROM_EMAIL') ?: $smtp_username;
    $from_name = getenv('FROM_NAME') ?: 'AgriSense';
    
    // Initialize mailer
    $mailer = new GmailSMTP($smtp_username, $smtp_app_password, $from_email, $from_name);
    
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

