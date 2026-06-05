<?php
require_once '../includes/session.php';
require_once '../config/database.php';

requireRole(['Admin']);

// Get date range parameters for print mode
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$print_mode = isset($_GET['print_mode']) ? $_GET['print_mode'] : false;

// Build WHERE clause based on date range
$where_clauses = [];
$params = [];
$types = "";

if (!empty($start_date) && !empty($end_date)) {
    $where_clauses[] = "a.Date BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types .= "ss";
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Get all attendance with date range filter
$sql = "SELECT a.*, u.Fullname, u.ICNumber, c.CourseName, rec.Fullname as RecordedByName
    FROM attendance a
    JOIN users u ON a.UserID = u.UserID
    JOIN courses c ON a.CourseID = c.CourseID
    LEFT JOIN users rec ON a.RecordedBy = rec.UserID
    $where_sql
    ORDER BY c.CourseName ASC, a.Date DESC, a.CreatedAt DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$attendance = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Group attendance by course
$grouped_by_course = [];
foreach ($attendance as $att) {
    $course_name = $att['CourseName'];
    if (!isset($grouped_by_course[$course_name])) {
        $grouped_by_course[$course_name] = [];
    }
    $grouped_by_course[$course_name][] = $att;
}

// Total statistics
$total_records = count($attendance);
$present_count = 0;
$absent_count = 0;
$late_count = 0;

foreach ($attendance as $att) {
    if ($att['Status'] == 'Present') $present_count++;
    elseif ($att['Status'] == 'Absent') $absent_count++;
    elseif ($att['Status'] == 'Late') $late_count++;
}

// Get min and max dates from attendance for date picker limits
$date_range_result = $conn->query("SELECT MIN(Date) as min_date, MAX(Date) as max_date FROM attendance");
$date_range = $date_range_result->fetch_assoc();
$min_date = $date_range['min_date'] ?? date('Y-m-d');
$max_date = $date_range['max_date'] ?? date('Y-m-d');

// Get selected course for detailed view
$selected_course = isset($_GET['course']) ? $_GET['course'] : '';
$view_details = isset($_GET['view_details']) ? $_GET['view_details'] : false;

// If viewing details for a specific course, filter the data
if ($view_details && $selected_course) {
    $filtered_attendance = array_filter($attendance, function($att) use ($selected_course) {
        return $att['CourseName'] == $selected_course;
    });
} else {
    $filtered_attendance = $attendance;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Reports - Kor Sispa</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Additional styles for attendance reports */
        .filter-bar {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .filter-group {
            display: inline-block;
            margin-right: 15px;
        }
        
        .filter-group label {
            display: block;
            font-size: 12px;
            margin-bottom: 5px;
            color: #666;
        }
        
        .filter-group input {
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
        }
        
        .btn {
            padding: 6px 14px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            border: none;
        }
        
        .btn-primary {
            background: #4a90e2;
            color: white;
        }
        
        .btn-primary:hover {
            background: #357abd;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-back {
            background: #6c757d;
            color: white;
            padding: 4px 10px;
            font-size: 11px;
        }
        
        .btn-back:hover {
            background: #5a6268;
        }
        
        .btn-print {
            background: #17a2b8;
            color: white;
        }
        
        .btn-print:hover {
            background: #138496;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .stat-card .number {
            font-size: 28px;
            font-weight: bold;
        }
        
        .stat-card.total .number { color: #4a90e2; }
        .stat-card.present .number { color: #28a745; }
        .stat-card.absent .number { color: #dc3545; }
        .stat-card.late .number { color: #ff9800; }
        
        .result-info {
            background: #e9ecef;
            padding: 8px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 13px;
            color: #495057;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .table-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .export-buttons {
            display: flex;
            gap: 10px;
        }
        
        /* Course Grid Layout */
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }
        
        .course-card {
            background: #504f4f;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
            border: 1px solid #e0e0e0;
        }
        
        .course-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-color: #4a90e2;
        }
        
        .course-card-header {
            background: #3f3f3f66;
            padding: 15px;
            color: white;
        }
        
        .course-card-header h4 {
            margin: 0 0 8px 0;
            font-size: 16px;
        }
        
        .course-stats {
            display: flex;
            gap: 15px;
            font-size: 12px;
        }
        
        .course-stats span {
            color: rgba(255,255,255,0.9);
        }
        
        .course-stats .present { color: #a5d6a7; }
        .course-stats .absent { color: #ffcdd2; }
        .course-stats .late { color: #ffe0b2; }
        
        .course-card-body {
            padding: 15px;
        }
        
        .course-card-body p {
            color: #fff;
            font-size: 12px;
            margin-bottom: 12px;
        }
        
        .btn-view-details {
            width: 100%;
            padding: 8px;
            background: #4a90e2;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .btn-view-details:hover {
            background: #357abd;
        }
        
        /* Detailed View Table */
        .detailed-view {
            margin-top: 20px;
        }
        
        .detailed-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .detailed-header h3 {
            margin: 0;
            color: #fff;
        }
        
        .course-info-badge {
            background: #4a90e2;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            color: white;
        }
        
        /* Print Header for Detailed View */
        .print-header {
            display: none;
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #4a90e2;
        }
        
        .print-header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .print-header p {
            color: #666;
            font-size: 12px;
            margin: 5px 0;
        }
        
        .print-header .course-name {
            font-size: 18px;
            font-weight: bold;
            color: #4a90e2;
            margin-top: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th {
            background: #f8f9fa;
            padding: 10px 12px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
        }
        
        table td {
            padding: 10px 12px;
            font-size: 12px;
            color: #b4b4b4;
            border-bottom: 1px solid #e9ecef;
        }
        
        table tr:hover td {
            background: #666;
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
        }
        
        .status-present {
            background: #d4edda;
            color: #155724;
        }
        
        .status-absent {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-late {
            background: #fff3cd;
            color: #856404;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 8px;
            color: #6c757d;
            font-size: 14px;
        }
        
        .date-range {
            display: flex;
            gap: 15px;
            align-items: flex-end;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-left: 15px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 13px;
        }
        
        /* Print Styles for Detailed View */
        @media print {
            /* Hide non-printable elements */
            .filter-bar, 
            .sidebar, 
            .top-bar, 
            .stats-cards,
            .result-info .btn-back,
            .header-actions button,
            .btn-logout,
            .courses-grid,
            .btn-view-details,
            .result-info a:not(.print-header),
            .export-buttons,
            .detailed-header .btn-print {
                display: none !important;
            }
            
            /* Show print header */
            .print-header {
                display: block !important;
            }
            
            /* Remove margins and backgrounds */
            .dashboard-container {
                display: block !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
                background: white !important;
            }
            
            .table-container {
                box-shadow: none !important;
                padding: 0 !important;
                background: white !important;
            }
            
            .detailed-view {
                margin-top: 0 !important;
            }
            
            .detailed-header {
                display: none !important;
            }
            
            /* Table print styles */
            table {
                border: 1px solid #dee2e6;
                width: 100%;
                border-collapse: collapse;
            }
            
            table th {
                background: #f8f9fa !important;
                border: 1px solid #dee2e6;
                font-size: 10px;
                padding: 8px;
            }
            
            table td {
                border: 1px solid #dee2e6;
                font-size: 10px;
                padding: 8px;
            }
            
            .status-badge {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
            
            /* Page break control */
            .course-card {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
        
        @media (max-width: 768px) {
            .stats-cards {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
            
            .date-range {
                flex-direction: column;
                width: 100%;
            }
            
            .date-range .filter-group {
                width: 100%;
            }
            
            .action-buttons {
                margin-left: 0;
                width: 100%;
            }
            
            .action-buttons .btn {
                flex: 1;
                text-align: center;
            }
            
            .courses-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="top-bar">
                <h2>Attendance Reports</h2>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
                    <a href="../actions/logout.php" class="btn-logout">Logout</a>
                </div>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert-success">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Date Range Filter Bar -->
            <div class="filter-bar">
                <form method="GET" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
                    <div class="date-range">
                        <div class="filter-group">
                            <label>START DATE</label>
                            <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" min="<?php echo $min_date; ?>" max="<?php echo $max_date; ?>">
                        </div>
                        <div class="filter-group">
                            <label>END DATE</label>
                            <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" min="<?php echo $min_date; ?>" max="<?php echo $max_date; ?>">
                        </div>
                    </div>
                    <div class="action-buttons">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <?php if (!empty($start_date) || !empty($end_date)): ?>
                            <a href="?" class="btn btn-secondary">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-cards">
                <div class="stat-card total">
                    <h3>Total Records</h3>
                    <div class="number"><?php echo $total_records; ?></div>
                </div>
                <div class="stat-card present">
                    <h3>Present</h3>
                    <div class="number"><?php echo $present_count; ?></div>
                </div>
                <div class="stat-card absent">
                    <h3>Absent</h3>
                    <div class="number"><?php echo $absent_count; ?></div>
                </div>
                <div class="stat-card late">
                    <h3>Late</h3>
                    <div class="number"><?php echo $late_count; ?></div>
                </div>
            </div>
            
            <!-- Result Info -->
            <div class="result-info">
                <span>📊 <?php echo $total_records; ?> record(s) across <?php echo count($grouped_by_course); ?> course(s)
                <?php if (!empty($start_date) && !empty($end_date)): ?>
                    • <?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?>
                <?php endif; ?>
                </span>
                <?php if ($view_details && $selected_course): ?>
                    <a href="?" class="btn btn-back">← Back to All Courses</a>
                <?php endif; ?>
            </div>
            
            <div class="table-container">
                <?php if ($view_details && $selected_course): ?>
                    <!-- Detailed View for Selected Course -->
                    <div class="detailed-view">
                        <!-- Print Header - Only visible when printing -->
                        <div class="print-header">
                            <h1>Kor Sispa Attendance Report</h1>
                            <p>Course: <?php echo htmlspecialchars($selected_course); ?></p>
                            <p>Generated on: <?php echo date('d M Y, H:i A'); ?></p>
                            <?php if (!empty($start_date) && !empty($end_date)): ?>
                                <p>Date Range: <?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?></p>
                            <?php endif; ?>
                            <p>Total Records: <?php echo count($filtered_attendance); ?></p>
                            <hr>
                        </div>
                        
                        <div class="detailed-header">
                            <h3>📚 Course Details: <?php echo htmlspecialchars($selected_course); ?></h3>
                            <div class="export-buttons">
                                <button onclick="printDetailedView()" class="btn btn-print">🖨️ Print This Report</button>
                                <div class="course-info-badge">
                                    Total Records: <?php echo count($filtered_attendance); ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (empty($filtered_attendance)): ?>
                            <div class="empty-state">
                                No attendance records found for <?php echo htmlspecialchars($selected_course); ?> in the selected date range.
                            </div>
                        <?php else: ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Cadet Name</th>
                                        <th>IC Number</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Recorded By</th>
                                        <th>Time</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $summary_present = 0;
                                    $summary_absent = 0;
                                    $summary_late = 0;
                                    foreach ($filtered_attendance as $att): 
                                        if ($att['Status'] == 'Present') $summary_present++;
                                        elseif ($att['Status'] == 'Absent') $summary_absent++;
                                        elseif ($att['Status'] == 'Late') $summary_late++;
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($att['Fullname']); ?></td>
                                        <td><?php echo htmlspecialchars($att['ICNumber'] ?? '-'); ?></td>
                                        <td><?php echo date('d M Y', strtotime($att['Date'])); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($att['Status']); ?>">
                                                <?php echo $att['Status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($att['RecordedByName'] ?? 'System'); ?></td>
                                        <td><?php echo date('H:i', strtotime($att['CreatedAt'])); ?></td>
                                        <td><?php echo htmlspecialchars($att['Remarks'] ?? '-'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot style="background: #374151;">
                                    <tr>
                                        <td colspan="7" style="padding: 10px;">
                                            <strong>Summary:</strong> 
                                            Present: <?php echo $summary_present; ?> | 
                                            Absent: <?php echo $summary_absent; ?> | 
                                            Late: <?php echo $summary_late; ?>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        <?php endif; ?>
                    </div>
                    
                <?php elseif (!empty($grouped_by_course)): ?>
                    <!-- Course Grid View -->
                    <div class="header-actions">
                        <h3>Courses with Attendance Records</h3>
                        <div class="export-buttons">
                            <button onclick="exportAllToCSV()" class="btn btn-secondary">📎 Export All CSV</button>
                        </div>
                    </div>
                    
                    <div class="courses-grid">
                        <?php foreach ($grouped_by_course as $course_name => $records): 
                            // Calculate course stats
                            $c_present = 0; $c_absent = 0; $c_late = 0;
                            foreach ($records as $r) {
                                if ($r['Status'] == 'Present') $c_present++;
                                elseif ($r['Status'] == 'Absent') $c_absent++;
                                elseif ($r['Status'] == 'Late') $c_late++;
                            }
                        ?>
                        <div class="course-card" onclick="viewCourseDetails('<?php echo addslashes($course_name); ?>')">
                            <div class="course-card-header">
                                <h4><?php echo htmlspecialchars($course_name); ?></h4>
                                <div class="course-stats">
                                    <span>Total: <?php echo count($records); ?></span>
                                    <span class="present">✓ Present: <?php echo $c_present; ?></span>
                                    <span class="absent">✗ Absent: <?php echo $c_absent; ?></span>
                                    <span class="late">⏰ Late: <?php echo $c_late; ?></span>
                                </div>
                            </div>
                            <div class="course-card-body">
                                <p>📅 Latest record: <?php echo date('d M Y', strtotime($records[0]['Date'])); ?></p>
                                <button class="btn-view-details" onclick="event.stopPropagation(); viewCourseDetails('<?php echo addslashes($course_name); ?>')">
                                    View Full Details →
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                <?php else: ?>
                    <div class="empty-state">
                        No attendance records found for the selected date range.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function viewCourseDetails(courseName) {
            // Get current URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            const startDate = urlParams.get('start_date') || '';
            const endDate = urlParams.get('end_date') || '';
            
            // Build new URL
            let newUrl = `?view_details=1&course=${encodeURIComponent(courseName)}`;
            if (startDate) newUrl += `&start_date=${startDate}`;
            if (endDate) newUrl += `&end_date=${endDate}`;
            
            window.location.href = newUrl;
        }
        
        function printDetailedView() {
            window.print();
        }
        
        function exportAllToCSV() {
            var csv = [];
            // Add headers
            csv.push(['Course', 'Cadet Name', 'IC Number', 'Date', 'Status', 'Recorded By', 'Time', 'Remarks']);
            
            // Collect data from all courses
            <?php foreach ($grouped_by_course as $course_name => $records): ?>
                <?php foreach ($records as $att): ?>
                    csv.push([
                        '<?php echo addslashes($course_name); ?>',
                        '<?php echo addslashes($att['Fullname']); ?>',
                        '<?php echo addslashes($att['ICNumber'] ?? '-'); ?>',
                        '<?php echo date('d M Y', strtotime($att['Date'])); ?>',
                        '<?php echo $att['Status']; ?>',
                        '<?php echo addslashes($att['RecordedByName'] ?? 'System'); ?>',
                        '<?php echo date('H:i', strtotime($att['CreatedAt'])); ?>',
                        '<?php echo addslashes($att['Remarks'] ?? '-'); ?>'
                    ].map(cell => '"' + cell.replace(/"/g, '""') + '"').join(','));
                <?php endforeach; ?>
            <?php endforeach; ?>
            
            var csvContent = csv.join('\n');
            var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            var link = document.createElement('a');
            var url = URL.createObjectURL(blob);
            
            link.setAttribute('href', url);
            link.setAttribute('download', 'attendance_all_courses.csv');
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>