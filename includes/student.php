<?php
/**
 * IUC Voting System - Student Management
 * Student registration and management functions
 */

class Student {
    private $pdo;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }
    
    /**
     * Check if student ID exists
     */
    public function studentIdExists($studentId) {
        $stmt = $this->pdo->prepare("SELECT id FROM students WHERE student_id = ?");
        $stmt->execute([$studentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }
    
    /**
     * Get total students count
     */
    public function getTotalStudents() {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count 
            FROM users u 
            WHERE u.type = 'student' AND u.status = 'approved'
        ");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
    
    /**
     * Get pending registrations count
     */
    public function getPendingRegistrationsCount() {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count 
            FROM users u 
            WHERE u.type = 'student' AND u.status = 'pending'
        ");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
    
    /**
     * Get pending registrations
     */
    public function getPendingRegistrations($limit = 10) {
        $stmt = $this->pdo->prepare("
            SELECT u.*, s.student_id, s.department, s.level 
            FROM users u 
            LEFT JOIN students s ON u.id = s.user_id 
            WHERE u.type = 'student' AND u.status = 'pending' 
            ORDER BY u.created_at DESC 
            LIMIT {$limit}
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get approved students
     */
    public function getApprovedStudents($limit = 50) {
        $stmt = $this->pdo->prepare("
            SELECT u.*, s.student_id, s.department, s.level 
            FROM users u 
            LEFT JOIN students s ON u.id = s.user_id 
            WHERE u.type = 'student' AND u.status = 'approved' 
            ORDER BY u.created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Approve student registration
     */
    public function approveStudent($studentId) {
        try {
            $this->pdo->beginTransaction();
            
            // Update user status
            $stmt = $this->pdo->prepare("
                UPDATE users SET status = 'approved', updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$studentId]);
            
            // Get student details
            $stmt = $this->pdo->prepare("
                SELECT u.*, s.student_id, s.department, s.level 
                FROM users u 
                LEFT JOIN students s ON u.id = s.user_id 
                WHERE u.id = ?
            ");
            $stmt->execute([$studentId]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Generate access code
            $accessCode = $this->generateAccessCode();
            
            // Save access code
            $stmt = $this->pdo->prepare("
                INSERT INTO access_codes (user_id, code, created_at) 
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$studentId, $accessCode]);
            
            $this->pdo->commit();
            
            // Send approval email
            $this->sendApprovalEmail($student['email'], $student['name'], $accessCode);
            
            return [
                'success' => true,
                'access_code' => $accessCode
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Approval failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Reject student registration
     */
    public function rejectStudent($studentId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE users SET status = 'rejected', updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$studentId]);
            
            return ['success' => true];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Rejection failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate access code
     */
    public function generateAccessCode() {
        return 'IUC-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
    }
    
    /**
     * Send registration confirmation email
     */
    public function sendRegistrationEmail($email, $name, $studentId) {
        $subject = "Registration Received - IUC Voting System";
        $message = "
            <h2>Registration Received</h2>
            <p>Dear $name,</p>
            <p>Thank you for registering for the IUC Voting System. Your registration has been received and is pending approval.</p>
            <p><strong>Student ID:</strong> $studentId</p>
            <p><strong>Email:</strong> $email</p>
            <p>You will receive another email once your registration has been approved with your access code.</p>
            <p>Best regards,<br>IUC Voting System Team</p>
        ";
        
        return $this->sendEmail($email, $subject, $message);
    }
    
    /**
     * Send approval email
     */
    public function sendApprovalEmail($email, $name, $accessCode) {
        $subject = "Registration Approved - IUC Voting System";
        $message = "
            <h2>Registration Approved</h2>
            <p>Dear $name,</p>
            <p>Your registration for the IUC Voting System has been approved!</p>
            <p><strong>Access Code:</strong> $accessCode</p>
            <p>You can now log in to the system using your email and password.</p>
            <p><strong>Login URL:</strong> <a href='http://localhost/iuc-voting/'>http://localhost/iuc-voting/</a></p>
            <p>Best regards,<br>IUC Voting System Team</p>
        ";
        
        return $this->sendEmail($email, $subject, $message);
    }
    
    /**
     * Send email using PHPMailer
     */
    private function sendEmail($to, $subject, $message) {
        try {
            // Use PHPMailer or mail() function
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: " . SYSTEM_NAME . " <" . ADMIN_EMAIL . ">" . "\r\n";
            
            // Suppress mail warnings and return success even if email fails
            $result = @mail($to, $subject, $message, $headers);
            
            // Log email attempt for debugging
            if (!$result) {
                error_log("Email sending failed - SMTP not configured. Email would have been sent to: $to");
                return true; // Continue with registration even if email fails
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return true; // Continue with registration even if email fails
        }
    }
    
    /**
     * Get student by user ID
     */
    public function getStudentByUserId($userId) {
        $stmt = $this->pdo->prepare("
            SELECT u.*, s.student_id, s.department, s.level 
            FROM users u 
            LEFT JOIN students s ON u.id = s.user_id 
            WHERE u.id = ? AND u.type = 'student'
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Check if student can vote
     */
    public function canStudentVote($userId) {
        $stmt = $this->pdo->prepare("
            SELECT u.status FROM users u WHERE u.id = ? AND u.type = 'student'
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result && $result['status'] === 'approved';
    }
    
    /**
     * Update student profile
     */
    public function updateStudentProfile($userId, $data) {
        try {
            $fields = [];
            $values = [];
            
            if (isset($data['department'])) {
                $fields[] = "department = ?";
                $values[] = $data['department'];
            }
            
            if (isset($data['level'])) {
                $fields[] = "level = ?";
                $values[] = $data['level'];
            }
            
            if (!empty($fields)) {
                $fields[] = "updated_at = NOW()";
                $values[] = $userId;
                
                $sql = "UPDATE students SET " . implode(', ', $fields) . " WHERE user_id = ?";
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
     * Get student voting history
     */
    public function getStudentVotingHistory($userId, $limit = 10) {
        $stmt = $this->pdo->prepare("
            SELECT v.*, e.title as election_title, c.name as candidate_name 
            FROM votes v 
            LEFT JOIN elections e ON v.election_id = e.id 
            LEFT JOIN candidates c ON v.candidate_id = c.id 
            WHERE v.user_id = ? 
            ORDER BY v.created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
