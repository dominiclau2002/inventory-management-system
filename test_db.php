<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting database setup...<br>";

// First, drop the existing database to recreate with new structure
$conn = mysqli_connect('localhost', 'root', '');
if($conn) {
    mysqli_query($conn, "DROP DATABASE IF EXISTS inventory_db");
    echo "Dropped existing database<br>";
    mysqli_close($conn);
}

try {
    require_once 'config/db.php';
    echo "Database setup completed successfully!<br>";
    echo "Connection established to: " . DB_NAME;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
