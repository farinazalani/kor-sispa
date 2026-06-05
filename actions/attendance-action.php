<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Trainer', 'Admin'])) {
    header('Location: ../index.php');
    exit();
}

// Only keep functions that are NOT in database.php
// Remove sanitize() - it's in database.php
// Remove executeQuery() - it's in database.php

// Helper function to clean time value (fixes the 15:00:00:00 issue)
// This function is unique to this file
function cleanTimeValue($time) {
    // Remove any extra :00 if present
    if (strlen($time) > 8) {
        $time = substr($time, 0, 8);
    }
    // Ensure time is in H:i:s format
    if (substr_count($time, ':') == 1) {
        $time .= ':00';
    }
    return $time;
}

$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');
$role = strtolower($_SESSION['role']);

try {
    switch ($action) {
        case 'bulk_add':
            bulkAddAttendance();
            break;
        case 'add':
            addAttendance();
            break;
        case 'edit':
            editAttendance();
            break;
        case 'delete':
            deleteAttendance();
            break;
        default:
            header("Location: ../$role/attendance.php");
            exit();
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Error: ' . $e->getMessage();
    header("Location: ../$role/attendance.php");
    exit();
}

function bulkAddAttendance() {
    global $conn, $role;
    
    $course_id = (int)$_POST['course_id'];
    $date = sanitize($_POST['date']);
    $time = sanitize($_POST['time']);
    
    // Clean the time value to prevent 15:00:00:00 format
    $time = cleanTimeValue($time);
    $created_at = $date . ' ' . $time;
    
    $recorded_by = $_SESSION['user_id'];
    $attendance_data = $_POST['attendance_data'] ?? [];

    if (empty($attendance_data)) {
        $_SESSION['error'] = "No attendance data provided.";
        header("Location: ../$role/attendance.php");
        exit();
    }

    $count = 0;
    $errors = 0;
    
    foreach ($attendance_data as $user_id => $data) {
        if (isset($data['present'])) {
            $status = sanitize($data['status']);
            $remarks = sanitize($data['remarks']);

            $check = $conn->prepare("SELECT AttendanceID FROM attendance WHERE UserID = ? AND CourseID = ? AND Date = ?");
            $check->bind_param("iis", $user_id, $course_id, $date);
            $check->execute();
            
            if ($check->get_result()->num_rows == 0) {
                $stmt = $conn->prepare("INSERT INTO attendance (UserID, CourseID, Date, Status, Remarks, RecordedBy, CreatedAt) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iisssis", $user_id, $course_id, $date, $status, $remarks, $recorded_by, $created_at);
                
                if ($stmt->execute()) {
                    $count++;
                } else {
                    $errors++;
                }
                $stmt->close();
            }
            $check->close();
        }
    }

    if ($count > 0) {
        $_SESSION['success'] = "Successfully recorded attendance for $count cadet(s).";
        if ($errors > 0) {
            $_SESSION['warning'] = "Failed to record $errors attendance(s).";
        }
    } else {
        $_SESSION['error'] = "No attendance records were saved.";
    }
    
    header("Location: ../$role/attendance.php");
    exit();
}

function addAttendance() {
    global $conn, $role;
    
    $user_id = (int)$_POST['user_id'];
    $course_id = (int)$_POST['course_id'];
    $date = sanitize($_POST['date']);
    $time = sanitize($_POST['time']);
    $status = sanitize($_POST['status']);
    $remarks = sanitize($_POST['remarks']);
    $recorded_by = $_SESSION['user_id'];
    
    $time = cleanTimeValue($time);
    $created_at = $date . ' ' . $time;
    
    $check = $conn->prepare("SELECT AttendanceID FROM attendance WHERE UserID = ? AND CourseID = ? AND Date = ?");
    $check->bind_param("iis", $user_id, $course_id, $date);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        $_SESSION['error'] = 'Attendance already recorded for this date';
        header("Location: ../$role/attendance.php");
        exit();
    }
    $check->close();
    
    $query = "INSERT INTO attendance (UserID, CourseID, Date, Status, Remarks, RecordedBy, CreatedAt) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('iisssis', $user_id, $course_id, $date, $status, $remarks, $recorded_by, $created_at);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Attendance recorded successfully';
    } else {
        $_SESSION['error'] = 'Failed to record attendance: ' . $stmt->error;
    }
    
    $stmt->close();
    header("Location: ../$role/attendance.php");
    exit();
}

function editAttendance() {
    global $conn, $role;
    
    $attendance_id = (int)$_POST['attendance_id'];
    $date = sanitize($_POST['date']);
    $time = sanitize($_POST['time']);
    $status = sanitize($_POST['status']);
    $remarks = sanitize($_POST['remarks']);
    
    $time = cleanTimeValue($time);
    $created_at = $date . ' ' . $time;
    
    $query = "UPDATE attendance SET Date = ?, CreatedAt = ?, Status = ?, Remarks = ? WHERE AttendanceID = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssssi', $date, $created_at, $status, $remarks, $attendance_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Attendance updated successfully';
    } else {
        $_SESSION['error'] = 'Failed to update attendance: ' . $stmt->error;
    }
    
    $stmt->close();
    header("Location: ../$role/attendance.php");
    exit();
}

function deleteAttendance() {
    global $conn, $role;
    
    $attendance_id = isset($_GET['attendance_id']) ? (int)$_GET['attendance_id'] : (isset($_POST['attendance_id']) ? (int)$_POST['attendance_id'] : 0);
    
    if ($attendance_id <= 0) {
        $_SESSION['error'] = 'Invalid attendance record ID';
        header("Location: ../$role/attendance.php");
        exit();
    }
    
    $query = "DELETE FROM attendance WHERE AttendanceID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $attendance_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Attendance deleted successfully';
    } else {
        $_SESSION['error'] = 'Failed to delete attendance: ' . $stmt->error;
    }
    
    $stmt->close();
    header("Location: ../$role/attendance.php");
    exit();
}

?>