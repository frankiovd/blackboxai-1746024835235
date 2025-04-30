<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gym_management');

// Create database connection
function getDBConnection() {
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PASS,
            array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        );
        return $conn;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Initialize database if it doesn't exist
function initializeDatabase() {
    try {
        // Create connection without database selected
        $conn = new PDO(
            "mysql:host=" . DB_HOST,
            DB_USER,
            DB_PASS,
            array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        );

        // Create database if it doesn't exist
        $conn->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
        
        // Select the database
        $conn->exec("USE " . DB_NAME);
        
        // Read and execute schema.sql
        $sql = file_get_contents(__DIR__ . '/../schema.sql');
        $conn->exec($sql);
        
        return true;
    } catch(PDOException $e) {
        die("Database initialization failed: " . $e->getMessage());
    }
}
