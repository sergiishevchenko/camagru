<?php

require_once __DIR__ . '/../config/env.php';

function smtpReadResponse($socket) {
    $response = '';
    stream_set_timeout($socket, 10);
    while ($line = fgets($socket, 515)) {
        $response .= $line;
        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }
    return $response;
}

function smtpExpect($socket, $expectedCode) {
    $response = smtpReadResponse($socket);
    $code = (int)substr($response, 0, 3);
    if ($code !== $expectedCode) {
        error_log("SMTP error: expected $expectedCode, got $code. Response: " . trim($response));
        return false;
    }
    return $response;
}

function smtpSend($socket, $command, $expectedCode = 250) {
    fwrite($socket, $command . "\r\n");
    return smtpExpect($socket, $expectedCode) !== false;
}

function smtpConnect($host, $port, $encryption) {
    $timeout = 10;

    if ($encryption === 'ssl') {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ]);
        $socket = @stream_socket_client(
            "ssl://$host:$port",
            $errno, $errstr, $timeout,
            STREAM_CLIENT_CONNECT, $context
        );
    } else {
        $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
    }

    if (!$socket) {
        error_log("SMTP connection failed to $host:$port ($encryption): $errstr ($errno)");
        return false;
    }

    stream_set_timeout($socket, 10);
    return $socket;
}

function smtpStartTls($socket) {
    if (!smtpSend($socket, "STARTTLS", 220)) {
        error_log("SMTP STARTTLS command rejected");
        return false;
    }

    $crypto = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
    if (!$crypto) {
        error_log("SMTP TLS negotiation failed");
        return false;
    }

    return true;
}

function smtpAuthenticate($socket, $user, $pass, $ehloResponse) {
    if (empty($user) || empty($pass)) {
        return true;
    }

    if (strpos($ehloResponse, 'AUTH') === false) {
        error_log("SMTP server does not advertise AUTH");
        return false;
    }

    if (strpos($ehloResponse, 'PLAIN') !== false) {
        $credentials = base64_encode("\0" . $user . "\0" . $pass);
        if (smtpSend($socket, "AUTH PLAIN $credentials", 235)) {
            return true;
        }
    }

    if (strpos($ehloResponse, 'LOGIN') !== false) {
        if (!smtpSend($socket, "AUTH LOGIN", 334)) {
            return false;
        }
        if (!smtpSend($socket, base64_encode($user), 334)) {
            return false;
        }
        if (!smtpSend($socket, base64_encode($pass), 235)) {
            return false;
        }
        return true;
    }

    error_log("SMTP: no supported AUTH mechanism (need PLAIN or LOGIN)");
    return false;
}

function smtpDotStuff($body) {
    return preg_replace('/^\./m', '..', $body);
}

function sendEmail($to, $subject, $body) {
    $smtpHost = env('SMTP_HOST', 'mailhog');
    $smtpPort = (int)env('SMTP_PORT', 1025);
    $smtpUser = env('SMTP_USER', '');
    $smtpPass = env('SMTP_PASS', '');
    $encryption = strtolower(env('SMTP_ENCRYPTION', 'none'));
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

    $socket = smtpConnect($smtpHost, $smtpPort, $encryption);
    if (!$socket) {
        return false;
    }

    if (smtpExpect($socket, 220) === false) {
        fclose($socket);
        return false;
    }

    fwrite($socket, "EHLO localhost\r\n");
    $ehloResponse = smtpReadResponse($socket);
    if ((int)substr($ehloResponse, 0, 3) !== 250) {
        error_log("SMTP EHLO failed: " . trim($ehloResponse));
        fclose($socket);
        return false;
    }

    if ($encryption === 'tls') {
        if (!smtpStartTls($socket)) {
            fclose($socket);
            return false;
        }
        fwrite($socket, "EHLO localhost\r\n");
        $ehloResponse = smtpReadResponse($socket);
        if ((int)substr($ehloResponse, 0, 3) !== 250) {
            error_log("SMTP EHLO after STARTTLS failed");
            fclose($socket);
            return false;
        }
    }

    if (!smtpAuthenticate($socket, $smtpUser, $smtpPass, $ehloResponse)) {
        fclose($socket);
        return false;
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

    $data = $headers . "\r\n" . smtpDotStuff($message) . "\r\n.";
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
