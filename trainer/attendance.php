<?php
require_once '../includes/session.php';
require_once '../config/database.php';

requireRole(['Trainer']);

$trainer_id = $_SESSION['user_id'];

// Get trainer's courses
$courses = $conn->query("SELECT * FROM courses WHERE TrainerID = $trainer_id AND Status = 'Active'")->fetch_all(MYSQLI_ASSOC);

// Get attendance records
$attendance = $conn->query("SELECT a.*, u.Fullname, c.CourseName, rec.Fullname as RecordedByName
    FROM attendance a
    JOIN users u ON a.UserID = u.UserID
    JOIN courses c ON a.CourseID = c.CourseID
    LEFT JOIN users rec ON a.RecordedBy = rec.UserID
    WHERE c.TrainerID = $trainer_id
    ORDER BY a.Date DESC, a.CreatedAt DESC LIMIT 50")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management - Kor Sispa</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .bulk-container { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .cadet-list-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .cadet-list-table th, .cadet-list-table td { padding: 12px; border-bottom: 1px solid #eee; }
        .hidden { display: none; }
        .modal-info { background: #f0f7ff; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 0.9em; border: 1px solid #cfe2ff; }
        .date-error { color: #dc3545; font-size: 0.85em; margin-top: 5px; display: none; }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; }
        .time-select { padding: 10px; border-radius: 4px; border: 1px solid #ddd; width: 100%; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        <div class="main-content">
            <div class="top-bar">
                <h2>Attendance Management</h2>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
                    <a href="../actions/logout.php" class="btn-logout">Logout</a>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <div class="bulk-container">
                <h3 style="padding: 15px">Take New Attendance</h3>
                <form action="../actions/attendance-action.php" method="POST" id="attendanceForm">
                    <input type="hidden" name="action" value="bulk_add">
                        <div class="form-group">
                            <label>Course</label>
                            <select name="course_id" id="course_id" required onchange="fetchCadetList(this.value)">
                                <option value="">-- Select Course --</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['CourseID']; ?>"><?php echo htmlspecialchars($course['CourseName']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" name="date" id="attendance_date" value="<?php echo date('Y-m-d'); ?>" required>
                            <div class="date-error" id="dateError">Attendance cannot be recorded for a past date.</div>
                        </div>
                        <div class="form-group">
                            <label>Time (Hour Only)</label>
                            <select name="time" id="attendance_time" class="time-select" required>
                                <option value="01:00:00">01.00</option>
                                <option value="02:00:00">02.00</option>
                                <option value="03:00:00">03.00</option>
                                <option value="04:00:00">04.00</option>
                                <option value="05:00:00">05.00</option>
                                <option value="06:00:00">06.00</option>
                                <option value="07:00:00">07.00</option>
                                <option value="08:00:00" selected>08.00</option>
                                <option value="09:00:00">09.00</option>
                                <option value="10:00:00">10.00</option>
                                <option value="11:00:00">11.00</option>
                                <option value="12:00:00">12.00</option>
                                <option value="13:00:00">13.00</option>
                                <option value="14:00:00">14.00</option>
                                <option value="15:00:00">15.00</option>
                                <option value="16:00:00">16.00</option>
                                <option value="17:00:00">17.00</option>
                                <option value="18:00:00">18.00</option>
                                <option value="19:00:00">19.00</option>
                                <option value="20:00:00">20.00</option>
                                <option value="21:00:00">21.00</option>
                                <option value="22:00:00">22.00</option>
                                <option value="23:00:00">23.00</option>
                            </select>
                        </div>

                    <div id="cadet_list_container" class="hidden">
                        <table class="cadet-list-table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="select_all" onclick="toggleAll(this)" checked></th>
                                    <th>Cadet Name</th>
                                    <th>Status</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody id="cadet_list_body"></tbody>
                        </table>
                        <button type="submit" class="btn btn-primary" id="submitBtn" style="margin-top:20px;">Save Attendance</button>
                    </div>
                </form>
            </div>

            <div class="table-container">
                <h3>Attendance History</h3>
                 <table>
                    <thead>
                        <tr>
                            <th>Cadet</th>
                            <th>Course</th>
                            <th>Day/Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendance as $att): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($att['Fullname']); ?></td>
                                <td><?php echo htmlspecialchars($att['CourseName']); ?></td>
                                <td><?php echo date('l, d M Y', strtotime($att['Date'])); ?></td>
                                <td>
                                    <?php 
                                        $time_val = $att['CreatedAt'];
                                        $hour = date('H', strtotime($time_val));
                                        echo $hour . '.00';
                                    ?>
                                </td>
                                <td><strong><?php echo $att['Status']; ?></strong></td>
                                <td>
                                    <button class="btn-sm btn-warning" onclick='openEditModal(<?php echo json_encode($att); ?>)'>Edit</button>
                                    <button class="btn-sm btn-danger" onclick="deleteAttendance(<?php echo $att['AttendanceID']; ?>)">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Attendance Record</h3>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <form action="../actions/attendance-action.php" method="POST" id="editForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="attendance_id" id="edit_attendance_id">
                
                <div class="modal-info">
                    <strong>Cadet:</strong> <span id="edit_display_name"></span><br>
                    <strong>Course:</strong> <span id="edit_display_course"></span>
                </div>

                <div style="display: flex; gap: 10px;">
                    <div class="form-group" style="flex:1;">
                        <label>Date</label>
                        <input type="date" name="date" id="edit_date" required>
                        <div class="date-error" id="editDateError" style="display:none;">Cannot set a past date</div>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Time (Hour Only)</label>
                        <select name="time" id="edit_time" class="time-select" required>
                            <option value="06:00:00">06.00</option>
                            <option value="07:00:00">07.00</option>
                            <option value="08:00:00">08.00</option>
                            <option value="09:00:00">09.00</option>
                            <option value="10:00:00">10.00</option>
                            <option value="11:00:00">11.00</option>
                            <option value="12:00:00">12.00</option>
                            <option value="13:00:00">13.00</option>
                            <option value="14:00:00">14.00</option>
                            <option value="15:00:00">15.00</option>
                            <option value="16:00:00">16.00</option>
                            <option value="17:00:00">17.00</option>
                            <option value="18:00:00">18.00</option>
                            <option value="19:00:00">19.00</option>
                            <option value="20:00:00">20.00</option>
                            <option value="21:00:00">21.00</option>
                            <option value="22:00:00">22.00</option>
                            <option value="23:00:00">23.00</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="edit_status" required>
                        <option value="Present">Present</option>
                        <option value="Absent">Absent</option>
                        <option value="Late">Late</option>
                        <option value="Excused">Excused</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Remarks</label>
                    <textarea name="remarks" id="edit_remarks" rows="2"></textarea>
                </div>

                <button type="submit" class="btn btn-primary" id="editSubmitBtn">Update Record</button>
            </form>
        </div>
    </div>

    <script>
        // Get today's date in YYYY-MM-DD format for comparison
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
            const dateInputs = document.querySelectorAll('input[type="date"]');
            dateInputs.forEach(input => {
                input.setAttribute('min', today);
            });
        }

        // Validate attendance form date
        function validateAttendanceDate() {
            const dateInput = document.getElementById('attendance_date');
            const dateError = document.getElementById('dateError');
            const submitBtn = document.getElementById('submitBtn');
            
            if (!dateInput.value) {
                dateError.style.display = 'block';
                dateError.textContent = 'Please select a date.';
                if(submitBtn) submitBtn.disabled = true;
                return false;
            }
            
            if (!isDateValid(dateInput.value)) {
                dateError.style.display = 'block';
                dateError.textContent = 'Attendance cannot be recorded for a past date. Please select today or a future date.';
                if(submitBtn) submitBtn.disabled = true;
                return false;
            }
            
            dateError.style.display = 'none';
            if(submitBtn) submitBtn.disabled = false;
            return true;
        }

        // Validate edit form date
        function validateEditDate() {
            const dateInput = document.getElementById('edit_date');
            const dateError = document.getElementById('editDateError');
            const submitBtn = document.getElementById('editSubmitBtn');
            
            if (!dateInput.value) {
                dateError.style.display = 'block';
                dateError.textContent = 'Please select a date.';
                if(submitBtn) submitBtn.disabled = true;
                return false;
            }
            
            if (!isDateValid(dateInput.value)) {
                dateError.style.display = 'block';
                dateError.textContent = 'Cannot set a past date for attendance.';
                if(submitBtn) submitBtn.disabled = true;
                return false;
            }
            
            dateError.style.display = 'none';
            if(submitBtn) submitBtn.disabled = false;
            return true;
        }

        function fetchCadetList(courseId) {
            const container = document.getElementById('cadet_list_container');
            const tbody = document.getElementById('cadet_list_body');
            if (!courseId) { container.classList.add('hidden'); return; }

            fetch(`../actions/get-enrolled-cadets-attendance.php?course_id=${courseId}`)
                .then(res => res.json())
                .then(data => {
                    tbody.innerHTML = '';
                    data.forEach(cadet => {
                        tbody.innerHTML += `
                            <tr>
                                <td><input type="checkbox" name="attendance_data[${cadet.UserID}][present]" value="1" checked></td>
                                <td>${escapeHtml(cadet.Fullname)}</td>
                                <td>
                                    <select name="attendance_data[${cadet.UserID}][status]">
                                        <option value="Present">Present</option>
                                        <option value="Absent">Absent</option>
                                        <option value="Late">Late</option>
                                        <option value="Excused">Excused</option>
                                    </select>
                                </td>
                                <td><input type="text" name="attendance_data[${cadet.UserID}][remarks]" style="width:100%"></td>
                            </tr>`;
                    });
                    container.classList.remove('hidden');
                    // Re-validate date when form becomes visible
                    validateAttendanceDate();
                })
                .catch(error => {
                    console.error('Error fetching cadet list:', error);
                    tbody.innerHTML = '<tr><td colspan="4" style="color:red;">Error loading cadet list</td></tr>';
                    container.classList.remove('hidden');
                });
        }

        // Escape HTML to prevent XSS
        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>]/g, function(m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                return m;
            });
        }

        function openEditModal(att) {
            document.getElementById('edit_attendance_id').value = att.AttendanceID;
            document.getElementById('edit_display_name').innerText = att.Fullname;
            document.getElementById('edit_display_course').innerText = att.CourseName;
            document.getElementById('edit_date').value = att.Date;
            
            // Extract hour from CreatedAt string (YYYY-MM-DD HH:mm:ss) and set dropdown
            const timeStr = att.CreatedAt;
            let hour = '08';
            if (timeStr && timeStr.includes(' ')) {
                hour = timeStr.split(' ')[1].substring(0, 2);
            }
            const formattedTime = hour.padStart(2, '0') + ':00:00';
            const timeSelect = document.getElementById('edit_time');
            if (timeSelect) {
                timeSelect.value = formattedTime;
            }
            
            document.getElementById('edit_status').value = att.Status;
            document.getElementById('edit_remarks').value = att.Remarks || '';
            
            // Validate edit date
            validateEditDate();
            
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() { document.getElementById('editModal').style.display = 'none'; }
        
        function toggleAll(src) { 
            document.querySelectorAll('input[name*="[present]"]').forEach(cb => cb.checked = src.checked); 
        }
        
        function deleteAttendance(id) { 
            if(confirm('Are you sure you want to delete this attendance record? This action cannot be undone.')) {
                window.location.href=`../actions/attendance-action.php?action=delete&attendance_id=${id}`;
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(e) { 
            if(e.target.className === 'modal') closeEditModal(); 
        }

        // Initialize date validation when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            // Set min date on all date inputs
            setMinDate();
            
            // Add event listeners for date validation
            const attendanceDateInput = document.getElementById('attendance_date');
            if (attendanceDateInput) {
                attendanceDateInput.addEventListener('change', validateAttendanceDate);
                attendanceDateInput.addEventListener('input', validateAttendanceDate);
            }
            
            const editDateInput = document.getElementById('edit_date');
            if (editDateInput) {
                editDateInput.addEventListener('change', validateEditDate);
                editDateInput.addEventListener('input', validateEditDate);
            }
            
            // Form submission validation for attendance form
            const attendanceForm = document.getElementById('attendanceForm');
            if (attendanceForm) {
                attendanceForm.addEventListener('submit', function(e) {
                    if (!validateAttendanceDate()) {
                        e.preventDefault();
                        alert('Please select a valid date (today or future date) for attendance.');
                    }
                });
            }
            
            // Form submission validation for edit form
            const editForm = document.getElementById('editForm');
            if (editForm) {
                editForm.addEventListener('submit', function(e) {
                    if (!validateEditDate()) {
                        e.preventDefault();
                        alert('Please select a valid date (today or future date) for attendance.');
                    }
                });
            }
            
            // Initial validation
            validateAttendanceDate();
        });
    </script>
</body>
</html>