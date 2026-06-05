<?php
require_once '../includes/session.php';
require_once '../config/database.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'enroll' && $role === 'Cadet') {
        $course_id = intval($_POST['course_id'] ?? 0);
        
        // Check if course exists and is active
        $check = $conn->query("SELECT * FROM courses WHERE CourseID = $course_id AND Status = 'Active'");
        if ($check->num_rows === 0) {
            $_SESSION['error'] = "Course not available";
            header("Location: ../cadet/courses.php");
            exit;
        }
        
        // Check if already enrolled
        $enrolled = $conn->query("SELECT * FROM enrollments WHERE CadetID = $user_id AND CourseID = $course_id");
        if ($enrolled->num_rows > 0) {
            $_SESSION['error'] = "You are already enrolled in this course";
            header("Location: ../cadet/courses.php");
            exit;
        }
        
        // Insert enrollment
        $query = "INSERT INTO enrollments (CadetID, CourseID, Status, EnrolledAt) 
                  VALUES ($user_id, $course_id, 'Enrolled', NOW())";
        
        if ($conn->query($query)) {
            $_SESSION['success'] = "Successfully enrolled in the course!";
        } else {
            $_SESSION['error'] = "Error: " . $conn->error;
        }
        
        header("Location: ../cadet/courses.php");
        exit;
    }
    
    if ($action === 'unenroll' && $role === 'Cadet') {
        $course_id = intval($_POST['course_id'] ?? 0);
        
        // Check if enrollment exists
        $check = $conn->query("SELECT * FROM enrollments WHERE CadetID = $user_id AND CourseID = $course_id");
        if ($check->num_rows === 0) {
            $_SESSION['error'] = "Enrollment not found";
            header("Location: ../cadet/courses.php");
            exit;
        }
        
        // Delete enrollment
        $query = "DELETE FROM enrollments WHERE CadetID = $user_id AND CourseID = $course_id";
        
        if ($conn->query($query)) {
            $_SESSION['success'] = "Successfully dropped the course!";
        } else {
            $_SESSION['error'] = "Error: " . $conn->error;
        }
        
        header("Location: ../cadet/courses.php");
        exit;
    }
}

header("Location: ../dashboard.php");
exit;
?>