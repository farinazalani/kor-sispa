<?php
// ==============================================
// INDEX PAGE - DISPLAY ALL ANNOUNCEMENTS (PUBLIC)
// ==============================================
// Include database configuration
require_once 'config/database.php';
session_start();

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$priority_filter = isset($_GET['priority']) ? $_GET['priority'] : '';
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : null;
$selected_month = isset($_GET['month']) ? (int)$_GET['month'] : null;

// Build WHERE clause based on filters
$where_clauses = [
    "1=1",
    "(a.ExpiryDate IS NULL OR a.ExpiryDate >= CURDATE())" // ONLY SHOW NON-EXPIRED ANNOUNCEMENTS
];
$params = [];
$types = "";

// Priority filter
if (!empty($priority_filter)) {
    $where_clauses[] = "a.Priority = ?";
    $params[] = $priority_filter;
    $types .= "s";
}

// Year filter
if ($selected_year) {
    $where_clauses[] = "YEAR(a.PostDate) = ?";
    $params[] = $selected_year;
    $types .= "i";
}

// Month filter
if ($selected_month) {
    $where_clauses[] = "MONTH(a.PostDate) = ?";
    $params[] = $selected_month;
    $types .= "i";
}

// Search functionality
if (!empty($search)) {
    $where_clauses[] = "(a.Title LIKE ? OR a.Description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$where_sql = "WHERE " . implode(" AND ", $where_clauses);

// Get announcements with filters - ORDER BY priority: Urgent first, then Event, then Normal
$sql = "SELECT a.*, u.Fullname, c.CourseName 
    FROM announcements a 
    JOIN users u ON a.PostedBy = u.UserID 
    LEFT JOIN courses c ON a.CourseID = c.CourseID 
    $where_sql
    ORDER BY FIELD(a.Priority, 'Urgent', 'Event', 'Normal'), a.PostDate DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$announcements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get all courses for dropdown (if needed for filtering)
$courses = $conn->query("SELECT * FROM courses WHERE Status = 'Active'")->fetch_all(MYSQLI_ASSOC);

// Get available years for dropdown
$years_result = $conn->query("SELECT DISTINCT YEAR(PostDate) as year FROM announcements ORDER BY year DESC");
$available_years = $years_result->fetch_all(MYSQLI_ASSOC);

// Check if there are urgent announcements
$has_urgent = false;
foreach ($announcements as $ann) {
    if ($ann['Priority'] == 'Urgent') {
        $has_urgent = true;
        break;
    }
}

// Helper function to get priority badge class
function getPriorityBadge($priority) {
    switch($priority) {
        case 'Urgent': return 'priority-urgent';
        case 'Event': return 'priority-event';
        default: return 'priority-normal';
    }
}

// Helper function to get priority icon
function getPriorityIcon($priority) {
    switch($priority) {
        case 'Urgent': return 'fa-exclamation-triangle';
        case 'Event': return 'fa-calendar-star';
        default: return 'fa-info-circle';
    }
}

// Helper function to check if announcement is expiring soon (within 3 days)
function isExpiringSoon($expiryDate) {
    if (!$expiryDate) return false;
    $today = new DateTime();
    $expiry = new DateTime($expiryDate);
    $diff = $today->diff($expiry)->days;
    return $diff <= 3 && $diff >= 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>KOR SISPA UTHM - Official Announcements & Updates</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&family=Oswald:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f0f2f5;
            overflow-x: hidden;
        }

        /* Navbar */
        .navbar {
            background: #0f172a;
            padding: 1rem 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            font-family: 'Oswald', sans-serif;
            font-weight: 700;
            font-size: 1.5rem;
            color: white !important;
        }
        .navbar-brand span {
            color: #3b82f6;
        }
        .nav-link {
            color: #cbd5e1 !important;
            font-weight: 500;
            transition: color 0.2s;
        }
        .nav-link:hover {
            color: #3b82f6 !important;
        }

        /* Hero Banner */
        .hero-banner {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            padding: 3rem 0;
            position: relative;
            overflow: hidden;
        }
        .hero-banner::before {
            content: "";
            position: absolute;
            width: 100%;
            height: 100%;
            background-image: radial-gradient(rgba(59,130,246,0.1) 1.5px, transparent 1.5px);
            background-size: 40px 40px;
            pointer-events: none;
        }
        .hero-title {
            font-family: 'Oswald', sans-serif;
            font-weight: 700;
            font-size: 2.5rem;
            color: white;
        }
        .hero-sub {
            color: #94a3b8;
            font-size: 1rem;
        }

        /* Announcement Cards */
        .announcement-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 1.8rem;
            margin-top: 2rem;
        }
        
        .announcement-card {
            background: white;
            border-radius: 1.2rem;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 1px solid #eef2ff;
            height: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        
        .announcement-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 35px -12px rgba(0,0,0,0.15);
            border-color: #3b82f6;
        }
        
        /* Expiring soon badge */
        .expiring-soon-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #f59e0b;
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
            z-index: 1;
        }
        
        .card-header-badge {
            padding: 0.8rem 1.2rem;
            background: #f8fafc;
            border-bottom: 1px solid #eef2ff;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .priority-badge {
            padding: 0.25rem 0.85rem;
            border-radius: 2rem;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .priority-urgent {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .priority-event {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .priority-normal {
            background: #f1f5f9;
            color: #475569;
        }
        
        .card-date {
            font-size: 0.7rem;
            color: #64748b;
        }
        
        .card-body-custom {
            padding: 1.2rem;
            flex: 1;
        }
        
        .card-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: #0f172a;
            line-height: 1.4;
        }
        
        .card-description {
            color: #475569;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 1rem;
        }
        
        .card-footer-info {
            padding: 0.8rem 1.2rem;
            background: #fafcff;
            border-top: 1px solid #eef2ff;
            font-size: 0.75rem;
            color: #64748b;
        }
        
        .card-footer-info i {
            width: 1.2rem;
            color: #3b82f6;
        }
        
        .expiry-date {
            font-size: 0.7rem;
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px dashed #e2e8f0;
        }
        
        .expiry-date i {
            color: #f59e0b;
        }
        
        .expiry-date.urgent i {
            color: #dc2626;
        }
        
        /* Filter Section */
        .filter-section {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            border: 1px solid #eef2ff;
        }
        
        .filter-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 0.3rem;
            letter-spacing: 0.5px;
        }
        
        .filter-select, .filter-input {
            padding: 0.6rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.8rem;
            font-size: 0.85rem;
            width: 100%;
            background: white;
        }
        
        .filter-select:focus, .filter-input:focus {
            border-color: #3b82f6;
            outline: none;
            box-shadow: 0 0 0 2px rgba(59,130,246,0.1);
        }
        
        .btn-filter {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 0.8rem;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .btn-filter:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }
        
        .btn-clear {
            background: #e2e8f0;
            color: #475569;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 0.8rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-clear:hover {
            background: #cbd5e1;
        }
        
        /* Urgent Banner */
        .urgent-alert {
            background: linear-gradient(95deg, #fff3cd, #ffe69b);
            border-left: 5px solid #dc3545;
            padding: 1rem 1.5rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .urgent-alert i {
            font-size: 1.8rem;
            color: #dc3545;
        }
        
        /* Result Info */
        .result-info {
            background: #f1f5f9;
            padding: 0.6rem 1rem;
            border-radius: 2rem;
            font-size: 0.8rem;
            color: #475569;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem;
            background: white;
            border-radius: 1.5rem;
        }
        
        /* Footer */
        .footer {
            background: #0f172a;
            color: #94a3b8;
            padding: 2.5rem 0;
            margin-top: 3rem;
        }
        
        @media (max-width: 768px) {
            .announcement-grid {
                grid-template-columns: 1fr;
            }
            .hero-title {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">KOR <span>SISPA</span> MANAGEMENT SYSTEM</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="#announcements"><i class="fas fa-bullhorn"></i> Announcements</a></li>
                <li class="nav-item"><a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- HERO BANNER -->
<section class="hero-banner" style="margin-top: 70px;">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8" data-aos="fade-up">
                <h1 class="hero-title">Official Announcements & Updates</h1>
                <p class="hero-sub mt-3">Stay informed with the latest news, events, and urgent notices from Kor SISPA Universiti Tun Hussein Onn Malaysia.</p>
            </div>
            <div class="col-lg-4 text-center d-none d-lg-block">
                <i class="fas fa-bullhorn" style="font-size: 4rem; color: #3b82f6; opacity: 0.7;"></i>
            </div>
        </div>
    </div>
</section>

<!-- MAIN CONTENT -->
<div class="container" style="padding: 2rem 0;" id="announcements">
    
    <!-- URGENT ALERT BANNER -->
    <?php if ($has_urgent): ?>
    <div class="urgent-alert" data-aos="fade-up">
        <i class="fas fa-exclamation-triangle"></i>
        <div>
            <strong>⚠️ URGENT ANNOUNCEMENTS</strong><br>
            There are urgent announcements that require your immediate attention. Please read them carefully.
        </div>
    </div>
    <?php endif; ?>
    
    <!-- RESULT INFO -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div class="result-info">
            <i class="fas fa-chart-line"></i> Showing <strong><?php echo count($announcements); ?></strong> active announcement(s)
            <?php if ($priority_filter): ?>
                with priority: <strong><?php echo $priority_filter; ?></strong>
            <?php endif; ?>
            <?php if ($selected_year): ?>
                in year: <strong><?php echo $selected_year; ?></strong>
            <?php endif; ?>
            <?php if ($selected_month): ?>
                in month: <strong><?php echo date('F', mktime(0, 0, 0, $selected_month, 1)); ?></strong>
            <?php endif; ?>
            <?php if (!empty($search)): ?>
                matching "<strong><?php echo htmlspecialchars($search); ?></strong>"
            <?php endif; ?>
        </div>
    </div>
    
    <!-- ANNOUNCEMENTS GRID -->
    <?php if (empty($announcements)): ?>
        <div class="empty-state" data-aos="fade-up">
            <i class="fas fa-inbox" style="font-size: 3.5rem; color: #cbd5e1;"></i>
            <h4 class="mt-3" style="color: #475569;">No active announcements found</h4>
            <p class="text-muted">There are no active announcements at the moment. Please check back later.</p>
        </div>
    <?php else: ?>
        <div class="announcement-grid">
            <?php foreach ($announcements as $announcement): 
                $expiring_soon = isExpiringSoon($announcement['ExpiryDate']);
                $expiry_date = $announcement['ExpiryDate'] ? date('d M Y', strtotime($announcement['ExpiryDate'])) : 'Never';
            ?>
                <div class="announcement-card" data-aos="fade-up" data-aos-delay="100">
                    <?php if ($expiring_soon && $announcement['Priority'] != 'Urgent'): ?>
                        <div class="expiring-soon-badge">
                            <i class="fas fa-clock"></i> Expiring Soon
                        </div>
                    <?php endif; ?>
                    <div class="card-header-badge">
                        <span class="priority-badge <?php echo getPriorityBadge($announcement['Priority']); ?>">
                            <i class="fas <?php echo getPriorityIcon($announcement['Priority']); ?>"></i> <?php echo $announcement['Priority']; ?>
                        </span>
                        <span class="card-date"><i class="far fa-calendar-alt"></i> <?php echo date('d M Y, H:i', strtotime($announcement['PostDate'])); ?></span>
                    </div>
                    <div class="card-body-custom">
                        <h3 class="card-title"><?php echo htmlspecialchars($announcement['Title']); ?></h3>
                        <div class="card-description">
                            <?php 
                            $desc = htmlspecialchars($announcement['Description']);
                            if (strlen($desc) > 200) {
                                echo nl2br(substr($desc, 0, 200)) . '...';
                            } else {
                                echo nl2br($desc);
                            }
                            ?>
                        </div>
                    </div>
                    <div class="card-footer-info">
                        <div><i class="fas fa-user-circle"></i> Posted by: <strong><?php echo htmlspecialchars($announcement['Fullname']); ?></strong></div>
                        <div class="mt-1">
                            <?php if ($announcement['CourseName']): ?>
                                <i class="fas fa-graduation-cap"></i> Course: <?php echo htmlspecialchars($announcement['CourseName']); ?>
                            <?php else: ?>
                                <i class="fas fa-globe"></i> Audience: All Courses
                            <?php endif; ?>
                        </div>
                        <div class="expiry-date <?php echo strtolower($announcement['Priority']); ?>">
                            <i class="fas fa-hourglass-half"></i> Expires: <?php echo $expiry_date; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- FOOTER -->
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h5 style="color: white;">KOR SISPA UTHM</h5>
                <p>Universiti Tun Hussein Onn Malaysia, Parit Raja, Batu Pahat, Johor.</p>
            </div>
            <div class="col-md-3">
                <h6>Quick Links</h6>
                <ul class="list-unstyled">
                    <li><a href="#" style="color:#94a3b8; text-decoration:none;">Official Portal</a></li>
                    <li><a href="#" style="color:#94a3b8; text-decoration:none;">Contact Us</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h6>Follow Us</h6>
                <div class="d-flex gap-3">
                    <a href="#" style="color:#94a3b8;"><i class="fab fa-facebook fa-lg"></i></a>
                    <a href="#" style="color:#94a3b8;"><i class="fab fa-instagram fa-lg"></i></a>
                </div>
            </div>
        </div>
        <hr class="mt-3" style="background:#334155;">
        <div class="text-center small">
            &copy; <?php echo date('Y'); ?>  System. All rights reserved.
        </div>
    </div>
</footer>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({
        duration: 800,
        once: true,
        offset: 50
    });
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
</script>
</body>
</html>