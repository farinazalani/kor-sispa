<?php
require_once '../includes/session.php';
require_once '../config/database.php';

requireRole(['Trainer']);

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid action'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'get_schedules') {
        $course_id = intval($_GET['course_id'] ?? 0);
        
        $check = $conn->query("SELECT CourseID FROM courses WHERE CourseID = $course_id AND TrainerID = {$_SESSION['user_id']}");
        if ($check->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $query = "SELECT * FROM course_schedules WHERE CourseID = $course_id ORDER BY ScheduleDate ASC, StartTime ASC";
        $result = $conn->query($query);
        $schedules = $result->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode(['success' => true, 'schedules' => $schedules]);
        exit;
    }
    
    if ($action === 'get_schedule') {
        $schedule_id = intval($_GET['schedule_id'] ?? 0);
        
        $query = "SELECT cs.* FROM course_schedules cs 
                  JOIN courses c ON cs.CourseID = c.CourseID 
                  WHERE cs.ScheduleID = $schedule_id AND c.TrainerID = {$_SESSION['user_id']}";
        $result = $conn->query($query);
        $schedule = $result->fetch_assoc();
        
        if ($schedule) {
            echo json_encode(['success' => true, 'schedule' => $schedule]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Schedule not found']);
        }
        exit;
    }
    
    if ($action === 'get_occupied_start_times') {
        $date = $conn->real_escape_string($_GET['date'] ?? '');
        $exclude_course_id = intval($_GET['exclude_course_id'] ?? 0);
        $exclude_schedule_id = intval($_GET['exclude_schedule_id'] ?? 0);
        
        if (!$date) {
            echo json_encode(['success' => false, 'message' => 'Date required']);
            exit;
        }
        
        $query = "SELECT DISTINCT cs.StartTime
                  FROM course_schedules cs
                  WHERE cs.ScheduleDate = '$date'
                  AND cs.Status != 'Completed'
                  AND cs.CourseID != $exclude_course_id
                  AND cs.ScheduleID != $exclude_schedule_id";
        
        $result = $conn->query($query);
        $occupiedStartTimes = [];
        while ($row = $result->fetch_assoc()) {
            $occupiedStartTimes[] = $row['StartTime'];
        }
        
        echo json_encode(['success' => true, 'occupiedStartTimes' => $occupiedStartTimes]);
        exit;
    }
    
    if ($action === 'check_conflict') {
        $course_id = intval($_GET['course_id'] ?? 0);
        $schedule_date = $conn->real_escape_string($_GET['schedule_date'] ?? '');
        $start_time = $conn->real_escape_string($_GET['start_time'] ?? '');
        $end_time = $conn->real_escape_string($_GET['end_time'] ?? '');
        $exclude_id = intval($_GET['exclude_id'] ?? 0);
        
        $check = $conn->query("SELECT CourseID FROM courses WHERE CourseID = $course_id AND TrainerID = {$_SESSION['user_id']}");
        if ($check->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $query = "SELECT cs.ScheduleID, c.CourseName 
                  FROM course_schedules cs 
                  JOIN courses c ON cs.CourseID = c.CourseID
                  WHERE cs.ScheduleDate = '$schedule_date'
                  AND cs.Status != 'Completed'
                  AND cs.ScheduleID != $exclude_id
                  AND cs.StartTime < '$end_time' 
                  AND cs.EndTime > '$start_time'";
        
        $result = $conn->query($query);
        $hasConflict = $result->num_rows > 0;
        
        echo json_encode(['success' => true, 'hasConflict' => $hasConflict]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_schedule') {
        $course_id = intval($_POST['course_id'] ?? 0);
        $schedule_date = $conn->real_escape_string($_POST['schedule_date'] ?? '');
        $start_time = $conn->real_escape_string($_POST['start_time'] ?? '');
        $end_time = $conn->real_escape_string($_POST['end_time'] ?? '');
        $location = $conn->real_escape_string($_POST['location'] ?? '');
        $status = $conn->real_escape_string($_POST['status'] ?? 'Active');
        
        $check = $conn->query("SELECT CourseID FROM courses WHERE CourseID = $course_id AND TrainerID = {$_SESSION['user_id']}");
        if ($check->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        if ($start_time >= $end_time) {
            echo json_encode(['success' => false, 'message' => 'End time must be after start time']);
            exit;
        }
        
        $end_hour = intval(substr($end_time, 0, 2));
        if ($end_hour >= 24) {
            echo json_encode(['success' => false, 'message' => 'End time exceeds 23:00']);
            exit;
        }
        
        $conflict_check = $conn->query("SELECT cs.ScheduleID FROM course_schedules cs 
                                        WHERE cs.ScheduleDate = '$schedule_date'
                                        AND cs.Status != 'Completed'
                                        AND cs.StartTime < '$end_time' 
                                        AND cs.EndTime > '$start_time'");
        if ($conflict_check->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Time conflict with existing schedule']);
            exit;
        }
        
        $query = "INSERT INTO course_schedules (CourseID, ScheduleDate, StartTime, EndTime, Location, Status) 
                  VALUES ($course_id, '$schedule_date', '$start_time', '$end_time', '$location', '$status')";
        
        if ($conn->query($query)) {
            $response = ['success' => true, 'message' => 'Schedule added successfully'];
        } else {
            $response = ['success' => false, 'message' => 'Database error: ' . $conn->error];
        }
    }
    
    if ($action === 'update_schedule') {
        $schedule_id = intval($_POST['schedule_id'] ?? 0);
        $schedule_date = $conn->real_escape_string($_POST['schedule_date'] ?? '');
        $start_time = $conn->real_escape_string($_POST['start_time'] ?? '');
        $end_time = $conn->real_escape_string($_POST['end_time'] ?? '');
        $location = $conn->real_escape_string($_POST['location'] ?? '');
        $status = $conn->real_escape_string($_POST['status'] ?? 'Active');
        
        $check = $conn->query("SELECT cs.ScheduleID FROM course_schedules cs 
                               JOIN courses c ON cs.CourseID = c.CourseID 
                               WHERE cs.ScheduleID = $schedule_id AND c.TrainerID = {$_SESSION['user_id']}");
        if ($check->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        if ($start_time >= $end_time) {
            echo json_encode(['success' => false, 'message' => 'End time must be after start time']);
            exit;
        }
        
        $end_hour = intval(substr($end_time, 0, 2));
        if ($end_hour >= 24) {
            echo json_encode(['success' => false, 'message' => 'End time exceeds 23:00']);
            exit;
        }
        
        $conflict_check = $conn->query("SELECT cs.ScheduleID FROM course_schedules cs 
                                        WHERE cs.ScheduleDate = '$schedule_date'
                                        AND cs.Status != 'Completed'
                                        AND cs.ScheduleID != $schedule_id
                                        AND cs.StartTime < '$end_time' 
                                        AND cs.EndTime > '$start_time'");
        if ($conflict_check->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Time conflict with existing schedule']);
            exit;
        }
        
        $query = "UPDATE course_schedules 
                  SET ScheduleDate = '$schedule_date', StartTime = '$start_time', EndTime = '$end_time', 
                      Location = '$location', Status = '$status' 
                  WHERE ScheduleID = $schedule_id";
        
        if ($conn->query($query)) {
            $response = ['success' => true, 'message' => 'Schedule updated successfully'];
        } else {
            $response = ['success' => false, 'message' => 'Database error: ' . $conn->error];
        }
    }
    
    if ($action === 'delete_schedule') {
        $schedule_id = intval($_POST['schedule_id'] ?? 0);
        
        $check = $conn->query("SELECT cs.ScheduleID FROM course_schedules cs 
                               JOIN courses c ON cs.CourseID = c.CourseID 
                               WHERE cs.ScheduleID = $schedule_id AND c.TrainerID = {$_SESSION['user_id']}");
        if ($check->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $query = "DELETE FROM course_schedules WHERE ScheduleID = $schedule_id";
        
        if ($conn->query($query)) {
            $response = ['success' => true, 'message' => 'Schedule deleted successfully'];
        } else {
            $response = ['success' => false, 'message' => 'Database error: ' . $conn->error];
        }
    }
    
    echo json_encode($response);
    exit;
}

echo json_encode($response);
?>