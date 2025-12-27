<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/functions.php';
require_once __DIR__ . '/../utils/email.php';

class AuthController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function showLogin() {
        if (isAuthenticated()) {
            redirect('/');
            return;
        }
        $this->renderView('login', ['title' => 'Login']);
    }

    public function login() {
        if (isAuthenticated()) {
            redirect('/');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/login');
            return;
        }

        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->renderView('login', ['title' => 'Login', 'error' => 'Invalid request']);
            return;
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $this->renderView('login', ['title' => 'Login', 'error' => 'Username and password are required']);
            return;
        }

        $user = $this->userModel->findByUsername($username);
        if (!$user || !verifyPassword($password, $user['password_hash'])) {
            $this->renderView('login', ['title' => 'Login', 'error' => 'Invalid username or password']);
            return;
        }

        if (!$user['email_verified']) {
            $this->renderView('login', ['title' => 'Login', 'error' => 'Please verify your email address first']);
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        generateCSRFToken();

        redirect('/');
    }

    public function showRegister() {
        if (isAuthenticated()) {
            redirect('/');
            return;
        }
        $this->renderView('register', ['title' => 'Register']);
    }

    public function register() {
        if (isAuthenticated()) {
            redirect('/');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/register');
            return;
        }

        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->renderView('register', ['title' => 'Register', 'error' => 'Invalid request']);
            return;
        }

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $errors = [];

        if (empty($username)) {
            $errors[] = 'Username is required';
        } elseif (strlen($username) < 3 || strlen($username) > 50) {
            $errors[] = 'Username must be between 3 and 50 characters';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'Username can only contain letters, numbers, and underscores';
        } elseif ($this->userModel->findByUsername($username)) {
            $errors[] = 'Username already exists';
        }

        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!isValidEmail($email)) {
            $errors[] = 'Invalid email format';
        } elseif ($this->userModel->findByEmail($email)) {
            $errors[] = 'Email already registered';
        }

        if (empty($password)) {
            $errors[] = 'Password is required';
        } elseif (!isValidPassword($password)) {
            $errors[] = 'Password must be at least 8 characters and contain both letters and numbers';
        }

        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match';
        }

        if (!empty($errors)) {
            $this->renderView('register', ['title' => 'Register', 'errors' => $errors]);
            return;
        }

        try {
            $result = $this->userModel->create($username, $email, $password);
            
            if (sendVerificationEmail($email, $username, $result['verification_token'])) {
                $this->renderView('register', [
                    'title' => 'Register',
                    'success' => 'Registration successful! Please check your email to verify your account.'
                ]);
            } else {
                $this->renderView('register', [
                    'title' => 'Register',
                    'error' => 'Registration successful, but failed to send verification email. Please contact support.'
                ]);
            }
        } catch (Exception $e) {
            $this->renderView('register', ['title' => 'Register', 'error' => 'Registration failed. Please try again.']);
        }
    }

    public function verify($token) {
        if (empty($token)) {
            redirect('/login');
            return;
        }

        if ($this->userModel->verifyEmail($token)) {
            $this->renderView('verify', ['title' => 'Email Verified', 'success' => true]);
        } else {
            $this->renderView('verify', ['title' => 'Verification Failed', 'success' => false]);
        }
    }

    public function showForgotPassword() {
        if (isAuthenticated()) {
            redirect('/');
            return;
        }
        $this->renderView('forgot-password', ['title' => 'Forgot Password']);
    }

    public function forgotPassword() {
        if (isAuthenticated()) {
            redirect('/');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/forgot-password');
            return;
        }

        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->renderView('forgot-password', ['title' => 'Forgot Password', 'error' => 'Invalid request']);
            return;
        }

        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !isValidEmail($email)) {
            $this->renderView('forgot-password', ['title' => 'Forgot Password', 'error' => 'Invalid email address']);
            return;
        }

        $user = $this->userModel->findByEmail($email);
        if ($user) {
            $token = generateToken();
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $this->userModel->setResetToken($email, $token, $expires);
            sendPasswordResetEmail($email, $user['username'], $token);
        }

        $this->renderView('forgot-password', [
            'title' => 'Forgot Password',
            'success' => 'If an account with that email exists, a password reset link has been sent.'
        ]);
    }

    public function showResetPassword($token) {
        if (isAuthenticated()) {
            redirect('/');
            return;
        }

        if (empty($token)) {
            redirect('/forgot-password');
            return;
        }

        $this->renderView('reset-password', ['title' => 'Reset Password', 'token' => $token]);
    }

    public function resetPassword() {
        if (isAuthenticated()) {
            redirect('/');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/forgot-password');
            return;
        }

        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->renderView('reset-password', ['title' => 'Reset Password', 'error' => 'Invalid request', 'token' => $_POST['token'] ?? '']);
            return;
        }

        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($token)) {
            redirect('/forgot-password');
            return;
        }

        $errors = [];

        if (empty($password)) {
            $errors[] = 'Password is required';
        } elseif (!isValidPassword($password)) {
            $errors[] = 'Password must be at least 8 characters and contain both letters and numbers';
        }

        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match';
        }

        if (!empty($errors)) {
            $this->renderView('reset-password', ['title' => 'Reset Password', 'errors' => $errors, 'token' => $token]);
            return;
        }

        if ($this->userModel->resetPassword($token, $password)) {
            $this->renderView('reset-password', ['title' => 'Reset Password', 'success' => true]);
        } else {
            $this->renderView('reset-password', ['title' => 'Reset Password', 'error' => 'Invalid or expired reset token', 'token' => $token]);
        }
    }

    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        redirect('/login');
    }

    private function renderView($view, $data = []) {
        extract($data);
        $csrfToken = generateCSRFToken();
        require_once __DIR__ . '/../views/layout.php';
    }
}
