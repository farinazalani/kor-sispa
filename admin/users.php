<?php
require_once '../includes/session.php';
require_once '../config/database.php';

requireRole(['Admin']);

// Get users by role
$all_users = $conn->query("SELECT * FROM users ORDER BY CreatedAt DESC")->fetch_all(MYSQLI_ASSOC);
$admins = $conn->query("SELECT * FROM users WHERE Role = 'Admin' ORDER BY CreatedAt DESC")->fetch_all(MYSQLI_ASSOC);
$trainers = $conn->query("SELECT * FROM users WHERE Role = 'Trainer' ORDER BY CreatedAt DESC")->fetch_all(MYSQLI_ASSOC);
$cadets = $conn->query("SELECT * FROM users WHERE Role = 'Cadet' ORDER BY CreatedAt DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Kor Sispa</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .nav-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .nav-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .nav-btn-admin {
            background: #f44336;
            color: white;
        }
        .nav-btn-trainer {
            background: #ff9800;
            color: white;
        }
        .nav-btn-cadet {
            background: #4caf50;
            color: white;
        }
        .nav-btn-all {
            background: #2196f3;
            color: white;
        }
        .nav-btn:hover {
            opacity: 0.8;
            transform: translateY(-2px);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .back-to-top {
            background: #9e9e9e;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .back-to-top:hover {
            background: #757575;
            transform: translateY(-2px);
        }
        
        .section-container {
            margin-bottom: 30px;
            scroll-margin-top: 20px;
        }
        
        .btn-add {
            background: #4caf50;
            color: white;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="top-bar">
                <h2>User Management</h2>
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
            
            <!-- Navigation Buttons -->
            <div class="nav-buttons">
                <button class="nav-btn nav-btn-all" onclick="scrollToSection('allUsersSection')">📋 All Users (<?php echo count($all_users); ?>)</button>
                <button class="nav-btn nav-btn-admin" onclick="scrollToSection('adminSection')">👑 Administrators (<?php echo count($admins); ?>)</button>
                <button class="nav-btn nav-btn-trainer" onclick="scrollToSection('trainerSection')">🏋️ Trainers (<?php echo count($trainers); ?>)</button>
                <button class="nav-btn nav-btn-cadet" onclick="scrollToSection('cadetSection')">🎓 Cadets (<?php echo count($cadets); ?>)</button>
            </div>
            
            <!-- Pending Approvals Section -->
            <div class="section-container">
                <div class="table-container" style="margin-bottom: 20px;">
                    <div class="section-header">
                        <h3 style="margin: 0;">Pending Approvals</h3>
                        <button class="back-to-top" onclick="scrollToTop()">⬆ Back to Top</button>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>IC Number</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Registration Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $pending = $conn->query("SELECT * FROM users WHERE Status = 'Inactive' ORDER BY CreatedAt DESC")->fetch_all(MYSQLI_ASSOC);
                            if (count($pending) > 0):
                                foreach ($pending as $user):
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['Fullname']); ?></td>
                                    <td><?php echo htmlspecialchars($user['Username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['Email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['ICNumber']); ?></td>
                                    <td><?php echo htmlspecialchars($user['Phone'] ?: 'N/A'); ?></td>
                                    <td>
                                        <span style="padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;
                                            background: <?php echo $user['Role'] === 'Admin' ? '#f44336' : ($user['Role'] === 'Trainer' ? '#ff9800' : '#4caf50'); ?>;
                                            color: white;">
                                            <?php echo $user['Role']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d M Y H:i', strtotime($user['CreatedAt'])); ?></td>
                                    <td>
                                        <a href="../actions/user-action.php?action=approve&user_id=<?php echo $user['UserID']; ?>" 
                                           class="btn-sm btn-success" 
                                           onclick="return confirm('Approve <?php echo htmlspecialchars($user['Fullname']); ?> as <?php echo $user['Role']; ?>?')">Approve</a>
                                        <button class="btn-sm btn-danger" onclick="rejectUser(<?php echo $user['UserID']; ?>, '<?php echo htmlspecialchars($user['Fullname']); ?>')">Reject</button>
                                        <button class="btn-sm btn-info" onclick='viewUserDetails(<?php echo json_encode($user); ?>)'>View Details</button>
                                    </td>
                                </tr>
                            <?php 
                                endforeach;
                            else:
                            ?>
                                <tr><td colspan="8" style="text-align: center; padding: 30px; color: #999;">No pending approvals</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- All Users Section -->
            <div id="allUsersSection" class="section-container">
                <div class="table-container" style="margin-bottom: 20px;">
                    <div class="section-header">
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <h3 style="margin: 0;">All Users (<?php echo count($all_users); ?>)</h3>
                            <button class="btn btn-primary" onclick="openAddUserModal()">Add New User</button>
                        </div>
                        <button class="back-to-top" onclick="scrollToTop()">⬆ Back to Top</button>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>IC Number</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($all_users) > 0): ?>
                                <?php foreach ($all_users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['UserID']; ?></td>
                                        <td><?php echo htmlspecialchars($user['Fullname']); ?></td>
                                        <td><?php echo htmlspecialchars($user['Username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['Email']); ?></td>
                                        <td>
                                            <span style="padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;
                                                background: <?php echo $user['Role'] === 'Admin' ? '#f44336' : ($user['Role'] === 'Trainer' ? '#ff9800' : '#4caf50'); ?>;
                                                color: white;">
                                                <?php echo $user['Role']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['ICNumber']); ?></td>
                                        <td>
                                            <span style="color: <?php echo $user['Status'] == 'Active' ? 'green' : 'red'; ?>">
                                                <?php echo $user['Status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn-sm btn-warning" onclick='editUser(<?php echo json_encode($user); ?>)'>Edit</button>
                                            <button class="btn-sm btn-danger" onclick="deleteUser(<?php echo $user['UserID']; ?>)">Delete</button>
                                            <button class="btn-sm btn-info" onclick='viewUserDetails(<?php echo json_encode($user); ?>)'>View</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="8" style="text-align: center; padding: 30px; color: #999;">No users found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Admin Section -->
            <div id="adminSection" class="section-container">
                <div class="table-container" style="margin-bottom: 20px;">
                    <div class="section-header">
                        <h3 style="margin: 0;">Administrators (<?php echo count($admins); ?>)</h3>
                        <button class="back-to-top" onclick="scrollToTop()">⬆ Back to Top</button>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>IC Number</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($admins) > 0): ?>
                                <?php foreach ($admins as $user): ?>
                                    <tr>
                                        <td><?php echo $user['UserID']; ?></td>
                                        <td><?php echo htmlspecialchars($user['Fullname']); ?></td>
                                        <td><?php echo htmlspecialchars($user['Username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['Email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['ICNumber']); ?></td>
                                        <td><?php echo htmlspecialchars($user['Phone'] ?: 'N/A'); ?></td>
                                        <td>
                                            <span style="color: <?php echo $user['Status'] == 'Active' ? 'green' : 'red'; ?>">
                                                <?php echo $user['Status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn-sm btn-warning" onclick='editUser(<?php echo json_encode($user); ?>)'>Edit</button>
                                            <button class="btn-sm btn-danger" onclick="deleteUser(<?php echo $user['UserID']; ?>)">Delete</button>
                                            <button class="btn-sm btn-info" onclick='viewUserDetails(<?php echo json_encode($user); ?>)'>View</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="8" style="text-align: center; padding: 30px; color: #999;">No admin users found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Trainer Section -->
            <div id="trainerSection" class="section-container">
                <div class="table-container" style="margin-bottom: 20px;">
                    <div class="section-header">
                        <h3 style="margin: 0;">Trainers (<?php echo count($trainers); ?>)</h3>
                        <button class="back-to-top" onclick="scrollToTop()">⬆ Back to Top</button>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>IC Number</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($trainers) > 0): ?>
                                <?php foreach ($trainers as $user): ?>
                                    <tr>
                                        <td><?php echo $user['UserID']; ?></td>
                                        <td><?php echo htmlspecialchars($user['Fullname']); ?></td>
                                        <td><?php echo htmlspecialchars($user['Username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['Email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['ICNumber']); ?></td>
                                        <td><?php echo htmlspecialchars($user['Phone'] ?: 'N/A'); ?></td>
                                        <td>
                                            <span style="color: <?php echo $user['Status'] == 'Active' ? 'green' : 'red'; ?>">
                                                <?php echo $user['Status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn-sm btn-warning" onclick='editUser(<?php echo json_encode($user); ?>)'>Edit</button>
                                            <button class="btn-sm btn-danger" onclick="deleteUser(<?php echo $user['UserID']; ?>)">Delete</button>
                                            <button class="btn-sm btn-info" onclick='viewUserDetails(<?php echo json_encode($user); ?>)'>View</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <td><td colspan="8" style="text-align: center; padding: 30px; color: #999;">No trainer users found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Cadet Section -->
            <div id="cadetSection" class="section-container">
                <div class="table-container" style="margin-bottom: 20px;">
                    <div class="section-header">
                        <h3 style="margin: 0;">Cadets (<?php echo count($cadets); ?>)</h3>
                        <button class="back-to-top" onclick="scrollToTop()">⬆ Back to Top</button>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>IC Number</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($cadets) > 0): ?>
                                <?php foreach ($cadets as $user): ?>
                                    <tr>
                                        <td><?php echo $user['UserID']; ?></td>
                                        <td><?php echo htmlspecialchars($user['Fullname']); ?></td>
                                        <td><?php echo htmlspecialchars($user['Username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['Email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['ICNumber']); ?></td>
                                        <td><?php echo htmlspecialchars($user['Phone'] ?: 'N/A'); ?></td>
                                        <td>
                                            <span style="color: <?php echo $user['Status'] == 'Active' ? 'green' : 'red'; ?>">
                                                <?php echo $user['Status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn-sm btn-warning" onclick='editUser(<?php echo json_encode($user); ?>)'>Edit</button>
                                            <button class="btn-sm btn-danger" onclick="deleteUser(<?php echo $user['UserID']; ?>)">Delete</button>
                                            <button class="btn-sm btn-info" onclick='viewUserDetails(<?php echo json_encode($user); ?>)'>View</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="8" style="text-align: center; padding: 30px; color: #999;">No cadet users found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New User</h3>
                <span class="close" onclick="closeAddUserModal()">&times;</span>
            </div>
            <form action="../actions/user-action.php" method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="fullname" required>
                </div>
                
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label>IC Number</label>
                    <input type="text" name="ic_number" required>
                </div>
                
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone">
                </div>
                
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" required>
                        <option value="">Select Role</option>
                        <option value="Admin">Admin</option>
                        <option value="Trainer">Trainer</option>
                        <option value="Cadet">Cadet</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" required>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Add User</button>
            </form>
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit User</h3>
                <span class="close" onclick="closeEditUserModal()">&times;</span>
            </div>
            <form action="../actions/user-action.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="fullname" id="edit_fullname" required>
                </div>
                
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" id="edit_username" required>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="edit_email" required>
                </div>
                
                <div class="form-group">
                    <label>IC Number</label>
                    <input type="text" name="ic_number" id="edit_ic_number" required>
                </div>
                
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" id="edit_phone">
                </div>
                
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="edit_role" required>
                        <option value="Admin">Admin</option>
                        <option value="Trainer">Trainer</option>
                        <option value="Cadet">Cadet</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="edit_status" required>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="Suspended">Suspended</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Update User</button>
            </form>
        </div>
    </div>
    
    <!-- View User Details Modal -->
    <div id="viewUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>User Registration Details</h3>
                <span class="close" onclick="closeViewUserModal()">&times;</span>
            </div>
            <div id="userDetailsContent" style="padding: 20px 0;">
                <!-- Details will be populated by JavaScript -->
            </div>
            <div style="text-align: right;">
                <button class="btn btn-primary" onclick="closeViewUserModal()">Close</button>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
    <script>
        function scrollToSection(sectionId) {
            const section = document.getElementById(sectionId);
            if (section) {
                section.scrollIntoView({ 
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }
        
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
        
        function openAddUserModal() {
            document.getElementById('addUserModal').style.display = 'block';
        }
        
        function closeAddUserModal() {
            document.getElementById('addUserModal').style.display = 'none';
        }
        
        function editUser(user) {
            document.getElementById('edit_user_id').value = user.UserID;
            document.getElementById('edit_fullname').value = user.Fullname;
            document.getElementById('edit_username').value = user.Username;
            document.getElementById('edit_email').value = user.Email;
            document.getElementById('edit_ic_number').value = user.ICNumber;
            document.getElementById('edit_phone').value = user.Phone || '';
            document.getElementById('edit_role').value = user.Role;
            document.getElementById('edit_status').value = user.Status;
            document.getElementById('editUserModal').style.display = 'block';
        }
        
        function closeEditUserModal() {
            document.getElementById('editUserModal').style.display = 'none';
        }
        
        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user?')) {
                window.location.href = '../actions/user-action.php?action=delete&user_id=' + userId;
            }
        }
        
        function rejectUser(userId, userName) {
            if (confirm('Are you sure you want to reject the registration of ' + userName + '? This will permanently delete their application.')) {
                window.location.href = '../actions/user-action.php?action=delete&user_id=' + userId;
            }
        }
        
        function viewUserDetails(user) {
            const content = `
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <strong>Full Name:</strong><br>
                        <span style="color: #666;">${user.Fullname}</span>
                    </div>
                    <div>
                        <strong>Username:</strong><br>
                        <span style="color: #666;">${user.Username}</span>
                    </div>
                    <div>
                        <strong>Email:</strong><br>
                        <span style="color: #666;">${user.Email}</span>
                    </div>
                    <div>
                        <strong>IC Number:</strong><br>
                        <span style="color: #666;">${user.ICNumber}</span>
                    </div>
                    <div>
                        <strong>Phone:</strong><br>
                        <span style="color: #666;">${user.Phone || 'Not provided'}</span>
                    </div>
                    <div>
                        <strong>Emergency Contact:</strong><br>
                        <span style="color: #666;">${user.EmergencyContact || 'Not provided'}</span>
                    </div>
                    <div>
                        <strong>Role:</strong><br>
                        <span style="padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;
                            background: ${user.Role === 'Admin' ? '#f44336' : (user.Role === 'Trainer' ? '#ff9800' : '#4caf50')};
                            color: white;">${user.Role}</span>
                    </div>
                    <div>
                        <strong>Registration Date:</strong><br>
                        <span style="color: #666;">${new Date(user.CreatedAt).toLocaleString()}</span>
                    </div>
                </div>
                ${user.Role === 'Admin' ? `
                    <div style="margin-top: 20px; padding: 15px; background: #fff3e0; border-left: 4px solid #ff9800; border-radius: 4px;">
                        <strong style="color: #ff9800;">⚠️ Admin Registration</strong><br>
                        <span style="color: #666; font-size: 14px;">This user is requesting Administrator privileges. Please verify their identity and authorization before approving.</span>
                    </div>
                ` : ''}
            `;
            
            document.getElementById('userDetailsContent').innerHTML = content;
            document.getElementById('viewUserModal').style.display = 'block';
        }
        
        function closeViewUserModal() {
            document.getElementById('viewUserModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>