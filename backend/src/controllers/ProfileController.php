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

        $emailNotifications = isset($_POST['email_notifications']) ? 1 : 0;
        $data = ['email_notifications' => $emailNotifications];

        if ($this->userModel->update($userId, $data)) {
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
