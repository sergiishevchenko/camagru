<?php

require_once __DIR__ . '/../models/Image.php';
require_once __DIR__ . '/../utils/functions.php';
require_once __DIR__ . '/../utils/image_processor.php';

class ImageController {
    private $imageModel;

    public function __construct() {
        $this->imageModel = new Image();
    }

    public function showEdit() {
        if (!isAuthenticated()) {
            redirect('/login');
            return;
        }

        $overlays = getAvailableOverlays();
        $userId = getCurrentUserId();
        $userImages = $this->imageModel->getByUserId($userId);
        $this->renderView('edit', [
            'title' => 'Edit Photo',
            'overlays' => $overlays,
            'userImages' => $userImages
        ]);
    }

    public function capture() {
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

        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);

        if ($data === null) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid JSON (received ' . strlen($raw) . ' bytes)']);
            return;
        }

        if (!isset($data['image']) || empty($data['image'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing image data']);
            return;
        }

        if (!verifyCSRFToken($data['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
            return;
        }

        $overlayId = $data['overlay_id'] ?? null;
        $result = processImageWithOverlay($data['image'], $overlayId);
        
        if (!$result['success']) {
            http_response_code(400);
            echo json_encode($result);
            return;
        }

        $userId = getCurrentUserId();
        $overlayDbValue = is_array($overlayId) ? implode(',', $overlayId) : $overlayId;
        $imageId = $this->imageModel->create($userId, $result['filename'], $overlayDbValue);

        echo json_encode([
            'success' => true,
            'image_id' => $imageId,
            'filename' => $result['filename']
        ]);
    }

    public function createGif() {
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
        $frames = $data['frames'] ?? [];
        if (!is_array($frames) || empty($frames)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Frames array required']);
            return;
        }
        $overlayId = !empty($data['overlay_id']) ? $data['overlay_id'] : null;
        $delayTicks = isset($data['delay']) ? max(5, min(50, (int)$data['delay'])) : 10;
        $result = createAnimatedGif($frames, $overlayId, $delayTicks);
        if (!$result['success']) {
            http_response_code(400);
            echo json_encode($result);
            return;
        }
        $userId = getCurrentUserId();
        $imageId = $this->imageModel->create($userId, $result['filename'], $overlayId);
        echo json_encode([
            'success' => true,
            'image_id' => $imageId,
            'filename' => $result['filename']
        ]);
    }

    public function upload() {
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

        try {
            if (!isset($_FILES['image'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Missing required fields']);
                return;
            }

            if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
                return;
            }

            $overlayId = null;
            if (isset($_POST['overlay_id'])) {
                $overlayId = $_POST['overlay_id'];
            } elseif (isset($_POST['overlay_ids']) && is_array($_POST['overlay_ids'])) {
                $overlayId = $_POST['overlay_ids'];
            }
            $result = processUploadedImage($_FILES['image'], $overlayId);
        
            if (!$result['success']) {
                http_response_code(400);
                echo json_encode($result);
                return;
            }

            $userId = getCurrentUserId();
            $overlayDbValue = is_array($overlayId) ? implode(',', $overlayId) : $overlayId;
            $imageId = $this->imageModel->create($userId, $result['filename'], $overlayDbValue);

            echo json_encode([
                'success' => true,
                'image_id' => $imageId,
                'filename' => $result['filename']
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Server error']);
        }
    }

    public function delete($id) {
        header('Content-Type: application/json');
        
        if (!isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
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
        $image = $this->imageModel->findById($id);

        if (!$image) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Image not found']);
            return;
        }

        if (!$this->imageModel->isOwner($id, $userId)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Forbidden']);
            return;
        }

        $filename = $image['filename'];
        if ($this->imageModel->delete($id, $userId)) {
            $filepath = __DIR__ . '/../../public/uploads/' . $filename;
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to delete image']);
        }
    }

    private function renderView($view, $data = []) {
        extract($data);
        $csrfToken = generateCSRFToken();
        require_once __DIR__ . '/../views/layout.php';
    }
}
