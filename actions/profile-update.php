<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$role = strtolower($_SESSION['role']);

$fullname = sanitize($_POST['fullname']);
$email = sanitize($_POST['email']);
$phone = sanitize($_POST['phone']);
$emergency_contact = sanitize($_POST['emergency_contact']);

// Update password if provided
if (!empty($_POST['new_password'])) {
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    $query = "UPDATE users SET Fullname = ?, Email = ?, Phone = ?, EmergencyContact = ?, PasswordHash = ? 
              WHERE UserID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('sssssi', $fullname, $email, $phone, $emergency_contact, $new_password, $user_id);
} else {
    $query = "UPDATE users SET Fullname = ?, Email = ?, Phone = ?, EmergencyContact = ? 
              WHERE UserID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssssi', $fullname, $email, $phone, $emergency_contact, $user_id);
}

if ($stmt->execute()) {
    $_SESSION['success'] = 'Profile updated successfully';
    $_SESSION['fullname'] = $fullname;
    $_SESSION['email'] = $email;
} else {
    $_SESSION['error'] = 'Failed to update profile';
}

header("Location: ../$role/profile.php");
exit();
?>