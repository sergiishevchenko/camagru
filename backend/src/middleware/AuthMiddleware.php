<?php

require_once __DIR__ . '/../utils/functions.php';

class AuthMiddleware {
    public function handle() {
        if (!isAuthenticated()) {
            redirect('/login');
            return false;
        }
        return true;
    }
}
