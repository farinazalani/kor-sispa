<?php
require_once '../includes/session.php';
require_once '../config/database.php';

requireRole(['Trainer']);

$trainer_id = $_SESSION['user_id'];

// Get statistics
$myCourses = $conn->query("SELECT COUNT(*) as count FROM courses WHERE TrainerID = $trainer_id")->fetch_assoc()['count'];
$totalCadets = $conn->query("SELECT COUNT(DISTINCT e.CadetID) as count FROM enrollments e 
    JOIN courses c ON e.CourseID = c.CourseID WHERE c.TrainerID = $trainer_id")->fetch_assoc()['count'];

// Get recent courses
$courses = $conn->query("SELECT * FROM courses WHERE TrainerID = $trainer_id ORDER BY ScheduleDate DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Get announcements
$announcements = $conn->query("SELECT * FROM announcements ORDER BY PostDate DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Dashboard - Kor Sispa</title>
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
            
            <div class="dashboard-cards">
                <div class="card card-primary">
                    <h3>My Courses</h3>
                    <div class="number"><?php echo $myCourses; ?></div>
                </div>
                
                <div class="card card-success">
                    <h3>Total Cadets</h3>
                    <div class="number"><?php echo $totalCadets; ?></div>
                </div>
            </div>
            
            <div class="table-container" style="margin-bottom: 20px;">
                <h3 style="margin-bottom: 20px;">My Courses</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Course Name</th>
                            <th>Description</th>
                            <th>Schedule Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($courses) > 0): ?>
                            <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($course['CourseName']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($course['Description'], 0, 50)) . '...'; ?></td>
                                    <td><?php echo date('d M Y', strtotime($course['ScheduleDate'])); ?></td>
                                    <td><?php echo $course['Status']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align: center;">No courses yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="table-container">
                <h3 style="margin-bottom: 20px;">Recent Announcements</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($announcements) > 0): ?>
                            <?php foreach ($announcements as $announcement): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($announcement['Title']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($announcement['Description'], 0, 50)) . '...'; ?></td>
                                    <td><?php echo date('d M Y', strtotime($announcement['PostDate'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" style="text-align: center;">No announcements yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>