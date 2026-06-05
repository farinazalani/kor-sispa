<?php
require_once '../includes/session.php';
require_once '../config/database.php';

requireRole(['Admin']);

// Get statistics
$totalUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE Status != 'Inactive'")->fetch_assoc()['count'];
$totalCadets = $conn->query("SELECT COUNT(*) as count FROM users WHERE Role = 'Cadet' AND Status = 'Active'")->fetch_assoc()['count'];
$totalTrainers = $conn->query("SELECT COUNT(*) as count FROM users WHERE Role = 'Trainer' AND Status = 'Active'")->fetch_assoc()['count'];
$totalCourses = $conn->query("SELECT COUNT(*) as count FROM courses")->fetch_assoc()['count'];
$pendingApprovals = $conn->query("SELECT COUNT(*) as count FROM users WHERE Status = 'Inactive'")->fetch_assoc()['count'];
$pendingAdmins = $conn->query("SELECT COUNT(*) as count FROM users WHERE Status = 'Inactive' AND Role = 'Admin'")->fetch_assoc()['count'];

// Get recent announcements
$announcements = $conn->query("SELECT a.*, u.Fullname FROM announcements a 
    JOIN users u ON a.PostedBy = u.UserID 
    ORDER BY a.PostDate DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Kor Sispa</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="top-bar">
                <h2>Dashboard</h2>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
                    <a href="../actions/logout.php" class="btn-logout">Logout</a>
                </div>
            </div>
            
            <?php if ($pendingAdmins > 0): ?>
                <div class="alert" style="background: #fff3e0; border-left: 4px solid #ff9800; color: #e65100; margin-bottom: 20px;">
                    <strong>⚠️ Important:</strong> There <?php echo $pendingAdmins == 1 ? 'is' : 'are'; ?> 
                    <strong><?php echo $pendingAdmins; ?></strong> pending 
                    <strong>Administrator</strong> registration<?php echo $pendingAdmins > 1 ? 's' : ''; ?> 
                    that require<?php echo $pendingAdmins == 1 ? 's' : ''; ?> your review and approval.
                    <a href="users.php" style="color: #ff9800; text-decoration: underline; margin-left: 10px;">Review now →</a>
                </div>
            <?php endif; ?>
            
            <div class="dashboard-cards">
                <div class="card card-primary">
                    <h3>Active Users</h3>
                    <div class="number"><?php echo $totalUsers; ?></div>
                </div>
                
                <div class="card card-success">
                    <h3>Total Cadets</h3>
                    <div class="number"><?php echo $totalCadets; ?></div>
                </div>
                
                <div class="card card-warning">
                    <h3>Total Trainers</h3>
                    <div class="number"><?php echo $totalTrainers; ?></div>
                </div>
                
                <div class="card card-danger">
                    <h3>Active Courses</h3>
                    <div class="number"><?php echo $totalCourses; ?></div>
                </div>
                
                <?php if ($pendingApprovals > 0): ?>
                <div class="card" style="border-left: 4px solid #ff9800; background: #fff3e0;">
                    <h3>Pending Approvals</h3>
                    <div class="number" style="color: #ff9800;"><?php echo $pendingApprovals; ?></div>
                    <a href="users.php" style="color: #ff9800; text-decoration: none; font-size: 14px;">View pending users →</a>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="table-container">
                <h3 style="margin-bottom: 20px;">Recent Announcements</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Posted By</th>
                            <th>Date</th>
                            <th>Priority</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($announcements) > 0): ?>
                            <?php foreach ($announcements as $announcement): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($announcement['Title']); ?></td>
                                    <td><?php echo htmlspecialchars($announcement['Fullname']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($announcement['PostDate'])); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower($announcement['Priority']); ?>">
                                            <?php echo $announcement['Priority']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">No announcements yet</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>