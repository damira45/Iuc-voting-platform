-- IUC Voting System Database Setup
-- Blockchain-based secure voting platform

-- Create database
CREATE DATABASE IF NOT EXISTS iuc_voting_system;
USE iuc_voting_system;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(191) NOT NULL,
    email VARCHAR(191) UNIQUE NOT NULL,
    password VARCHAR(191) NOT NULL,
    type ENUM('student', 'admin') NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_type (type),
    INDEX idx_status (status)
);

-- Students table (extends users)
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    student_id VARCHAR(50) UNIQUE NOT NULL,
    department VARCHAR(100) NOT NULL,
    level VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_department (department)
);

-- Elections table
CREATE TABLE elections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(191) NOT NULL,
    description TEXT,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('draft', 'active', 'completed', 'cancelled') DEFAULT 'draft',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date)
);

-- Candidates table
CREATE TABLE candidates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    election_id INT NOT NULL,
    name VARCHAR(191) NOT NULL,
    description TEXT,
    position INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE,
    INDEX idx_election (election_id),
    UNIQUE KEY unique_candidate_position (election_id, position)
);

-- Votes table
CREATE TABLE votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    election_id INT NOT NULL,
    candidate_id INT NOT NULL,
    user_id INT NOT NULL,
    transaction_hash VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_vote (election_id, user_id),
    INDEX idx_election_user (election_id, user_id),
    INDEX idx_transaction (transaction_hash)
);

-- Access codes table
CREATE TABLE access_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_code (code)
);

-- Blockchain transactions table
CREATE TABLE blockchain_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    election_id INT,
    transaction_hash VARCHAR(100) NOT NULL,
    block_number INT,
    type ENUM('election_created', 'vote_cast', 'results_finalized') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE,
    INDEX idx_transaction_hash (transaction_hash),
    INDEX idx_election_type (election_id, type)
);

-- Admin users table (for system administrators)
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    permissions JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Activity logs table
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_action (user_id, action),
    INDEX idx_created (created_at)
);

-- Notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(191) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created (created_at)
);

-- System settings table
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert default admin user
INSERT INTO users (name, email, password, type, status) VALUES 
('System Administrator', 'admin@iuc.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'approved');

-- Insert admin permissions
INSERT INTO admin_users (user_id, permissions) VALUES 
(1, JSON_OBJECT(
    'manage_students', true,
    'manage_elections', true,
    'view_results', true,
    'system_settings', true
));

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('system_name', 'IUC Voting System', 'System name'),
('blockchain_enabled', 'true', 'Enable blockchain integration'),
('max_elections', '10', 'Maximum concurrent elections'),
('voting_timeout', '300', 'Voting session timeout in seconds'),
('email_notifications', 'true', 'Enable email notifications');

-- Create indexes for performance
CREATE INDEX idx_users_type_status ON users(type, status);
CREATE INDEX idx_elections_dates_status ON elections(start_date, end_date, status);
CREATE INDEX idx_votes_election_candidate ON votes(election_id, candidate_id);
CREATE INDEX idx_activity_logs_created_user ON activity_logs(created_at, user_id);

-- Create view for active elections with vote counts
CREATE VIEW active_elections_view AS
SELECT 
    e.*,
    COUNT(v.id) as total_votes,
    COUNT(DISTINCT v.user_id) as unique_voters
FROM elections e
LEFT JOIN votes v ON e.id = v.election_id
WHERE e.status = 'active' 
    AND e.start_date <= CURDATE() 
    AND e.end_date >= CURDATE()
GROUP BY e.id;

-- Create view for student voting eligibility
CREATE VIEW eligible_students_view AS
SELECT 
    u.id,
    u.name,
    u.email,
    s.student_id,
    s.department,
    s.level
FROM users u
JOIN students s ON u.id = s.user_id
WHERE u.type = 'student' 
    AND u.status = 'approved';

-- Create trigger for activity logging
DELIMITER //
CREATE TRIGGER after_user_login
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    IF NEW.updated_at != OLD.updated_at THEN
        INSERT INTO activity_logs (user_id, action, details, created_at)
        VALUES (NEW.id, 'login', 'User logged in', NOW());
    END IF;
END//
DELIMITER ;

-- Create stored procedure for election statistics
DELIMITER //
CREATE PROCEDURE GetElectionStatistics(IN election_id INT)
BEGIN
    SELECT 
        e.title,
        e.start_date,
        e.end_date,
        COUNT(v.id) as total_votes,
        COUNT(DISTINCT v.user_id) as unique_voters,
        COUNT(c.id) as total_candidates
    FROM elections e
    LEFT JOIN votes v ON e.id = v.election_id
    LEFT JOIN candidates c ON e.id = c.election_id
    WHERE e.id = election_id
    GROUP BY e.id;
END//
DELIMITER ;

-- Create stored procedure for student voting history
DELIMITER //
CREATE PROCEDURE GetStudentVotingHistory(IN student_id INT)
BEGIN
    SELECT 
        e.title as election_title,
        c.name as candidate_name,
        v.transaction_hash,
        v.created_at as voted_at
    FROM votes v
    JOIN elections e ON v.election_id = e.id
    JOIN candidates c ON v.candidate_id = c.id
    WHERE v.user_id = student_id
    ORDER BY v.created_at DESC;
END//
DELIMITER ;

-- Sample data for testing
INSERT INTO elections (title, description, start_date, end_date, status, created_by) VALUES
('Student Union Election 2024', 'Annual student union leadership election', '2024-01-15', '2024-01-30', 'active', 1),
('Department Representative Election', 'Department-level representative selection', '2024-02-01', '2024-02-15', 'draft', 1);

INSERT INTO candidates (election_id, name, description, position) VALUES
(1, 'Alice Johnson', 'Computer Science Level 3 student', 1),
(1, 'Bob Smith', 'Business Administration Level 2 student', 2),
(1, 'Carol Davis', 'Engineering Level 4 student', 3);

-- Create backup procedure
DELIMITER //
CREATE PROCEDURE BackupSystem()
BEGIN
    DECLARE backup_file VARCHAR(255);
    SET backup_file = CONCAT('backup_', DATE_FORMAT(NOW(), '%Y%m%d_%H%i%s'), '.sql');
    
    SET @sql = CONCAT('mysqldump -u root -p iuc_voting_system > ', backup_file);
    -- PREPARE stmt FROM @sql;
    -- EXECUTE stmt;
    -- DEALLOCATE PREPARE stmt;
    
    SELECT CONCAT('Backup initiated: ', backup_file) as message;
END//
DELIMITER ;
