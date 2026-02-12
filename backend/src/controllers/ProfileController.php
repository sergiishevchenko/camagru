<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Image.php';
require_once __DIR__ . '/../utils/functions.php';

class ProfileController {
    private $userModel;
    private $imageModel;

    public function __construct() {
        $this->userModel = new User();
        $this->imageModel = new Image();
    }

    public function show() {
        if (!isAuthenticated()) {
            redirect('/login');
            return;
        }

        $userId = getCurrentUserId();
        $user = $this->userModel->findById($userId);
        if (!$user) {
            redirect('/login');
            return;
        }

        $images = $this->imageModel->getByUserId($userId);

        $this->renderView('profile', [
            'title' => 'Profile',
            'user' => $user,
            'images' => $images
        ]);
    }

    public function update() {
        if (!isAuthenticated()) {
            redirect('/login');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/profile');
            return;
        }

        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['profile_error'] = 'Invalid request';
            redirect('/profile');
            return;
        }

        $userId = getCurrentUserId();
        $user = $this->userModel->findById($userId);
        if (!$user) {
            redirect('/login');
            return;
        }

        $errors = [];
        $data = [];

        $newUsername = trim($_POST['username'] ?? '');
        if (!empty($newUsername) && $newUsername !== $user['username']) {
            if (strlen($newUsername) < 3 || strlen($newUsername) > 50) {
                $errors[] = 'Username must be between 3 and 50 characters';
            } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $newUsername)) {
                $errors[] = 'Username can only contain letters, numbers, and underscores';
            } elseif ($this->userModel->findByUsername($newUsername)) {
                $errors[] = 'Username already taken';
            } else {
                $data['username'] = $newUsername;
            }
        }

        $newEmail = trim($_POST['email'] ?? '');
        if (!empty($newEmail) && $newEmail !== $user['email']) {
            if (!isValidEmail($newEmail)) {
                $errors[] = 'Invalid email format';
            } elseif ($this->userModel->findByEmail($newEmail)) {
                $errors[] = 'Email already registered';
            } else {
                $data['email'] = $newEmail;
            }
        }

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        if (!empty($newPassword)) {
            if (empty($currentPassword)) {
                $errors[] = 'Current password is required to set a new password';
            } elseif (!verifyPassword($currentPassword, $user['password_hash'])) {
                $errors[] = 'Current password is incorrect';
            } elseif (!isValidPassword($newPassword)) {
                $errors[] = 'New password must be at least 8 characters with letters and numbers';
            } elseif ($newPassword !== $confirmPassword) {
                $errors[] = 'New passwords do not match';
            } else {
                $data['password_hash'] = hashPassword($newPassword);
            }
        }

        $emailNotifications = isset($_POST['email_notifications']) ? 1 : 0;
        $data['email_notifications'] = $emailNotifications;

        if (!empty($errors)) {
            $_SESSION['profile_errors'] = $errors;
            redirect('/profile');
            return;
        }

        if ($this->userModel->update($userId, $data)) {
            if (isset($data['username'])) {
                $_SESSION['username'] = $data['username'];
            }
            $_SESSION['profile_success'] = 'Profile updated successfully.';
        } else {
            $_SESSION['profile_error'] = 'Failed to update profile.';
        }

        redirect('/profile');
    }

    private function renderView($view, $data = []) {
        extract($data);
        $csrfToken = generateCSRFToken();
        require_once __DIR__ . '/../views/layout.php';
    }
}
