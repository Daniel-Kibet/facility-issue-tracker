<?php
// Initialize variables
$errorMsg = '';
$successMsg = '';

// Database connection
$host = 'localhost';
$dbname = 'facility_tracker';  // Replace with actual DB name
$username = 'root';  // Replace with DB username
$password = '';  // Replace with DB password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $errorMsg = "Database connection failed: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$errorMsg) {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $errorMsg = 'All fields are required';
    } elseif ($password !== $confirm_password) {
        $errorMsg = 'Passwords do not match';
    } elseif (strlen($password) < 8) {
        $errorMsg = 'Password must be at least 8 characters long';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);

            if ($stmt->rowCount() > 0) {
                $errorMsg = 'Username or email already exists';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$username, $email, $hashedPassword]);

                $successMsg = 'Registration successful! You can now <a href="login.php">login</a>.';
            }
        } catch (PDOException $e) {
            $errorMsg = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up - Issues Portal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    * {
        margin: 0; padding: 0; box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body, html {
        height: 100%;
    }

    .bg-image {
        background-image: url('background.jpg'); /* Change to your bg image */
        background-size: cover;
        background-position: center;
        filter: blur(5px);
        height: 100%;
        position: absolute;
        width: 100%;
        z-index: -2;
    }

    .overlay {
        background-color: rgba(0, 0, 0, 0.6);
        position: absolute;
        width: 100%;
        height: 100%;
        z-index: -1;
    }

    .login-box {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
    }

    .login-container {
        background-color: rgba(255, 255, 255, 0.95);
        padding: 40px;
        border-radius: 12px;
        width: 100%;
        max-width: 400px;
        box-shadow: 0 0 15px rgba(0,0,0,0.3);
    }

    .login-top {
        text-align: center;
        margin-bottom: 25px;
    }

    .login-top .icon {
        font-size: 3rem;
        color: #3498db;
    }

    h1 {
        font-size: 24px;
        margin-top: 10px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    label {
        display: block;
        margin-bottom: 6px;
        font-weight: 500;
    }

    .input-group {
        position: relative;
    }

    .input-group i.icon-left {
        position: absolute;
        top: 50%;
        left: 10px;
        transform: translateY(-50%);
        color: #777;
    }

    .input-group input {
        width: 100%;
        padding: 10px 10px 10px 35px;
        border: 1px solid #ccc;
        border-radius: 6px;
    }

    .toggle-password {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #999;
    }

    .btn {
        width: 100%;
        padding: 12px;
        background-color: #3498db;
        border: none;
        color: white;
        font-size: 16px;
        border-radius: 6px;
        cursor: pointer;
        transition: background 0.3s;
    }

    .btn:hover {
        background-color: #2980b9;
    }

    .login-prompt, .footer {
        text-align: center;
        margin-top: 15px;
        font-size: 14px;
    }

    .login-prompt a {
        color: #3498db;
        text-decoration: none;
    }

    .error-message, .success-message {
        margin-bottom: 15px;
        padding: 10px;
        border-radius: 6px;
        font-size: 14px;
    }

    .error-message {
        background-color: #f8d7da;
        color: #721c24;
    }

    .success-message {
        background-color: #d4edda;
        color: #155724;
    }
  </style>
</head>
<body>

<div class="bg-image"></div>
<div class="overlay"></div>

<div class="login-box">
    <div class="login-container">
        <div class="login-top">
            <div class="icon"><i class="fas fa-user-plus"></i></div>
            <h1>Create Account</h1>
        </div>

        <?php if ($errorMsg): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errorMsg) ?>
            </div>
        <?php endif; ?>

        <?php if ($successMsg): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?= $successMsg ?>
            </div>
        <?php endif; ?>

        <form class="login-form" method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-group">
                    <i class="fas fa-user icon-left"></i>
                    <input type="text" name="username" id="username" required placeholder="Choose a username">
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <div class="input-group">
                    <i class="fas fa-envelope icon-left"></i>
                    <input type="email" name="email" id="email" required placeholder="Enter your email">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-group">
                    <i class="fas fa-lock icon-left"></i>
                    <input type="password" name="password" id="password" required placeholder="Create a password (min 8 chars)">
                    <i class="fas fa-eye toggle-password" onclick="toggleVisibility('password')"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="input-group">
                    <i class="fas fa-lock icon-left"></i>
                    <input type="password" name="confirm_password" id="confirm_password" required placeholder="Confirm your password">
                    <i class="fas fa-eye toggle-password" onclick="toggleVisibility('confirm_password')"></i>
                </div>
            </div>

            <button type="submit" class="btn"><i class="fas fa-user-plus"></i> Sign Up</button>
        </form>

        <div class="login-prompt">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>

        <div class="footer">
            <p>Issues Portal &copy; <?= date('Y') ?> | All Rights Reserved</p>
        </div>
    </div>
</div>

<script>
  function toggleVisibility(fieldId) {
    const input = document.getElementById(fieldId);
    input.type = input.type === "password" ? "text" : "password";
  }
</script>

</body>
</html>
