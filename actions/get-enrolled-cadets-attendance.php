<?php
require_once '../includes/session.php';
require_once '../config/database.php';

// Ensure only authorized users can fetch this data
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Trainer'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

header('Content-Type: application/json');

if ($course_id > 0) {
    /**
     * NOTE: I updated the JOIN to use 'CadetID' based on your first query 
     * but kept the logic for the dropdown. 
     * Adjust 'e.CadetID' to 'e.UserID' if your enrollment table uses UserID.
     */
    $query = "SELECT u.UserID, u.Fullname 
              FROM users u 
              JOIN enrollments e ON u.UserID = e.CadetID 
              WHERE e.CourseID = ? AND u.Role = 'Cadet'
              ORDER BY u.Fullname ASC";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cadets = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($cadets);
} else {
    echo json_encode([]);
}
exit;