<?php
require_once '../includes/session.php';
require_once '../config/database.php';

requireRole(['Trainer']);

$trainer_id = $_SESSION['user_id'];

// Get all cadets enrolled in trainer's courses
$cadets = $conn->query("SELECT DISTINCT u.UserID, u.Fullname
    FROM users u
    JOIN enrollments e ON u.UserID = e.CadetID
    JOIN courses c ON e.CourseID = c.CourseID
    WHERE c.TrainerID = $trainer_id AND u.Role = 'Cadet'
    ORDER BY u.Fullname")->fetch_all(MYSQLI_ASSOC);

// Get updated physical performance records
$performances = $conn->query("SELECT pp.*, u.Fullname, rec.Fullname as RecordedByName
    FROM physical_performance pp
    JOIN users u ON pp.UserID = u.UserID
    LEFT JOIN users rec ON pp.RecordedBy = rec.UserID
    WHERE pp.RecordedBy = $trainer_id OR pp.UserID IN (
        SELECT DISTINCT e.CadetID FROM enrollments e
        JOIN courses c ON e.CourseID = c.CourseID
        WHERE c.TrainerID = $trainer_id
    )
    ORDER BY pp.TestDate DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Physical Test Management - Kor Sispa</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Error message styling */
        .date-error {
            color: #dc3545;
            font-size: 0.8rem;
            margin-top: 4px;
            display: none;
        }
        .form-group.has-error input {
            border-color: #dc3545;
            background-color: #6666;
        }
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        @media (prefers-color-scheme: dark) {
            .date-error {
                color: #f87171;
            }
            .form-group.has-error input {
                border-color: #f87171;
                background-color: rgba(220, 38, 38, 0.1);
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        <div class="main-content">
            <div class="top-bar">
                <h2>Physical Test Management</h2>
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

            <div style="margin-bottom: 20px;">
                <button class="btn btn-primary" onclick="openAddModal()">Record New Session</button>
            </div>

            <div class="table-container">
                <h3 style="margin-bottom: 20px;">Consolidated Test Records</h3>
                 <table>
                    <thead>
                        <tr>
                            <th>Cadet Name</th>
                            <th>Date</th>
                            <th>Push-ups</th>
                            <th>Sit-ups</th>
                            <th>Pull-ups</th>
                            <th>Running</th>
                            <th>Remark</th>
                            <th>Recorded By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($performances) > 0): ?>
                            <?php foreach ($performances as $p): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($p['Fullname']); ?></strong></td>
                                    <td><?php echo date('d M Y', strtotime($p['TestDate'])); ?></td>
                                    <td><?php echo $p['PushUps']; ?></td>
                                    <td><?php echo $p['SitUps']; ?></td>
                                    <td><?php echo $p['PullUps']; ?></td>
                                    <td><?php echo number_format($p['Running24km'], 2); ?> min</td>
                                    <td><small><?php echo !empty($p['Remark']) ? htmlspecialchars($p['Remark']) : '<em style="color:#999;">N/A</em>'; ?></small></td>
                                    <td><small><?php echo htmlspecialchars($p['RecordedByName']); ?></small></td>
                                    <td>
                                        <button class="btn-sm btn-warning" onclick='editPerformance(<?php echo json_encode($p); ?>)'>Edit</button>
                                        <button class="btn-sm btn-danger" onclick="deletePerformance(<?php echo $p['PerformanceID']; ?>)">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="9" style="text-align: center;">No session records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Record Physical Test Session</h3>
                <span class="close" onclick="closeAddModal()">&times;</span>
            </div>
            <form action="../actions/physical-test-action.php" method="POST" id="addForm" onsubmit="return validateAddDate()">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label>Select Cadet</label>
                    <select name="user_id" required>
                        <option value="">-- Select Cadet --</option>
                        <?php foreach ($cadets as $cadet): ?>
                            <option value="<?php echo $cadet['UserID']; ?>"><?php echo htmlspecialchars($cadet['Fullname']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" id="add-date-group">
                    <label>Test Date</label>
                    <input type="date" name="test_date" id="add_test_date" value="<?php echo date('Y-m-d'); ?>" required>
                    <div class="date-error" id="add-date-error">Test date cannot be in the past. Please select today or a future date.</div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label>Push-ups</label>
                        <input type="number" name="push_ups" value="0" required min="0">
                    </div>
                    <div class="form-group">
                        <label>Sit-ups</label>
                        <input type="number" name="sit_ups" value="0" required min="0">
                    </div>
                    <div class="form-group">
                        <label>Pull-ups</label>
                        <input type="number" name="pull_ups" value="0" required min="0">
                    </div>
                    <div class="form-group">
                        <label>Running (2.4km) Mins</label>
                        <input type="number" step="0.01" name="running_24km" placeholder="e.g. 12.50" required min="0">
                    </div>
                </div>

                <div class="form-group">
                    <label>Remark</label>
                    <textarea name="remark" placeholder="General performance notes" rows="2" style="width: 100%; border: 1px solid #ddd; padding: 8px; border-radius: 4px;"></textarea>
                </div>

                <button type="submit" class="btn btn-primary" id="addSubmitBtn">Save Session Record</button>
            </form>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Performance Record</h3>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <form action="../actions/physical-test-action.php" method="POST" id="editForm" onsubmit="return validateEditDate()">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="performance_id" id="edit_performance_id">
                
                <div class="form-group" id="edit-date-group">
                    <label>Test Date</label>
                    <input type="date" name="test_date" id="edit_test_date" required>
                    <div class="date-error" id="edit-date-error">Test date cannot be in the past. Please select today or a future date.</div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label>Push-ups</label>
                        <input type="number" name="push_ups" id="edit_push_ups" required min="0">
                    </div>
                    <div class="form-group">
                        <label>Sit-ups</label>
                        <input type="number" name="sit_ups" id="edit_sit_ups" required min="0">
                    </div>
                    <div class="form-group">
                        <label>Pull-ups</label>
                        <input type="number" name="pull_ups" id="edit_pull_ups" required min="0">
                    </div>
                    <div class="form-group">
                        <label>Running (2.4km)</label>
                        <input type="number" step="0.01" name="running_24km" id="edit_running" required min="0">
                    </div>
                </div>

                <div class="form-group">
                    <label>Remark</label>
                    <textarea name="remark" id="edit_remark" rows="2" style="width: 100%; border: 1px solid #ddd; padding: 8px; border-radius: 4px;"></textarea>
                </div>

                <button type="submit" class="btn btn-primary" id="editSubmitBtn">Update Record</button>
            </form>
        </div>
    </div>

    <script>
        // Get today's date in YYYY-MM-DD format
        function getTodayDate() {
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const day = String(today.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        // Validate date (cannot be past)
        function isDateValid(dateString) {
            if (!dateString) return false;
            const today = getTodayDate();
            return dateString >= today;
        }

        // Set min date attribute on date inputs
        function setMinDate() {
            const today = getTodayDate();
            const addDateInput = document.getElementById('add_test_date');
            const editDateInput = document.getElementById('edit_test_date');
            
            if (addDateInput) {
                addDateInput.setAttribute('min', today);
            }
            if (editDateInput) {
                editDateInput.setAttribute('min', today);
            }
        }

        // Validate Add Form Date
        function validateAddDate() {
            const dateInput = document.getElementById('add_test_date');
            const errorSpan = document.getElementById('add-date-error');
            const submitBtn = document.getElementById('addSubmitBtn');
            
            if (!dateInput.value) {
                errorSpan.style.display = 'block';
                errorSpan.textContent = 'Please select a test date.';
                dateInput.classList.add('error');
                document.getElementById('add-date-group').classList.add('has-error');
                if(submitBtn) submitBtn.disabled = true;
                return false;
            }
            
            if (!isDateValid(dateInput.value)) {
                errorSpan.style.display = 'block';
                errorSpan.textContent = 'Test date cannot be in the past. Please select today or a future date.';
                dateInput.classList.add('error');
                document.getElementById('add-date-group').classList.add('has-error');
                if(submitBtn) submitBtn.disabled = true;
                return false;
            }
            
            errorSpan.style.display = 'none';
            dateInput.classList.remove('error');
            document.getElementById('add-date-group').classList.remove('has-error');
            if(submitBtn) submitBtn.disabled = false;
            return true;
        }

        // Validate Edit Form Date
        function validateEditDate() {
            const dateInput = document.getElementById('edit_test_date');
            const errorSpan = document.getElementById('edit-date-error');
            const submitBtn = document.getElementById('editSubmitBtn');
            
            if (!dateInput.value) {
                errorSpan.style.display = 'block';
                errorSpan.textContent = 'Please select a test date.';
                dateInput.classList.add('error');
                document.getElementById('edit-date-group').classList.add('has-error');
                if(submitBtn) submitBtn.disabled = true;
                return false;
            }
            
            if (!isDateValid(dateInput.value)) {
                errorSpan.style.display = 'block';
                errorSpan.textContent = 'Test date cannot be in the past. Please select today or a future date.';
                dateInput.classList.add('error');
                document.getElementById('edit-date-group').classList.add('has-error');
                if(submitBtn) submitBtn.disabled = true;
                return false;
            }
            
            errorSpan.style.display = 'none';
            dateInput.classList.remove('error');
            document.getElementById('edit-date-group').classList.remove('has-error');
            if(submitBtn) submitBtn.disabled = false;
            return true;
        }

        // Real-time validation for add form date
        function attachAddDateValidation() {
            const dateInput = document.getElementById('add_test_date');
            if (dateInput) {
                dateInput.addEventListener('change', validateAddDate);
                dateInput.addEventListener('input', validateAddDate);
            }
        }

        // Real-time validation for edit form date
        function attachEditDateValidation() {
            const dateInput = document.getElementById('edit_test_date');
            if (dateInput) {
                dateInput.addEventListener('change', validateEditDate);
                dateInput.addEventListener('input', validateEditDate);
            }
        }

        function openAddModal() { 
            document.getElementById('addModal').style.display = 'block';
            setMinDate();
            // Reset error messages
            const errorSpan = document.getElementById('add-date-error');
            const dateInput = document.getElementById('add_test_date');
            if (errorSpan) errorSpan.style.display = 'none';
            if (dateInput) {
                dateInput.classList.remove('error');
                document.getElementById('add-date-group')?.classList.remove('has-error');
            }
            validateAddDate();
        }
        
        function closeAddModal() { 
            document.getElementById('addModal').style.display = 'none';
            // Reset form
            const form = document.getElementById('addForm');
            if (form) form.reset();
            const errorSpan = document.getElementById('add-date-error');
            if (errorSpan) errorSpan.style.display = 'none';
        }
        
        function closeEditModal() { 
            document.getElementById('editModal').style.display = 'none';
            const errorSpan = document.getElementById('edit-date-error');
            if (errorSpan) errorSpan.style.display = 'none';
        }

        function editPerformance(data) {
            document.getElementById('edit_performance_id').value = data.PerformanceID;
            
            // Extract just the date part (YYYY-MM-DD)
            const testDate = data.TestDate.split(' ')[0];
            document.getElementById('edit_test_date').value = testDate;
            document.getElementById('edit_push_ups').value = data.PushUps;
            document.getElementById('edit_sit_ups').value = data.SitUps;
            document.getElementById('edit_pull_ups').value = data.PullUps;
            document.getElementById('edit_running').value = data.Running24km;
            document.getElementById('edit_remark').value = data.Remark || '';
            
            // Set min date and validate
            setMinDate();
            validateEditDate();
            
            document.getElementById('editModal').style.display = 'block';
        }

        function deletePerformance(id) {
            if (confirm('Are you sure you want to delete this performance record? This action cannot be undone.')) {
                window.location.href = '../actions/physical-test-action.php?action=delete&performance_id=' + id;
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            setMinDate();
            attachAddDateValidation();
            attachEditDateValidation();
            validateAddDate();
        });
    </script>
</body>
</html>