<?php
require_once '../includes/session.php';
require_once '../config/database.php';

requireRole(['Admin']);

// Handle status update via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    header('Content-Type: application/json');
    $course_id = intval($_POST['course_id'] ?? 0);
    $new_status = $_POST['status'] ?? '';
    
    if ($course_id && in_array($new_status, ['Active', 'Inactive', 'Completed'])) {
        $update = $conn->prepare("UPDATE courses SET Status = ? WHERE CourseID = ?");
        $update->bind_param("si", $new_status, $course_id);
        if ($update->execute()) {
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }
}

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

// Build WHERE clause based on filters using course_schedules
$where_clauses = [];
$params = [];
$types = "";

// Date range filter using course_schedules
if (!empty($start_date) && !empty($end_date)) {
    $where_clauses[] = "EXISTS (SELECT 1 FROM course_schedules cs WHERE cs.CourseID = c.CourseID AND cs.ScheduleDate BETWEEN ? AND ?)";
    $params[] = $start_date;
    $params[] = $end_date;
    $types .= "ss";
} elseif (!empty($start_date)) {
    $where_clauses[] = "EXISTS (SELECT 1 FROM course_schedules cs WHERE cs.CourseID = c.CourseID AND cs.ScheduleDate >= ?)";
    $params[] = $start_date;
    $types .= "s";
} elseif (!empty($end_date)) {
    $where_clauses[] = "EXISTS (SELECT 1 FROM course_schedules cs WHERE cs.CourseID = c.CourseID AND cs.ScheduleDate <= ?)";
    $params[] = $end_date;
    $types .= "s";
}

