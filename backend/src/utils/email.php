<?php

require_once __DIR__ . '/../config/env.php';

function smtpSend($socket, $command, $expectedCode = 250) {
    fwrite($socket, $command . "\r\n");
    $response = '';
    while ($line = fgets($socket, 515)) {
        $response .= $line;
        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }
    $code = (int)substr($response, 0, 3);
    if ($code !== $expectedCode) {
        error_log("SMTP error: expected $expectedCode, got $code. Response: $response");
        return false;
    }
    return true;
}

function sendEmail($to, $subject, $body) {
    $smtpHost = env('SMTP_HOST', 'mailhog');
    $smtpPort = (int)env('SMTP_PORT', 1025);
    $smtpUser = env('SMTP_USER', '');
    $smtpPass = env('SMTP_PASS', '');
    $fromEmail = env('SMTP_FROM_EMAIL', 'noreply@camagru.local');
    $fromName = env('SMTP_FROM_NAME', 'Camagru');

    $message = "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .button { display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class='container'>
        $body
        <div class='footer'>
            <p>This is an automated message from Camagru. Please do not reply.</p>
        </div>
    </div>
</body>
</html>";

    $socket = @fsockopen($smtpHost, $smtpPort, $errno, $errstr, 10);
    if (!$socket) {
        error_log("SMTP connection failed: $errstr ($errno)");
        return false;
    }

    $greeting = fgets($socket, 515);
    if ((int)substr($greeting, 0, 3) !== 220) {
        fclose($socket);
        return false;
    }

    if (!smtpSend($socket, "EHLO localhost", 250)) {
        fclose($socket);
        return false;
    }

    if (!empty($smtpUser) && !empty($smtpPass)) {
        if (!smtpSend($socket, "AUTH LOGIN", 334)) {
            fclose($socket);
            return false;
        }
        if (!smtpSend($socket, base64_encode($smtpUser), 334)) {
            fclose($socket);
            return false;
        }
        if (!smtpSend($socket, base64_encode($smtpPass), 235)) {
            fclose($socket);
            return false;
        }
    }

    if (!smtpSend($socket, "MAIL FROM:<$fromEmail>", 250)) {
        fclose($socket);
        return false;
    }

    if (!smtpSend($socket, "RCPT TO:<$to>", 250)) {
        fclose($socket);
        return false;
    }

    if (!smtpSend($socket, "DATA", 354)) {
        fclose($socket);
        return false;
    }

    $headers = "From: $fromName <$fromEmail>\r\n";
    $headers .= "To: $to\r\n";
    $headers .= "Subject: $subject\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "Date: " . date('r') . "\r\n";
    $headers .= "Message-ID: <" . uniqid('camagru_') . "@camagru.local>\r\n";

    $data = $headers . "\r\n" . $message . "\r\n.";
    if (!smtpSend($socket, $data, 250)) {
        fclose($socket);
        return false;
    }

    smtpSend($socket, "QUIT", 221);
    fclose($socket);
    return true;
}

function sendVerificationEmail($email, $username, $token) {
    $baseUrl = getBaseUrl();
    $verificationLink = $baseUrl . "/verify/" . $token;
    
    $subject = "Verify your Camagru account";
    $body = "
        <h2>Welcome to Camagru, $username!</h2>
        <p>Thank you for registering. Please verify your email address by clicking the link below:</p>
        <p><a href='$verificationLink' class='button'>Verify Email</a></p>
        <p>Or copy and paste this link into your browser:</p>
        <p>$verificationLink</p>
        <p>This link will expire in 24 hours.</p>
    ";
    
    return sendEmail($email, $subject, $body);
}

function sendPasswordResetEmail($email, $username, $token) {
    $baseUrl = getBaseUrl();
    $resetLink = $baseUrl . "/reset-password/" . $token;
    
    $subject = "Reset your Camagru password";
    $body = "
        <h2>Password Reset Request</h2>
        <p>Hello $username,</p>
        <p>You requested to reset your password. Click the link below to reset it:</p>
        <p><a href='$resetLink' class='button'>Reset Password</a></p>
        <p>Or copy and paste this link into your browser:</p>
        <p>$resetLink</p>
        <p>This link will expire in 1 hour.</p>
        <p>If you did not request this, please ignore this email.</p>
    ";
    
    return sendEmail($email, $subject, $body);
}

function sendCommentNotificationEmail($email, $username, $commenterUsername, $imageId) {
    $baseUrl = getBaseUrl();
    $imageLink = $baseUrl . "/#image-" . $imageId;
    
    $subject = "New comment on your photo";
    $body = "
        <h2>New Comment</h2>
        <p>Hello $username,</p>
        <p>$commenterUsername commented on your photo.</p>
        <p><a href='$imageLink' class='button'>View Photo</a></p>
        <p>Or copy and paste this link into your browser:</p>
        <p>$imageLink</p>
    ";
    
    return sendEmail($email, $subject, $body);
}
