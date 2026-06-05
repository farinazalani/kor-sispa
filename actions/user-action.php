<?php
session_start();
require_once '../config/database.php';

// Helper function to handle nulls and trimming safely for PHP 8.1+
function safe_trim($value) {
    return trim($value ?? '');
}

// Check if admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../index.php');
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        addUser();
        break;
    case 'edit':
        editUser();
        break;
    case 'delete':
        deleteUser();
        break;
    case 'approve':
        approveUser();
        break;
    default:
        header('Location: ../admin/users.php');
        exit();
}

function addUser() {
    global $conn;
    
    // Using ?? '' ensures we don't pass null to sanitize/trim
    $fullname = sanitize(safe_trim($_POST['fullname']));
    $username = sanitize(safe_trim($_POST['username']));
    $email    = sanitize(safe_trim($_POST['email']));
    $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
    
    // FIXED KEYS: Matching the HTML input names exactly
    $ic_number = sanitize(safe_trim($_POST['ICNumber'] ?? $_POST['ic_number'] ?? ''));
    $phone     = sanitize(safe_trim($_POST['Phone'] ?? $_POST['phone'] ?? ''));
    
    $role   = sanitize($_POST['role'] ?? 'Cadet');
    $status = sanitize($_POST['status'] ?? 'Inactive');
    
    // Validation: Prevent empty IC Number which causes "Duplicate entry ''"
    if (empty($ic_number)) {
        $_SESSION['error'] = 'IC Number is required.';
        header('Location: ../admin/users.php');
        exit();
    }
    
    // Check if username, email, or IC exists
    $stmt_check = $conn->prepare("SELECT UserID FROM users WHERE Username = ? OR Email = ? OR ICNumber = ?");
    $stmt_check->bind_param("sss", $username, $email, $ic_number);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        $_SESSION['error'] = 'Username, Email, or IC Number already exists';
        header('Location: ../admin/users.php');
        exit();
    }
    
    $query = "INSERT INTO users (Fullname, Username, Email, PasswordHash, ICNumber, Phone, Role, Status, CreatedAt) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = executeQuery($query, [$fullname, $username, $email, $password, $ic_number, $phone, $role, $status], 'ssssssss');
    
    if ($stmt) {
        $_SESSION['success'] = 'User added successfully';
    } else {
        $_SESSION['error'] = 'Failed to add user';
    }
    
    header('Location: ../admin/users.php');
    exit();
}

function editUser() {
    global $conn;
    
    $user_id  = (int)($_POST['user_id'] ?? 0);
    $fullname = sanitize(safe_trim($_POST['fullname']));
    $username = sanitize(safe_trim($_POST['username']));
    $email    = sanitize(safe_trim($_POST['email']));
    $ic_number= sanitize(safe_trim($_POST['ICNumber'] ?? $_POST['ic_number'] ?? ''));
    $phone    = sanitize(safe_trim($_POST['Phone'] ?? $_POST['phone'] ?? ''));
    $role     = sanitize($_POST['role'] ?? 'Cadet');
    $status   = sanitize($_POST['status'] ?? 'Active');
    
    // Check if username/email/IC exists for other users
    $stmt_check = $conn->prepare("SELECT UserID FROM users WHERE (Username = ? OR Email = ? OR ICNumber = ?) AND UserID != ?");
    $stmt_check->bind_param("sssi", $username, $email, $ic_number, $user_id);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        $_SESSION['error'] = 'Username, Email, or IC Number already used by another account';
        header('Location: ../admin/users.php');
        exit();
    }
    
    $query = "UPDATE users SET Fullname = ?, Username = ?, Email = ?, ICNumber = ?, 
              Phone = ?, Role = ?, Status = ? WHERE UserID = ?";
    
    $stmt = executeQuery($query, [$fullname, $username, $email, $ic_number, $phone, $role, $status, $user_id], 'sssssssi');
    
    if ($stmt) {
        $_SESSION['success'] = 'User updated successfully';
    } else {
        $_SESSION['error'] = 'Failed to update user';
    }
    
    header('Location: ../admin/users.php');
    exit();
}

function deleteUser() {
    $user_id = (int)($_GET['user_id'] ?? 0);
    
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['error'] = 'You cannot delete your own account';
        header('Location: ../admin/users.php');
        exit();
    }
    
    $query = "DELETE FROM users WHERE UserID = ?";
    $stmt = executeQuery($query, [$user_id], 'i');
    
    if ($stmt) {
        $_SESSION['success'] = 'User deleted/rejected successfully';
    } else {
        $_SESSION['error'] = 'Failed to delete user';
    }
    
    header('Location: ../admin/users.php');
    exit();
}

function approveUser() {
    $user_id = (int)($_GET['user_id'] ?? 0);
    
    $query = "UPDATE users SET Status = 'Active' WHERE UserID = ?";
    $stmt = executeQuery($query, [$user_id], 'i');
    
    if ($stmt) {
        $_SESSION['success'] = 'User approved and activated successfully';
    } else {
        $_SESSION['error'] = 'Failed to approve user';
    }
    
    header('Location: ../admin/users.php');
    exit();
}