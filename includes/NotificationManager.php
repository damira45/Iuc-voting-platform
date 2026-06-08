<?php
/**
 * IUC Voting System - Notification Manager
 * Handles all notification operations and student registration notifications
 */

class NotificationManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Create a new notification
     */
    public function createNotification($type, $title, $message, $relatedUserId = null, $relatedStudentId = null, $priority = 'medium', $actionRequired = false, $actionUrl = null, $actionText = null) {
        $sql = "INSERT INTO notifications (type, title, message, related_user_id, related_student_id, priority, action_required, action_url, action_text) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$type, $title, $message, $relatedUserId, $relatedStudentId, $priority, $actionRequired, $actionUrl, $actionText]);
    }
    
    /**
     * Get notifications for admin dashboard
     */
    public function getAdminNotifications($limit = 10, $status = 'unread') {
        $sql = "SELECT n.*, u.name as student_name, u.email as student_email 
                FROM notifications n 
                LEFT JOIN users u ON n.related_student_id = u.id 
                WHERE n.status = ? 
                ORDER BY n.created_at DESC 
                LIMIT {$limit}";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get notification count by status
     */
    public function getNotificationCount($status = 'unread') {
        $sql = "SELECT COUNT(*) as count FROM notifications WHERE status = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$status]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId) {
        $sql = "UPDATE notifications SET status = 'read', read_at = NOW() WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$notificationId]);
    }
    
    /**
     * Mark all notifications as read
     */
    public function markAllAsRead() {
        $sql = "UPDATE notifications SET status = 'read', read_at = NOW() WHERE status = 'unread'";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute();
    }
    
    /**
     * Dismiss notification
     */
    public function dismissNotification($notificationId) {
        $sql = "UPDATE notifications SET status = 'dismissed', dismissed_at = NOW() WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$notificationId]);
    }
    
    /**
     * Generate voting code for student
     */
    public function generateVotingCode($studentId, $electionId, $adminId) {
        // Generate unique voting code
        $votingCode = $this->generateUniqueVotingCode();
        
        // Insert voting code
        $sql = "INSERT INTO voting_codes (student_id, election_id, voting_code, generated_by_admin, expires_at) 
                VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))";
        
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([$studentId, $electionId, $adminId, $votingCode]);
        
        if ($result) {
            // Get student info for notification
            $studentSql = "SELECT name, email FROM users WHERE id = ?";
            $studentStmt = $this->pdo->prepare($studentSql);
            $studentStmt->execute([$studentId]);
            $student = $studentStmt->fetch(PDO::FETCH_ASSOC);
            
            // Create notification for admin to send the code
            $this->createNotification(
                'voting_code_required',
                'Voting Code Generated',
                "Voting code has been generated for student {$student['name']} ({$student['email']}). Please send the code to the student.",
                null,
                $studentId,
                'high',
                true,
                "index.php?page=voter_registration&action=send_code&student_id={$studentId}&code={$votingCode}",
                'Send Voting Code'
            );
            
            return $votingCode;
        }
        
        return false;
    }
    
    /**
     * Generate unique voting code
     */
    private function generateUniqueVotingCode() {
        do {
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            $code = 'VOTE-';
            for ($i = 0; $i < 16; $i++) {
                if ($i === 4 || $i === 8 || $i === 12) $code .= '-';
                $code .= $chars[rand(0, strlen($chars) - 1)];
            }
            
            // Safety check: ensure we never return '1' or any invalid format
            if ($code === '1' || strlen($code) < 19) {
                continue; // Skip and generate again
            }
            
            $sql = "SELECT COUNT(*) as count FROM voting_codes WHERE voting_code = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$code]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } while ($result['count'] > 0 || $code === '1' || strlen($code) < 19);
        
        return $code;
    }
    
    /**
     * Send voting code to student (simulated email)
     */
    public function sendVotingCodeToStudent($studentId, $votingCode, $adminId) {
        // Get student info
        $sql = "SELECT full_name, email FROM users WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$studentId]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($student) {
            // Update voting code status
            $updateSql = "UPDATE voting_codes SET status = 'sent', sent_at = NOW(), sent_by_admin = ? WHERE voting_code = ?";
            $updateStmt = $this->pdo->prepare($updateSql);
            $updateStmt->execute([$adminId, $votingCode]);
            
            // Create notification that code was sent
            $this->createNotification(
                'general',
                'Voting Code Sent',
                "Voting code has been sent to student {$student['full_name']} ({$student['email']}).",
                null,
                $studentId,
                'low',
                false,
                null,
                null
            );
            
            // In production, this would send actual email
            // For now, we'll just log it
            error_log("Voting code {$votingCode} sent to {$student['email']} by admin {$adminId}");
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Get pending voting code requests
     */
    public function getPendingVotingCodeRequests() {
        $sql = "SELECT n.*, u.full_name, u.email, u.student_id 
                FROM notifications n 
                JOIN users u ON n.related_student_id = u.id 
                WHERE n.type = 'student_registration' AND n.status = 'unread' 
                ORDER BY n.created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get voting codes for a student
     */
    public function getStudentVotingCodes($studentId) {
        $sql = "SELECT vc.*, e.title as election_title, e.end_date as election_end_date 
                FROM voting_codes vc 
                JOIN elections e ON vc.election_id = e.id 
                WHERE vc.student_id = ? 
                ORDER BY vc.created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$studentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Check if student has valid voting code for election
     */
    public function hasValidVotingCode($studentId, $electionId) {
        $sql = "SELECT COUNT(*) as count 
                FROM voting_codes 
                WHERE student_id = ? AND election_id = ? AND status = 'sent' AND expires_at > NOW()";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$studentId, $electionId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] > 0;
    }
    
    /**
     * Use voting code (mark as used)
     */
    public function useVotingCode($votingCode, $studentId) {
        $sql = "UPDATE voting_codes SET status = 'used', used_at = NOW() 
                WHERE voting_code = ? AND student_id = ? AND status = 'sent'";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$votingCode, $studentId]);
    }
    
    /**
     * Get notification statistics
     */
    public function getNotificationStats() {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'unread' THEN 1 ELSE 0 END) as unread,
                    SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read,
                    SUM(CASE WHEN status = 'dismissed' THEN 1 ELSE 0 END) as dismissed,
                    SUM(CASE WHEN priority = 'critical' THEN 1 ELSE 0 END) as critical,
                    SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high,
                    SUM(CASE WHEN action_required = TRUE THEN 1 ELSE 0 END) as action_required
                FROM notifications";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
