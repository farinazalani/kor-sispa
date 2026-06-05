<?php
require_once '../includes/session.php';
require_once '../config/database.php';

requireRole(['Trainer']);

$trainer_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $course_name = $conn->real_escape_string($_POST['course_name']);
        $description = $conn->real_escape_string($_POST['description']);
        $status = $conn->real_escape_string($_POST['status']);
        
        $query = "INSERT INTO courses (CourseName, Description, TrainerID, Status) 
                  VALUES ('$course_name', '$description', $trainer_id, '$status')";
        
        if ($conn->query($query)) {
            $_SESSION['success'] = "Course created successfully";
        } else {
            $_SESSION['error'] = "Error: " . $conn->error;
        }
    }
    
    if ($action === 'edit') {
        $course_id = intval($_POST['course_id']);
        $course_name = $conn->real_escape_string($_POST['course_name']);
        $description = $conn->real_escape_string($_POST['description']);
        $status = $conn->real_escape_string($_POST['status']);
        
        $check = $conn->query("SELECT CourseID FROM courses WHERE CourseID = $course_id AND TrainerID = $trainer_id");
        if ($check->num_rows === 0) {
            $_SESSION['error'] = "Unauthorized action";
            header("Location: ../trainer/courses.php");
            exit;
        }
        
        $query = "UPDATE courses SET CourseName = '$course_name', Description = '$description', Status = '$status' 
                  WHERE CourseID = $course_id";
        
        if ($conn->query($query)) {
            $_SESSION['success'] = "Course updated successfully";
        } else {
            $_SESSION['error'] = "Error: " . $conn->error;
        }
    }
    
    header("Location: ../trainer/courses.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete') {
    $course_id = intval($_GET['course_id']);
    
    $check = $conn->query("SELECT CourseID FROM courses WHERE CourseID = $course_id AND TrainerID = $trainer_id");
    if ($check->num_rows === 0) {
        $_SESSION['error'] = "Unauthorized action";
        header("Location: ../trainer/courses.php");
        exit;
    }
    
    $query = "DELETE FROM courses WHERE CourseID = $course_id";
    
    if ($conn->query($query)) {
        $_SESSION['success'] = "Course deleted successfully";
    } else {
        $_SESSION['error'] = "Error: " . $conn->error;
    }
    
    header("Location: ../trainer/courses.php");
    exit;
}
?>