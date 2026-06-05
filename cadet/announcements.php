<?php
require_once '../includes/session.php';
require_once '../config/database.php';

requireRole(['Cadet']);

// Get all announcements (only non-expired by default)
$announcements = $conn->query("SELECT a.*, u.Fullname, c.CourseName 
    FROM announcements a 
    JOIN users u ON a.PostedBy = u.UserID 
    LEFT JOIN courses c ON a.CourseID = c.CourseID 
    WHERE (a.ExpiryDate IS NULL OR a.ExpiryDate >= CURDATE())
    ORDER BY a.PostDate DESC")->fetch_all(MYSQLI_ASSOC);

// Function to get card class based on priority
function getCardClass($priority) {
    switch ($priority) {
        case 'Urgent':
            return 'card-urgent';
        case 'Event':
            return 'card-event';
        case 'Normal':
            return 'card-normal';
        default:
            return 'card';
    }
}

// Function to get priority badge color
function getPriorityBadge($priority) {
    switch ($priority) {
        case 'Urgent':
            return '<span class="priority-badge priority-urgent">🚨 Urgent</span>';
        case 'Event':
            return '<span class="priority-badge priority-event">🎉 Event</span>';
        case 'Normal':
            return '<span class="priority-badge priority-normal">📌 Normal</span>';
        default:
            return '<span class="priority-badge">' . htmlspecialchars($priority) . '</span>';
    }
}

// Function to check if expiring soon (within 3 days)
function isExpiringSoon($expiryDate) {
    if (!$expiryDate) return false;
    $today = new DateTime();
    $expiry = new DateTime($expiryDate);
    $diff = $today->diff($expiry)->days;
    return $diff <= 3 && $diff >= 0 && $expiry >= $today;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - Kor Sispa</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Dark Mode Variables */
        :root {
            --bg-dark: #0a0e27;
            --bg-card: #11162e;
            --bg-tertiary: #1a1f3a;
            --text-primary: #ffffff;
            --text-secondary: #a0a8c0;
            --text-muted: #6c7293;
            --border-color: #2a2f4a;
            --accent-urgent: #dc3545;
            --accent-event: #28a745;
            --accent-normal: #6c757d;
            --accent-warning: #f59e0b;
        }

        /* Dark mode background */
        body {
            background: var(--bg-dark);
        }

        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        /* Base card styles - Dark Mode */
        .card, .card-urgent, .card-event, .card-normal {
            background: var(--bg-card);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
            transition: transform 0.2s, box-shadow 0.2s;
            border-left: 5px solid;
            position: relative;
        }

        .card:hover, .card-urgent:hover, .card-event:hover, 
        .card-normal:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.4);
        }

        /* Different border colors for different priorities */
        .card-urgent {
            border-left-color: var(--accent-urgent);
            background: var(--bg-card);
        }

        .card-normal {
            border-left-color: var(--accent-normal);
            background: var(--bg-card);
        }

        .card-event {
            border-left-color: var(--accent-event);
            background: var(--bg-card);
        }

        /* Title styling based on priority - Dark Mode */
        .card-urgent h3 {
            color: #f87171;
        }

        .card-event h3 {
            color: #4ade80;
        }

        .card-normal h3 {
            color: #9ca3af;
        }

        /* Card text colors - Dark Mode */
        .card p {
            color: var(--text-secondary);
            line-height: 1.5;
        }

        hr {
            margin: 15px 0;
            border: none;
            border-top: 1px solid var(--border-color);
        }

        small {
            display: block;
            font-size: 0.85em;
            color: var(--text-muted);
            line-height: 1.5;
        }

        small strong {
            color: var(--text-secondary);
        }

        /* Priority badge styles - Dark Mode */
        .priority-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: bold;
            margin-top: 5px;
        }

        .priority-urgent {
            background: var(--accent-urgent);
            color: white;
            animation: pulse 2s infinite;
        }

        .priority-normal {
            background: var(--accent-normal);
            color: white;
        }

        .priority-event {
            background: var(--accent-event);
            color: white;
        }

        /* Animation for urgent announcements */
        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.05);
                opacity: 0.9;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* Expiry related styles - Dark Mode */
        .expiry-date {
            margin-top: 8px;
            padding-top: 5px;
            border-top: 1px dashed var(--border-color);
            font-size: 0.8em;
            color: var(--text-muted);
        }

        .expiry-date i {
            color: var(--accent-warning);
        }

        .expiry-date.urgent i {
            color: var(--accent-urgent);
        }

        .expiring-soon {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--accent-warning);
            color: #1a1a1a;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: bold;
        }

        .event-date {
            margin-top: 5px;
            font-size: 0.8em;
            color: #4ade80;
        }

        .event-date i {
            color: #4ade80;
        }

        @media (max-width: 768px) {
            .dashboard-cards {
                grid-template-columns: 1fr;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        <div class="main-content">
            <div class="top-bar">
                <h2>Announcements</h2>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
                    <a href="../actions/logout.php" class="btn-logout">Logout</a>
                </div>
            </div>
            
            <div class="dashboard-cards">
                <?php if (empty($announcements)): ?>
                    <div class="card" style="text-align: center;">
                        <p>No announcements available at the moment.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($announcements as $announcement): 
                        $show_event_date = ($announcement['Priority'] == 'Event' && $announcement['EventDate']);
                        $expiring_soon = isExpiringSoon($announcement['ExpiryDate']);
                        $expiry_date = $announcement['ExpiryDate'] ? date('d M Y', strtotime($announcement['ExpiryDate'])) : 'Never';
                    ?>
                        <div class="<?php echo getCardClass($announcement['Priority']); ?>">
                            <?php if ($expiring_soon && $announcement['Priority'] != 'Urgent'): ?>
                                <div class="expiring-soon">
                                    <i class="fas fa-clock"></i> Expiring Soon
                                </div>
                            <?php endif; ?>
                            <h3><?php echo htmlspecialchars($announcement['Title']); ?></h3>
                            <p><?php echo nl2br(htmlspecialchars($announcement['Description'])); ?></p>
                            <hr>
                            <small>
                                <strong>Posted by:</strong> <?php echo htmlspecialchars($announcement['Fullname']); ?><br>
                                <strong>Date:</strong> <?php echo date('d M Y H:i', strtotime($announcement['PostDate'])); ?><br>
                                <?php if ($show_event_date): ?>
                                    <div class="event-date">
                                        <i class="fas fa-calendar-day"></i> <strong>Event Date:</strong> <?php echo date('d M Y', strtotime($announcement['EventDate'])); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="expiry-date <?php echo strtolower($announcement['Priority']); ?>">
                                    <i class="fas fa-hourglass-half"></i> <strong>Expiry Date:</strong> <?php echo $expiry_date; ?>
                                </div>
                                <strong>Priority:</strong> <?php echo getPriorityBadge($announcement['Priority']); ?><br>
                                <?php if ($announcement['CourseName']): ?>
                                    <strong>Course:</strong> <?php echo htmlspecialchars($announcement['CourseName']); ?>
                                <?php endif; ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>