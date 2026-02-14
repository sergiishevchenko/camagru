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

        $wantsJson = !empty($_GET['format']) && $_GET['format'] === 'json' ||
            (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
        if ($wantsJson) {
            header('Content-Type: application/json');
            echo json_encode([
                'images' => $images,
                'page' => $page,
                'hasMore' => $page < $totalPages,
                'nextPage' => $page < $totalPages ? $page + 1 : null
            ]);
            return;
        }

        $this->renderView('index', [
            'title' => 'Camagru - Gallery',
            'images' => $images,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'hasNextPage' => $page < $totalPages,
            'hasPrevPage' => $page > 1,
            'infiniteScroll' => true
        ]);
    }

    public function showImage($id) {
        $id = (int)$id;
        $image = $this->imageModel->findById($id);
        if (!$image) {
            http_response_code(404);
            echo '404 - Image not found';
            return;
        }

        require_once __DIR__ . '/../models/Like.php';
        require_once __DIR__ . '/../models/Comment.php';
        $likeModel = new Like();
        $commentModel = new Comment();
        $likeCount = $likeModel->getCount($id);
        $comments = $commentModel->getByImageId($id);

        $baseUrl = getBaseUrl();
        $imageUrl = $baseUrl . '/uploads/' . $image['filename'];
        $pageUrl = $baseUrl . '/image/' . $id;
        $title = 'Camagru - Photo by @' . $image['username'];
        $this->renderView('image', [
            'title' => $title,
            'image' => $image,
            'imageUrl' => $imageUrl,
            'likeCount' => $likeCount,
            'comments' => $comments,
            'pageUrl' => $pageUrl,
            'metaOg' => [
                'title' => $title,
                'image' => $imageUrl,
                'url' => $pageUrl
            ]
        ]);
    }

    private function renderView($view, $data = []) {
        extract($data);
        $csrfToken = generateCSRFToken();
        require_once __DIR__ . '/../views/layout.php';
    }
}
