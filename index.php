<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Initialize database if needed
initializeDatabase();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: php/auth/login.php");
    exit();
}

// Redirect based on user type
if ($_SESSION['user_type'] === 'trainer') {
    header("Location: php/trainers/dashboard.php");
} else {
    header("Location: php/clients/dashboard.php");
}
exit();
