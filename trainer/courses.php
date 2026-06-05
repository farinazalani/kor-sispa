<?php
require_once '../includes/session.php';
require_once '../config/database.php';

requireRole(['Trainer']);

$trainer_id = $_SESSION['user_id'];

$query = "SELECT c.*, 
    (SELECT COUNT(*) FROM enrollments e WHERE e.CourseID = c.CourseID AND e.Status = 'Enrolled') as EnrolledCount
    FROM courses c 
    WHERE c.TrainerID = $trainer_id 
    ORDER BY c.CourseName ASC";

$courses = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses - Kor Sispa</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; display: inline-block; }
        .status-active { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .status-inactive { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        
        .btn-sm { padding: 5px 12px; font-size: 0.75rem; border-radius: 4px; border: none; cursor: pointer; margin: 2px; }
        .btn-primary { background-color: #3b82f6; color: white; }
        .btn-warning { background-color: #f59e0b; color: white; }
        .btn-danger { background-color: #ef4444; color: white; }
        .btn-info { background-color: #06b6d4; color: white; }
        .btn-secondary { background-color: #6b7280; color: white; }
        
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fff; margin: 5% auto; padding: 0; width: 480px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-width: 90%; max-height: 90%; overflow-y: auto; }
        .modal-header { padding: 15px 20px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 20px; }
        .modal-footer { padding: 15px 20px; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 10px; }
        .close { font-size: 28px; cursor: pointer; color: #6b7280; line-height: 1; }
        .close:hover { color: #000; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 4px; }
        .form-group textarea { resize: vertical; }
        
        .alert { padding: 12px 15px; border-radius: 4px; margin-bottom: 20px; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        
        @media (prefers-color-scheme: dark) {
            .modal-content { background-color: #1f2937; color: #f3f4f6; }
            .modal-header { border-bottom-color: #374151; }
            .modal-footer { border-top-color: #374151; }
            .form-group input, .form-group textarea, .form-group select { background-color: #374151; border-color: #4b5563; color: #f3f4f6; }
            .close:hover { color: #fff; }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?> 
        <div class="main-content">
            <div class="top-bar">
                <h2>Manage Training Courses</h2>
                <div class="user-info">
                    <span>Trainer: <strong><?php echo htmlspecialchars($_SESSION['fullname']); ?></strong></span>
                    <a href="../actions/logout.php" class="btn-logout">Logout</a>
                </div>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <div style="margin-bottom: 20px;">
                <button class="btn btn-primary" onclick="openAddModal()">+ Create New Course</button>
            </div>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Course Name</th>
                            <th>Description</th>
                            <th>Enrolled</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($courses) > 0): ?>
                            <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($course['CourseName']); ?></strong></td>
                                    <td><?php echo htmlspecialchars(substr($course['Description'] ?? '', 0, 60)) . (strlen($course['Description'] ?? '') > 60 ? '...' : ''); ?></td>
                                    <td><?php echo $course['EnrolledCount']; ?> Cadets</div>
                                    <td>
                                        <span class="status-badge <?php echo $course['Status'] == 'Active' ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $course['Status']; ?>
                                        </span>
                                    </div>
                                    <td>
                                        <a href="schedule.php?course_id=<?php echo $course['CourseID']; ?>" class="btn-sm btn-info" style="text-decoration:none; display:inline-block;">📅 View Schedule</a>
                                        <button class="btn-sm btn-warning" onclick='editCourse(<?php echo json_encode($course); ?>)'>Edit</button>
                                        <button class="btn-sm btn-danger" onclick="deleteCourse(<?php echo $course['CourseID']; ?>)">Delete</button>
                                    </div>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center; padding:30px;">No courses found. Create one to begin.</div></tr>
                        <?php endif; ?>
                    </tbody>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Course Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Course</h3>
                <span class="close" onclick="closeAddModal()">&times;</span>
            </div>
            <form action="../actions/course-action-trainer.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label>Course Name *</label>
                        <input type="text" name="course_name" required placeholder="e.g. Navigation & Map Reading">
                    </div>
                    <div class="form-group">
                        <label>Description *</label>
                        <textarea name="description" rows="4" required placeholder="Course description..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Course</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Course Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Course</h3>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <form action="../actions/course-action-trainer.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="course_id" id="edit_course_id">
                    <div class="form-group">
                        <label>Course Name *</label>
                        <input type="text" name="course_name" id="edit_course_name" required>
                    </div>
                    <div class="form-group">
                        <label>Description *</label>
                        <textarea name="description" id="edit_description" rows="4" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="edit_status">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Course</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openAddModal() { 
            document.getElementById('addModal').style.display = 'block';
        }
        
        function closeAddModal() { 
            document.getElementById('addModal').style.display = 'none';
            const form = document.querySelector('#addModal form');
            if (form) form.reset();
        }
        
        function closeEditModal() { 
            document.getElementById('editModal').style.display = 'none';
        }
        
        function editCourse(course) {
            document.getElementById('edit_course_id').value = course.CourseID;
            document.getElementById('edit_course_name').value = course.CourseName;
            document.getElementById('edit_description').value = course.Description || '';
            document.getElementById('edit_status').value = course.Status;
            document.getElementById('editModal').style.display = 'block';
        }
        
        function deleteCourse(id) {
            if (confirm('Delete this course? All schedules will also be deleted.')) {
                window.location.href = '../actions/course-action-trainer.php?action=delete&course_id=' + id;
            }
        }
        
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>