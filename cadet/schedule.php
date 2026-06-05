<?php
require_once '../includes/session.php';
require_once '../config/database.php';

requireRole(['Cadet']);

$cadet_id = $_SESSION['user_id'];

// Get enrolled courses first
$enrolled_courses = $conn->query("SELECT 
    c.CourseID,
    c.CourseName,
    c.Description as CourseDescription,
    u.Fullname as TrainerName
FROM enrollments e
JOIN courses c ON e.CourseID = c.CourseID
LEFT JOIN users u ON c.TrainerID = u.UserID
WHERE e.CadetID = $cadet_id AND e.Status = 'Enrolled'
ORDER BY c.CourseName ASC")->fetch_all(MYSQLI_ASSOC);

// Get all schedules for enrolled courses
$schedules = [];
if (count($enrolled_courses) > 0) {
    $course_ids = array_column($enrolled_courses, 'CourseID');
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

// Group schedules by course for list view
$schedules_by_course = [];
foreach ($schedules as $schedule) {
    $course_id = $schedule['CourseID'];
    if (!isset($schedules_by_course[$course_id])) {
        $schedules_by_course[$course_id] = [
            'course_name' => $schedule['CourseName'],
            'trainer' => $schedule['TrainerName'],
            'schedules' => []
        ];
    }
    $schedules_by_course[$course_id]['schedules'][] = $schedule;
}

// Prepare calendar events
$calendar_events = [];
foreach ($schedules as $schedule) {
    $ts = strtotime($schedule['ScheduleDate']);
    $startTime = date('h:i A', strtotime($schedule['StartTime']));
    $endTime = date('h:i A', strtotime($schedule['EndTime']));
    
    $calendar_events[] = [
        'id' => $schedule['ScheduleID'],
        'title' => $schedule['CourseName'],
        'date' => date('Y-m-d', $ts),
        'start_time' => $startTime,
        'end_time' => $endTime,
        'full_time' => $startTime . ' - ' . $endTime,
        'day' => date('l', $ts),
        'trainer' => $schedule['TrainerName'],
        'location' => $schedule['Location'] ?? 'TBA',
        'course_name' => $schedule['CourseName']
    ];
}

// ========== WEEKLY TIMETABLE PREPARATION ==========
$time_slots = [];
for ($hour = 8; $hour <= 22; $hour++) {
    $start = sprintf("%02d:00:00", $hour);
    $time_slots[] = [
        'start' => $start,
        'label' => sprintf("%02d:00", $hour)
    ];
}

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

function getDayOfWeek($date) {
    return date('l', strtotime($date));
}

$timetable = [];
foreach ($days as $day) {
    $timetable[$day] = [];
    foreach ($time_slots as $slot) {
        $timetable[$day][$slot['start']] = null;
    }
}

$schedule_details = [];

foreach ($schedules as $schedule) {
    $day = getDayOfWeek($schedule['ScheduleDate']);
    $start_time = $schedule['StartTime'];
    $schedule_details[$schedule['ScheduleID']] = $schedule;
    
    foreach ($time_slots as $slot) {
        if ($start_time >= $slot['start'] && $start_time < date('H:i:s', strtotime($slot['start'] . ' +1 hour'))) {
            $trainer = $schedule['TrainerName'];
            $trainer = preg_replace('/^(DR|PROF|DATIN|HAJAH|USTAZ)\s+/i', '', $trainer);
            $trainer = substr($trainer, 0, 10);
            
            $content = sprintf(
                "<div class='class-cell' data-schedule-id='%d' title='%s&#013;Time: %s - %s&#013;Trainer: %s&#013;Location: %s'>
                    <div class='course-name'>%s</div>
                    <div class='trainer-name'>%s</div>
                    <div class='location'>📍%s</div>
                </div>",
                $schedule['ScheduleID'],
                htmlspecialchars($schedule['CourseName']),
                date('h:i A', strtotime($schedule['StartTime'])),
                date('h:i A', strtotime($schedule['EndTime'])),
                htmlspecialchars($trainer),
                htmlspecialchars($schedule['Location'] ?? 'TBA'),
                htmlspecialchars(substr($schedule['CourseName'], 0, 16)),
                htmlspecialchars($trainer),
                htmlspecialchars(substr($schedule['Location'] ?? 'TBA', 0, 10))
            );
            $timetable[$day][$slot['start']] = $content;
            break;
        }
    }
}

$today = new DateTime();
$start_of_week = clone $today;
$start_of_week->modify('monday this week');

$classes_this_week = 0;
foreach ($schedules as $schedule) {
    $schedule_date = new DateTime($schedule['ScheduleDate']);
    if ($schedule_date >= $start_of_week && $schedule_date <= (clone $start_of_week)->modify('+6 days')) {
        $classes_this_week++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedule - Kor Sispa</title>
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
            --danger: #d63031;
            --warning: #fdcb6e;
            --calendar-day-bg: #252525;
            --calendar-today: #2d3a3a;
            --table-header: #252525;
            --cell-hover: #2a2a2a;
            --shadow: rgba(0,0,0,0.3);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: var(--bg-body); color: var(--text-main); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

        .dashboard-container { display: flex; min-height: 100vh; }
        .main-content { flex: 1; padding: 20px; margin-left: 260px; overflow-x: auto; }

        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid var(--border); flex-wrap: wrap; gap: 10px; }
        .top-bar h2 { color: var(--accent); font-size: 1.3rem; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .btn-logout { background: var(--danger); color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 0.85rem; }
        .btn-logout:hover { background: #c12829; }

        .view-tabs { margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap; }
        .tab-btn { padding: 10px 20px; border: 2px solid var(--border); background: var(--bg-card); color: var(--text-main); border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s ease; font-size: 0.9rem; }
        .tab-btn:hover { border-color: var(--accent); transform: translateY(-2px); }
        .tab-btn.active { background: var(--accent); color: white; border-color: var(--accent); }

        /* List View Styles */
        .table-container { background: var(--bg-card); padding: 20px; border-radius: 12px; border: 1px solid var(--border); margin-bottom: 20px; overflow-x: auto; }
        .table-container h3 { margin-bottom: 15px; color: var(--accent); font-size: 1.1rem; }
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        th { text-align: center; padding: 12px 8px; border-bottom: 2px solid var(--border); font-weight: 600; color: var(--text-secondary); text-transform: uppercase; font-size: 0.75rem; }
        td { text-align: center; padding: 12px 8px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        tr:hover { background: var(--calendar-day-bg); }
        .schedule-badge { display: inline-block; background: var(--accent); color: white; padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; margin: 2px; }

        /* Weekly Timetable Styles */
        .week-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; background: var(--bg-card); padding: 10px 15px; border-radius: 8px; border: 1px solid var(--border); flex-wrap: wrap; gap: 10px; }
        .week-nav-btn { background: var(--accent); color: white; border: none; padding: 5px 12px; border-radius: 5px; cursor: pointer; font-size: 0.75rem; font-weight: 600; }
        .week-nav-btn:hover { background: var(--accent-hover); }
        .week-date { font-size: 0.85rem; color: var(--text-secondary); }
        .timetable-wrapper { background: var(--bg-card); border-radius: 10px; border: 1px solid var(--border); overflow-x: auto; }
        .timetable { width: 100%; border-collapse: collapse; font-size: 0.7rem; min-width: 700px; }
        .timetable th { background: var(--table-header); padding: 8px 4px; text-align: center; border: 1px solid var(--border); font-weight: 600; color: var(--accent); }
        .timetable td { padding: 4px; border: 1px solid var(--border); vertical-align: top; min-height: 60px; background-color: #1a1a1a; }
        .timetable tr:hover td { background-color: var(--cell-hover); }
        .time-col { background: var(--table-header); font-weight: bold; text-align: center; width: 55px; color: var(--text-secondary); }
        .day-col { background: var(--table-header); font-weight: bold; text-align: center; width: 100px; }
        .day-col .date { font-size: 0.6rem; color: var(--text-secondary); display: block; margin-top: 2px; }
        .class-cell { background: linear-gradient(135deg, var(--accent), var(--accent-hover)); border-radius: 4px; padding: 4px; color: white; font-size: 0.65rem; text-align: center; cursor: pointer; transition: all 0.2s; }
        .class-cell .course-name { font-weight: bold; font-size: 0.7rem; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .class-cell .trainer-name { font-size: 0.6rem; opacity: 0.9; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .class-cell .location { font-size: 0.55rem; opacity: 0.8; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .class-cell:hover { transform: scale(1.02); box-shadow: 0 2px 6px rgba(0,0,0,0.3); }
        .empty-cell { color: var(--text-secondary); text-align: center; font-size: 0.6rem; display: flex; align-items: center; justify-content: center; min-height: 55px; }

        /* Calendar View Styles */
        .calendar-container { background: var(--bg-card); padding: 20px; border-radius: 12px; border: 1px solid var(--border); max-width: 900px; margin: 0 auto; }
        .calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
        .calendar-header h3 { font-size: 1.2rem; color: var(--accent); flex: 1; text-align: center; }
        .calendar-nav-btn { background: var(--accent); color: white; border: none; padding: 6px 14px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 0.85rem; }
        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; }
        .cal-day-name { text-align: center; font-weight: bold; font-size: 0.7rem; padding: 8px 0; color: var(--text-secondary); text-transform: uppercase; }
        .day-box { min-height: 80px; background: var(--calendar-day-bg); border-radius: 6px; padding: 4px; border: 1px solid var(--border); position: relative; overflow-y: auto; }
        .day-box:hover { box-shadow: 0 2px 6px var(--shadow); background: var(--bg-card); }
        .day-box.today { background: var(--calendar-today); border: 2px solid var(--accent); }
        .day-box.has-events { border-left: 3px solid var(--accent); }
        .day-num { font-weight: bold; font-size: 0.8rem; margin-bottom: 4px; display: block; color: var(--text-main); text-align: right; padding-right: 4px; }
        .day-box.today .day-num { color: var(--accent); }
        .event-link { background: linear-gradient(135deg, var(--accent), var(--accent-hover)); color: white; font-size: 0.65rem; padding: 4px 6px; border-radius: 4px; margin-bottom: 3px; cursor: pointer; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-align: left; }
        .event-link:hover { transform: translateX(2px); background: var(--accent-hover); }
        .event-count { position: absolute; top: 2px; right: 4px; background: var(--accent); color: white; font-size: 0.6rem; padding: 1px 4px; border-radius: 8px; font-weight: bold; }
        .no-events { font-size: 0.6rem; color: var(--text-secondary); text-align: center; margin-top: 8px; font-style: italic; }
        .calendar-legend { display: flex; gap: 15px; margin-top: 15px; flex-wrap: wrap; justify-content: center; padding: 10px; background: var(--calendar-day-bg); border-radius: 6px; }
        .legend-item { display: flex; align-items: center; gap: 6px; font-size: 0.75rem; }
        .legend-box { width: 16px; height: 16px; border-radius: 3px; }

        /* Common Styles */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .stat-card { background: var(--bg-card); padding: 12px; border-radius: 10px; border: 1px solid var(--border); text-align: center; }
        .stat-number { font-size: 1.5rem; font-weight: bold; color: var(--accent); }
        .stat-label { color: var(--text-secondary); font-size: 0.7rem; margin-top: 5px; }
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); align-items: center; justify-content: center; }
        .modal-content { background: var(--bg-card); padding: 25px; border-radius: 12px; width: 90%; max-width: 400px; position: relative; }
        .modal-close { position: absolute; top: 12px; right: 12px; background: none; border: none; font-size: 1.3rem; cursor: pointer; color: var(--text-secondary); }
        .modal-close:hover { color: var(--danger); }
        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem; }
        .alert-warning { background: #4a3a1a; color: #f5e6a5; border-left: 4px solid var(--warning); }
        .empty-state { text-align: center; padding: 50px; color: var(--text-secondary); }
        .print-btn { background: var(--success); color: white; border: none; padding: 6px 12px; border-radius: 5px; cursor: pointer; font-size: 0.75rem; font-weight: 600; margin-left: 10px; }
        .print-btn:hover { background: #00944a; }

        @media print { .sidebar, .top-bar, .week-nav, .stats-grid, .print-btn, .btn-logout, .view-tabs { display: none !important; } .main-content { margin-left: 0 !important; padding: 0 !important; } }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding: 15px; } .time-col { width: 45px; } .class-cell { padding: 2px; } }
    </style>
</head>
<body>

    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="top-bar">
                <h2>📅 My Class Schedule</h2>
                <div>
                    <button onclick="window.print()" class="print-btn">🖨️ Print</button>
                    <a href="../actions/logout.php" class="btn-logout">Logout</a>
                </div>
            </div>

            <?php if (count($enrolled_courses) == 0): ?>
                <div class="alert alert-warning">⚠️ You haven't enrolled in any courses yet. Go to <strong>My Courses</strong> to enroll first.</div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-number"><?php echo count($enrolled_courses); ?></div><div class="stat-label">Enrolled Courses</div></div>
                <div class="stat-card"><div class="stat-number"><?php echo count($schedules); ?></div><div class="stat-label">Total Classes</div></div>
                <div class="stat-card"><div class="stat-number"><?php echo $classes_this_week; ?></div><div class="stat-label">This Week</div></div>
            </div>

            <!-- View Tabs - ORDER: List, Weekly, Calendar -->
            <div class="view-tabs">
                <button class="tab-btn active" onclick="showView('list', this)">📋 List View</button>
                <button class="tab-btn" onclick="showView('timetable', this)">📊 Weekly Timetable</button>
                <button class="tab-btn" onclick="showView('calendar', this)">📅 Calendar View</button>
            </div>

            <!-- LIST VIEW (Default) -->
            <div id="view-list" class="view-section">
                <div class="table-container">
                    <h3>📋 All Class Schedules</h3>
                    <?php if(count($schedules) > 0): ?>
                        <?php foreach ($schedules_by_course as $course_id => $course_data): ?>
                            <div style="margin-bottom: 25px;">
                                <h4 style="color: var(--accent); margin-bottom: 10px; padding-bottom: 5px; border-bottom: 1px solid var(--border);">
                                    📖 <?php echo htmlspecialchars($course_data['course_name']); ?>
                                    <small style="color: var(--text-secondary); font-size: 0.75rem;">(Trainer: <?php echo htmlspecialchars($course_data['trainer']); ?>)</small>
                                </h4>
                                <table>
                                    <thead><tr><th>Date</th><th>Day</th><th>Time</th><th>Location</th><th>Duration</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($course_data['schedules'] as $schedule): 
                                            $ts = strtotime($schedule['ScheduleDate']);
                                            $startTime = date('h:i A', strtotime($schedule['StartTime']));
                                            $endTime = date('h:i A', strtotime($schedule['EndTime']));
                                            $duration = (strtotime($schedule['EndTime']) - strtotime($schedule['StartTime'])) / 3600;
                                            $durationText = $duration . ' hour' . ($duration > 1 ? 's' : '');
                                        ?>
                                            <tr>
                                                <td><?php echo date('d M Y', $ts); ?></td>
                                                <td><?php echo date('l', $ts); ?></td>
                                                <td><?php echo $startTime . ' - ' . $endTime; ?></td>
                                                <td><span class="schedule-badge">📍 <?php echo htmlspecialchars($schedule['Location'] ?? 'TBA'); ?></span></td>
                                                <td><?php echo $durationText; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state"><p>📭 No schedules available for your enrolled courses.</p></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- WEEKLY TIMETABLE VIEW -->
            <div id="view-timetable" class="view-section" style="display:none;">
                <?php if(count($schedules) > 0): ?>
                    <div class="week-nav">
                        <button onclick="changeWeek(-1)" class="week-nav-btn">‹ Previous Week</button>
                        <span class="week-date" id="weekRange"></span>
                        <button onclick="changeWeek(1)" class="week-nav-btn">Next Week ›</button>
                        <button onclick="goToCurrentWeek()" class="week-nav-btn" style="background: var(--success);">Today</button>
                    </div>
                    <div class="timetable-wrapper">
                        <table class="timetable" id="timetable">
                            <thead id="timetableHeader"></thead>
                            <tbody id="timetableBody"></tbody>
                        </table>
                    </div>
                    <div style="margin-top: 15px; padding: 10px; background: var(--bg-card); border-radius: 6px; font-size: 0.65rem;">
                        <span>📖 Legend: 🎓 Course | 👨‍🏫 Trainer | 📍 Location | 💡 Click for details</span>
                    </div>
                <?php else: ?>
                    <div class="empty-state"><p>📭 No schedules available.</p></div>
                <?php endif; ?>
            </div>

            <!-- CALENDAR VIEW -->
            <div id="view-calendar" class="view-section" style="display:none;">
                <div class="calendar-container">
                    <div class="calendar-header">
                        <button onclick="changeMonth(-1)" class="calendar-nav-btn">‹ Prev</button>
                        <h3 id="currentMonth">Month Year</h3>
                        <button onclick="changeMonth(1)" class="calendar-nav-btn">Next ›</button>
                    </div>
                    <div class="calendar-grid" id="calendarGrid"></div>
                    <div class="calendar-legend">
                        <div class="legend-item"><div class="legend-box" style="background: var(--calendar-today); border: 2px solid var(--accent);"></div><span>Today</span></div>
                        <div class="legend-item"><div class="legend-box" style="background: var(--accent);"></div><span>Has Class</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="eventModal" class="modal" onclick="closeModal()">
        <div class="modal-content" onclick="event.stopPropagation()">
            <button class="modal-close" onclick="closeModal()">×</button>
            <h3 id="modalTitle" style="color:var(--accent); margin-bottom:15px;">Class Details</h3>
            <hr style="border:none; border-top:1px solid var(--border); margin:10px 0;">
            <p><strong>📖 Course:</strong> <span id="modalCourse"></span></p>
            <p><strong>🕒 Time:</strong> <span id="modalTime"></span></p>
            <p><strong>📍 Location:</strong> <span id="modalLocation"></span></p>
            <p><strong>📅 Date:</strong> <span id="modalDate"></span></p>
            <p><strong>👤 Trainer:</strong> <span id="modalTrainer"></span></p>
            <div style="margin-top:20px; text-align:right;"><button onclick="closeModal()" class="week-nav-btn">Close</button></div>
        </div>
    </div>

    <script>
        const events = <?php echo json_encode($calendar_events); ?>;
        const schedulesData = <?php echo json_encode($schedules); ?>;
        const scheduleDetails = <?php echo json_encode($schedule_details); ?>;
        const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        
        const timeSlots = [];
        for (let hour = 8; hour <= 22; hour++) timeSlots.push({ start: hour.toString().padStart(2,'0')+':00:00', label: hour.toString().padStart(2,'0')+':00' });
        
        let currentDate = new Date();
        let currentWeekOffset = 0;

        function showView(viewId, btn) {
            document.querySelectorAll('.view-section').forEach(v => v.style.display = 'none');
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById('view-' + viewId).style.display = 'block';
            btn.classList.add('active');
            if (viewId === 'timetable') buildTimetable();
        }

        // CALENDAR FUNCTIONS
        function renderCalendar() {
            const grid = document.getElementById('calendarGrid');
            const monthHeader = document.getElementById('currentMonth');
            grid.innerHTML = '';
            const month = currentDate.getMonth(), year = currentDate.getFullYear();
            monthHeader.innerText = currentDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
            const today = new Date();
            const isCurrentMonth = today.getMonth() === month && today.getFullYear() === year;
            const todayDate = today.getDate();
            ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].forEach(day => grid.innerHTML += `<div class="cal-day-name">${day}</div>`);
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            for(let i=0; i<firstDay; i++) grid.innerHTML += `<div class="day-box" style="opacity:0.3;"></div>`;
            for(let d=1; d<=daysInMonth; d++) {
                const dateString = `${year}-${String(month+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
                const dayEvents = events.filter(e => e.date === dateString);
                const isToday = isCurrentMonth && d === todayDate;
                let eventHtml = '';
                dayEvents.forEach(e => { eventHtml += `<div class="event-link" onclick="openModal(${e.id})">🕒 ${e.start_time}<br><strong>${e.title.substring(0,12)}</strong></div>`; });
                if(!dayEvents.length) eventHtml = `<div class="no-events">No class</div>`;
                grid.innerHTML += `<div class="day-box ${isToday ? 'today' : ''} ${dayEvents.length ? 'has-events' : ''}">
                    <span class="day-num">${d}</span>${dayEvents.length ? `<span class="event-count">${dayEvents.length}</span>` : ''}${eventHtml}</div>`;
            }
        }

        // TIMETABLE FUNCTIONS
        function getWeekDates(offset) {
            const today = new Date();
            const currentDay = today.getDay();
            const mondayDiff = currentDay === 0 ? -6 : 1 - currentDay;
            let startOfWeek = new Date(today);
            startOfWeek.setDate(today.getDate() + mondayDiff + (offset * 7));
            const weekDates = {};
            for(let i=0; i<7; i++) { const date = new Date(startOfWeek); date.setDate(startOfWeek.getDate() + i); weekDates[days[i]] = date; }
            return weekDates;
        }
        function formatDate(date) { return date.toLocaleDateString('en-GB', { day:'2-digit', month:'short' }); }
        function getNextHour(timeStr) { let hour = parseInt(timeStr.split(':')[0]) + 1; return hour.toString().padStart(2,'0')+':00:00'; }
        function buildTimetable() {
            const weekDates = getWeekDates(currentWeekOffset);
            const firstDate = weekDates[days[0]], lastDate = weekDates[days[6]];
            const weekRangeElem = document.getElementById('weekRange');
            if(weekRangeElem) weekRangeElem.innerText = `${formatDate(firstDate)} - ${formatDate(lastDate)} ${firstDate.getFullYear()}`;
            let headerHtml = '<tr><th class="time-col">Time</th>';
            for(let i=0; i<days.length; i++) { const date = weekDates[days[i]]; headerHtml += `<th class="day-col">${days[i]}<span class="date">${formatDate(date)}</span></th>`; }
            headerHtml += '</tr>';
            document.getElementById('timetableHeader').innerHTML = headerHtml;
            let bodyHtml = '';
            for(let slot of timeSlots) {
                bodyHtml += '<tr><td class="time-col">'+slot.label+'</td>';
                for(let i=0; i<days.length; i++) {
                    const date = weekDates[days[i]], dateStr = date.toISOString().split('T')[0];
                    let schedule = null;
                    for(let s of schedulesData) {
                        if(s.ScheduleDate === dateStr && s.StartTime >= slot.start && s.StartTime < getNextHour(slot.start)) { schedule = s; break; }
                    }
                    if(schedule) {
                        let trainer = (schedule.TrainerName || '').replace(/^(DR|PROF|DATIN|HAJAH|USTAZ)\s+/i, '').substring(0,10);
                        bodyHtml += `<td><div class="class-cell" onclick="openTimetableModal(${schedule.ScheduleID})"><div class="course-name">${escapeHtml(schedule.CourseName.substring(0,16))}</div><div class="trainer-name">${escapeHtml(trainer)}</div><div class="location">📍${escapeHtml((schedule.Location||'TBA').substring(0,10))}</div></div></td>`;
                    } else bodyHtml += '<td><div class="empty-cell">-</div></td>';
                }
                bodyHtml += '</tr>';
            }
            document.getElementById('timetableBody').innerHTML = bodyHtml;
        }
        function openTimetableModal(scheduleId) {
            const s = scheduleDetails[scheduleId]; if(!s) return;
            document.getElementById('modalCourse').innerText = s.CourseName;
            document.getElementById('modalTime').innerText = s.StartTime.substring(0,5)+' - '+s.EndTime.substring(0,5);
            document.getElementById('modalLocation').innerText = s.Location || 'TBA';
            document.getElementById('modalDate').innerText = new Date(s.ScheduleDate).toLocaleDateString('en-GB', { weekday:'long', day:'numeric', month:'long', year:'numeric' });
            document.getElementById('modalTrainer').innerText = s.TrainerName || 'TBA';
            document.getElementById('eventModal').style.display = 'flex';
        }
        function changeWeek(d) { currentWeekOffset += d; buildTimetable(); }
        function goToCurrentWeek() { currentWeekOffset = 0; buildTimetable(); }
        function openModal(id) { const e = events.find(x=>x.id==id); if(!e) return; document.getElementById('modalCourse').innerText=e.title; document.getElementById('modalTime').innerText=e.full_time; document.getElementById('modalLocation').innerText=e.location; document.getElementById('modalDate').innerText=e.day+', '+e.date; document.getElementById('modalTrainer').innerText=e.trainer; document.getElementById('eventModal').style.display='flex'; }
        function closeModal() { document.getElementById('eventModal').style.display='none'; }
        function changeMonth(dir) { currentDate.setMonth(currentDate.getMonth()+dir); renderCalendar(); }
        function escapeHtml(t) { if(!t) return ''; const d=document.createElement('div'); d.textContent=t; return d.innerHTML; }

        renderCalendar(); buildTimetable();
        document.addEventListener('keydown', e => { if(e.key==='Escape') closeModal(); if(e.key==='ArrowLeft') changeMonth(-1); if(e.key==='ArrowRight') changeMonth(1); });
    </script>
</body>
</html>