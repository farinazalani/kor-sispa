<?php
require_once '../includes/session.php';
require_once '../config/database.php';

// Check authorization
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Trainer'])) {
    echo '<p style="color: #ef4444;">Unauthorized access</p>';
    exit;
}

$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : 'list'; // 'list' or 'details'

if ($course_id <= 0) {
    echo '<p style="color: #64748b;">Invalid course ID</p>';
    exit;
}

// Get course name first
$course_name_query = $conn->prepare("SELECT CourseName FROM courses WHERE CourseID = ?");
$course_name_query->bind_param("i", $course_id);
$course_name_query->execute();
$course_name_result = $course_name_query->get_result();
$course = $course_name_result->fetch_assoc();
$course_name = $course ? $course['CourseName'] : 'Course';

if ($action == 'details') {
    // Untuk paparan DETAILS seorang kadet
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    
    if ($user_id <= 0) {
        echo '<p style="color: #ef4444;">Invalid user ID</p>';
        exit;
    }
    
    $query = "SELECT u.Fullname, u.Email, u.Phone, u.ICNumber, e.EnrolledAt, e.Status 
              FROM enrollments e 
              JOIN users u ON e.CadetID = u.UserID 
              WHERE e.CourseID = ? AND u.UserID = ? AND u.Role = 'Cadet'";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $course_id, $user_id);
    $stmt->execute();
    $cadet = $stmt->get_result()->fetch_assoc();
    
    if ($cadet) {
        $words = explode(" ", $cadet['Fullname']);
        $initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ""));
        $statusColor = $cadet['Status'] == 'Enrolled' ? '#10b981' : '#f59e0b';
        ?>
        <div style="background: var(--bg-tertiary); border-radius: 12px; padding: 20px;">
            <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid var(--border-color);">
                <div style="width: 80px; height: 80px; background: var(--accent-blue); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 2rem; color: white;">
                    <?php echo $initials; ?>
                </div>
                <div>
                    <h3 style="margin: 0 0 5px 0; color: var(--text-primary);"><?php echo htmlspecialchars($cadet['Fullname']); ?></h3>
                    <span style="background: <?php echo $statusColor; ?>; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px;">
                        <?php echo htmlspecialchars($cadet['Status']); ?>
                    </span>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                <div style="padding: 10px; background: var(--bg-secondary); border-radius: 8px;">
                    <strong style="color: var(--text-muted);">📧 Email</strong><br>
                    <span style="color: var(--text-primary);"><?php echo htmlspecialchars($cadet['Email'] ?? '-'); ?></span>
                </div>
                <div style="padding: 10px; background: var(--bg-secondary); border-radius: 8px;">
                    <strong style="color: var(--text-muted);">🆔 IC Number</strong><br>
                    <span style="color: var(--text-primary);"><?php echo htmlspecialchars($cadet['ICNumber'] ?? '-'); ?></span>
                </div>
                <div style="padding: 10px; background: var(--bg-secondary); border-radius: 8px;">
                    <strong style="color: var(--text-muted);">📱 Phone</strong><br>
                    <span style="color: var(--text-primary);"><?php echo htmlspecialchars($cadet['Phone'] ?: 'N/A'); ?></span>
                </div>
                <div style="padding: 10px; background: var(--bg-secondary); border-radius: 8px;">
                    <strong style="color: var(--text-muted);">📅 Enrolled On</strong><br>
                    <span style="color: var(--text-primary);"><?php echo $cadet['EnrolledAt'] ? date('d M Y', strtotime($cadet['EnrolledAt'])) : '-'; ?></span>
                </div>
            </div>
            
            <div style="margin-top: 20px; text-align: center;">
                <button onclick="showCadetList(<?php echo $course_id; ?>)" style="background: var(--accent-blue); color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer;">
                    ← Back to List
                </button>
            </div>
        </div>
        <?php
    } else {
        echo '<p style="color: #ef4444;">Cadet not found</p>';
    }
    $stmt->close();
} else {
    // LIST VIEW - show only names
    $query = "SELECT u.UserID, u.Fullname, e.Status 
              FROM enrollments e 
              JOIN users u ON e.CadetID = u.UserID 
              WHERE e.CourseID = ? AND u.Role = 'Cadet'
              ORDER BY u.Fullname";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cadets = $result->fetch_all(MYSQLI_ASSOC);
    
    echo '<div style="margin-bottom: 15px; padding: 10px; background: var(--bg-tertiary); border-radius: 8px;">
            <strong>📚 Course:</strong> ' . htmlspecialchars($course_name) . '<br>
            <strong>👥 Total Cadets:</strong> ' . count($cadets) . '
          </div>';
    
    if (count($cadets) > 0) {
        echo '<div class="cadet-list-wrapper">';
        echo '<table style="width: 100%; border-collapse: collapse;">';
        echo '<thead>
                <tr style="background: var(--bg-tertiary); border-bottom: 2px solid var(--border-color);">
                    <th style="padding: 12px; text-align: left;">#</th>
                    <th style="padding: 12px; text-align: left;">Cadet Name</th>
                    <th style="padding: 12px; text-align: left;">Status</th>
                    <th style="padding: 12px; text-align: left;">Action</th>
                </tr>
              </thead>
              <tbody>';
        
        $count = 1;
        foreach ($cadets as $cadet) {
            $statusColor = $cadet['Status'] == 'Enrolled' ? '#10b981' : '#f59e0b';
            echo '<tr style="border-bottom: 1px solid var(--border-color);">';
            echo '<td style="padding: 12px;">' . $count++ . '</td>';
            echo '<td style="padding: 12px;"><strong>' . htmlspecialchars($cadet['Fullname']) . '</strong></td>';
            echo '<td style="padding: 12px;">';
            echo '<span style="background: ' . $statusColor . '; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px;">' . $cadet['Status'] . '</span>';
            echo '</td>';
            echo '<td style="padding: 12px;">';
            echo '<button onclick="showCadetDetails(' . $course_id . ', ' . $cadet['UserID'] . ')" style="background: var(--accent-cyan); color: white; border: none; padding: 5px 12px; border-radius: 4px; cursor: pointer;">';
            echo 'View Details →';
            echo '</button>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table></div>';
    } else {
        echo '<div style="text-align:center; padding: 40px; background: var(--bg-tertiary); border-radius: 8px;">
                <p style="color: #64748b;">No cadets are currently enrolled in this course.</p>
              </div>';
    }
    $stmt->close();
}

$conn->close();
?>