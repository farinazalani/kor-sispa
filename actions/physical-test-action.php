<?php
session_start();
require_once '../config/database.php';

// Ensure sanitize() function exists
if (!function_exists('sanitize')) {
    function sanitize($data) {
        return htmlspecialchars(strip_tags(trim($data)));
    }
}

// Access Control
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Trainer', 'Admin'])) {
    header('Location: ../index.php');
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');
// Keep role lowercase for redirection paths
$role = strtolower($_SESSION['role']);

switch ($action) {
    case 'add':
        addPhysicalPerformance();
        break;
    case 'edit':
        editPhysicalPerformance();
        break;
    case 'delete':
        deletePhysicalPerformance();
        break;
    default:
        header("Location: ../$role/physical-tests.php");
        exit();
}

function addPhysicalPerformance() {
    global $conn, $role;
    
    $user_id     = (int)$_POST['user_id'];
    $test_date   = sanitize($_POST['test_date']);
    $push_ups    = (int)$_POST['push_ups'];
    $sit_ups     = (int)$_POST['sit_ups'];
    $pull_ups    = (int)$_POST['pull_ups'];
    $running     = (float)$_POST['running_24km'];
    $remark      = sanitize($_POST['remark']);
    $recorded_by = $_SESSION['user_id'];
    
    $query = "INSERT INTO physical_performance (UserID, TestDate, PushUps, SitUps, PullUps, Running24km, Remark, RecordedBy) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    // Corrected bind_param: i (UserID), s (Date), i (Push), i (Sit), i (Pull), d (Run), s (Remark), i (RecBy)
    $stmt->bind_param('isiiidsi', $user_id, $test_date, $push_ups, $sit_ups, $pull_ups, $running, $remark, $recorded_by);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Physical test record added successfully.';
    } else {
        $_SESSION['error'] = 'Failed to add record: ' . $conn->error;
    }
    
    header("Location: ../$role/physical-tests.php");
    exit();
}

function editPhysicalPerformance() {
    global $conn, $role;
    
    $performance_id = (int)$_POST['performance_id'];
    $test_date      = sanitize($_POST['test_date']);
    $push_ups       = (int)$_POST['push_ups'];
    $sit_ups        = (int)$_POST['sit_ups'];
    $pull_ups       = (int)$_POST['pull_ups'];
    $running        = (float)$_POST['running_24km'];
    $remark         = sanitize($_POST['remark']);
    
    $query = "UPDATE physical_performance SET 
                TestDate = ?, 
                PushUps = ?, 
                SitUps = ?, 
                PullUps = ?, 
                Running24km = ?, 
                Remark = ? 
              WHERE PerformanceID = ?";
    
    $stmt = $conn->prepare($query);
    // Type mapping: s (Date), i (Push), i (Sit), i (Pull), d (Run), s (Remark), i (ID)
    $stmt->bind_param('siiidsi', $test_date, $push_ups, $sit_ups, $pull_ups, $running, $remark, $performance_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Physical test record updated successfully.';
    } else {
        $_SESSION['error'] = 'Failed to update record.';
    }
    
    header("Location: ../$role/physical-tests.php");
    exit();
}

function deletePhysicalPerformance() {
    global $conn, $role;
    
    $performance_id = (int)$_GET['performance_id'];
    $user_id = $_SESSION['user_id'];
    
    // Security check: Admins can delete anything, Trainers can only delete their own records
    if ($_SESSION['role'] === 'Admin') {
        $query = "DELETE FROM physical_performance WHERE PerformanceID = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $performance_id);
    } else {
        $query = "DELETE FROM physical_performance WHERE PerformanceID = ? AND RecordedBy = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ii', $performance_id, $user_id);
    }
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $_SESSION['success'] = 'Record deleted successfully.';
        } else {
            $_SESSION['error'] = 'Record not found or unauthorized.';
        }
    } else {
        $_SESSION['error'] = 'Failed to delete record.';
    }
    
    header("Location: ../$role/physical-tests.php");
    exit();
}