<?php
require_once '../includes/session.php';
require_once '../config/database.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

/**
 * Calculate expiry date based on priority and post date
 * 
 * @param string $priority - 'Normal', 'Urgent', 'Event'
 * @param string $post_date - Y-m-d H:i:s format
 * @param string|null $event_date - Y-m-d format (only for Event priority)
 * @return string - Y-m-d format
 */
function calculateExpiryDate($priority, $post_date, $event_date = null) {
    $date = new DateTime($post_date);
    
    switch ($priority) {
        case 'Urgent':
            // Expire after 3 days from post date
            $date->modify('+3 days');
            break;
        case 'Event':
            // Expire 1 day after event date
            if ($event_date) {
                $date = new DateTime($event_date);
                $date->modify('+1 day');
            } else {
                // Fallback if no event date provided
                $date->modify('+30 days');
            }
            break;
        case 'Normal':
        default:
            // Expire after 15 days from post date
            $date->modify('+15 days');
            break;
    }
    
    return $date->format('Y-m-d');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' && in_array($role, ['Admin', 'Trainer'])) {
        $title = $conn->real_escape_string($_POST['title']);
        $description = $conn->real_escape_string($_POST['description']);
        $priority = $conn->real_escape_string($_POST['priority']);
        $course_id = !empty($_POST['course_id']) ? intval($_POST['course_id']) : 'NULL';
        $event_date = !empty($_POST['event_date']) ? $_POST['event_date'] : null;
        $post_date = date('Y-m-d H:i:s');
        
        // Calculate expiry date based on priority
        $expiry_date = calculateExpiryDate($priority, $post_date, $event_date);
        $expiry_date_sql = "'$expiry_date'";
        $event_date_sql = $event_date ? "'$event_date'" : 'NULL';
        
        $query = "INSERT INTO announcements (Title, Description, Priority, CourseID, EventDate, ExpiryDate, PostedBy, PostDate) 
                  VALUES ('$title', '$description', '$priority', $course_id, $event_date_sql, $expiry_date_sql, $user_id, NOW())";
        
        if ($conn->query($query)) {
            $_SESSION['success'] = "Announcement posted successfully. Expiry date: " . date('d M Y', strtotime($expiry_date));
        } else {
            $_SESSION['error'] = "Error: " . $conn->error;
        }
        
        header("Location: ../admin/announcements.php");
        exit;
    }
    
    if ($action === 'edit' && in_array($role, ['Admin', 'Trainer'])) {
        $announcement_id = intval($_POST['announcement_id']);
        $title = $conn->real_escape_string($_POST['title']);
        $description = $conn->real_escape_string($_POST['description']);
        $priority = $conn->real_escape_string($_POST['priority']);
        $course_id = !empty($_POST['course_id']) ? intval($_POST['course_id']) : 'NULL';
        $event_date = !empty($_POST['event_date']) ? $_POST['event_date'] : null;
        
        // IMPORTANT: Keep the original post date (DO NOT change it)
        // Only recalculate expiry based on original post date
        $orig_query = $conn->query("SELECT PostDate FROM announcements WHERE AnnouncementID = $announcement_id");
        $orig = $orig_query->fetch_assoc();
        $post_date = $orig['PostDate']; // Keep original post date
        
        // Calculate expiry date based on priority (using original post date)
        $expiry_date = calculateExpiryDate($priority, $post_date, $event_date);
        $expiry_date_sql = "'$expiry_date'";
        $event_date_sql = $event_date ? "'$event_date'" : 'NULL';
        
        $query = "UPDATE announcements 
                  SET Title = '$title', Description = '$description', Priority = '$priority', 
                      CourseID = $course_id, EventDate = $event_date_sql, ExpiryDate = $expiry_date_sql
                  WHERE AnnouncementID = $announcement_id";
        
        if ($conn->query($query)) {
            $_SESSION['success'] = "Announcement updated successfully. New expiry date: " . date('d M Y', strtotime($expiry_date));
        } else {
            $_SESSION['error'] = "Error: " . $conn->error;
        }
        
        header("Location: ../admin/announcements.php");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete' && in_array($role, ['Admin', 'Trainer'])) {
    $announcement_id = intval($_GET['announcement_id']);
    
    $query = "DELETE FROM announcements WHERE AnnouncementID = $announcement_id";
    
    if ($conn->query($query)) {
        $_SESSION['success'] = "Announcement deleted successfully";
    } else {
        $_SESSION['error'] = "Error: " . $conn->error;
    }
    
    header("Location: ../admin/announcements.php");
    exit;
}

header("Location: ../dashboard.php");
exit;
?>