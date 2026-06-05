<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $role = strtolower($_SESSION['role']);
    header("Location: $role/dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Login · Kor SISPA Management System</title>
    <!-- Google Fonts & simple reset -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <!-- Font Awesome 6 (free icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
        }

        /* subtle animated background pattern */
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: radial-gradient(rgba(255,255,255,0.05) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
        }

        /* main card container */
        .login-container {
            width: 100%;
            max-width: 470px;
            margin: 0 auto;
            animation: fadeSlideUp 0.6s cubic-bezier(0.2, 0.9, 0.4, 1.1);
        }

        @keyframes fadeSlideUp {
            0% {
                opacity: 0;
                transform: translateY(24px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-box {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(0px);
            border-radius: 2.5rem;
            padding: 2rem 2rem 2.2rem;
            box-shadow: 0 25px 45px -12px rgba(0, 0, 0, 0.4), 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .login-box:hover {
            box-shadow: 0 30px 50px -18px black;
        }

        /* logo area */
        .login-logo {
            display: flex;
            justify-content: center;
            margin-bottom: 1.2rem;
        }
        .login-logo img {
            width: 85px;
            height: auto;
            filter: drop-shadow(0 6px 12px rgba(0,0,0,0.1));
            transition: transform 0.2s;
        }
        .login-logo img:hover {
            transform: scale(1.02);
        }

        /* titles */
        .login-box h2 {
            font-size: 1.8rem;
            font-weight: 700;
            text-align: center;
            letter-spacing: -0.3px;
            background: linear-gradient(120deg, #0f2b3d, #1e4a6e);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            margin-bottom: 0.25rem;
        }

        .subhead {
            text-align: center;
            color: #5b6e8c;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 1.8rem;
            border-bottom: 1px solid #e9edf2;
            display: inline-block;
            width: auto;
            padding-bottom: 0.5rem;
            letter-spacing: 0.3px;
        }

        /* alerts */
        .alert {
            padding: 0.9rem 1rem;
            border-radius: 1.2rem;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            backdrop-filter: blur(4px);
        }
        .alert-danger {
            background: #ffe6e5;
            border-left: 5px solid #e53e3e;
            color: #b91c1c;
        }
        .alert-success {
            background: #e3f7ec;
            border-left: 5px solid #2c7a4b;
            color: #1e5a3a;
        }
        .alert i {
            font-size: 1.1rem;
        }

        /* form groups */
        .form-group {
            margin-bottom: 1.4rem;
        }

        label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #334155;
            margin-bottom: 0.5rem;
        }

        .input-icon-group {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon-group i {
            position: absolute;
            left: 15px;
            color: #94a3b8;
            font-size: 1rem;
            pointer-events: none;
        }

        input, select {
            width: 100%;
            padding: 0.85rem 1rem 0.85rem 2.6rem;
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            border: 1.5px solid #e2e8f0;
            border-radius: 1.2rem;
            background: #ffffff;
            transition: all 0.2s ease;
            color: #0f172a;
            font-weight: 500;
        }

        select {
            padding-left: 2.6rem;
            cursor: pointer;
            appearance: none;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="%234b5563" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>');
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1rem;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.2);
        }

        /* button */
        .btn-primary {
            background: linear-gradient(95deg, #1e3a5f, #0f2c44);
            border: none;
            padding: 0.9rem 1.2rem;
            border-radius: 2rem;
            font-weight: 700;
            font-size: 0.95rem;
            letter-spacing: 0.7px;
            color: white;
            width: 100%;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 6px 14px rgba(0, 30, 60, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary i {
            font-size: 1rem;
            transition: transform 0.2s;
        }

        .btn-primary:hover {
            background: linear-gradient(95deg, #113753, #0a2338);
            transform: translateY(-2px);
            box-shadow: 0 14px 22px -8px rgba(0, 0, 0, 0.3);
        }

        .btn-primary:hover i {
            transform: translateX(3px);
        }

        .btn-primary:active {
            transform: translateY(1px);
        }

        /* footer links */
        .register-link {
            text-align: center;
            margin-top: 1.8rem;
            font-size: 0.85rem;
            color: #475569;
        }

        .register-link a {
            color: #2563eb;
            font-weight: 700;
            text-decoration: none;
            transition: color 0.2s;
            margin-left: 6px;
        }

        .register-link a:hover {
            color: #1e40af;
            text-decoration: underline;
        }

        .demo-hint {
            text-align: center;
            margin-top: 1.4rem;
            font-size: 0.7rem;
            background: #f8fafc;
            display: inline-block;
            width: 100%;
            padding: 0.55rem;
            border-radius: 2rem;
            color: #3b4e6e;
            font-weight: 500;
        }

        .demo-hint strong {
            background: #e6edf5;
            padding: 2px 8px;
            border-radius: 30px;
            font-weight: 700;
            font-family: monospace;
        }

        hr {
            margin: 16px 0 8px;
            border: none;
            height: 1px;
            background: linear-gradient(to right, #e2e8f0, transparent);
        }

        /* responsive */
        @media (max-width: 500px) {
            .login-box {
                padding: 1.6rem;
            }
            .login-box h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-box">

        <!-- LOGO with animation -->
        <div class="login-logo">
            <!-- using placeholder logo? but we keep original asset name -->
            <img src="assets/logo-sispa-removebg.png" alt="KOR SISPA Emblem" onerror="this.onerror=null; this.src='https://placehold.co/100x100?text=KOR+SISPA';">
        </div>

        <h2>KOR SISPA UTHM</h2>
        <div style="text-align: center; margin-bottom: 1rem;">
            <span class="subhead">Management System</span>
        </div>

        <!-- PHP session messages (error/success) -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></span>
            </div>
        <?php endif; ?>

        <!-- LOGIN FORM -->
        <form action="actions/login.php" method="POST">
            <div class="form-group">
                <label for="username"><i class="far fa-user-circle" style="margin-right: 4px;"></i> Username or Email</label>
                <div class="input-icon-group">
                    <i class="fas fa-envelope"></i>
                    <input type="text" id="username" name="username" placeholder="e.g., cadet_ali or ali@uthm.edu.my" required autocomplete="username">
                </div>
            </div>

            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <div class="input-icon-group">
                    <i class="fas fa-key"></i>
                    <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
                </div>
            </div>

            <div class="form-group">
                <label for="role"><i class="fas fa-user-tag"></i> Select Role</label>
                <div class="input-icon-group">
                    <i class="fas fa-badge-check"></i>
                    <select id="role" name="role" required>
                        <option value="" disabled selected>— Access level —</option>
                        <option value="Admin">👑 Administrator</option>
                        <option value="Trainer">📚 Trainer</option>
                        <option value="Cadet">🪖 Cadet</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn-primary">
                <i class="fas fa-arrow-right-to-bracket"></i> LOGIN
            </button>
        </form>

        <div class="register-link">
            <span>⚡ Don't have an account?</span>
            <a href="register.php">Create account ➡️</a>
        </div>

        <div class="register-link">
            <a href="index.php"> ⬅️ Back </a>
        </div>

        <hr>

        <div style="text-align: center; margin-top: 12px; font-size: 12px; color: #6c86a3;">
            <i class="fas fa-shield-alt"></i> Secure cadet management portal
        </div>
    </div>
</div>

<!-- optional inline: ensure no js conflict, but pure style enhancement -->
</body>
</html>