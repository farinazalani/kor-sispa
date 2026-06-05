<?php
require_once '../includes/session.php';
require_once '../config/database.php';

requireRole(['Cadet']);

$cadet_id = $_SESSION['user_id'];

// Get filter dates from GET parameters
$date_start = isset($_GET['date_start']) && !empty($_GET['date_start']) ? $_GET['date_start'] : null;
$date_end = isset($_GET['date_end']) && !empty($_GET['date_end']) ? $_GET['date_end'] : null;

// Get the minimum and maximum dates that have attendance data for this cadet
$date_range_query = "
    SELECT MIN(DATE(Date)) as min_date, MAX(DATE(Date)) as max_date
    FROM attendance
    WHERE UserID = ?
";
$stmt_range = $conn->prepare($date_range_query);
$stmt_range->bind_param("i", $cadet_id);
$stmt_range->execute();
$date_range = $stmt_range->get_result()->fetch_assoc();

$min_date = $date_range['min_date'];
$max_date = $date_range['max_date'];

// Build query for attendance records with date filters
$attendance_query = "
    SELECT a.*, c.CourseName 
    FROM attendance a 
    JOIN courses c ON a.CourseID = c.CourseID 
    WHERE a.UserID = ?
";

$params = [$cadet_id];
$types = "i";

// Add date filters if provided
if ($date_start && $date_end) {
    $attendance_query .= " AND DATE(a.Date) BETWEEN ? AND ?";
    $params[] = $date_start;
    $params[] = $date_end;
    $types .= "ss";
} elseif ($date_start) {
    $attendance_query .= " AND DATE(a.Date) >= ?";
    $params[] = $date_start;
    $types .= "s";
} elseif ($date_end) {
    $attendance_query .= " AND DATE(a.Date) <= ?";
    $params[] = $date_end;
    $types .= "s";
}

$attendance_query .= " ORDER BY a.Date DESC";

