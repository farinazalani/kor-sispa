<?php
/* =========================
   CHANGE ROLE HERE:
   admin | trainer | cadet
   ========================= */
$role = 'trainer';

$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>KOR SISPA UTHM</title>

<style>
:root{
    --primary:#0b3c5d;     /* APM Blue */
    --secondary:#f7931e;   /* APM Orange */
    --bg:#f4f6f9;
    --text:#000;
}

body.dark{
    --bg:#111827;
    --text:#fff;
}

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial, sans-serif;
}

body{
    background:var(--bg);
    color:var(--text);
}

/* ================= SIDEBAR ================= */
.sidebar{
    width:260px;
    height:100vh;
    background:var(--primary);
    position:fixed;
    left:0;
    top:0;
    transition:0.3s;
    overflow-y:auto; /* enable scroll if too long */
    padding-top:20px;
    z-index:1000;
}

/* Scrollbar styling */
.sidebar::-webkit-scrollbar {
    width:6px;
}
.sidebar::-webkit-scrollbar-thumb {
    background:var(--secondary);
    border-radius:3px;
}

/* Closed sidebar */
.sidebar.closed{
    width:70px;
}
.sidebar.closed img{
    width:35px;
}
.sidebar.closed h3,
.sidebar.closed p,
.sidebar.closed span{
    display:none;
}

/* Sidebar header */
.sidebar-header{
    text-align:center;
    padding:20px 10px;
}
.sidebar-header img{
    width:100px;
    margin-bottom:10px;
    transition:0.3s;
}
.sidebar-header h3{
    color:#fff;
    font-size:17px;
}
.sidebar-header p{
    font-size:12px;
    color:#ddd;
}

/* Sidebar menu */
.sidebar-menu{
    list-style:none;
    padding:10px;
}

.sidebar-menu li{
    margin-bottom:10px;
}

.sidebar-menu li a{
    display:flex;
    align-items:center;
    padding:12px 15px;
    background: #1f2933; /* card background */
    color:#fff;
    text-decoration:none;
    border-radius:10px; /* card style */
    transition:0.3s;
    box-shadow:0 2px 5px rgba(0,0,0,0.1);
}

.sidebar-menu li a span{
    margin-left:10px;
    font-weight:500;
}

/* Hover effect */
.sidebar-menu li a:hover{
    background:var(--secondary);
    color:#000;
    box-shadow:0 4px 10px rgba(0,0,0,0.2);
}

/* Active menu item */
.sidebar-menu li a.active{
    background:var(--secondary);
    color:#000;
    font-weight:bold;
    box-shadow:0 4px 10px rgba(0,0,0,0.3);
}

/* Collapsed sidebar cards */
.sidebar.closed li a{
    justify-content:center;
    padding:12px 0;
}

/* TOPBAR */
.topbar{
    height:60px;
    background:#fff;
    position:fixed;
    left:260px;
    right:0;
    top:0;
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 20px;
    transition:0.3s;
    box-shadow:0 2px 6px rgba(0,0,0,0.1);
}

body.dark .topbar{
    background:#1f2933;
}

body.dark .topbar .system-name{
    color:white;
}

body.dark .topbar .toggle-btn{
    color: white;
}

body.dark h2{
    color: white;
}

body.dark .table-container{
    background:#1f2933;
}

body.dark tr:hover{
    background:#6b7280;
}


.sidebar.closed ~ .topbar{
    left:70px;
}

.toggle-btn{
    font-size:26px;
    background:none;
    border:none;
    cursor:pointer;
}

.system-name{
    font-weight:bold;
    color:var(--primary);
    margin-left:30px;
    font-size: 22.5px;
}

