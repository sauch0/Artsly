<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Artsly | Become a Member</title>
    <link rel="stylesheet" href="index.css">
    <style>
        .auth-card {
            max-width: 500px;
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
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            color: white;
            transition: var(--transition);
        }
        .form-control:focus {
            outline: none;
            border-color: var(--accent);
            background: rgba(255,255,255,0.1);
        }
        select.form-control option {
            background-color: var(--bg-dark);
            color: white;
        }
    </style>
</head>
<body style="display: flex; justify-content: center; align-items: center; min-height: 100vh;">
    
    <div class="bg-blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
    </div>

    <div class="auth-card">
        <div style="text-align: center; margin-bottom: 2rem;">
            <div class="logo">Artsly</div>
        </div>

        <?php
        if ($_SERVER['REQUEST_METHOD'] == 'POST'):
            include 'database/dbconnect.php';
            $name = mysqli_real_escape_string($conn, $_POST['name']);
            $email = mysqli_real_escape_string($conn, $_POST['email']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $role = mysqli_real_escape_string($conn, $_POST['role']);

            // Check if email already exists
            $checkEmail = "SELECT id FROM users WHERE email='$email'";
            $checkResult = $conn->query($checkEmail);

            if ($checkResult && $checkResult->num_rows > 0) {
                echo "<p style='color: #ef4444; text-align: center; margin-bottom: 1rem;'>Email already used</p>";
            } else {
                $sql = "INSERT INTO users (name, email, password, role)
                        VALUES ('$name', '$email', '$password', '$role')";

                if ($conn->query($sql) === TRUE) {
                    echo "<p style='color: #10b981; text-align: center; margin-bottom: 1rem;'>Registration successful. <a href='login.php' style='color: var(--accent);'>Login here</a></p>";
                } else {
                    echo "<p style='color: #ef4444; text-align: center; margin-bottom: 1rem;'>Error: " . $conn->error . "</p>";
                }
            }
        endif;
        ?>

        <form method="POST" id="registerForm">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" id="name" class="form-control" placeholder="John Doe" maxlength="20">
                <small class="validation-error" id="nameError" style="color: #ef4444; font-size: 0.8rem; display: none; margin-top: 0.5rem;"></small>
            </div>
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
            <div class="form-group">
                <label>Role</label>
                <select name="role" class="form-control">
                    <option value="user">Art Enthusiast (User)</option>
                    <option value="artist">Visionary (Artist)</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; margin-top: 1rem;">
                Create Account
            </button>
        </form>
        
        <p style="text-align: center; margin-top: 2rem; color: var(--secondary); font-size: 0.9rem;">
            Already have an account? <a href="login.php" style="color: var(--accent); text-decoration: none;">Log in</a>
        </p>
    </div>

    <script>
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            let isValid = true;
            const name = document.getElementById('name');
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            
            const nameError = document.getElementById('nameError');
            const emailError = document.getElementById('emailError');
            const passwordError = document.getElementById('passwordError');

            // Reset errors
            [nameError, emailError, passwordError].forEach(err => err.style.display = 'none');
            [name, email, password].forEach(el => el.style.borderColor = 'rgba(255, 255, 255, 0.1)');

            // Name validation
            if (!name.value.trim()) {
                nameError.textContent = 'Full name is required';
                nameError.style.display = 'block';
                name.style.borderColor = '#ef4444';
                isValid = false;
            } else if (name.value.trim().length > 20) {
                nameError.textContent = 'Name must be 20 characters or less';
                nameError.style.display = 'block';
                name.style.borderColor = '#ef4444';
                isValid = false;
            }

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
            } else if (password.value.length < 6) {
                passwordError.textContent = 'Password must be at least 6 characters';
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