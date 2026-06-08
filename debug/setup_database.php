<?php
/**
 * Quick Database Setup for IUC Voting System
 * Run this once to create the database and tables
 */

// Database configuration
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'iuc_voting_system';

try {
    // Connect to MySQL (without database)
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    echo "Database '$dbname' created or already exists.<br>";
    
    // Connect to the database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(191) NOT NULL,
            email VARCHAR(191) UNIQUE NOT NULL,
            password VARCHAR(191) NOT NULL,
            type ENUM('student', 'admin') NOT NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "Users table created or already exists.<br>";
    
    // Create students table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNIQUE NOT NULL,
            student_id VARCHAR(50) UNIQUE NOT NULL,
            name VARCHAR(191) NOT NULL,
            email VARCHAR(191) UNIQUE NOT NULL,
            department VARCHAR(100) NOT NULL,
            year VARCHAR(50) NOT NULL,
            voting_code VARCHAR(50) UNIQUE NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            email_sent BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "Students table created or already exists.<br>";
    
    // Create candidates table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS candidates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(191) NOT NULL,
            position VARCHAR(191) NOT NULL,
            manifesto TEXT,
            campaign_slogan VARCHAR(191),
            image VARCHAR(191),
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "Candidates table created or already exists.<br>";
    
    // Insert a demo admin user
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (name, email, password, type, status) VALUES (?, ?, ?, 'admin', 'approved')");
    $stmt->execute(['Administrator', 'admin@iuc.edu', $adminPassword]);
    echo "Demo admin user created (admin@iuc.edu / admin123).<br>";
    
    echo "<h3>Database setup completed successfully!</h3>";
    echo "<p>You can now:</p>";
    echo "<ul>";
    echo "<li><a href='index.php?page=admin_login'>Login as Admin (admin@iuc.edu / admin123)</a></li>";
    echo "<li><a href='index.php?page=register'>Register as Student</a></li>";
    echo "<li><a href='index.php?page=login'>Login as Student</a></li>";
    echo "<li><a href='index.php'>Go to Home Page</a></li>";
    echo "</ul>";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
