<?php
require_once '../includes/session.php';
require_once '../config/database.php';

requireRole(['Cadet']);

$cadet_id = $_SESSION['user_id'];

// Get enrolled courses (by CourseID)
$enrolled = $conn->query("SELECT 
    c.CourseID,
    c.CourseName,
    c.Description as CourseDescription,
    u.Fullname as TrainerName,
    e.Status as EnrollmentStatus,
    e.EnrolledAt
FROM enrollments e
JOIN courses c ON e.CourseID = c.CourseID
LEFT JOIN users u ON c.TrainerID = u.UserID
WHERE e.CadetID = $cadet_id AND e.Status = 'Enrolled'
ORDER BY e.EnrolledAt DESC")->fetch_all(MYSQLI_ASSOC);

// Get available courses (not enrolled yet) - ALL COURSES FROM ALL TRAINERS
$available = $conn->query("SELECT 
    c.CourseID,
    c.CourseName,
    c.Description as CourseDescription,
    u.Fullname as TrainerName,
    c.Status as CourseStatus
FROM courses c
LEFT JOIN users u ON c.TrainerID = u.UserID
WHERE c.Status = 'Active' 
AND c.CourseID NOT IN (
    SELECT CourseID FROM enrollments WHERE CadetID = $cadet_id
)
ORDER BY c.CourseName ASC")->fetch_all(MYSQLI_ASSOC);

// Get schedules for enrolled courses (to show in list view)
$schedules = [];
if (count($enrolled) > 0) {
    $course_ids = array_column($enrolled, 'CourseID');
    $ids_string = implode(',', $course_ids);
    
    $schedules_result = $conn->query("SELECT 
        cs.ScheduleID,
        cs.CourseID,
        cs.ScheduleDate,
        cs.StartTime,
        cs.EndTime,
        cs.Location,
        c.CourseName,
        u.Fullname as TrainerName
    FROM course_schedules cs
    JOIN courses c ON cs.CourseID = c.CourseID
    LEFT JOIN users u ON c.TrainerID = u.UserID
    WHERE cs.CourseID IN ($ids_string)
    AND cs.Status = 'Active'
    ORDER BY cs.ScheduleDate ASC, cs.StartTime ASC");
    
    $schedules = $schedules_result->fetch_all(MYSQLI_ASSOC);
}

$total_courses = $conn->query("SELECT COUNT(*) as total FROM courses WHERE Status = 'Active'")->fetch_assoc();
$total_courses = $total_courses['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - Kor Sispa</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --bg-body: #121212;
            --bg-card: #1e1e1e;
            --text-main: #f5f6fa;
            --text-secondary: #b2bec3;
            --border: #333333;
            --accent: #0984e3;
            --accent-hover: #0770c4;
            --success: #00b85c;
            --success-hover: #00944a;
            --danger: #d63031;
            --warning: #fdcb6e;
            --calendar-day-bg: #252525;
            --shadow: rgba(0,0,0,0.3);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background-color: var(--bg-body); 
            color: var(--text-main); 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            padding: 20px;
            margin-left: 260px;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border);
        }

        .top-bar h2 {
            color: var(--accent);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn-logout {
            background: var(--danger);
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.3s;
        }

        .btn-logout:hover {
            background: #c12829;
        }

        .view-tabs { 
            margin-bottom: 20px; 
            display: flex; 
            gap: 10px;
            flex-wrap: wrap;
        }
        .tab-btn { 
            padding: 10px 20px; 
            border: 2px solid var(--border); 
            background: var(--bg-card); 
            color: var(--text-main);
            border-radius: 8px; 
            cursor: pointer; 
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        .tab-btn:hover {
            border-color: var(--accent);
            transform: translateY(-2px);
        }
        .tab-btn.active { 
            background: var(--accent); 
            color: white; 
            border-color: var(--accent);
        }

        .table-container { 
            background: var(--bg-card); 
            padding: 20px; 
            border-radius: 12px; 
            border: 1px solid var(--border);
            box-shadow: 0 2px 8px var(--shadow);
            margin-bottom: 20px;
            overflow-x: auto;
        }
        .table-container h3 {
            margin-bottom: 15px;
            color: var(--accent);
            font-size: 1.1rem;
        }
        table { 
            width: 100%; 
            border-collapse: collapse;
            font-size: 0.85rem;
        }
        th { 
            text-align: center; 
            padding: 12px 8px; 
            border-bottom: 2px solid var(--border);
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }
        td { 
            text-align: center;
            padding: 12px 8px; 
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }
        tr:hover {
            background: var(--calendar-day-bg);
        }

        /* Small buttons */
        .btn-sm {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.7rem;
            transition: all 0.3s ease;
            display: inline-block;
            text-decoration: none;
        }
        .btn-xs {
            padding: 4px 8px;
            font-size: 0.65rem;
        }
        .btn-success {
            background: var(--success);
            color: white;
        }
        .btn-success:hover {
            background: var(--success-hover);
        }
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        .btn-danger:hover {
            background: #c12829;
        }
        .btn-info {
            background: var(--accent);
            color: white;
        }
        .btn-info:hover {
            background: var(--accent-hover);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: var(--bg-card);
            padding: 15px;
            border-radius: 10px;
            border: 1px solid var(--border);
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--accent);
        }
        .stat-label {
            color: var(--text-secondary);
            font-size: 0.8rem;
            margin-top: 5px;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: bold;
            display: inline-block;
        }
        .badge-enrolled {
            background: var(--success);
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--text-secondary);
        }

        .schedule-item {
            display: inline-block;
            background: var(--accent);
            color: white;
            padding: 3px 8px;
            border-radius: 15px;
            font-size: 0.65rem;
            margin: 2px;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            table {
                font-size: 0.7rem;
            }
            th, td {
                padding: 8px 4px;
            }
            .btn-sm {
                padding: 4px 8px;
                font-size: 0.65rem;
            }
        }
    </style>
</head>
<body>

    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="top-bar">
                <h2>📚 My Courses</h2>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
                    <a href="../actions/logout.php" class="btn-logout">Logout</a>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">✓ <?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">✗ <?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($enrolled); ?></div>
                    <div class="stat-label">Enrolled Courses</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($available); ?></div>
                    <div class="stat-label">Available Courses</div>
                </div>
            </div>

            <!-- View Tabs -->
            <div class="view-tabs">
                <button class="tab-btn active" onclick="showView('enrolled', this)">📋 My Enrolled Courses</button>
                <button class="tab-btn" onclick="showView('available', this)">🎯 Available Courses</button>
            </div>

            <!-- My Enrolled Courses -->
            <div id="view-enrolled" class="view-section">
                <div class="table-container">
                    <h3>📖 My Enrolled Courses</h3>
                    <?php if(count($enrolled) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Course Name</th>
                                <th>Trainer</th>
                                <th>Description</th>
                                <th>Schedules</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($enrolled as $course): 
                                // Get schedules for this course
                                $course_schedules = array_filter($schedules, function($s) use ($course) {
                                    return $s['CourseID'] == $course['CourseID'];
                                });
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($course['CourseName']); ?></strong></td>
                                <td><?php echo htmlspecialchars($course['TrainerName']); ?></td>
                                <td><?php echo htmlspecialchars(substr($course['CourseDescription'] ?? '', 0, 50)) . (strlen($course['CourseDescription'] ?? '') > 50 ? '...' : ''); ?></td>
                                <td>
                                    <?php if(count($course_schedules) > 0): ?>
                                        <?php foreach($course_schedules as $sch): ?>
                                            <span class="schedule-item">
                                                📅 <?php echo date('d M', strtotime($sch['ScheduleDate'])); ?> 
                                                🕒 <?php echo date('h:i A', strtotime($sch['StartTime'])); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span style="color: var(--text-secondary); font-size:0.7rem;">No schedules</span>
                                    <?php endif; ?>
                                </div>
                                <td>
                                    <form action="../actions/course-action.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="unenroll">
                                        <input type="hidden" name="course_id" value="<?php echo $course['CourseID']; ?>">
                                        <button type="submit" class="btn-sm btn-danger" onclick="return confirm('Drop this course?')">Drop Course</button>
                                    </form>
                                    <a href="schedule.php" class="btn-sm btn-info" style="margin-left:3px;">View Schedule</a>
                                </div>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>📭 You haven't enrolled in any courses yet.</p>
                            <p>Go to <strong>"Available Courses"</strong> tab to enroll!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Available Courses -->
            <div id="view-available" class="view-section" style="display:none;">
                <div class="table-container">
                    <h3>🎯 Available Courses (From All Trainers)</h3>
                    <?php if(count($available) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Course Name</th>
                                <th>Trainer</th>
                                <th>Description</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($available as $course): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($course['CourseName']); ?></strong></td>
                                <td>
                                    <span style="color: var(--accent);">👨‍🏫</span> <?php echo htmlspecialchars($course['TrainerName']); ?>
                                </div>
                                <td><?php echo htmlspecialchars(substr($course['CourseDescription'] ?? '', 0, 80)) . (strlen($course['CourseDescription'] ?? '') > 80 ? '...' : ''); ?></td>
                                <td>
                                    <form action="../actions/course-action.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="enroll">
                                        <input type="hidden" name="course_id" value="<?php echo $course['CourseID']; ?>">
                                        <button type="submit" class="btn-sm btn-success">Enroll</button>
                                    </form>
                                </div>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>🎯 No available courses at the moment.</p>
                            <?php if($total_courses == 0): ?>
                                <p style="margin-top: 10px; font-size: 0.85rem;">No courses have been created yet. Please check back later.</p>
                            <?php else: ?>
                                <p style="margin-top: 10px; font-size: 0.85rem;">You are already enrolled in all available courses!</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showView(viewId, btn) {
            document.querySelectorAll('.view-section').forEach(v => v.style.display = 'none');
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById('view-' + viewId).style.display = 'block';
            btn.classList.add('active');
        }
    </script>
</body>
</html>