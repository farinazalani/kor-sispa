<?php
require_once '../includes/session.php';
require_once '../config/database.php';

requireRole(['Admin']);

header('Content-Type: application/json');

$course_id = intval($_GET['course_id'] ?? 0);

if (!$course_id) {
    echo json_encode(['success' => false, 'message' => 'Course ID required']);
    exit;
}

// Get all schedules for this course
$query = "SELECT 
    cs.*,
    (SELECT COUNT(*) FROM enrollments e WHERE e.CourseID = cs.CourseID AND e.Status = 'Enrolled') as EnrolledCount
FROM course_schedules cs
WHERE cs.CourseID = $course_id
ORDER BY cs.ScheduleDate ASC, cs.StartTime ASC";

$result = $conn->query($query);
$schedules = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode(['success' => true, 'schedules' => $schedules]);
?>