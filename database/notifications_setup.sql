-- IUC Voting System - Notifications Database Setup
-- This file creates the notifications system tables

-- Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('student_registration', 'voting_code_required', 'election_started', 'system_alert', 'security_warning', 'general') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    related_user_id INT NULL,
    related_student_id INT NULL,
    status ENUM('unread', 'read', 'dismissed') DEFAULT 'unread',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    dismissed_at TIMESTAMP NULL,
    action_required BOOLEAN DEFAULT FALSE,
    action_url VARCHAR(255) NULL,
    action_text VARCHAR(100) NULL
);

-- Create voting_codes table for student voting codes
CREATE TABLE IF NOT EXISTS voting_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    election_id INT NOT NULL,
    voting_code VARCHAR(20) UNIQUE NOT NULL,
    status ENUM('generated', 'sent', 'used', 'expired') DEFAULT 'generated',
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL,
    used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    generated_by_admin INT NOT NULL,
    sent_by_admin INT NULL,
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (election_id) REFERENCES elections(id),
    FOREIGN KEY (generated_by_admin) REFERENCES users(id),
    FOREIGN KEY (sent_by_admin) REFERENCES users(id)
);

-- Create notification_preferences table for admin settings
CREATE TABLE IF NOT EXISTS notification_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    notification_type VARCHAR(50) NOT NULL,
    enabled BOOLEAN DEFAULT TRUE,
    email_notification BOOLEAN DEFAULT TRUE,
    in_app_notification BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id),
    UNIQUE KEY unique_admin_type (admin_id, notification_type)
);

-- Insert default notification preferences for admin users
INSERT IGNORE INTO notification_preferences (admin_id, notification_type, enabled, email_notification, in_app_notification)
SELECT id, 'student_registration', TRUE, TRUE, TRUE FROM users WHERE user_type = 'admin';

INSERT IGNORE INTO notification_preferences (admin_id, notification_type, enabled, email_notification, in_app_notification)
SELECT id, 'voting_code_required', TRUE, TRUE, TRUE FROM users WHERE user_type = 'admin';

INSERT IGNORE INTO notification_preferences (admin_id, notification_type, enabled, email_notification, in_app_notification)
SELECT id, 'election_started', TRUE, TRUE, TRUE FROM users WHERE user_type = 'admin';

INSERT IGNORE INTO notification_preferences (admin_id, notification_type, enabled, email_notification, in_app_notification)
SELECT id, 'system_alert', TRUE, TRUE, TRUE FROM users WHERE user_type = 'admin';

INSERT IGNORE INTO notification_preferences (admin_id, notification_type, enabled, email_notification, in_app_notification)
SELECT id, 'security_warning', TRUE, TRUE, TRUE FROM users WHERE user_type = 'admin';

-- Create indexes for better performance
CREATE INDEX idx_notifications_status ON notifications(status);
CREATE INDEX idx_notifications_type ON notifications(type);
CREATE INDEX idx_notifications_created_at ON notifications(created_at);
CREATE INDEX idx_notifications_related_user ON notifications(related_user_id);
CREATE INDEX idx_notifications_related_student ON notifications(related_student_id);

CREATE INDEX idx_voting_codes_student ON voting_codes(student_id);
CREATE INDEX idx_voting_codes_election ON voting_codes(election_id);
CREATE INDEX idx_voting_codes_status ON voting_codes(status);
CREATE INDEX idx_voting_codes_code ON voting_codes(voting_code);

-- Create trigger to automatically create notification when student registers
DELIMITER //
CREATE TRIGGER after_student_registration
AFTER INSERT ON users
FOR EACH ROW
BEGIN
    IF NEW.user_type = 'student' THEN
        INSERT INTO notifications (type, title, message, related_student_id, priority, action_required, action_url, action_text)
        VALUES (
            'student_registration',
            'New Student Registration',
            CONCAT('Student ', NEW.full_name, ' (', NEW.email, ') has registered and requires voting code generation.'),
            NEW.id,
            'high',
            TRUE,
            'index.php?page=voter_registration&action=generate_code&student_id=',
            CONCAT('Generate Code for ', NEW.full_name)
        );
    END IF;
END//
DELIMITER ;

-- Create function to generate unique voting codes
DELIMITER //
CREATE FUNCTION generate_voting_code() RETURNS VARCHAR(20)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE new_code VARCHAR(20);
    DECLARE code_exists INT;
    
    -- Generate unique code with format VOTE-YYYY-XXXX
    REPEAT
        SET new_code = CONCAT('VOTE-', YEAR(NOW()), '-', LPAD(FLOOR(RAND() * 10000), 4, '0'));
        SELECT COUNT(*) INTO code_exists FROM voting_codes WHERE voting_code = new_code;
    UNTIL code_exists = 0 END REPEAT;
    
    RETURN new_code;
END//
DELIMITER ;

-- Create procedure to generate voting code for student
DELIMITER //
CREATE PROCEDURE generate_student_voting_code(
    IN p_student_id INT,
    IN p_election_id INT,
    IN p_admin_id INT,
    OUT p_voting_code VARCHAR(20)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Generate voting code
    SET p_voting_code = generate_voting_code();
    
    -- Insert voting code
    INSERT INTO voting_codes (student_id, election_id, voting_code, generated_by_admin, expires_at)
    VALUES (p_student_id, p_election_id, p_voting_code, p_admin_id, DATE_ADD(NOW(), INTERVAL 30 DAY));
    
    -- Create notification for admin to send the code
    INSERT INTO notifications (type, title, message, related_student_id, priority, action_required, action_url, action_text)
    VALUES (
        'voting_code_required',
        'Voting Code Generated',
        CONCAT('Voting code has been generated for student. Please send the code to the student.'),
        p_student_id,
        'high',
        TRUE,
        CONCAT('index.php?page=voter_registration&action=send_code&student_id=', p_student_id, '&code=', p_voting_code),
        'Send Voting Code'
    );
    
    COMMIT;
END//
DELIMITER ;
