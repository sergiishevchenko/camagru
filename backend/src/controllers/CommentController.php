<?php

require_once __DIR__ . '/../models/Comment.php';
require_once __DIR__ . '/../models/Image.php';
require_once __DIR__ . '/../utils/functions.php';
require_once __DIR__ . '/../utils/email.php';

class CommentController {
    private $commentModel;
    private $imageModel;

    public function __construct() {
        $this->commentModel = new Comment();
        $this->imageModel = new Image();
    }

    public function create($imageId) {
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

        $content = trim($data['content'] ?? '');

        if (empty($content)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Comment content is required']);
            return;
        }

        if (strlen($content) > 1000) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Comment is too long']);
            return;
        }

        $imageId = (int)$imageId;
        $image = $this->imageModel->findById($imageId);

        if (!$image) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Image not found']);
            return;
        }

        $userId = getCurrentUserId();
        $commentId = $this->commentModel->create($userId, $imageId, $content);

        $imageOwner = $this->commentModel->getImageOwner($imageId);
        
        if ($imageOwner && $imageOwner['id'] != $userId && $imageOwner['email_notifications']) {
            sendCommentNotificationEmail(
                $imageOwner['email'],
                $imageOwner['username'],
                $_SESSION['username'] ?? 'User',
                $imageId
            );
        }

        $comments = $this->commentModel->getByImageId($imageId);
        $newComment = null;
        foreach ($comments as $comment) {
            if ($comment['id'] == $commentId) {
                $newComment = $comment;
                break;
            }
        }

        echo json_encode([
            'success' => true,
            'comment' => $newComment
        ]);
    }

    public function getByImage($imageId) {
        $imageId = (int)$imageId;
        $comments = $this->commentModel->getByImageId($imageId);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'comments' => $comments
        ]);
    }
}
