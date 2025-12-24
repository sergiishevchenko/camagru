<?php

require_once __DIR__ . '/../config/database.php';

class Comment {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function create($userId, $imageId, $content) {
        $stmt = $this->db->prepare("
            INSERT INTO comments (user_id, image_id, content)
            VALUES (:user_id, :image_id, :content)
        ");
        
        $stmt->execute([
            ':user_id' => $userId,
            ':image_id' => $imageId,
            ':content' => $content
        ]);
        
        return $this->db->lastInsertId();
    }

    public function getByImageId($imageId) {
        $stmt = $this->db->prepare("
            SELECT c.*, u.username 
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.image_id = :image_id
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([':image_id' => $imageId]);
        return $stmt->fetchAll();
    }

    public function getImageOwner($imageId) {
        $stmt = $this->db->prepare("
            SELECT u.id, u.email, u.email_notifications, u.username
            FROM images i
            JOIN users u ON i.user_id = u.id
            WHERE i.id = :image_id
        ");
        $stmt->execute([':image_id' => $imageId]);
        return $stmt->fetch();
    }
}
