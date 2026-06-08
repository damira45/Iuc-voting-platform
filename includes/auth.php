<?php
/**
 * IUC Voting System - Authentication
 * User authentication and authorization
 */

class Auth {
    private $pdo;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }
    
    /**
     * User login
     */
    public function login($email, $password, $userType) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.*, s.student_id, s.department, s.level 
                FROM users u 
                LEFT JOIN students s ON u.id = s.user_id 
                WHERE u.email = ? AND u.type = ? AND u.status = 'approved'
            ");
            $stmt->execute([$email, $userType]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                return [
                    'success' => true,
                    'user' => $user
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Invalid email or password'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Login failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * User registration
     */
    public function register($data) {
        try {
            $this->pdo->beginTransaction();
            
            // Insert user
            $stmt = $this->pdo->prepare("
                INSERT INTO users (name, email, password, type, status, created_at) 
                VALUES (?, ?, ?, ?, 'pending', NOW())
            ");
            $stmt->execute([
                $data['name'],
                $data['email'],
                password_hash($data['password'], PASSWORD_DEFAULT),
                $data['type']
            ]);
            
            $userId = $this->pdo->lastInsertId();
            
            // Insert student details if student
            if ($data['type'] === 'student') {
                $stmt = $this->pdo->prepare("
                    INSERT INTO students (user_id, student_id, department, level, created_at) 
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $userId,
                    $data['student_id'],
                    $data['department'],
                    $data['level']
                ]);
            }
            
            $this->pdo->commit();
            
            // Get user data
            $stmt = $this->pdo->prepare("
                SELECT u.*, s.student_id, s.department, s.level 
                FROM users u 
                LEFT JOIN students s ON u.id = s.user_id 
                WHERE u.id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'user' => $user
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Check if email exists
     */
    public function emailExists($email) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }
    
    /**
     * Get user by ID
     */
    public function getUserById($userId) {
        $stmt = $this->pdo->prepare("
            SELECT u.*, s.student_id, s.department, s.level 
            FROM users u 
            LEFT JOIN students s ON u.id = s.user_id 
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($userId, $data) {
        try {
            $fields = [];
            $values = [];
            
            if (isset($data['name'])) {
                $fields[] = "name = ?";
                $values[] = $data['name'];
            }
            
            if (isset($data['email'])) {
                $fields[] = "email = ?";
                $values[] = $data['email'];
            }
            
            if (!empty($fields)) {
                $fields[] = "updated_at = NOW()";
                $values[] = $userId;
                
                $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($values);
            }
            
            return ['success' => true];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Update failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Change password
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Get current password
            $stmt = $this->pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($currentPassword, $user['password'])) {
                return [
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ];
            }
            
            // Update password
            $stmt = $this->pdo->prepare("
                UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?
            ");
            $stmt->execute([
                password_hash($newPassword, PASSWORD_DEFAULT),
                $userId
            ]);
            
            return ['success' => true];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Password change failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Approve user registration
     */
    public function approveUser($userId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE users SET status = 'approved', updated_at = NOW() WHERE id = ?
            ");
            $stmt->execute([$userId]);
            
            return ['success' => true];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Approval failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Reject user registration
     */
    public function rejectUser($userId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE users SET status = 'rejected', updated_at = NOW() WHERE id = ?
            ");
            $stmt->execute([$userId]);
            
            return ['success' => true];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Rejection failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get pending registrations
     */
    public function getPendingRegistrations($limit = 10) {
        $stmt = $this->pdo->prepare("
            SELECT u.*, s.student_id, s.department, s.level 
            FROM users u 
            LEFT JOIN students s ON u.id = s.user_id 
            WHERE u.status = 'pending' 
            ORDER BY u.created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Generate JWT token
     */
    public function generateToken($user) {
        $payload = [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'type' => $user['type'],
            'exp' => time() + SESSION_TIMEOUT
        ];
        
        return base64_encode(json_encode($payload));
    }
    
    /**
     * Validate JWT token
     */
    public function validateToken($token) {
        try {
            $payload = json_decode(base64_decode($token), true);
            
            if ($payload['exp'] < time()) {
                return false;
            }
            
            return $payload;
        } catch (Exception $e) {
            return false;
        }
    }
}
?>
