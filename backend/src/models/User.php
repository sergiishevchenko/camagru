<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/functions.php';

class User {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function create($username, $email, $password) {
        $passwordHash = hashPassword($password);
        $verificationToken = generateToken();
        
        $stmt = $this->db->prepare("
            INSERT INTO users (username, email, password_hash, verification_token)
            VALUES (:username, :email, :password_hash, :verification_token)
        ");
        
        $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password_hash' => $passwordHash,
            ':verification_token' => $verificationToken
        ]);
        
        return [
            'id' => $this->db->lastInsertId(),
            'verification_token' => $verificationToken
        ];
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }

    public function findByUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        return $stmt->fetch();
    }

    public function verifyEmail($token) {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET email_verified = TRUE, verification_token = NULL 
            WHERE verification_token = :token
        ");
        $stmt->execute([':token' => $token]);
        return $stmt->rowCount() > 0;
    }

    public function setResetToken($email, $token, $expires) {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET reset_token = :token, reset_token_expires = :expires 
            WHERE email = :email
        ");
        $stmt->execute([
            ':email' => $email,
            ':token' => $token,
            ':expires' => $expires
        ]);
        return $stmt->rowCount() > 0;
    }

    public function resetPassword($token, $newPassword) {
        $passwordHash = hashPassword($newPassword);
        $stmt = $this->db->prepare("
            UPDATE users 
            SET password_hash = :password_hash, 
                reset_token = NULL, 
                reset_token_expires = NULL 
            WHERE reset_token = :token 
            AND reset_token_expires > NOW()
        ");
        $stmt->execute([
            ':password_hash' => $passwordHash,
            ':token' => $token
        ]);
        return $stmt->rowCount() > 0;
    }

    public function update($userId, $data) {
        $allowedFields = ['username', 'email', 'password_hash', 'email_notifications'];
        $updates = [];
        $params = [':id' => $userId];

        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $updates[] = "$field = :$field";
                $params[":$field"] = $value;
            }
        }

        if (empty($updates)) {
            return false;
        }

        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
}
