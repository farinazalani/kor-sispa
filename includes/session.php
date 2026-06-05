<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

// Check user role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../index.php");
        exit();
    }
}

// Redirect based on role
function requireRole($allowedRoles) {
    requireLogin();
    
    if (!in_array($_SESSION['role'], $allowedRoles)) {
        header("Location: ../index.php");
        exit();
    }
}

// Get current user data
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    require_once '../config/database.php';
    
    $query = "SELECT * FROM users WHERE UserID = ?";
    $stmt = executeQuery($query, [$_SESSION['user_id']], 'i');
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// Logout function
function logout() {
    session_destroy();
    header("Location: ../index.php");
    exit();
}
?>