// Search functionality
if (!empty($search)) {
    $where_clauses[] = "(c.CourseName LIKE ? OR u.Fullname LIKE ? OR c.Status LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Get all courses with trainer info and schedule count
$sql = "SELECT c.*, u.Fullname as TrainerName,
    (SELECT COUNT(*) FROM enrollments e WHERE e.CourseID = c.CourseID AND e.Status = 'Enrolled') as EnrolledCount,
    (SELECT COUNT(*) FROM course_schedules cs WHERE cs.CourseID = c.CourseID AND cs.Status = 'Active') as ScheduleCount
FROM courses c 
LEFT JOIN users u ON c.TrainerID = u.UserID 
$where_sql
ORDER BY c.CreatedAt DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get all schedules for quick reference
$all_schedules = [];
$sched_result = $conn->query("SELECT cs.*, c.CourseName, u.Fullname as TrainerName 
    FROM course_schedules cs 
    JOIN courses c ON cs.CourseID = c.CourseID 
    LEFT JOIN users u ON c.TrainerID = u.UserID 
    ORDER BY cs.ScheduleDate DESC, cs.StartTime ASC");
if ($sched_result) {
    $all_schedules = $sched_result->fetch_all(MYSQLI_ASSOC);
}

// Group schedules by course
$schedules_by_course = [];
foreach ($all_schedules as $schedule) {
    $course_id = $schedule['CourseID'];
    if (!isset($schedules_by_course[$course_id])) {
        $schedules_by_course[$course_id] = [];
    }
    $schedules_by_course[$course_id][] = $schedule;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Management - Kor Sispa</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* ============================================
        BASE VARIABLES (Light Mode Default)
        ============================================ */
        :root {
            --bg-primary: #f0f2f5;
            --bg-secondary: #ffffff;
            --bg-tertiary: #f8f9fa;
            --text-primary: #1e293b;
            --text-secondary: #475569;
            --text-muted: #94a3b8;
            --border-color: #e2e8f0;
            --accent-blue: #3b82f6;
            --accent-green: #10b981;
            --accent-red: #ef4444;
            --accent-gray: #6b7280;
            --accent-cyan: #06b6d4;
            --accent-orange: #f59e0b;
            --hover-bg: #f1f5f9;
        }

        /* ============================================
        DARK MODE - Synchronize dengan sidebar
        ============================================ */
        body.dark {
            --bg-primary: #1e293b;
            --bg-secondary: #1e293b;
            --bg-tertiary: #1a1f3a;
            --text-primary: #ffffff;
            --text-secondary: #a0a8c0;
            --text-muted: #6c7293;
            --border-color: #2a2f4a;
            --accent-blue: #3b82f6;
            --accent-green: #10b981;
            --accent-red: #ef4444;
            --accent-gray: #6b7280;
            --accent-cyan: #06b6d4;
            --accent-orange: #f59e0b;
            --hover-bg: #1f2542;
        }

        body {
            background: var(--bg-primary);
            color: var(--text-primary);
            transition: all 0.3s ease;
        }

        .filter-bar {
            background: var(--bg-secondary);
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
            justify-content: space-between;
            border: 1px solid var(--border-color);
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .filter-group label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
        }
        
        .filter-group input, .filter-group select {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 14px;
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }
        
        .filter-group input:focus, .filter-group select:focus {
            outline: none;
            border-color: var(--accent-blue);
        }
        
        .filter-group button {
            padding: 8px 16px;
            background: var(--accent-blue);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .filter-group button:hover {
            background: #2563eb;
        }
        
        .date-range {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }
        
        .date-range .filter-group {
            flex: 1;
        }
        
        .search-box {
            display: flex;
            gap: 10px;
        }
        
        .search-box input {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            width: 250px;
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }
        
        .search-box input::placeholder {
            color: var(--text-muted);
        }
        
        .search-box button {
            padding: 8px 16px;
            background: var(--accent-green);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .search-box button:hover {
            background: #059669;
        }
        
        .clear-filters {
            background: var(--accent-gray) !important;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .clear-filters:hover {
            background: #4b5563 !important;
        }
        
        .result-info {
            margin-bottom: 15px;
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        .table-container {
            background: var(--bg-secondary);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
            border: 1px solid var(--border-color);
            overflow-x: auto;
        }
        
        .table-container h3 {
            color: var(--text-primary);
            margin-bottom: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        table th {
            background: var(--bg-tertiary);
            font-weight: 600;
            color: var(--text-primary);
        }
        
        table tr:hover {
            background: var(--hover-bg);
        }
        
        .badge {
            background: var(--accent-blue);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .badge-enrolled {
            background: var(--accent-green);
        }
        
        .status-select {
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }
        
        .status-select.status-active {
            background: var(--accent-green);
            color: white;
        }
        
        .status-select.status-inactive {
            background: var(--accent-red);
            color: white;
        }
        
        .status-select.status-completed {
            background: var(--accent-gray);
            color: white;
        }
        
        .status-select:hover {
            opacity: 0.85;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-info {
            background: var(--accent-cyan);
            color: white;
        }
        
        .btn-info:hover {
            background: #0891b2;
        }
        
        .btn-schedule {
            background: var(--accent-orange);
            color: white;
        }
        
        .btn-schedule:hover {
            background: #d97706;
        }
        
        .schedule-item {
            display: inline-block;
            background: var(--accent-blue);
            color: white;
            padding: 2px 6px;
            border-radius: 12px;
            font-size: 0.6rem;
            margin: 1px;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
        }
        
        .modal-content {
            background-color: var(--bg-secondary);
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 60%;
            max-height: 80vh;
            overflow-y: auto;
            border: 1px solid var(--border-color);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .close-btn {
            cursor: pointer;
            font-size: 24px;
            font-weight: bold;
            color: var(--text-secondary);
        }
        
        .close-btn:hover {
            color: var(--text-primary);
        }
        
        .cadet-list-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .cadet-list-table th,
        .cadet-list-table td {
            padding: 10px;
            border-bottom: 1px solid var(--border-color);
            text-align: left;
        }
        
        .cadet-list-table th {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }
        
        .cadet-list-table td {
            color: var(--text-secondary);
        }
        
        #modalTitle {
            color: var(--text-primary);
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        #modalTitle span.course-highlight {
            color: var(--accent-blue);
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid var(--accent-green);
            color: #34d399;
        }
        
        .alert-danger {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid var(--accent-red);
            color: #f87171;
        }
        
        .top-bar {
            background: var(--bg-secondary);
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid var(--border-color);
        }
        
        .top-bar h2 {
            color: var(--text-primary);
        }
        
        .user-info {
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .btn-logout {
            background: var(--accent-red);
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            margin-left: 10px;
        }
        
        .btn-logout:hover {
            background: #dc2626;
        }
        
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--accent-green);
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            z-index: 1100;
            animation: fadeInOut 3s ease;
        }
        
        .toast.error {
            background: var(--accent-red);
        }
        
        @keyframes fadeInOut {
            0% { opacity: 0; transform: translateX(100%); }
            10% { opacity: 1; transform: translateX(0); }
            90% { opacity: 1; transform: translateX(0); }
            100% { opacity: 0; transform: translateX(100%); }
        }
        
        @media (max-width: 768px) {
            .filter-bar {
                flex-direction: column;
            }
            .date-range {
                flex-direction: column;
                width: 100%;
            }
            .date-range .filter-group {
                width: 100%;
            }
            .search-box {
                width: 100%;
            }
            .search-box input {
                flex: 1;
            }
            .modal-content {
                width: 95%;
                margin: 10% auto;
            }
            table {
                font-size: 14px;
            }
            table th, table td {
                padding: 8px;
            }
        }
        
        .empty-state {
            text-align: center;
            padding: 50px;
            background: var(--bg-tertiary);
            border-radius: 8px;
            color: var(--text-secondary);
        }

        .cadet-list-wrapper {
            overflow-x: auto;
            margin-top: 10px;
        }

        .cadet-list-wrapper table {
            width: 100%;
            border-collapse: collapse;
        }

        .cadet-list-wrapper th {
            text-align: left;
            font-weight: 600;
        }

        .cadet-list-wrapper td {
            vertical-align: middle;
        }

        .cadet-list-wrapper tbody tr:hover {
            background: var(--hover-bg);
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="top-bar">
                <h2>📚 Course Management</h2>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
                    <a href="../actions/logout.php" class="btn-logout">Logout</a>
                </div>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Filter Bar -->
            <div class="filter-bar">
                <div class="date-range">
                    <div class="filter-group">
                        <label>Start Date</label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                    </div>
                    <div class="filter-group">
                        <label>End Date</label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                    </div>
                    <div class="filter-group">
                        <label>&nbsp;</label>
                        <button onclick="applyDateFilter()">Apply</button>
                    </div>
                </div>
                
                <div class="search-box">
                    <form method="GET" style="display: flex; gap: 10px;" id="searchForm">
                        <input type="text" name="search" placeholder="Search course, trainer..." value="<?php echo htmlspecialchars($search); ?>">
                        <?php if (!empty($start_date)): ?>
                            <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                        <?php endif; ?>
                        <?php if (!empty($end_date)): ?>
                            <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                        <?php endif; ?>
                        <button type="submit">Search</button>
                    </form>
                </div>
            </div>
            
            <!-- Clear Filters Button -->
            <?php if (!empty($start_date) || !empty($end_date) || !empty($search)): ?>
                <div style="margin-bottom: 15px;">
                    <a href="?" class="clear-filters" style="padding: 6px 12px; background: #6c757d; color: white; border-radius: 4px; text-decoration: none; font-size: 13px;">Clear All Filters</a>
                </div>
            <?php endif; ?>
            
            <!-- Result Info -->
            <div class="result-info">
                📊 Showing <?php echo count($courses); ?> course(s)
                <?php if (!empty($start_date) && !empty($end_date)): ?>
                    (schedules from <?php echo date('d M Y', strtotime($start_date)); ?> - <?php echo date('d M Y', strtotime($end_date)); ?>)
                <?php elseif (!empty($start_date)): ?>
                    (schedules from <?php echo date('d M Y', strtotime($start_date)); ?> onwards)
                <?php elseif (!empty($end_date)): ?>
                    (schedules up to <?php echo date('d M Y', strtotime($end_date)); ?>)
                <?php endif; ?>
                <?php if (!empty($search)): ?>
                    matching "<strong><?php echo htmlspecialchars($search); ?></strong>"
                <?php endif; ?>
            </div>
            
            <div class="table-container">
                <h3>📖 All Courses</h3>
                
                <?php if (empty($courses)): ?>
                    <div class="empty-state">
                        <p>No courses found matching your criteria.</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Course Name</th>
                                    <th>Trainer</th>
                                    <th>Schedules</th>
                                    <th>Enrolled</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courses as $course): 
                                    $course_schedules = $schedules_by_course[$course['CourseID']] ?? [];
                                ?>
                                    <tr>
                                        <td style="min-width: 120px;">
                                            <strong><?php echo htmlspecialchars($course['CourseName']); ?></strong>
                                            <?php if ($course['Description']): ?>
                                                <br>
                                                <small style="color: var(--text-muted); font-size: 11px;"><?php echo htmlspecialchars(substr($course['Description'], 0, 50)) . (strlen($course['Description']) > 50 ? '...' : ''); ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <td style="min-width: 120px;"><?php echo htmlspecialchars($course['TrainerName'] ?? 'Not Assigned'); ?></div>
                                        <td>
                                            <?php if (count($course_schedules) > 0): ?>
                                                <div style="display: flex; flex-wrap: wrap; gap: 3px;">
                                                    <?php foreach (array_slice($course_schedules, 0, 3) as $sch): ?>
                                                        <span class="schedule-item">📅 <?php echo date('d/m', strtotime($sch['ScheduleDate'])); ?></span>
                                                    <?php endforeach; ?>
                                                    <?php if (count($course_schedules) > 3): ?>
                                                        <span class="schedule-item">+<?php echo count($course_schedules) - 3; ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <small style="color: var(--text-muted); font-size: 15px;"><?php echo count($course_schedules); ?> schedule(s)</small>
                                            <?php else: ?>
                                                <span style="color: var(--text-muted); font-size: 15px;">No schedules</span>
                                            <?php endif; ?>
                                        </div>
                                        <td><span class="badge badge-enrolled">👥 <?php echo $course['EnrolledCount']; ?></span></div>
                                        <td>
                                            <select class="status-select <?php 
                                                echo $course['Status'] == 'Active' ? 'status-active' : 
                                                    ($course['Status'] == 'Completed' ? 'status-completed' : 'status-inactive'); 
                                            ?>" 
                                                onchange="updateCourseStatus(<?php echo $course['CourseID']; ?>, this.value)">
                                                <option value="Active" <?php echo $course['Status'] == 'Active' ? 'selected' : ''; ?>>Active</option>
                                                <option value="Inactive" <?php echo $course['Status'] == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                <option value="Completed" <?php echo $course['Status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                            </select>
                                        </div>
                                        <td style="white-space: nowrap;">
                                            <button class="btn-sm btn-schedule" onclick="viewSchedules(<?php echo $course['CourseID']; ?>, '<?php echo addslashes($course['CourseName']); ?>')">📅 Schedules</button>
                                            <button class="btn-sm btn-info" onclick="viewEnrolled(<?php echo $course['CourseID']; ?>, '<?php echo addslashes($course['CourseName']); ?>')">👥 Cadets</button>
                                        </div>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal for viewing schedules -->
    <div id="scheduleModal" class="modal">
        <div class="modal-content" style="width: 80%;">
            <div class="modal-header">
                <h3 id="scheduleModalTitle">📅 Course Schedules</h3>
                <span class="close-btn" onclick="closeScheduleModal()">&times;</span>
            </div>
            <div id="scheduleListContainer">
                <p style="text-align:center; padding: 40px;">Loading schedules...</p>
            </div>
        </div>
    </div>

    <!-- Modal for viewing enrolled cadets -->
    <div id="cadetModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="cadetModalTitle">👥 Enrolled Cadets</h3>
                <span class="close-btn" onclick="closeCadetModal()">&times;</span>
            </div>
            <div id="cadetListContainer">
                <p style="text-align:center; padding: 40px;">Loading cadets...</p>
            </div>
        </div>
    </div>
    
    <script>
        function updateCourseStatus(courseId, newStatus) {
            const select = event.target;
            select.style.opacity = '0.5';
            
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=update_status&course_id=${courseId}&status=${newStatus}`
            })
            .then(response => response.json())
            .then(data => {
                select.style.opacity = '1';
                if (data.success) {
                    let newClass = 'status-select ';
                    if (newStatus === 'Active') newClass += 'status-active';
                    else if (newStatus === 'Inactive') newClass += 'status-inactive';
                    else newClass += 'status-completed';
                    select.className = newClass;
                    showToast('✅ ' + data.message, 'success');
                } else {
                    showToast('❌ ' + data.message, 'error');
                }
            })
            .catch(error => {
                select.style.opacity = '1';
                showToast('❌ Failed to update status', 'error');
            });
        }
        
        function showToast(message, type) {
            const existingToast = document.querySelector('.toast');
            if (existingToast) existingToast.remove();
            
            const toast = document.createElement('div');
            toast.className = 'toast ' + (type === 'error' ? 'error' : '');
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
        
        function viewSchedules(courseId, courseName) {
            const modal = document.getElementById('scheduleModal');
            const container = document.getElementById('scheduleListContainer');
            const title = document.getElementById('scheduleModalTitle');
            
            title.innerHTML = `📅 Schedules for: <span style="color: var(--accent-blue);">${courseName}</span>`;
            modal.style.display = 'block';
            container.innerHTML = '<div style="text-align:center; padding: 40px;">Loading schedules...</div>';
            
            fetch(`../actions/get-course-schedules.php?course_id=${courseId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.schedules.length > 0) {
                        let html = '<div class="cadet-list-wrapper"><table class="schedule-table">';
                        html += '<thead><tr><th>Date</th><th>Day</th><th>Start</th><th>End</th><th>Duration</th><th>Location</th><th>Status</th><th>Enrolled</th></tr></thead><tbody>';
                        
                        data.schedules.forEach(schedule => {
                            const date = new Date(schedule.ScheduleDate);
                            const day = date.toLocaleDateString('en-GB', { weekday: 'short' });
                            const startTime = schedule.StartTime.substring(0,5);
                            const endTime = schedule.EndTime.substring(0,5);
                            const duration = (new Date('1970-01-01T' + schedule.EndTime) - new Date('1970-01-01T' + schedule.StartTime)) / 3600000;
                            const statusClass = schedule.Status === 'Active' ? 'status-active' : (schedule.Status === 'Completed' ? 'status-completed' : 'status-inactive');
                            
                            html += `<tr>
                                <td>${date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })}</div>
                                <td>${day}</div>
                                <td>${startTime}</div>
                                <td>${endTime}</div>
                                <td>${duration}h</div>
                                <td>${schedule.Location || '-'}</div>
                                <td><span class="status-badge ${statusClass}" style="font-size: 10px;">${schedule.Status}</span></div>
                                <td><span class="badge">👥 ${schedule.EnrolledCount || 0}</span></div>
                            </table>`;
                        });
                        
                        html += '</tbody></table></div>';
                        container.innerHTML = html;
                    } else {
                        container.innerHTML = '<div class="empty-state"><p>No schedules found for this course.</p></div>';
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    container.innerHTML = '<div class="empty-state"><p style="color: #ef4444;">Error loading schedules. Please try again.</p></div>';
                });
        }
        
        function closeScheduleModal() {
            document.getElementById('scheduleModal').style.display = 'none';
        }
        
        function viewEnrolled(courseId, courseName) {
            const modal = document.getElementById('cadetModal');
            const container = document.getElementById('cadetListContainer');
            const title = document.getElementById('cadetModalTitle');
            
            title.innerHTML = `👥 Enrolled Cadets for: <span style="color: var(--accent-blue);">${courseName}</span>`;
            modal.style.display = 'block';
            container.innerHTML = '<div style="text-align:center; padding: 40px;">Loading cadets...</div>';
            
            fetch(`../actions/get-enrolled-cadets.php?course_id=${courseId}&action=list`)
                .then(response => response.text())
                .then(html => {
                    if (html && html.includes('cadet-list-wrapper')) {
                        container.innerHTML = html;
                    } else if (html && html.includes('No cadets')) {
                        container.innerHTML = '<div class="empty-state">👥 No cadets enrolled in this course.</div>';
                    } else {
                        container.innerHTML = html;
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    container.innerHTML = '<div class="empty-state" style="color: #ef4444;">❌ Error loading cadets. Please try again.</div>';
                });
        }
        
        function showCadetList(courseId) {
            const container = document.getElementById('cadetListContainer');
            container.innerHTML = '<div style="text-align:center; padding: 40px;">Loading list...</div>';
            
            fetch(`../actions/get-enrolled-cadets.php?course_id=${courseId}&action=list`)
                .then(response => response.text())
                .then(html => {
                    container.innerHTML = html;
                })
                .catch(err => {
                    console.error('Error:', err);
                    container.innerHTML = '<div class="empty-state" style="color: #ef4444;">Error loading list.</div>';
                });
        }
        
        function showCadetDetails(courseId, userId) {
            const container = document.getElementById('cadetListContainer');
            container.innerHTML = '<div style="text-align:center; padding: 40px;">Loading details...</div>';
            
            fetch(`../actions/get-enrolled-cadets.php?course_id=${courseId}&action=details&user_id=${userId}`)
                .then(response => response.text())
                .then(html => {
                    container.innerHTML = html;
                })
                .catch(err => {
                    console.error('Error:', err);
                    container.innerHTML = '<div class="empty-state" style="color: #ef4444;">Error loading details.</div>';
                });
        }
        
        function closeCadetModal() {
            document.getElementById('cadetModal').style.display = 'none';
        }
        
        function applyDateFilter() {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const search = document.querySelector('input[name="search"]')?.value || '';
            
            let url = '?';
            const params = [];
            if (startDate) params.push(`start_date=${startDate}`);
            if (endDate) params.push(`end_date=${endDate}`);
            if (search) params.push(`search=${encodeURIComponent(search)}`);
            window.location.href = url + params.join('&');
        }
        
        // Dark mode is handled by sidebar.php
        window.onclick = function(event) {
            const scheduleModal = document.getElementById('scheduleModal');
            const cadetModal = document.getElementById('cadetModal');
            if (event.target == scheduleModal) closeScheduleModal();
            if (event.target == cadetModal) closeCadetModal();
        }
    </script>
</body>
</html>