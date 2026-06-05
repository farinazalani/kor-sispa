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
    <title>Register · Kor SISPA Management System</title>
    <!-- Google Fonts: Inter & fallback -->
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
            background: linear-gradient(145deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            padding: 2rem 1.5rem;
            position: relative;
        }

        /* animated background pattern */
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: radial-gradient(rgba(255,255,255,0.04) 1.2px, transparent 1.2px);
            background-size: 42px 42px;
            pointer-events: none;
        }

        /* main container + card */
        .register-container {
            max-width: 580px;
            margin: 0 auto;
            animation: fadeSlideUp 0.5s cubic-bezier(0.2, 0.9, 0.4, 1.1);
        }

        @keyframes fadeSlideUp {
            0% {
                opacity: 0;
                transform: translateY(28px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .register-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 2rem;
            padding: 1.8rem 2rem 2.2rem;
            box-shadow: 0 25px 45px -12px rgba(0, 0, 0, 0.45), 0 2px 8px rgba(0,0,0,0.02);
            transition: transform 0.2s;
            border: 1px solid rgba(255,255,255,0.3);
        }

        .register-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 32px 50px -18px black;
        }

        /* logo area */
        .logo-area {
            display: flex;
            justify-content: center;
            margin-bottom: 1rem;
        }
        .logo-area img {
            width: 85px;
            height: auto;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.1));
            transition: all 0.2s;
        }

        h2 {
            font-size: 1.9rem;
            font-weight: 700;
            text-align: center;
            background: linear-gradient(125deg, #0f2b3d, #1f5882);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            letter-spacing: -0.3px;
            margin-top: 0.2rem;
        }

        .register-sub {
            text-align: center;
            color: #5b6e8c;
            font-weight: 500;
            font-size: 0.85rem;
            border-bottom: 1px solid #eef2f7;
            display: inline-block;
            width: auto;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }

        /* alerts */
        .alert {
            padding: 0.85rem 1rem;
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
            background: #ffe9e7;
            border-left: 5px solid #e53e3e;
            color: #b91c1c;
        }
        .alert-success {
            background: #e6f6ec;
            border-left: 5px solid #2c7a4b;
            color: #1e5a3a;
        }
        .alert i {
            font-size: 1.1rem;
        }

        /* form styling */
        .form-row {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 0.2rem;
        }
        .form-group {
            margin-bottom: 1.3rem;
            flex: 1;
            min-width: 180px;
        }
        label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #334155;
            margin-bottom: 0.45rem;
        }
        label i {
            margin-right: 4px;
            font-size: 0.75rem;
            color: #4b6b8f;
        }
        .input-icon {
            position: relative;
            display: flex;
            align-items: center;
        }
        .input-icon i {
            position: absolute;
            left: 14px;
            color: #94a3b8;
            font-size: 0.95rem;
            pointer-events: none;
        }
        input, select {
            width: 100%;
            padding: 0.8rem 0.9rem 0.8rem 2.5rem;
            font-size: 0.9rem;
            font-family: 'Inter', sans-serif;
            border: 1.5px solid #e2e8f0;
            border-radius: 1.2rem;
            background: #ffffff;
            transition: all 0.2s ease;
            color: #0f172a;
            font-weight: 500;
        }
        select {
            padding-left: 2.5rem;
            appearance: none;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="%234b5563" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>');
            background-repeat: no-repeat;
            background-position: right 1rem center;
            cursor: pointer;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.2);
        }
        small {
            font-size: 0.7rem;
            color: #5b6e8c;
            display: block;
            margin-top: 6px;
            margin-left: 8px;
        }
        .btn-primary {
            background: linear-gradient(95deg, #1e3a5f, #0f2c44);
            border: none;
            padding: 0.85rem;
            border-radius: 2rem;
            font-weight: 700;
            font-size: 0.9rem;
            letter-spacing: 1px;
            color: white;
            width: 100%;
            cursor: pointer;
            transition: all 0.25s;
            box-shadow: 0 6px 14px rgba(0, 30, 60, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 8px;
        }
        .btn-primary i {
            font-size: 0.95rem;
        }
        .btn-primary:hover {
            background: linear-gradient(95deg, #113753, #091f31);
            transform: translateY(-2px);
            box-shadow: 0 12px 20px -8px rgba(0,0,0,0.35);
        }
        .btn-primary:active {
            transform: translateY(1px);
        }
        .login-link {
            text-align: center;
            margin-top: 1.8rem;
            font-size: 0.85rem;
            color: #475569;
        }
        .login-link a {
            color: #2563eb;
            font-weight: 700;
            text-decoration: none;
            margin-left: 5px;
            transition: color 0.2s;
        }
        .login-link a:hover {
            color: #1e40af;
            text-decoration: underline;
        }
        hr {
            margin: 1.2rem 0 0.5rem;
            border: none;
            height: 1px;
            background: linear-gradient(90deg, #e2e8f0, transparent);
        }
        .badge-warning {
            background: #fef9e3;
            padding: 0.45rem 1rem;
            border-radius: 2rem;
            font-size: 0.7rem;
            text-align: center;
            display: inline-block;
            width: 100%;
            margin-top: 1rem;
            color: #a16207;
            font-weight: 500;
        }
        @media (max-width: 600px) {
            .register-card {
                padding: 1.5rem;
            }
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            h2 {
                font-size: 1.6rem;
            }
        }
    </style>
</head>
<body>
<div class="register-container">
    <div class="register-card">
        <!-- LOGO -->
        <div class="logo-area">
            <img src="assets/logo-sispa.png" alt="KOR SISPA Crest" onerror="this.onerror=null; this.src='https://placehold.co/100x100?text=KOR+SISPA';">
        </div>

        <h2>KOR SISPA UTHM</h2>
        <div style="text-align: center; margin-bottom: 0.5rem;">
            <span class="register-sub">✦ Registration Form ✦</span>
        </div>

        <!-- PHP MESSAGES (session flash) -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></span>
            </div>
        <?php endif; ?>

        <!-- REGISTRATION FORM -->
        <form action="actions/register-action.php" method="POST" id="registerForm" novalidate>
            <!-- Full name and username row -->
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-user-pen"></i> Full Name *</label>
                    <div class="input-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="fullname" name="fullname" placeholder="Ahmad bin Abdullah" required>
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-at"></i> Username *</label>
                    <div class="input-icon">
                        <i class="fas fa-tag"></i>
                        <input type="text" id="username" name="username" placeholder="ahmad_uthm" required>
                    </div>
                    <small>Unique identifier, will be used for login</small>
                </div>
            </div>

            <!-- Email + IC Number row -->
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email Address *</label>
                    <div class="input-icon">
                        <i class="far fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="cadet@uthm.edu.my" required>
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-id-card"></i> IC Number *</label>
                    <div class="input-icon">
                        <i class="fas fa-fingerprint"></i>
                        <input type="text" id="ic_number" name="ic_number" placeholder="990101011234" pattern="[0-9]{12}" maxlength="12" autocomplete="off" required>
                    </div>
                    <small>12 digits without dashes (e.g., 990101011234)</small>
                </div>
            </div>

            <!-- phone + emergency row -->
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-phone-alt"></i> Phone Number</label>
                    <div class="input-icon">
                        <i class="fas fa-mobile-alt"></i>
                        <input type="text" id="phone" name="phone" placeholder="012-3456789">
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-hand-holding-heart"></i> Emergency Contact</label>
                    <div class="input-icon">
                        <i class="fas fa-address-book"></i>
                        <input type="text" id="emergency_contact" name="emergency_contact" placeholder="Name & Phone (e.g., Ali 019-8887766)">
                    </div>
                </div>
            </div>

            <!-- password + confirm row -->
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password *</label>
                    <div class="input-icon">
                        <i class="fas fa-key"></i>
                        <input type="password" id="password" name="password" required minlength="6">
                    </div>
                    <small>Minimum 6 characters (strongly recommended)</small>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-check-circle"></i> Confirm Password *</label>
                    <div class="input-icon">
                        <i class="fas fa-shield-alt"></i>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                </div>
            </div>

            <!-- role selection -->
            <div class="form-group">
                <label><i class="fas fa-user-tie"></i> Register As *</label>
                <div class="input-icon">
                    <i class="fas fa-users-viewfinder"></i>
                    <select id="role" name="role" required>
                        <option value="" disabled selected>— Select your role —</option>
                        <option value="Cadet">🪖 Cadet (Kor Sispa member)</option>
                        <option value="Trainer">📖 Trainer / Coach</option>
                    </select>
                </div>
                <small><i class="fas fa-clock"></i> All registrations require approval from existing administrators.</small>
            </div>

            <button type="submit" class="btn-primary">
                <i class="fas fa-user-plus"></i> REGISTER ACCOUNT
            </button>

            <div class="login-link">
                <i class="fas fa-sign-in-alt"></i> Already have an account?
                <a href="login.php">Login here ➡️</a>
            </div>
            <hr>
            <div class="badge-warning">
                <i class="fas fa-shield-heart"></i> ⚡ Your personal data is protected & used only for cadet management.
            </div>
        </form>
    </div>
</div>

<!-- enhanced frontend validation with real-time feedback -->
<script>
    (function() {
        const form = document.getElementById('registerForm');
        const password = document.getElementById('password');
        const confirmPw = document.getElementById('confirm_password');
        const icInput = document.getElementById('ic_number');
        const fullnameInput = document.getElementById('fullname');
        const usernameInput = document.getElementById('username');
        const emailInput = document.getElementById('email');

        // Helper to show error alert inside a dedicated area (avoid duplicates)
        function showInlineError(message) {
            // remove any existing dynamic alert inside card (not from session)
            const existingDynamicAlert = document.querySelector('.dynamic-validation-alert');
            if(existingDynamicAlert) existingDynamicAlert.remove();

            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger dynamic-validation-alert';
            alertDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i><span>${message}</span>`;
            const firstChild = form.querySelector('.form-row');
            if(firstChild) {
                form.insertBefore(alertDiv, firstChild);
            } else {
                form.insertBefore(alertDiv, form.firstChild);
            }
            // auto remove after 4 seconds
            setTimeout(() => {
                if(alertDiv && alertDiv.parentNode) alertDiv.remove();
            }, 4500);
        }

        function clearDynamicAlerts() {
            const existing = document.querySelectorAll('.dynamic-validation-alert');
            existing.forEach(el => el.remove());
        }

        // real-time password match styling (optional user feedback)
        function validatePasswordMatch() {
            if(confirmPw.value.length > 0 && password.value !== confirmPw.value) {
                confirmPw.style.borderColor = "#e53e3e";
                confirmPw.style.boxShadow = "0 0 0 2px rgba(229,62,62,0.1)";
            } else if(confirmPw.value.length > 0 && password.value === confirmPw.value) {
                confirmPw.style.borderColor = "#2c7a4b";
                confirmPw.style.boxShadow = "none";
            } else {
                confirmPw.style.borderColor = "#e2e8f0";
            }
        }

        password.addEventListener('input', validatePasswordMatch);
        confirmPw.addEventListener('input', validatePasswordMatch);

        // IC number format realtime check
        icInput.addEventListener('input', function(e) {
            let val = this.value.replace(/\D/g, '').slice(0,12);
            this.value = val;
            if(val.length === 12) {
                this.style.borderColor = "#2c7a4b";
            } else if(val.length > 0) {
                this.style.borderColor = "#eab308";
            } else {
                this.style.borderColor = "#e2e8f0";
            }
        });

        // Form submission validation (client side)
        form.addEventListener('submit', function(e) {
            clearDynamicAlerts();
            let errorMsg = '';

            // 1) Basic required (browser will catch empty required but custom checks also)
            if(!fullnameInput.value.trim()) errorMsg = 'Full name is required.';
            else if(!usernameInput.value.trim()) errorMsg = 'Username is required.';
            else if(usernameInput.value.length < 3) errorMsg = 'Username must be at least 3 characters.';
            else if(!emailInput.value.trim()) errorMsg = 'Email address is required.';
            else if(!emailInput.value.includes('@') || !emailInput.value.includes('.')) errorMsg = 'Please provide a valid email address.';
            else if(!icInput.value || icInput.value.length !== 12) errorMsg = 'IC Number must be exactly 12 digits.';
            else if(!password.value) errorMsg = 'Password is required.';
            else if(password.value.length < 6) errorMsg = 'Password must be at least 6 characters.';
            else if(password.value !== confirmPw.value) errorMsg = 'Passwords do not match.';
            else {
                const roleSelect = document.getElementById('role');
                if(!roleSelect.value) errorMsg = 'Please select a valid role (Cadet or Trainer).';
            }

            if(errorMsg) {
                e.preventDefault();
                showInlineError(errorMsg);
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return false;
            }

            // Additional: ensure IC contains only digits (already forced)
            if(!/^\d{12}$/.test(icInput.value)) {
                e.preventDefault();
                showInlineError('IC Number must contain exactly 12 numeric digits.');
                return false;
            }

            // Success message removal, form will submit to PHP
            return true;
        });
    })();
</script>
</body>
</html>