<?php
require_once 'db.php';

echo "<h2>Testing Database Connection</h2>";

try {
    $db = DB::getInstance()->getConnection();
    echo "✅ Database connected successfully!<br><br>";
    
    // Check tables
    $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo "<strong>Tables:</strong><br>";
    foreach($tables as $t) echo "- $t<br>";
    echo "<br>";
    
    // Check data in app_data
    echo "<strong>Data in app_data table:</strong><br>";
    $stmt = $db->query("SELECT username, LENGTH(data_json) as size FROM app_data");
    $rows = $stmt->fetchAll();
    
    if(empty($rows)){
        echo "❌ No data found!<br>";
    } else {
        foreach($rows as $row) {
            echo "Username: {$row['username']}, Data Size: {$row['size']} bytes<br>";
        }
    }
    
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>