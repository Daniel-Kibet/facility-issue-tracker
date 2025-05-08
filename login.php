<?php
// Initialize variables
$errorMsg = '';

// Database connection
$host = 'localhost';
$dbname = 'facility_tracker';
$dbusername = 'root';
$dbpassword = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $dbusername, $dbpassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $errorMsg = "Database connection failed: " . $e->getMessage();
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Set session variables and redirect to dashboard
            session_start();
            $_SESSION['user'] = $user['username'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['loggedin'] = true;
            header('Location: index.php');
            exit;
        } else {
            $errorMsg = 'Invalid username or password';
        }
    } catch (PDOException $e) {
        $errorMsg = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issues Portal - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --dark-color: #34495e;
            --light-color: #ecf0f1;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        /* Background image with overlay */
        .bg-image {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('images/background.jpg');
            background-size: cover;
            background-position: center;
            z-index: -2;
            filter: brightness(1.1) contrast(1.1); /* Enhance image visibility */
        }
        
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.3); /* Lighter overlay to see background better */
            z-index: -1;
        }
        
        .login-box {
            width: 400px;
            position: relative;
            perspective: 1000px;
        }
        
        .login-container {
            width: 100%;
            background-color: rgba(255, 255, 255, 0.75); /* More transparent background */
            border-radius: 24px 24px 16px 16px; /* More curved top */
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px); /* Glass effect */
            animation: fadeIn 0.8s ease-in-out;
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            max-height: 85vh;
        }
        
        .login-top {
            background-color: rgba(52, 152, 219, 0.85);
            padding: 30px 0 50px;
            text-align: center;
            border-radius: 24px 24px 50% 50% / 24px 24px 30px 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: -20px;
            position: relative;
        }
        
        .login-top .icon {
            width: 80px;
            height: 80px;
            background-color: white;
            color: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin: 0 auto 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .login-top h1 {
            color: white;
            font-size: 28px;
            margin: 0;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
        }
        
        .login-content {
            padding: 30px 40px 40px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-form .form-group {
            margin-bottom: 25px;
        }
        
        .login-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group i.icon-left {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
        }
        
        .input-group i.toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
            cursor: pointer;
            padding: 5px;
            z-index: 10;
        }
        
        .input-group i.toggle-password:hover {
            color: var(--primary-color);
        }
        
        .login-form input[type="text"],
        .login-form input[type="password"] {
            width: 100%;
            padding: 14px 40px 14px 40px;
            border: 2px solid rgba(0,0,0,0.1);
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background-color: rgba(255, 255, 255, 0.8);
        }
        
        .login-form input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            outline: none;
            background-color: rgba(255, 255, 255, 0.95);
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .remember-me input {
            margin-right: 10px;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3);
        }
        
        .btn:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(52, 152, 219, 0.4);
        }
        
        .btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 5px rgba(52, 152, 219, 0.4);
        }
        
        .error-message {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid var(--danger-color);
            display: flex;
            align-items: center;
        }
        
        .error-message i {
            margin-right: 10px;
            font-size: 16px;
        }
        
        .forgot-password {
            text-align: center;
            margin-top: 25px;
        }
        
        .forgot-password a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 15px;
            transition: all 0.3s ease;
            padding: 5px 10px;
            border-radius: 4px;
        }
        
        .forgot-password a:hover {
            background-color: rgba(52, 152, 219, 0.1);
            text-decoration: none;
        }
        
        .signup-prompt {
            text-align: center;
            margin-top: 15px;
            color: rgba(0,0,0,0.7);
            font-size: 15px;
        }
        
        .signup-prompt a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .signup-prompt a:hover {
            text-decoration: underline;
        }
        
        .footer {
            text-align: center;
            margin-top: 20px;
            color: rgba(0,0,0,0.5);
            font-size: 13px;
            padding-bottom: 10px;
        }
        
        /* Responsive styles */
        @media (max-width: 500px) {
            .login-box {
                width: 90%;
            }
            
            .login-content {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Background image with overlay -->
    <div class="bg-image"></div>
    <div class="overlay"></div>
    
    <div class="login-box">
        <div class="login-container">
            <div class="login-top">
                <div class="icon">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <h1>Issues Portal</h1>
            </div>
            
            <div class="login-content">
                <?php if ($errorMsg): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errorMsg) ?>
                    </div>
                <?php endif; ?>
                
                <form class="login-form" method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-group">
                            <i class="fas fa-user icon-left"></i>
                            <input type="text" id="username" name="username" required placeholder="Enter your username">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-group">
                            <i class="fas fa-lock icon-left"></i>
                            <input type="password" id="password" name="password" required placeholder="Enter your password">
                            <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                        </div>
                    </div>
                    
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    
                    <button type="submit" class="btn">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </form>
                
                <div class="forgot-password">
                    <a href="#" onclick="alert('Please contact your administrator')">Forgot Password?</a>
                </div>
                
                <div class="signup-prompt">
                    <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
                </div>
                
                <div class="footer">
                    <p>Issues Portal &copy; <?= date('Y') ?> | All Rights Reserved</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Add simple animation when focusing on inputs
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-5px)';
                this.parentElement.style.transition = 'all 0.3s ease';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });
        
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        
        togglePassword.addEventListener('click', function() {
            // Toggle the type attribute
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            // Toggle the eye icon
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>