/* Dark Mode Toggle Button */
.dark-btn {
    background: linear-gradient(135deg, #1e293b, #0f172a);
    color: #f1f5f9;
    border: 1px solid #334155;
    padding: 8px 16px;
    border-radius: 30px;
    cursor: pointer;
    font-size: 0.85rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.dark-btn:hover {
    background: linear-gradient(135deg, #334155, #1e293b);
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
}

.dark-btn:active {
    transform: translateY(0);
}

/* Light mode version (if needed) */
body:not(.dark-mode) .dark-btn {
    background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
    color: #1e293b;
    border: 1px solid #94a3b8;
}

body:not(.dark-mode) .dark-btn:hover {
    background: linear-gradient(135deg, #cbd5e1, #94a3b8);
}

/* MAIN */
.main-content{
    margin-left:260px;
    margin-top:70px;
    padding:20px;
    transition:0.3s;
}

.sidebar.closed ~ .main-content{
    margin-left:70px;
}

/* MOBILE */
@media(max-width:768px){
    .sidebar{
        left:-260px;
    }
    .sidebar.show{
        left:0;
    }
    .topbar{
        left:0;
    }
    .main-content{
        margin-left:0;
    }
}

</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="logo-sispa-removebg.png">
        <h3>KOR SISPA UTHM</h3>
        <p><?= ucfirst($role) ?> Panel</p>
    </div>

    <ul class="sidebar-menu">

    <!-- ================= ADMIN ================= -->
    <?php if($role=='admin'){ ?>
        <li><a href="dashboard.php">📊 <span>Dashboard</span></a></li>
        <li><a href="profile.php">👤 <span>Profile</span></a></li>
        <li><a href="users.php">👥 <span>User Management</span></a></li>
        <li><a href="announcements.php">📢 <span>Announcements</span></a></li>
        <li><a href="courses.php">📚 <span>Courses</span></a></li>
        <li ><a href="schedule.php">📅<span>Schedule</span></a></li>
        <li><a href="attendance-reports.php">✅ <span>Attendance Reports</span></a></li>
        <li><a href="physical-test-reports.php">💪 <span>Physical Test Reports</span></a></li>
        <li><a href="reports.php">📈 <span>Reports</span></a></li>
    <?php } ?>

    <!-- ================= TRAINER ================= -->
    <?php if($role=='trainer'){ ?>
        <li><a href="dashboard.php">📊 <span>Dashboard</span></a></li>
        <li><a href="profile.php">👤 <span>Profile</span></a></li>
        <li><a href="announcements.php">📢 <span>Announcements</span></a></li>
        <li><a href="courses.php">📚 <span>Manage Courses</span></a></li>
        <li ><a href="schedule.php">📅<span>Manage Schedule</span></a></li>
        <li><a href="attendance.php">✅ <span>Manage Attendance</span></a></li>
        <li><a href="physical-tests.php">💪 <span>Manage Physical Test</span></a></li>
    <?php } ?>

    <!-- ================= CADET ================= -->
    <?php if($role=='cadet'){ ?>
        <li><a href="dashboard.php">📊 <span>Dashboard</span></a></li>
        <li><a href="profile.php">👤 <span>Profile</span></a></li>
        <li><a href="announcements.php">📢 <span>Announcements</span></a></li>
        <li><a href="courses.php">📚 <span>My Courses</span></a></li>
        <li ><a href="schedule.php">📅<span>My Schedule</span></a></li>
        <li><a href="attendance.php">✅ <span>My Attendance</span></a></li>
        <li><a href="physical-tests.php">💪 <span>My Physical Test</span></a></li>
    <?php } ?>

    </ul>
</div>

<!-- TOPBAR -->
<div class="topbar">
    <div>
        <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
        <span class="system-name">KOR SISPA MANAGEMENT SYSTEM</span>
    </div>
    <button class="dark-btn" onclick="toggleDark()">🌙 Dark</button>
</div>

<script>
function toggleSidebar(){
    let sb = document.getElementById("sidebar");
    if(window.innerWidth <= 768){
        sb.classList.toggle("show");
    }else{
        sb.classList.toggle("closed");
    }
}

// Check localStorage on page load
window.addEventListener('DOMContentLoaded', () => {
    const darkMode = localStorage.getItem('darkMode');
    if (darkMode === 'enabled') {
        document.body.classList.add('dark');
    }
});

// Toggle dark mode
function toggleDark(){
    document.body.classList.toggle('dark');

    // Save preference in localStorage
    if(document.body.classList.contains('dark')){
        localStorage.setItem('darkMode','enabled');
    } else {
        localStorage.setItem('darkMode','disabled');
    }
}
</script>

</body>
</html>
