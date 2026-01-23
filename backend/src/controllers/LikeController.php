<?php

require_once __DIR__ . '/../models/Like.php';
require_once __DIR__ . '/../utils/functions.php';

class LikeController {
    private $likeModel;

    public function __construct() {
        $this->likeModel = new Like();
    }

    public function toggle($imageId) {
        header('Content-Type: application/json');
        
        if (!isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (!verifyCSRFToken($data['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
            return;
        }

        $userId = getCurrentUserId();
        $imageId = (int)$imageId;

        $isLiked = $this->likeModel->isLiked($userId, $imageId);

        if ($isLiked) {
            $this->likeModel->remove($userId, $imageId);
            $liked = false;
        } else {
            $this->likeModel->add($userId, $imageId);
            $liked = true;
        }

        $count = $this->likeModel->getCount($imageId);

        echo json_encode([
            'success' => true,
            'liked' => $liked,
            'count' => $count
        ]);
    }
}
