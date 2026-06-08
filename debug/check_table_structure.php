<?php
/**
 * Check actual database table structure
 */

require_once 'config/config.php';

try {
    if (!$pdo) {
        die("Database connection failed");
    }
    
    echo "<h2>Students Table Structure</h2>";
    
    // Get all columns in students table
    $stmt = $pdo->query("DESCRIBE students");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $availableColumns = [];
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
        
        $availableColumns[] = $column['Field'];
    }
    
    echo "</table>";
    
    echo "<h3>Available Columns:</h3>";
    echo "<ul>";
    foreach ($availableColumns as $column) {
        echo "<li>" . htmlspecialchars($column) . "</li>";
    }
    echo "</ul>";
    
    // Show sample data
    echo "<h3>Sample Data:</h3>";
    $stmt = $pdo->query("SELECT * FROM students LIMIT 3");
    $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($sampleData) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>";
        foreach ($availableColumns as $column) {
            echo "<th>" . htmlspecialchars($column) . "</th>";
        }
        echo "</tr>";
        
        foreach ($sampleData as $row) {
            echo "<tr>";
            foreach ($availableColumns as $column) {
                echo "<td>" . htmlspecialchars($row[$column] ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No data in students table</p>";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
