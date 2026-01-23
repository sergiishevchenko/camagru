<?php

require_once __DIR__ . '/../config/env.php';

function sendEmail($to, $subject, $body) {
    $smtpHost = env('SMTP_HOST', 'mailhog');
    $smtpPort = env('SMTP_PORT', 1025);
    $fromEmail = env('SMTP_FROM_EMAIL', 'noreply@camagru.local');
    $fromName = env('SMTP_FROM_NAME', 'Camagru');

    $headers = "From: $fromName <$fromEmail>\r\n";
    $headers .= "Reply-To: $fromEmail\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

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

    $result = mail($to, $subject, $message, $headers);
    return $result;
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
