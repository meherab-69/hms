<?php
// Include the database connection
include 'includes/db_connection.php';

// Test query
try {
    $sql = "SHOW TABLES";
    $stmt = $conn->query($sql);

    echo "Database connection is working!<br>";
    echo "Tables in the database:<br>";

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Tables_in_hms'] . "<br>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>