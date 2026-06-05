<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $role = sanitize($_POST['role']);
    
    // Validate inputs
    if (empty($username) || empty($password) || empty($role)) {
        $_SESSION['error'] = 'All fields are required';
        header('Location: ../login.php');
        exit();
    }
    
    // Check user credentials
    $query = "SELECT * FROM users WHERE (Username = ? OR Email = ?) AND Role = ? AND Status = 'Active'";
    $stmt = executeQuery($query, [$username, $username, $role], 'sss');
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['PasswordHash'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['UserID'];
            $_SESSION['username'] = $user['Username'];
            $_SESSION['fullname'] = $user['Fullname'];
            $_SESSION['role'] = $user['Role'];
            $_SESSION['email'] = $user['Email'];
            
            // Redirect based on role
            $roleDir = strtolower($user['Role']);
            header("Location: ../$roleDir/dashboard.php");
            exit();
        } else {
            $_SESSION['error'] = 'Invalid credentials';
            header('Location: ../login.php');
            exit();
        }
    } else {
        $_SESSION['error'] = 'Invalid credentials or account inactive';
        header('Location: ../login.php');
        exit();
    }
} else {
    header('Location: ../login.php');
    exit();
}
?>