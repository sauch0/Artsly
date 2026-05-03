<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | Artsly</title>
    <link rel="stylesheet" href="index.css">
    <style>
        .auth-card {
            max-width: 450px;
            width: 90%;
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            padding: 3rem;
            border-radius: 24px;
            box-shadow: 0 40px 100px rgba(0,0,0,0.5);
            animation: fadeInUp 0.8s ease;
        }

        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--secondary);
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            color: white;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent);
            background: rgba(255, 255, 255, 0.1);
        }

        /* Modal Styles */
        .error-modal {
            position: fixed;
            top: 2rem;
            right: 2rem;
            background: rgba(239, 68, 68, 0.15);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ff9494;
            padding: 1.5rem 2rem;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            display: flex;
            align-items: flex-start;
            gap: 1.2rem;
            z-index: 2000;
            animation: slideInRight 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            max-width: 450px;
            line-height: 1.5;
        }

        @keyframes slideInRight {
            from { transform: translateX(120%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .close-modal {
            cursor: pointer;
            opacity: 0.6;
            transition: 0.3s;
        }

        .close-modal:hover { opacity: 1; }
    </style>
</head>
<body style="display: flex; justify-content: center; align-items: center; min-height: 100vh;">

    <div class="bg-blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
    </div>

    <?php
    session_start();
    $showError = false;
    $errorMessage = "";

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        include 'database/dbconnect.php';
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = $_POST['password'];

        $sql = "SELECT * FROM users WHERE email='$email'";
        $result = $conn->query($sql);
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'banned') {
                $showError = true;
                $reason = $user['ban_reason'] ? htmlspecialchars($user['ban_reason']) : "No reason provided";
                $errorMessage = "<strong>Access Denied:</strong> Your account has been banned.<br><br>
                                 <strong>Reason:</strong> $reason<br><br>
                                 Please contact <a href='mailto:admin@artsly.com' style='color:#ef4444; font-weight:700'>admin@artsly.com</a> to appeal.";
            } else {
                $_SESSION['user'] = $user;
                if ($user['role'] === 'admin') {
                    header('Location: admin_dashboard.php');
                } elseif ($user['role'] === 'artist') {
                    header('Location: artist_dashboard.php');
                } else {
                    header('Location: landing.php');
                }
                exit;
            }
        } else {
            $showError = true;
            $errorMessage = "invalid email or password please login again";
        }
    }
    ?>

    <?php if ($showError): ?>
    <div class="error-modal" id="errorPopup">
        <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <span><?php echo $errorMessage; ?></span>
        <div class="close-modal" onclick="document.getElementById('errorPopup').style.display='none'">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </div>
    </div>
    <script>
        setTimeout(() => {
            const popup = document.getElementById('errorPopup');
            if(popup) {
                popup.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                popup.style.opacity = '0';
                popup.style.transform = 'translateX(120%)';
                setTimeout(() => popup.remove(), 500);
            }
        }, 5000);
    </script>
    <?php endif; ?>

    <div class="auth-card">
        <div style="text-align: center; margin-bottom: 2rem;">
            <div class="logo">Artsly</div>
        </div>

        <form method="POST" id="loginForm">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="name@company.com">
                <small class="validation-error" id="emailError" style="color: #ef4444; font-size: 0.8rem; display: none; margin-top: 0.5rem;"></small>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••">
                <small class="validation-error" id="passwordError" style="color: #ef4444; font-size: 0.8rem; display: none; margin-top: 0.5rem;"></small>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; margin-top: 1rem;">
                Sign In
            </button>
        </form>

        <p style="text-align: center; margin-top: 2rem; color: var(--secondary); font-size: 0.9rem;">
            Don't have an account? <a href="register.php" style="color: var(--accent); text-decoration: none;">Create one now</a>
        </p>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            let isValid = true;
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            const emailError = document.getElementById('emailError');
            const passwordError = document.getElementById('passwordError');

            // Reset errors
            emailError.style.display = 'none';
            passwordError.style.display = 'none';
            email.style.borderColor = 'rgba(255, 255, 255, 0.1)';
            password.style.borderColor = 'rgba(255, 255, 255, 0.1)';

            // Email validation
            if (!email.value) {
                emailError.textContent = 'Email is required';
                emailError.style.display = 'block';
                email.style.borderColor = '#ef4444';
                isValid = false;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
                emailError.textContent = 'Please enter a valid email';
                emailError.style.display = 'block';
                email.style.borderColor = '#ef4444';
                isValid = false;
            }

            // Password validation
            if (!password.value) {
                passwordError.textContent = 'Password is required';
                passwordError.style.display = 'block';
                password.style.borderColor = '#ef4444';
                isValid = false;
            }

            if (!isValid) e.preventDefault();
        });
    </script>
    <footer><p>&copy; 2026 Artsly. By Saumya Chitrakar</p></footer>
</body>
</html>
