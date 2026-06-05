<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $fullname = sanitize($_POST['fullname']);
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $ic_number = sanitize($_POST['ic_number']);
    $phone = sanitize($_POST['phone']);
    $emergency_contact = sanitize($_POST['emergency_contact']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = sanitize($_POST['role']);
    
    // Validation
    $errors = [];
    
    // Check required fields
    if (empty($fullname) || empty($username) || empty($email) || empty($ic_number) || empty($password) || empty($role)) {
        $errors[] = 'All required fields must be filled';
    }
    
    // Validate IC Number (12 digits)
    if (!preg_match('/^[0-9]{12}$/', $ic_number)) {
        $errors[] = 'IC Number must be exactly 12 digits';
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    // Check password match
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    // Check password length
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long';
    }
    
    // Validate role (allow Admin, Trainer, and Cadet)
    if (!in_array($role, ['Cadet', 'Trainer', 'Admin'])) {
        $errors[] = 'Invalid role selected';
    }
    
    // Check if username already exists
    $check_username = $conn->query("SELECT UserID FROM users WHERE Username = '$username'");
    if ($check_username->num_rows > 0) {
        $errors[] = 'Username already exists';
    }
    
    // Check if email already exists
    $check_email = $conn->query("SELECT UserID FROM users WHERE Email = '$email'");
    if ($check_email->num_rows > 0) {
        $errors[] = 'Email already exists';
    }
    
    // Check if IC Number already exists
    $check_ic = $conn->query("SELECT UserID FROM users WHERE ICNumber = '$ic_number'");
    if ($check_ic->num_rows > 0) {
        $errors[] = 'IC Number already registered';
    }
    
    // If there are errors, redirect back
    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
        header('Location: ../register.php');
        exit();
    }
    
    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // New registrations start as Inactive (pending admin approval)
    $status = 'Inactive';
    
    // Insert new user
    $query = "INSERT INTO users (Fullname, Username, Email, PasswordHash, ICNumber, Phone, EmergencyContact, Role, Status) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('sssssssss', $fullname, $username, $email, $password_hash, $ic_number, $phone, $emergency_contact, $role, $status);
    
    if ($stmt->execute()) {
        $successMessage = 'Registration successful! Your account is pending admin approval. ';
        
        if ($role === 'Admin') {
            $successMessage .= 'As an Admin registration, your account must be approved by an existing administrator before you can login.';
        } else {
            $successMessage .= 'You will be able to login once an administrator activates your account.';
        }
        
        $_SESSION['success'] = $successMessage;
        
        // Send notification to admin (you can implement email notification here)
        // For now, we'll just log it
        $new_user_id = $stmt->insert_id;
        
        header('Location: ../register.php');
        exit();
    } else {
        $_SESSION['error'] = 'Registration failed. Please try again.';
        header('Location: ../register.php');
        exit();
    }
    
} else {
    header('Location: ../register.php');
    exit();
}
?>