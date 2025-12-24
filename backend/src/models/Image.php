<?php

require_once __DIR__ . '/../config/database.php';

class Image {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function create($userId, $filename, $overlayId = null) {
        $stmt = $this->db->prepare("
            INSERT INTO images (user_id, filename, overlay_id)
            VALUES (:user_id, :filename, :overlay_id)
        ");
        
        $stmt->execute([
            ':user_id' => $userId,
            ':filename' => $filename,
            ':overlay_id' => $overlayId
        ]);
        
        return $this->db->lastInsertId();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("
            SELECT i.*, u.username 
            FROM images i 
            JOIN users u ON i.user_id = u.id 
            WHERE i.id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function getAll($page = 1, $perPage = 5) {
        $offset = ($page - 1) * $perPage;
        
        $stmt = $this->db->prepare("
            SELECT i.*, u.username,
                   (SELECT COUNT(*) FROM likes l WHERE l.image_id = i.id) as like_count,
                   (SELECT COUNT(*) FROM comments c WHERE c.image_id = i.id) as comment_count
            FROM images i
            JOIN users u ON i.user_id = u.id
            ORDER BY i.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function getTotalCount() {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM images");
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }

    public function getByUserId($userId) {
        $stmt = $this->db->prepare("
            SELECT * FROM images 
            WHERE user_id = :user_id 
            ORDER BY created_at DESC
        ");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function delete($id, $userId) {
        $stmt = $this->db->prepare("
            DELETE FROM images 
            WHERE id = :id AND user_id = :user_id
        ");
        $stmt->execute([
            ':id' => $id,
            ':user_id' => $userId
        ]);
        return $stmt->rowCount() > 0;
    }

    public function isOwner($imageId, $userId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM images 
            WHERE id = :id AND user_id = :user_id
        ");
        $stmt->execute([
            ':id' => $imageId,
            ':user_id' => $userId
        ]);
        $result = $stmt->fetch();
        return ($result['count'] ?? 0) > 0;
    }
}
