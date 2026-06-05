<?php
require_once '../includes/session.php';
require_once '../config/database.php';

requireRole(['Cadet']);

$cadet_id = $_SESSION['user_id'];

// Get statistics
$enrolledCourses = $conn->query("SELECT COUNT(*) as count FROM enrollments WHERE CadetID = $cadet_id AND Status = 'Enrolled'")->fetch_assoc()['count'];
$attendanceCount = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE UserID = $cadet_id AND Status = 'Present'")->fetch_assoc()['count'];
$testsCount = $conn->query("SELECT COUNT(*) as count FROM physical_performance WHERE UserID = $cadet_id")->fetch_assoc()['count'];

// Get enrolled courses
$courses = $conn->query("SELECT c.*, u.Fullname as TrainerName 
    FROM courses c 
    JOIN enrollments e ON c.CourseID = e.CourseID 
    LEFT JOIN users u ON c.TrainerID = u.UserID 
    WHERE e.CadetID = $cadet_id AND e.Status = 'Enrolled'")->fetch_all(MYSQLI_ASSOC);

// Get recent attendance
$attendance = $conn->query("SELECT a.*, c.CourseName 
    FROM attendance a 
    JOIN courses c ON a.CourseID = c.CourseID 
    WHERE a.UserID = $cadet_id 
    ORDER BY a.Date DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Get announcements
$announcements = $conn->query("SELECT * FROM announcements ORDER BY PostDate DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadet Dashboard - Kor Sispa</title>
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
                    <h3>Enrolled Courses</h3>
                    <div class="number"><?php echo $enrolledCourses; ?></div>
                </div>
                
                <div class="card card-success">
                    <h3>Total Attendance</h3>
                    <div class="number"><?php echo $attendanceCount; ?></div>
                </div>
                
                <div class="card card-warning">
                    <h3>Physical Tests</h3>
                    <div class="number"><?php echo $testsCount; ?></div>
                </div>
            </div>
            
            <div class="table-container" style="margin-bottom: 20px;">
                <h3 style="margin-bottom: 20px;">My Courses</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Course Name</th>
                            <th>Trainer</th>
                            <th>Schedule Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($courses) > 0): ?>
                            <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($course['CourseName']); ?></td>
                                    <td><?php echo htmlspecialchars($course['TrainerName']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($course['ScheduleDate'])); ?></td>
                                    <td><?php echo $course['Status']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align: center;">No courses enrolled yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="table-container" style="margin-bottom: 20px;">
                <h3 style="margin-bottom: 20px;">Recent Attendance</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($attendance) > 0): ?>
                            <?php foreach ($attendance as $att): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($att['CourseName']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($att['Date'])); ?></td>
                                    <td style="color: <?php echo $att['Status'] == 'Present' ? 'green' : 'red'; ?>">
                                        <?php echo $att['Status']; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" style="text-align: center;">No attendance records yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="table-container">
                <h3 style="margin-bottom: 20px;">Announcements</h3>
                <?php foreach ($announcements as $announcement): ?>
                    <div class="card" style="margin-bottom: 15px;">
                        <h3><?php echo htmlspecialchars($announcement['Title']); ?></h3>
                        <p><?php echo nl2br(htmlspecialchars($announcement['Description'])); ?></p>
                        <small>Posted on: <?php echo date('d M Y H:i', strtotime($announcement['PostDate'])); ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>