<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'inventory_db');

// Create connection for initial setup (without database)
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);

// Check connection
if($conn === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if(mysqli_query($conn, $sql)){
    // Select the database
    mysqli_select_db($conn, DB_NAME);
    
    // Create users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'user') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $sql);

    // Create products table
    $sql = "CREATE TABLE IF NOT EXISTS products (
        id INT PRIMARY KEY AUTO_INCREMENT,
        product_name VARCHAR(255) NOT NULL,
        category ENUM('Headset(PCD)', 'Keyboard', 'Mouse', 'Mouse Mat', 'Speaker','Smart Home','Headset(MCD)','Broadcaster','Systems','Systems Accessories', 'Controller', 'Accessories') NOT NULL,
        serial_number VARCHAR(100) UNIQUE,
        alt_serial_number VARCHAR(100) UNIQUE,
        main_owner VARCHAR(100) NOT NULL,
        prototype_version ENUM('DVT','DVT2','EVT','PVT','MP/Golden Sample') NOT NULL,
        project_name VARCHAR(100) NOT NULL,
        description TEXT,
        status ENUM('available', 'borrowed') DEFAULT 'available',
        remarks TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT chk_serial_required CHECK (serial_number IS NOT NULL OR alt_serial_number IS NOT NULL)
    )";
    mysqli_query($conn, $sql);

    // Create borrows table
    $sql = "CREATE TABLE IF NOT EXISTS borrows (
        id INT PRIMARY KEY AUTO_INCREMENT,
        product_id INT NOT NULL,
        user_id INT NOT NULL,
        borrow_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        return_date TIMESTAMP NULL,
        actual_return_date TIMESTAMP NULL,
        status ENUM('active', 'returned') DEFAULT 'active',
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    mysqli_query($conn, $sql);

    // Check if admin user exists
    $result = mysqli_query($conn, "SELECT id FROM users WHERE username = 'admin'");
    if(mysqli_num_rows($result) == 0){
        // Insert default admin user (password: admin123)
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (name, username, password, role) VALUES
                ('Administrator', 'admin', '$admin_password', 'admin')";
        mysqli_query($conn, $sql);
    }

} else {
    die("ERROR: Could not create database. " . mysqli_error($conn));
}

// Close and reopen connection with database selected
mysqli_close($conn);
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($conn === false){
    die("ERROR: Could not connect to database. " . mysqli_connect_error());
}

// Set MySQL timezone to Singapore Time
mysqli_query($conn, "SET time_zone = '+08:00'");
?> 