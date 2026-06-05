<?php
require_once '../includes/session.php';
require_once '../config/database.php';

requireRole(['Trainer']);

// Get search parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$priority_filter = isset($_GET['priority']) ? $_GET['priority'] : '';
$show_expired = isset($_GET['show_expired']) ? $_GET['show_expired'] : '';

// Build WHERE clause
$where_clause = "a.PostedBy = {$_SESSION['user_id']}";
$params = [];
$types = "";

// Only show non-expired announcements by default
if ($show_expired !== 'yes') {
    $where_clause .= " AND (a.ExpiryDate IS NULL OR a.ExpiryDate >= CURDATE())";
}

// Priority filter
if (!empty($priority_filter)) {
    $where_clause .= " AND a.Priority = ?";
    $params[] = $priority_filter;
    $types .= "s";
}

// Search functionality
if (!empty($search)) {
    $where_clause .= " AND (a.Title LIKE ? OR a.Description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

// Get announcements posted by this trainer
$sql = "SELECT a.*, c.CourseName, u.Fullname 
    FROM announcements a 
    LEFT JOIN courses c ON a.CourseID = c.CourseID 
    JOIN users u ON a.PostedBy = u.UserID 
    WHERE $where_clause
    ORDER BY FIELD(a.Priority, 'Urgent', 'Event', 'Normal'), a.PostDate DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$announcements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get trainer's courses for dropdown
$courses = $conn->query("SELECT * FROM courses WHERE TrainerID = {$_SESSION['user_id']} AND Status = 'Active' ORDER BY CourseName")->fetch_all(MYSQLI_ASSOC);

// Count expired announcements
$expired_count = $conn->query("SELECT COUNT(*) as cnt FROM announcements WHERE PostedBy = {$_SESSION['user_id']} AND ExpiryDate IS NOT NULL AND ExpiryDate < CURDATE()")->fetch_assoc();
$expired_count = $expired_count['cnt'];

// Check if there are urgent announcements
$has_urgent = false;
foreach ($announcements as $ann) {
    if ($ann['Priority'] == 'Urgent') {
        $has_urgent = true;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Announcements - Kor Sispa</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .filter-bar {
            background: #f8f9fa;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
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
            color: #666;
        }
        
        .filter-group select, .filter-group input {
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .filter-group button {
            padding: 8px 16px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .filter-group button:hover {
            background: #2980b9;
        }
        
        .filter-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 8px 16px;
            border: 1px solid #e0e0e0;
            background: white;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            color: #333;
            font-size: 14px;
        }
        
        .filter-btn.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .filter-btn:hover {
            background: #f0f0f0;
        }
        
        .search-box {
            display: flex;
            gap: 10px;
            margin-left: auto;
        }
        
        .search-box input {
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            width: 250px;
        }
        
        .search-box button {
            padding: 8px 16px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .clear-search {
            background: #6c757d !important;
        }
        
        .result-info {
            margin-bottom: 15px;
            color: #666;
            font-size: 14px;
        }
        
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .priority-guide {
            font-size: 13px;
            background: #f0f0f0;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 1px solid #e0e0e0;
        }
        
        .priority-guide-content {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
        }
        
        .priority-guide-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .priority-dot {
            width: 12px;
            height: 12px;
            border-radius: 3px;
        }
        
        .priority-dot.urgent { background: #dc3545; }
        .priority-dot.event { background: #28a745; }
        .priority-dot.normal { background: #6c757d; }
        
        .priority-order {
            margin-left: auto;
            font-size: 12px;
            color: #666;
        }
        
        .urgent-warning {
            background: #fff3cd;
            border-left: 4px solid #dc3545;
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-size: 13px;
            color: #856404;
        }
        
        .urgent-warning strong {
            color: #dc3545;
        }
        
        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
            width: 100%;
        }
        
        .filter-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
            min-width: 150px;
        }
        
        .filter-item label {
            font-size: 12px;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .filter-item select {
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 13px;
            background: white;
            cursor: pointer;
        }
        
        .search-container {
            display: flex;
            gap: 10px;
            margin-left: auto;
            align-items: flex-end;
        }
        
        .search-container input {
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            width: 250px;
        }
        
        .search-container button {
            padding: 8px 20px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .search-container button:hover {
            background: #218838;
        }
        
        .clear-filter {
            padding: 8px 16px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .clear-filter:hover {
            background: #5a6268;
        }
        
        /* New styles for expiry */
        .expired-badge {
            background: #dc3545;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            margin-left: 10px;
        }
        
        .expired-notice {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-size: 13px;
            color: #721c24;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .expired-notice a {
            color: #dc3545;
            text-decoration: underline;
        }
        
        .show-expired-btn {
            background: #6c757d;
            color: white;
            padding: 5px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
        }
        
        .show-expired-btn:hover {
            background: #5a6268;
        }
        
        .expired-overlay {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #dc3545;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .event-date-badge {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            display: inline-block;
        }
        
        .card {
            position: relative;
        }
        
        @media (max-width: 768px) {
            .filter-bar {
                flex-direction: column;
            }
            
            .filter-row {
                flex-direction: column;
            }
            
            .search-container {
                margin-left: 0;
                width: 100%;
            }
            
            .search-container input {
                flex: 1;
            }
            
            .dashboard-cards {
                grid-template-columns: 1fr;
            }
            
            .priority-guide-content {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .priority-order {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="top-bar">
                <h2>My Announcements</h2>
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
            
            <!-- Expired Notice -->
            <?php if ($expired_count > 0 && $show_expired !== 'yes'): ?>
            <div class="expired-notice">
                <span><i class="fas fa-clock"></i> <strong><?php echo $expired_count; ?> announcement(s)</strong> have expired and are hidden.</span>
                <a href="?show_expired=yes&<?php echo http_build_query(array_filter(['priority' => $priority_filter, 'search' => $search])); ?>" class="show-expired-btn">
                    <i class="fas fa-eye"></i> Show Expired
                </a>
            </div>
            <?php endif; ?>
            
            <?php if ($show_expired === 'yes'): ?>
            <div class="expired-notice" style="background: #e2e3e5; border-left-color: #6c757d; color: #383d41;">
                <span><i class="fas fa-eye"></i> Showing expired announcements as well.</span>
                <a href="?<?php echo http_build_query(array_filter(['priority' => $priority_filter, 'search' => $search])); ?>" class="show-expired-btn" style="background: #28a745;">
                    <i class="fas fa-eye-slash"></i> Hide Expired
                </a>
            </div>
            <?php endif; ?>
            
            <div style="margin-bottom: 20px;">
                <button class="btn btn-primary" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Add New Announcement
                </button>
            </div>
            
            <!-- Result Info -->
            <div class="result-info">
                <i class="fas fa-chart-line"></i> Showing <?php echo count($announcements); ?> announcement(s)
                <?php if ($priority_filter): ?>
                    with priority: <strong><?php echo $priority_filter; ?></strong>
                <?php endif; ?>
                <?php if (!empty($search)): ?>
                    matching "<strong><?php echo htmlspecialchars($search); ?></strong>"
                <?php endif; ?>
                <?php if ($show_expired === 'yes'): ?>
                    <span style="color: #dc3545;">(including expired)</span>
                <?php endif; ?>
            </div>
            
            <!-- Announcements Grid -->
            <div class="dashboard-cards">
                <?php if (empty($announcements)): ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 50px; background: #f8f9fa; border-radius: 8px;">
                        <i class="fas fa-inbox" style="font-size: 3rem; color: #999;"></i>
                        <p style="margin-top: 10px; color: #666;">No announcements found.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($announcements as $announcement): 
                        $is_expired = ($announcement['ExpiryDate'] && strtotime($announcement['ExpiryDate']) < time());
                        $show_event_date = ($announcement['Priority'] == 'Event' && $announcement['EventDate']);
                        $post_date = new DateTime($announcement['PostDate']);
                        $expiry_date = $announcement['ExpiryDate'] ? new DateTime($announcement['ExpiryDate']) : null;
                        $post_date_str = $post_date->format('d M Y');
                        $expiry_str = $expiry_date ? $expiry_date->format('d M Y') : '-';
                    ?>
                        <div class="card" style="<?php echo $announcement['Priority'] === 'Urgent' ? 'border-left: 4px solid #dc3545;' : ($announcement['Priority'] === 'Event' ? 'border-left: 4px solid #28a745;' : ''); ?>">
                            <?php if ($is_expired): ?>
                                <div class="expired-overlay">EXPIRED</div>
                            <?php endif; ?>
                            <h3><?php echo htmlspecialchars($announcement['Title']); ?></h3>
                            <p><?php echo nl2br(htmlspecialchars($announcement['Description'])); ?></p>
                            <hr>
                            <small>
                                <strong>Posted by:</strong> <?php echo htmlspecialchars($announcement['Fullname'] ?? 'Unknown'); ?><br>
                                <strong>Post Date:</strong> <?php echo $post_date_str; ?><br>
                                <?php if ($show_event_date): ?>
                                    <strong>Event Date:</strong> <span class="event-date-badge"><i class="fas fa-calendar-day"></i> <?php echo date('d M Y', strtotime($announcement['EventDate'])); ?></span><br>
                                <?php endif; ?>
                                <strong>Expiry Date:</strong> <?php echo $expiry_str; ?>
                                <?php if ($is_expired): ?>
                                    <span class="expired-badge">Expired</span>
                                <?php endif; ?>
                                <br>
                                <strong>Priority:</strong> 
                                <span style="<?php echo $announcement['Priority'] === 'Urgent' ? 'color: #dc3545; font-weight: bold;' : ($announcement['Priority'] === 'Event' ? 'color: #28a745;' : ''); ?>">
                                    <?php echo $announcement['Priority']; ?>
                                </span><br>
                                <?php if ($announcement['CourseName']): ?>
                                    <strong>Course:</strong> <?php echo htmlspecialchars($announcement['CourseName']); ?>
                                <?php else: ?>
                                    <strong>Course:</strong> All Courses
                                <?php endif; ?>
                            </small>
                            <div style="margin-top: 10px;">
                                <button class="btn-sm btn-warning" onclick='editAnnouncement(<?php echo json_encode($announcement); ?>)'>Edit</button>
                                <button class="btn-sm btn-danger" onclick="deleteAnnouncement(<?php echo $announcement['AnnouncementID']; ?>)">Delete</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Add Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Announcement</h3>
                <span class="close" onclick="closeAddModal()">&times;</span>
            </div>
            <form action="../actions/announcement-action.php" method="POST" id="addForm">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" name="title" required>
                </div>
                
                <div class="form-group">
                    <label>Description *</label>
                    <textarea name="description" rows="5" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>Priority *</label>
                    <select name="priority" id="priority_select" required>
                        <option value="Normal">Normal (Expires after 15 days)</option>
                        <option value="Urgent">Urgent (Expires after 3 days)</option>
                        <option value="Event">Event (Expires 1 day after event date)</option>
                    </select>
                </div>
                
                <div class="form-group" id="event_date_group" style="display: none;">
                    <label>Event Date * (for Event priority only)</label>
                    <input type="date" name="event_date" id="event_date">
                    <small style="color: #666; font-size: 11px;">Announcement will expire 1 day after this event date.</small>
                </div>
                
                <div class="form-group">
                    <label>Course (Optional - leave empty for all courses)</label>
                    <select name="course_id">
                        <option value="">All Courses</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['CourseID']; ?>">
                                <?php echo htmlspecialchars($course['CourseName']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Post Announcement</button>
            </form>
        </div>
    </div>
    
    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Announcement</h3>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <form action="../actions/announcement-action.php" method="POST" id="editForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="announcement_id" id="edit_id">
                
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" name="title" id="edit_title" required>
                </div>
                
                <div class="form-group">
                    <label>Description *</label>
                    <textarea name="description" id="edit_description" rows="5" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>Priority *</label>
                    <select name="priority" id="edit_priority" required>
                        <option value="Normal">Normal (Expires after 15 days)</option>
                        <option value="Urgent">Urgent (Expires after 3 days)</option>
                        <option value="Event">Event (Expires 1 day after event date)</option>
                    </select>
                </div>
                
                <div class="form-group" id="edit_event_date_group" style="display: none;">
                    <label>Event Date * (for Event priority only)</label>
                    <input type="date" name="event_date" id="edit_event_date">
                    <small style="color: #666; font-size: 11px;">Announcement will expire 1 day after this event date.</small>
                </div>
                
                <div class="form-group">
                    <label>Course (Optional)</label>
                    <select name="course_id" id="edit_course_id">
                        <option value="">All Courses</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['CourseID']; ?>">
                                <?php echo htmlspecialchars($course['CourseName']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Update Announcement</button>
            </form>
        </div>
    </div>
    
    <script>
        // Show/hide event date field based on priority selection for ADD modal
        const prioritySelect = document.getElementById('priority_select');
        if (prioritySelect) {
            prioritySelect.addEventListener('change', function() {
                const eventGroup = document.getElementById('event_date_group');
                if (this.value === 'Event') {
                    eventGroup.style.display = 'block';
                    document.getElementById('event_date').required = true;
                } else {
                    eventGroup.style.display = 'none';
                    document.getElementById('event_date').required = false;
                    document.getElementById('event_date').value = '';
                }
            });
        }
        
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
            document.getElementById('event_date_group').style.display = 'none';
            document.getElementById('priority_select').value = 'Normal';
            document.getElementById('addForm').reset();
        }
        
        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }
        
        function editAnnouncement(announcement) {
            document.getElementById('edit_id').value = announcement.AnnouncementID;
            document.getElementById('edit_title').value = announcement.Title;
            document.getElementById('edit_description').value = announcement.Description;
            document.getElementById('edit_priority').value = announcement.Priority;
            document.getElementById('edit_course_id').value = announcement.CourseID || '';
            
            if (announcement.Priority === 'Event') {
                document.getElementById('edit_event_date_group').style.display = 'block';
                document.getElementById('edit_event_date').value = announcement.EventDate || '';
            } else {
                document.getElementById('edit_event_date_group').style.display = 'none';
            }
            
            document.getElementById('editModal').style.display = 'block';
        }
        
        // Show/hide event date field for EDIT modal
        const editPriority = document.getElementById('edit_priority');
        if (editPriority) {
            editPriority.addEventListener('change', function() {
                const eventGroup = document.getElementById('edit_event_date_group');
                if (this.value === 'Event') {
                    eventGroup.style.display = 'block';
                    document.getElementById('edit_event_date').required = true;
                } else {
                    eventGroup.style.display = 'none';
                    document.getElementById('edit_event_date').required = false;
                    document.getElementById('edit_event_date').value = '';
                }
            });
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        function deleteAnnouncement(id) {
            if (confirm('Are you sure you want to delete this announcement?')) {
                window.location.href = '../actions/announcement-action.php?action=delete&announcement_id=' + id;
            }
        }
        
        function filterByPriority() {
            const priority = document.getElementById('priorityFilter').value;
            const url = new URL(window.location.href);
            if (priority) {
                url.searchParams.set('priority', priority);
            } else {
                url.searchParams.delete('priority');
            }
            window.location.href = url.toString();
        }
        
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>