$stmt = $conn->prepare($attendance_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$attendance = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate statistics (always based on ALL records, not just filtered)
$totalPresent = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE UserID = $cadet_id AND Status = 'Present'")->fetch_assoc()['count'];
$totalAbsent = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE UserID = $cadet_id AND Status = 'Absent'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance - Kor Sispa</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Date filter styles */
        .filter-container {
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .filter-group label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #334155;
        }
        .filter-group input {
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 0.9rem;
        }
        .filter-group input:disabled {
            background-color: #f1f5f9;
            cursor: not-allowed;
        }
        .btn-filter {
            background-color: #3b82f6;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }
        .btn-filter:hover {
            background-color: #2563eb;
        }
        .btn-filter:disabled {
            background-color: #94a3b8;
            cursor: not-allowed;
        }
        .btn-reset {
            background-color: #64748b;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
        }
        .btn-reset:hover {
            background-color: #475569;
        }
        .filter-summary {
            font-size: 0.85rem;
            color: #475569;
            margin-left: auto;
            display: flex;
            align-items: center;
        }
        .date-hint {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 4px;
        }
        .no-data-message {
            font-size: 0.85rem;
            color: #dc2626;
            margin-left: 10px;
        }
        
        /* Card styles if not already defined */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .card {
            background: #1f2933;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .card-success {
            border-left: 4px solid #10b981;
        }
        .card-danger {
            border-left: 4px solid #ef4444;
        }
        .card h3 {
            margin: 0 0 10px 0;
            font-size: 1rem;
            color: #666;
        }
        .card .number {
            font-size: 2rem;
            font-weight: bold;
        }
        .card-success .number {
            color: #10b981;
        }
        .card-danger .number {
            color: #ef4444;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        <div class="main-content">
            <div class="top-bar">
                <h2>My Attendance</h2>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
                    <a href="../actions/logout.php" class="btn-logout">Logout</a>
                </div>
            </div>
            
            <div class="dashboard-cards">
                <div class="card card-success">
                    <h3>Total Present</h3>
                    <div class="number"><?php echo $totalPresent; ?></div>
                </div>
                
                <div class="card card-danger">
                    <h3>Total Absent</h3>
                    <div class="number"><?php echo $totalAbsent; ?></div>
                </div>
            </div>
            
            <div class="table-container">
                <h3 style="margin-bottom: 20px;">Attendance Records</h3>
                
                <!-- Date Filter Form -->
                <form method="GET" action="" class="filter-container" id="filterForm">
                    <div class="filter-group">
                        <?php if ($min_date && $max_date): ?>
                            <div class="date-hint">
                                Range: <?php echo date('d M Y', strtotime($min_date)); ?> - <?php echo date('d M Y', strtotime($max_date)); ?>
                            </div>
                        <?php endif; ?>
                        <label for="date_start">Start Date</label>
                        <input type="date" id="date_start" name="date_start" 
                               value="<?php echo htmlspecialchars($date_start ?? ''); ?>"
                               min="<?php echo $min_date; ?>" 
                               max="<?php echo $max_date; ?>">  
                    </div>
                    <div class="filter-group">
                        <label for="date_end">End Date</label>
                        <input type="date" id="date_end" name="date_end" 
                               value="<?php echo htmlspecialchars($date_end ?? ''); ?>"
                               min="<?php echo $min_date; ?>" 
                               max="<?php echo $max_date; ?>">
                    </div>
                    <button type="submit" class="btn-filter" <?php echo empty($min_date) ? 'disabled' : ''; ?>>Filter</button>
                    <?php if ($date_start || $date_end): ?>
                        <a href="?" class="btn-reset">Reset</a>
                    <?php endif; ?>
                    <?php if ($date_start || $date_end): ?>
                        <div class="filter-summary">
                            Showing records 
                            <?php if ($date_start && $date_end): ?>
                                from <?php echo date('d M Y', strtotime($date_start)); ?> to <?php echo date('d M Y', strtotime($date_end)); ?>
                            <?php elseif ($date_start): ?>
                                from <?php echo date('d M Y', strtotime($date_start)); ?> onwards
                            <?php elseif ($date_end): ?>
                                up to <?php echo date('d M Y', strtotime($date_end)); ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (empty($min_date)): ?>
                        <div class="no-data-message">
                            No attendance records available
                        </div>
                    <?php endif; ?>
                </form>

                <script>
                    // Get min and max dates from PHP
                    const minDate = '<?php echo $min_date; ?>';
                    const maxDate = '<?php echo $max_date; ?>';
                    
                    // Function to set date restrictions and handle logical constraints
                    function setupDateFilters() {
                        const startDateInput = document.getElementById('date_start');
                        const endDateInput = document.getElementById('date_end');
                        
                        if (!startDateInput || !endDateInput) return;
                        
                        // Set initial min/max attributes
                        if (minDate && maxDate) {
                            startDateInput.min = minDate;
                            startDateInput.max = maxDate;
                            endDateInput.min = minDate;
                            endDateInput.max = maxDate;
                        }
                        
                        // When start date changes, update end date's min
                        startDateInput.addEventListener('change', function() {
                            const startDate = this.value;
                            if (startDate) {
                                endDateInput.min = startDate;
                            } else {
                                endDateInput.min = minDate;
                            }
                            
                            // If end date is less than new start date, clear it
                            if (endDateInput.value && startDate && endDateInput.value < startDate) {
                                endDateInput.value = '';
                            }
                        });
                        
                        // When end date changes, update start date's max
                        endDateInput.addEventListener('change', function() {
                            const endDate = this.value;
                            if (endDate) {
                                startDateInput.max = endDate;
                            } else {
                                startDateInput.max = maxDate;
                            }
                            
                            // If start date is greater than new end date, clear it
                            if (startDateInput.value && endDate && startDateInput.value > endDate) {
                                startDateInput.value = '';
                            }
                        });
                    }
                    
                    // Initialize date filters if there are dates available
                    if (minDate && maxDate) {
                        setupDateFilters();
                    }
                </script>

                <table>
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($attendance) > 0): ?>
                            <?php foreach ($attendance as $att): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($att['CourseName']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($att['Date'])); ?></td>
                                    <td style="color: <?php echo $att['Status'] == 'Present' ? 'green' : 'red'; ?>; font-weight: 500;">
                                        <?php echo $att['Status']; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($att['Remarks'] ?: '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 30px; color: #666;">
                                    <?php if ($date_start || $date_end): ?>
                                        No attendance records found in the selected date range.
                                    <?php elseif (empty($min_date)): ?>
                                        No attendance records found yet.
                                    <?php else: ?>
                                        No attendance records found.
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>