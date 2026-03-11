<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function sendPasswordResetEmail($email, $username, $reset_token) {
    $mail = new PHPMailer(true);
    try {
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'agrisensenagcarlanlgu@gmail.com';
        $mail->Password = 'guuqnbeqhwgevjbi';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('agrisensenagcarlanlgu@gmail.com', 'AgriSense System');
        $mail->addAddress($email, $username);
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $reset_link = $protocol . '://' . $host . '/reset_password.php?token=' . $reset_token;
        $mail->isHTML(true);
        $mail->Subject = 'AgriSense - Password Reset Request';
        $mail->Body = 'Hello ' . $username . ', click here to reset your password: ' . $reset_link;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('PHPMailer Error: ' . $mail->ErrorInfo);
        return false;
    }
}
?>
