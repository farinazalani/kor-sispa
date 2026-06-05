<?php
require_once '../includes/session.php';
require_once '../config/database.php';

requireRole(['Admin']);

// Get filter dates from GET parameters
$date_start = isset($_GET['date_start']) && !empty($_GET['date_start']) ? $_GET['date_start'] : null;
$date_end = isset($_GET['date_end']) && !empty($_GET['date_end']) ? $_GET['date_end'] : null;

// Get the minimum and maximum dates that have physical test data in the database
$date_range_query = "SELECT MIN(DATE(TestDate)) as min_date, MAX(DATE(TestDate)) as max_date FROM physical_performance";
$date_range = $conn->query($date_range_query)->fetch_assoc();

$min_date = $date_range['min_date'];
$max_date = $date_range['max_date'];

// Build query with date filters
$query = "SELECT pp.*, u.Fullname, rec.Fullname as RecordedByName 
          FROM physical_performance pp
          JOIN users u ON pp.UserID = u.UserID
          LEFT JOIN users rec ON pp.RecordedBy = rec.UserID
          WHERE 1=1";

$params = [];
$types = "";

// Add date filters if provided
if ($date_start && $date_end) {
    $query .= " AND DATE(pp.TestDate) BETWEEN ? AND ?";
    $params[] = $date_start;
    $params[] = $date_end;
    $types .= "ss";
} elseif ($date_start) {
    $query .= " AND DATE(pp.TestDate) >= ?";
    $params[] = $date_start;
    $types .= "s";
} elseif ($date_end) {
    $query .= " AND DATE(pp.TestDate) <= ?";
    $params[] = $date_end;
    $types .= "s";
}

$query .= " ORDER BY pp.TestDate DESC, u.Fullname ASC";

// Execute query with parameters
if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $report_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $report_data = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Physical Test Reports - Kor Sispa</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Print-specific styling */
        @media print {
            .sidebar, .top-bar, .btn-primary, .btn-logout, .filter-container, .btn-filter, .btn-reset { display: none !important; }
            .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
            .table-container { box-shadow: none !important; border: none !important; }
            table { border: 1px solid #000 !important; width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #000 !important; padding: 8px !important; font-size: 12px; }
            .score-cell { background-color: transparent !important; }
        }
        
        .report-header {
            text-align: center;
            margin-bottom: 30px;
            display: none; /* Only show in print */
        }
        
        @media print { .report-header { display: block; } }

        .score-cell { font-weight: 600; text-align: center; color: #dadada; }
        .status-badge { font-size: 0.85em; color: #666; }
        
        /* Date filter styles */
        .filter-container {
            background: #f8fafc;
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
        .btn-primary {
            background-color: #667eea;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 500;
        }
        .btn-primary:hover {
            background-color: #5563c1;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="top-bar">
                <h2>Physical Test Reports</h2>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
                    <a href="../actions/logout.php" class="btn-logout">Logout</a>
                </div>
            </div>
            
            <div class="table-container">
                <div class="report-header">
                    <h1>Kor Sispa Physical Fitness Report</h1>
                    <p>Generated on: <?php echo date('d M Y, H:i A'); ?></p>
                    <hr>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3>Consolidated Cadet Performance</h3>
                    <button onclick="window.print()" class="btn-primary">Download/Print PDF</button>
                </div>

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
                            No physical test records available
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
                            <th>Cadet Name</th>
                            <th>Test Date</th>
                            <th>Push-ups</th>
                            <th>Sit-ups</th>
                            <th>Pull-ups</th>
                            <th>Running (2.4km)</th>
                            <th>Remark</th>
                            <th>Recorded By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($report_data) > 0): ?>
                            <?php foreach ($report_data as $data): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($data['Fullname']); ?></strong></td>
                                    <td><?php echo date('d M Y', strtotime($data['TestDate'])); ?></td>
                                    <td class="score-cell"><?php echo $data['PushUps']; ?></td>
                                    <td class="score-cell"><?php echo $data['SitUps']; ?></td>
                                    <td class="score-cell"><?php echo $data['PullUps']; ?></td>
                                    <td class="score-cell"><?php echo number_format($data['Running24km'], 2); ?> min</td>
                                    <td><span class="status-badge"><?php echo htmlspecialchars($data['Remark'] ?: '-'); ?></span></td>
                                    <td><small><?php echo htmlspecialchars($data['RecordedByName'] ?? 'System'); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 30px; color: #666;">
                                    <?php if ($date_start || $date_end): ?>
                                        No physical test records found in the selected date range.
                                    <?php elseif (empty($min_date)): ?>
                                        No physical test records found in the database.
                                    <?php else: ?>
                                        No physical test records found.
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