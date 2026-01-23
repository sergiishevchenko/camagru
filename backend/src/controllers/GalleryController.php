<?php

require_once __DIR__ . '/../models/Image.php';
require_once __DIR__ . '/../models/Like.php';
require_once __DIR__ . '/../utils/functions.php';

class GalleryController {
    private $imageModel;
    private $likeModel;

    public function __construct() {
        $this->imageModel = new Image();
        $this->likeModel = new Like();
    }

    public function index() {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 5;

        $images = $this->imageModel->getAll($page, $perPage);
        $totalImages = $this->imageModel->getTotalCount();
        $totalPages = ceil($totalImages / $perPage);

        $userId = getCurrentUserId();
        foreach ($images as &$image) {
            $image['is_liked'] = $userId ? $this->likeModel->isLiked($userId, $image['id']) : false;
            $image['is_owner'] = $userId && $image['user_id'] == $userId;
        }

        $this->renderView('index', [
            'title' => 'Camagru - Gallery',
            'images' => $images,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'hasNextPage' => $page < $totalPages,
            'hasPrevPage' => $page > 1
        ]);
    }

    private function renderView($view, $data = []) {
        extract($data);
        $csrfToken = generateCSRFToken();
        require_once __DIR__ . '/../views/layout.php';
    }
}
