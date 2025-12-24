<?php

require_once __DIR__ . '/../config/database.php';

class Like {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function add($userId, $imageId) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO likes (user_id, image_id)
                VALUES (:user_id, :image_id)
            ");
            $stmt->execute([
                ':user_id' => $userId,
                ':image_id' => $imageId
            ]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function remove($userId, $imageId) {
        $stmt = $this->db->prepare("
            DELETE FROM likes 
            WHERE user_id = :user_id AND image_id = :image_id
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':image_id' => $imageId
        ]);
        return $stmt->rowCount() > 0;
    }

    public function isLiked($userId, $imageId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM likes 
            WHERE user_id = :user_id AND image_id = :image_id
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':image_id' => $imageId
        ]);
        $result = $stmt->fetch();
        return ($result['count'] ?? 0) > 0;
    }

    public function getCount($imageId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM likes 
            WHERE image_id = :image_id
        ");
        $stmt->execute([':image_id' => $imageId]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    }
}
