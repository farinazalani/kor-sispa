<?php
require_once '../includes/session.php';
require_once '../config/database.php';

requireRole(['Admin', 'Trainer']);

date_default_timezone_set('Asia/Kuala_Lumpur');

// Get selected report
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'overview';
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$cadet_id = isset($_GET['cadet_id']) ? (int)$_GET['cadet_id'] : 0;
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Get data for filters
$courses = $conn->query("SELECT * FROM courses ORDER BY CourseName")->fetch_all(MYSQLI_ASSOC);
$cadets = $conn->query("SELECT UserID, Fullname FROM users WHERE Role = 'Cadet' ORDER BY Fullname")->fetch_all(MYSQLI_ASSOC);

// Get selected course info
$selected_course = null;
if ($course_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM courses WHERE CourseID = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $selected_course = $stmt->get_result()->fetch_assoc();
}

// ========== ATTENDANCE DATA ==========
$attendance = [];
$conditions = [];
if ($course_id > 0) $conditions[] = "a.CourseID = $course_id";
if ($cadet_id > 0) $conditions[] = "a.UserID = $cadet_id";
if ($start_date) $conditions[] = "a.Date >= '$start_date'";
if ($end_date) $conditions[] = "a.Date <= '$end_date'";
$where = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
$attendance = $conn->query("SELECT a.*, u.Fullname, u.ICNumber, c.CourseName, 
    trainer.Fullname as RecordedByName
    FROM attendance a 
    JOIN users u ON a.UserID = u.UserID 
    JOIN courses c ON a.CourseID = c.CourseID
    LEFT JOIN users trainer ON a.RecordedBy = trainer.UserID 
    $where 
    ORDER BY a.Date ASC")->fetch_all(MYSQLI_ASSOC);

// ========== COURSE DATA ==========
$courseData = [];
$where_course = $course_id > 0 ? "WHERE c.CourseID = $course_id" : "";
$courseData = $conn->query("SELECT c.*, u.Fullname as TrainerName,
    (SELECT COUNT(*) FROM enrollments e WHERE e.CourseID = c.CourseID) as TotalEnrolled
    FROM courses c 
    LEFT JOIN users u ON c.TrainerID = u.UserID 
    $where_course
    ORDER BY c.CourseName ASC")->fetch_all(MYSQLI_ASSOC);

// ========== PHYSICAL TEST DATA ==========
$physical_data = [];
$conditions_phy = [];
if ($cadet_id > 0) $conditions_phy[] = "pp.UserID = $cadet_id";
if ($start_date) $conditions_phy[] = "DATE(pp.TestDate) >= '$start_date'";
if ($end_date) $conditions_phy[] = "DATE(pp.TestDate) <= '$end_date'";
$where_phy = !empty($conditions_phy) ? "WHERE " . implode(" AND ", $conditions_phy) : "";
$physical_data = $conn->query("SELECT pp.*, u.Fullname, rec.Fullname as RecordedByName 
    FROM physical_performance pp
    JOIN users u ON pp.UserID = u.UserID
    LEFT JOIN users rec ON pp.RecordedBy = rec.UserID
    $where_phy
    ORDER BY pp.TestDate DESC")->fetch_all(MYSQLI_ASSOC);

// ========== OVERVIEW STATISTICS ==========
$totalUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE Status != 'Inactive'")->fetch_assoc()['count'];
$totalCadets = $conn->query("SELECT COUNT(*) as count FROM users WHERE Role = 'Cadet' AND Status = 'Active'")->fetch_assoc()['count'];
$totalTrainers = $conn->query("SELECT COUNT(*) as count FROM users WHERE Role = 'Trainer' AND Status = 'Active'")->fetch_assoc()['count'];
$totalCourses = $conn->query("SELECT COUNT(*) as count FROM courses")->fetch_assoc()['count'];
$totalAttendance = $conn->query("SELECT COUNT(*) as count FROM attendance")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report System - Kor Sispa</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* ========== BASE STYLES ========== */
        :root {
            --bg-body: #f0f2f5;
            --bg-card: #ffffff;
            --text-main: #1e293b;
            --text-secondary: #475569;
            --text-muted: #94a3b8;
            --border: #e2e8f0;
            --accent: #1e293b;
            --accent-hover: #2563eb;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --table-header: #f1f5f9;
            --shadow: rgba(0,0,0,0.05);
        }

        body.dark {
            --bg-body: #121212;
            --bg-card: #1e1e1e;
            --text-main: #f5f6fa;
            --text-secondary: #b2bec3;
            --text-muted: #6c7293;
            --border: #333333;
            --accent: #f5f6fa;
            --accent-hover: #0770c4;
            --success: #00b85c;
            --danger: #d63031;
            --warning: #fdcb6e;
            --table-header: #252525;
            --shadow: rgba(0,0,0,0.3);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background: var(--bg-body);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-main);
            transition: all 0.3s ease;
        }
        
        .dashboard-container { display: flex; }
        
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 25px;
        }
        
        .top-bar {
            background: var(--bg-card);
            padding: 15px 25px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            border: 1px solid var(--border);
        }
        
        .top-bar h2 { color: var(--text-main); }
        
        .btn-logout {
            background: var(--danger);
            color: white;
            padding: 8px 20px;
            border-radius: 8px;
            text-decoration: none;
        }
        
        /* ========== REPORT SELECTOR (Dropdown + Button in one row) ========== */
        .report-selector {
            background: var(--bg-card);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid var(--border);
            display: flex;
            gap: 20px;
            align-items: flex-end;
            flex-wrap: nowrap;
        }
        
        .report-selector .form-group {
            flex: 1;
            min-width: 200px;
        }
        
        .report-selector label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 13px;
            color: var(--text-secondary);
        }
        
        .report-selector select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            background: var(--bg-card);
            color: var(--text-main);
            cursor: pointer;
        }
        
        .btn-generate {
            background: var(--success);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            flex-shrink: 0;
            white-space: nowrap;
        }
        .btn-generate:hover { opacity: 0.9; }
        
        /* ========== FILTER SECTION ========== */
        .filter-bar {
            background: var(--bg-card);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid var(--border);
        }
        
        .filter-form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
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
        
        .filter-group select, .filter-group input {
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 13px;
            background: var(--bg-card);
            color: var(--text-main);
            min-width: 150px;
        }
        
        .btn-filter {
            background: var(--success);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            cursor: pointer;
        }
        
        .btn-reset {
            background: var(--danger);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
        }
        
        .action-buttons {
            margin-bottom: 20px;
            text-align: right;
        }
        
        .btn-print {
            background: var(--success);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            cursor: pointer;
        }
        
        /* ========== OFFICIAL REPORT CONTENT ========== */
        .report-content {
            background: white;
            padding: 40px 35px;
            margin-top: 20px;
        }
        
        body.dark .report-content { background: var(--bg-card); }
        
        .official-header {
            text-align: left;
            margin-bottom: 25px;
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
        }
        
        body.dark .official-header { border-bottom-color: var(--border); }
        
        .logo-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            margin-bottom: 10px;
        }
        
        .logo-left, .logo-right {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .logo-img {
            max-width: 70px;
            max-height: 70px;
            object-fit: contain;
        }
        
        .header-text { text-align: left; }
        
        .official-header h1 {
            font-size: 22px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 5px;
            color: #1a3a5c;
        }
        
        body.dark .official-header h1 { color: var(--text-main); }
        
        .official-header .address {
            font-size: 11px;
            line-height: 1.5;
            color: #333;
        }
        
        body.dark .official-header .address { color: var(--text-secondary); }
        
        .report-title {
            text-align: center;
            margin: 25px 0 20px 0;
        }
        
        .report-title h2 {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
        }
        
        /* ===== IMPROVED INFO-LINE WITH FLEXBOX ALIGNMENT ===== */
        .info-lines-container {
            margin: 20px 0;
            width: 100%;
        }
        
        .info-line {
            display: flex;
            align-items: baseline;
            margin-bottom: 12px;
            font-size: 12px;
            line-height: 1.5;
        }
        
        .info-line .label {
            width: 110px;
            font-weight: bold;
            flex-shrink: 0;
        }
        
        .info-line .colon {
            width: 20px;
            flex-shrink: 0;
            text-align: center;
        }
        
        .info-line .value {
            flex: 1;
            border-bottom: 1px solid #000;
            padding-left: 8px;
            min-height: 22px;
        }
        
        body.dark .info-line .value {
            border-bottom-color: var(--border);
        }
        
        /* For dark mode text in value */
        body.dark .info-line .value {
            color: var(--text-secondary);
        }
        
        /* Alternative table-based alignment for print compatibility */
        .info-table {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
        }
        
        .info-table tr {
            margin-bottom: 8px;
        }
        
        .info-table td {
            padding: 6px 5px;
            font-size: 12px;
            vertical-align: baseline;
        }
        
        .info-table td.label {
            width: 110px;
            font-weight: bold;
        }
        
        .info-table td.colon {
            width: 20px;
            text-align: center;
        }
        
        .info-table td.value {
            border-bottom: 1px solid #000;
            padding-left: 8px;
        }
        
        body.dark .info-table td.value {
            border-bottom-color: var(--border);
        }
        
        .official-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 12px;
        }
        
        .official-table th, .official-table td {
            border: 1px solid #000;
            padding: 10px 8px;
            vertical-align: top;
        }
        
        .official-table th {
            background: #f5f5f5;
            font-weight: bold;
            text-align: center;
        }
        
        body.dark .official-table th { background: var(--table-header); color: var(--text-main); }
        body.dark .official-table td { color: var(--text-secondary); }
        
        .official-table td.center { text-align: center; }
        
        .signature-section {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature-box {
            width: 45%;
            text-align: center;
            font-size: 12px;
        }
        
        .signature-box .label { font-weight: bold; margin-bottom: 5px; }
        
        .signature-box .line {
            border-top: 1px solid #000;
            margin-top: 40px;
            padding-top: 8px;
        }
        
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 5px solid;
            text-align: center;
        }
        
        body.dark .card { background: var(--bg-card); }
        
        .card-primary { border-left-color: #1a3a5c; }
        .card-success { border-left-color: var(--success); }
        .card-warning { border-left-color: var(--warning); }
        .card-danger { border-left-color: var(--danger); }
        .card-info { border-left-color: #17a2b8; }
        
        .card .number { font-size: 28px; font-weight: bold; margin-top: 10px; }
        .card small { font-size: 11px; color: #666; }
        body.dark .card small { color: var(--text-muted); }
        
        .badge-pass {
            background: var(--success);
            color: white;
            padding: 2px 10px;
            border-radius: 4px;
        }
        
        .badge-fail {
            background: var(--danger);
            color: white;
            padding: 2px 10px;
            border-radius: 4px;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            background: #fff3cd;
            border-radius: 8px;
            color: #856404;
        }
        body.dark .no-data { background: rgba(245, 158, 11, 0.2); color: var(--warning); }
        
        .date-range-info {
            font-size: 11px;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .report-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ccc;
            font-size: 10px;
            color: #666;
        }
        body.dark .report-footer { border-top-color: var(--border); color: var(--text-muted); }
        
        @media (max-width: 768px) {
            .main-content { margin-left: 0; }
            .report-selector { flex-wrap: wrap; }
            .filter-form { flex-direction: column; }
            .report-selector .form-group { min-width: 100%; }
            .btn-generate { width: 100%; }
        }
        
        @media print {
            .sidebar, .top-bar, .report-selector, .filter-bar, .action-buttons, .no-print { display: none !important; }
            .main-content { margin: 0 !important; padding: 0 !important; }
            .report-content { padding: 20px; }
            .official-table th, .official-table td { border-color: #000 !important; }
            .info-table td.value { border-bottom: 1px solid #000 !important; }
            @page { size: A4; margin: 1.5cm; }
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="top-bar">
            <h2><i class="fas fa-chart-line"></i> Official Report System</h2>
            <div class="user-info">
                <span>👤 <?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
                <a href="../actions/logout.php" class="btn-logout">🚪 Logout</a>
            </div>
        </div>
        
        <!-- Report Type Dropdown - Dalam satu baris -->
        <div class="filter-bar no-print">
            <div class="form-group">
                <label>📊 SELECT REPORT TYPE</label>
                <select id="report_type_select" name="report_type">
                    <option value="overview" <?php echo $report_type == 'overview' ? 'selected' : ''; ?>>📈 Overview Report</option>
                    <option value="attendance" <?php echo $report_type == 'attendance' ? 'selected' : ''; ?>>📋 Attendance Report</option>
                    <option value="course" <?php echo $report_type == 'course' ? 'selected' : ''; ?>>📚 Course Report</option>
                    <option value="physical" <?php echo $report_type == 'physical' ? 'selected' : ''; ?>>🏃 Physical Test Report</option>
                </select>
            </div>
            <button class="btn-generate" onclick="changeReportType()">Generate Report</button>
        </div>
        
        <!-- Filter Section (untuk semua report kecuali overview dan course) -->
        <?php if ($report_type != 'overview'): ?>
        <div class="filter-bar no-print">
            <form method="GET" action="" class="filter-form">
                <input type="hidden" name="report_type" value="<?php echo $report_type; ?>">
                
                <?php if ($report_type != 'course'): ?>
                <div class="filter-group">
                    <label>📖 COURSE</label>
                    <select name="course_id">
                        <option value="0">All Courses</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?php echo $c['CourseID']; ?>" <?php echo $course_id == $c['CourseID'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['CourseName']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="filter-group">
                    <label>👨‍🎓 CADET</label>
                    <select name="cadet_id">
                        <option value="0">All Cadets</option>
                        <?php foreach ($cadets as $c): ?>
                            <option value="<?php echo $c['UserID']; ?>" <?php echo $cadet_id == $c['UserID'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['Fullname']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <?php if ($report_type == 'attendance' || $report_type == 'physical'): ?>
                <div class="filter-group">
                    <label>📅 START DATE</label>
                    <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                </div>
                <div class="filter-group">
                    <label>📅 END DATE</label>
                    <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                </div>
                <?php endif; ?>
                
                <button type="submit" class="btn-filter">🔍 APPLY FILTER</button>
                <?php if ($course_id > 0 || $cadet_id > 0 || $start_date || $end_date): ?>
                    <a href="?report_type=<?php echo $report_type; ?>" class="btn-reset">🗑️ RESET</a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Action Buttons -->
        <div class="action-buttons no-print">
            <button onclick="window.print()" class="btn-print">🖨️ PRINT REPORT</button>
        </div>
        <?php endif; ?>
        
        <!-- ========== REPORT CONTENT ========== -->
        
        <!-- OVERVIEW REPORT -->
        <?php if ($report_type == 'overview'): ?>
        <div class="report-content">
            <div class="official-header">
                <div class="logo-container">
                    <div class="logo-left"><img src="logo-sispa.png" alt="Logo KOR SISPA" class="logo-img" onerror="this.style.display='none'"></div>
                    <div class="header-text">
                        <h1>Kor SISPA UTHM</h1>
                        <div class="address">Pejabat Kor SISPA UTHM, Kabin A13, Jalan Bravo 5, Padang Kawad,<br>Universiti Tun Hussein Onn, 86400 Parit Raja, Johor</div>
                    </div>
                    <div class="logo-right"><img src="logo-uthm.png" alt="Logo UTHM" class="logo-img" onerror="this.style.display='none'"></div>
                </div>
            </div>
            
            <div class="report-title">
                <h2>OVERVIEW REPORT</h2>
                <p>Prepared on: <?php echo date('d/m/Y h:i A'); ?></p>
            </div>
            
            <div class="dashboard-cards">
                <div class="card card-primary"><small>Total Active Users</small><div class="number"><?= $totalUsers ?></div></div>
                <div class="card card-success"><small>Total Active Cadets</small><div class="number"><?= $totalCadets ?></div></div>
                <div class="card card-warning"><small>Total Trainers</small><div class="number"><?= $totalTrainers ?></div></div>
                <div class="card card-info"><small>Total Courses</small><div class="number"><?= $totalCourses ?></div></div>
                <div class="card card-danger"><small>Attendance Records</small><div class="number"><?= $totalAttendance ?></div></div>
            </div>
            
            <div class="signature-section">
                <div class="signature-box"><div class="label">Date Generator:</div><?php echo date('d/m/Y'); ?></div>
                <div class="signature-box"><div class="label">Officer Sign:</div>_________________________</div>
            </div>
            
            <div class="report-footer">KOR SISPA UTHM - Overview Report</div>
        </div>
        <?php endif; ?>
        
        <!-- ATTENDANCE REPORT - WITH CENTER ALIGNMENT -->
        <?php if ($report_type == 'attendance'): ?>
        <div class="report-content">
            <div class="official-header">
                <div class="logo-container">
                    <div class="logo-left"><img src="logo-sispa.png" alt="Logo KOR SISPA" class="logo-img" onerror="this.style.display='none'"></div>
                    <div class="header-text">
                        <h1>Kor SISPA UTHM</h1>
                        <div class="address">Pejabat Kor SISPA UTHM, Kabin A13, Jalan Bravo 5, Padang Kawad,<br>Universiti Tun Hussein Onn, 86400 Parit Raja, Johor</div>
                    </div>
                    <div class="logo-right"><img src="logo-uthm.png" alt="Logo UTHM" class="logo-img" onerror="this.style.display='none'"></div>
                </div>
            </div>
            
            <div style="text-align: right; font-size: 11px; font-weight: bold;">PA/PKPU-03/L4</div>
            
            <div class="report-title">
                <h2>CIVIL DEFENSE TRAINING ATTENDANCE LIST</h2>
            </div>
            
            <!-- CENTERED INFO LINES WITH TABLE -->
            <table class="info-table" style="margin-left: auto; margin-right: auto; width: 60%; margin-top: 30px; margin-bottom: 50px;">
                <tr>
                    <td class="label" style="text-align: left; width: 120px;">Course Name</td>
                    <td class="colon" style="text-align: center; width: 30px;">:</td>
                    <td class="value" style="text-align: left;">
                        <?php 
                        if ($selected_course) {
                            echo htmlspecialchars($selected_course['CourseName']);
                        } else {
                            echo 'All Courses';
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td class="label" style="text-align: left;">Date</td>
                    <td class="colon" style="text-align: center;">:</td>
                    <td class="value" style="text-align: left;">
                        <?php 
                        if ($start_date && $end_date) {
                            echo date('d/m/Y', strtotime($start_date)) . ' to ' . date('d/m/Y', strtotime($end_date));
                        } elseif ($start_date) {
                            echo date('d/m/Y', strtotime($start_date));
                        } elseif ($end_date) {
                            echo 'Up to ' . date('d/m/Y', strtotime($end_date));
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td class="label" style="text-align: left;">Venue</td>
                    <td class="colon" style="text-align: center;">:</td>
                    <td class="value" style="text-align: left;">
                        <?php 
                        if ($selected_course && isset($selected_course['Location'])) {
                            echo htmlspecialchars($selected_course['Location']);
                        } else {
                            echo 'Training Center, Padang Kawad';
                        }
                        ?>
                    </td>
                </tr>
            </table>
            
            <!-- Attendance Table -->
            <?php if (empty($attendance)): ?>
                <div class="no-data">⚠️ No attendance records found.</div>
            <?php else: ?>
            <table class="official-table">
                <thead>
                    <tr>
                        <th width="5%">No.</th>
                        <th width="25%">Cabin Name</th>
                        <th width="20%">ID Number</th>
                        <th width="15%">Date</th>
                        <th width="25%">Recorded By</th>
                        <th width="10%">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($attendance as $att): ?>
                    <tr>
                        <td class="center"><?php echo $no++; ?></td>
                        <td><?php echo htmlspecialchars($att['Fullname']); ?></td>
                        <td><?php echo htmlspecialchars($att['ICNumber'] ?? '-'); ?></td>
                        <td class="center"><?php echo date('d/m/Y', strtotime($att['Date'])); ?></td>
                        <td><?php echo htmlspecialchars($att['RecordedByName'] ?? '-'); ?></td>
                        <td class="center"><?php echo htmlspecialchars($att['Status']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            
            <div class="signature-section">
                <div class="signature-box"><div class="label">Date Generator:</div><?php echo date('d/m/Y'); ?></div>
                <div class="signature-box"><div class="label">Officer Sign:</div>_________________________</div>
            </div>
            
            <div class="report-footer">PA/PKPU-03/L4 - Official Attendance Form</div>
        </div>
        <?php endif; ?>
        
        <!-- COURSE REPORT -->
        <?php if ($report_type == 'course'): ?>
        <div class="report-content">
            <div class="official-header">
                <div class="logo-container">
                    <div class="logo-left"><img src="logo-sispa-removebg.png" alt="Logo KOR SISPA" class="logo-img" onerror="this.style.display='none'"></div>
                    <div class="header-text">
                        <h1>Kor SISPA UTHM</h1>
                        <div class="address">Pejabat Kor SISPA UTHM, <br>Kabin A13, Jalan Bravo 5, Padang Kawad,<br>Universiti Tun Hussein Onn, 86400 Parit Raja, Johor</div>
                    </div>
                    <div class="logo-right"><img src="logo-uthm.png" alt="Logo UTHM" class="logo-img" onerror="this.style.display='none'"></div>
                </div>
            </div>
            
            <div class="report-title" style="margin-top: 30px; margin-bottom: 30px;"><h2>COURSE REPORT</h2></div>
            
            <?php if (empty($courseData)): ?>
                <div class="no-data">⚠️ No course records found.</div>
            <?php else: ?>
            <table class="official-table">
                <thead>
                    <tr>
                        <th width="5%">No.</th>
                        <th width="30%">Course Name</th>
                        <th width="30%">Trainer</th>
                        <th width="10%">Enrollment</th>
                        <th width="10%">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($courseData as $c): ?>
                    <tr>
                        <td class="center"><?php echo $no++; ?></td>
                        <td><?php echo htmlspecialchars($c['CourseName']); ?></td>
                        <td><?php echo htmlspecialchars($c['TrainerName'] ?? '-'); ?></td>
                        <td class="center"><?php echo $c['TotalEnrolled']; ?> students</td>
                        <td class="center"><?php echo htmlspecialchars($c['Status']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            
            <div class="signature-section">
                <div class="signature-box"><div class="label">Date Generator:</div><?php echo date('d/m/Y'); ?></div>
                <div class="signature-box"><div class="label">Officer Sign:</div>_________________________</div>
            </div>
            
            <div class="report-footer">KOR SISPA UTHM - Course Report</div>
        </div>
        <?php endif; ?>
        
        <!-- PHYSICAL TEST REPORT -->
        <?php if ($report_type == 'physical'): ?>
        <div class="report-content">
            <div class="official-header">
                <div class="logo-container">
                    <div class="logo-left"><img src="logo-sispa.png" alt="Logo KOR SISPA" class="logo-img" onerror="this.style.display='none'"></div>
                    <div class="header-text">
                        <h1>Kor SISPA UTHM</h1>
                        <div class="address">Pejabat Kor SISPA UTHM, Kabin A13, Jalan Bravo 5, Padang Kawad,<br>Universiti Tun Hussein Onn, 86400 Parit Raja, Johor</div>
                    </div>
                    <div class="logo-right"><img src="logo-uthm.png" alt="Logo UTHM" class="logo-img" onerror="this.style.display='none'"></div>
                </div>
            </div>
            
            <div class="report-title" style="margin-top: 30px; margin-bottom: 30px;"><h2>PHYSICAL TEST REPORT</h2></div>
            
            <?php if ($start_date || $end_date): ?>
            <div class="date-range-info"><strong>Date Range:</strong> <?php echo $start_date ? date('d/m/Y', strtotime($start_date)) : 'Start'; ?> - <?php echo $end_date ? date('d/m/Y', strtotime($end_date)) : 'End'; ?></div>
            <?php endif; ?>
            
            <?php if (empty($physical_data)): ?>
                <div class="no-data">⚠️ No physical test records found.</div>
            <?php else: ?>
            <table class="official-table">
                <thead>
                    <tr>
                        <th width="3%">No.</th>
                        <th width="20%">Cabin Name</th>
                        <th width="12%">Test Date</th>
                        <th width="10%">Push Ups</th>
                        <th width="10%">Sit Ups</th>
                        <th width="10%">Pull Ups</th>
                        <th width="10%">2.4km Run</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($physical_data as $p): 
                        $result = ($p['PushUps'] >= 20 && $p['SitUps'] >= 25 && $p['PullUps'] >= 5 && $p['Running24km'] <= 12) ? 'Pass' : 'Fail';
                    ?>
                    <tr>
                        <td class="center"><?php echo $no++; ?></td>
                        <td><?php echo htmlspecialchars($p['Fullname']); ?></td>
                        <td class="center"><?php echo date('d/m/Y', strtotime($p['TestDate'])); ?></td>
                        <td class="center"><?php echo $p['PushUps']; ?></td>
                        <td class="center"><?php echo $p['SitUps']; ?></td>
                        <td class="center"><?php echo $p['PullUps']; ?></td>
                        <td class="center"><?php echo $p['Running24km']; ?> min</div>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            
            <div class="signature-section">
                <div class="signature-box"><div class="label">Date Generator:</div><?php echo date('d/m/Y'); ?></div>
                <div class="signature-box"><div class="label">Officer Sign:</div>_________________________</div>
            </div>
            
            <div class="report-footer">KOR SISPA UTHM - Physical Test Report</div>
        </div>
        <?php endif; ?>
        
    </div>
</div>

<script>
function changeReportType() {
    const select = document.getElementById('report_type_select');
    const reportType = select.value;
    window.location.href = '?report_type=' + reportType;
}
</script>

</body>
</html>