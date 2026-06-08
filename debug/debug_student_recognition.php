<?php
/**
 * Debug student recognition issue - check which students are registered
 */

require_once 'config/config.php';

echo "<h2>Debug Student Recognition Issue</h2>";

if (!$pdo) {
    echo "<p style='color: red;'>Database not available</p>";
    exit;
}

// Show all registered students
echo "<h3>All Registered Students:</h3>";
$stmt = $pdo->query("SELECT u.id, u.name, u.email, u.type, u.created_at, 
                    s.student_id, s.department, s.level 
                    FROM users u 
                    LEFT JOIN students s ON u.id = s.user_id 
                    WHERE u.type = 'student' 
                    ORDER BY u.created_at DESC");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($students) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f3f4f6;'>
            <th style='padding: 0.5rem;'>ID</th>
            <th style='padding: 0.5rem;'>Name</th>
            <th style='padding: 0.5rem;'>Email</th>
            <th style='padding: 0.5rem;'>Student ID</th>
            <th style='padding: 0.5rem;'>Department</th>
            <th style='padding: 0.5rem;'>Level</th>
            <th style='padding: 0.5rem;'>Registered</th>
          </tr>";
    
    foreach ($students as $student) {
        echo "<tr>";
        echo "<td style='padding: 0.5rem;'>{$student['id']}</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($student['name']) . "</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($student['email']) . "</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($student['student_id'] ?? 'N/A') . "</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($student['department'] ?? 'N/A') . "</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($student['level'] ?? 'N/A') . "</td>";
        echo "<td style='padding: 0.5rem;'>{$student['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No students found</p>";
}

// Show recent notifications
echo "<h3>Recent Student Registration Notifications:</h3>";
$stmt = $pdo->query("SELECT n.*, u.name as student_name, u.email as student_email 
                    FROM notifications n 
                    LEFT JOIN users u ON n.related_student_id = u.id 
                    WHERE n.type = 'student_registration' 
                    ORDER BY n.created_at DESC LIMIT 10");
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($notifications) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f3f4f6;'>
            <th style='padding: 0.5rem;'>ID</th>
            <th style='padding: 0.5rem;'>Title</th>
            <th style='padding: 0.5rem;'>Related Student</th>
            <th style='padding: 0.5rem;'>Student Email</th>
            <th style='padding: 0.5rem;'>Status</th>
            <th style='padding: 0.5rem;'>Created</th>
          </tr>";
    
    foreach ($notifications as $notification) {
        echo "<tr>";
        echo "<td style='padding: 0.5rem;'>{$notification['id']}</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($notification['title']) . "</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($notification['student_name'] ?? 'N/A') . "</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($notification['student_email'] ?? 'N/A') . "</td>";
        echo "<td style='padding: 0.5rem;'>{$notification['status']}</td>";
        echo "<td style='padding: 0.5rem;'>{$notification['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No student registration notifications found</p>";
}

// Show voting codes
echo "<h3>Existing Voting Codes:</h3>";
$stmt = $pdo->query("SELECT vc.*, u.name as student_name, u.email as student_email 
                    FROM voting_codes vc 
                    JOIN users u ON vc.student_id = u.id 
                    ORDER BY vc.created_at DESC");
$codes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($codes) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f3f4f6;'>
            <th style='padding: 0.5rem;'>ID</th>
            <th style='padding: 0.5rem;'>Student</th>
            <th style='padding: 0.5rem;'>Email</th>
            <th style='padding: 0.5rem;'>Voting Code</th>
            <th style='padding: 0.5rem;'>Status</th>
            <th style='padding: 0.5rem;'>Created</th>
          </tr>";
    
    foreach ($codes as $code) {
        echo "<tr>";
        echo "<td style='padding: 0.5rem;'>{$code['id']}</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($code['student_name']) . "</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($code['student_email']) . "</td>";
        echo "<td style='padding: 0.5rem; font-family: monospace;'>{$code['voting_code']}</td>";
        echo "<td style='padding: 0.5rem;'>{$code['status']}</td>";
        echo "<td style='padding: 0.5rem;'>{$code['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No voting codes found</p>";
}

// Check for the specific student "clair"
echo "<h3>Search for Student 'clair':</h3>";
$stmt = $pdo->prepare("SELECT u.*, s.student_id FROM users u LEFT JOIN students s ON u.id = s.user_id WHERE u.name LIKE ? OR u.email LIKE ?");
$stmt->execute(['%clair%', '%clair%']);
$clairStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($clairStudents) > 0) {
    echo "<p style='color: green;'>Found " . count($clairStudents) . " student(s) matching 'clair':</p>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f3f4f6;'>
            <th style='padding: 0.5rem;'>ID</th>
            <th style='padding: 0.5rem;'>Name</th>
            <th style='padding: 0.5rem;'>Email</th>
            <th style='padding: 0.5rem;'>Student ID</th>
          </tr>";
    
    foreach ($clairStudents as $student) {
        echo "<tr>";
        echo "<td style='padding: 0.5rem;'>{$student['id']}</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($student['name']) . "</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($student['email']) . "</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($student['student_id'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No student found matching 'clair'</p>";
}

echo "<hr>";
echo "<p><a href='index.php?page=admin'>Admin Dashboard</a></p>";
echo "<p><a href='index.php?page=register'>Register New Student</a></p>";
echo "<p><a href='index.php?page=voter_registration'>Voter Registration</a></p>";
?>
