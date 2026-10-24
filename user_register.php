<?php
session_start();
require_once 'config/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($username) || empty($email) || empty($password)) {
        $error = 'All fields are required.';
    } else {
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->rowCount() > 0) {
            $error = 'This username or email is already taken.';
        } else {
            // Hash the password for security
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $insert = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            if ($insert->execute([$username, $email, $hashedPassword])) {
                $success = 'Account created successfully! You can now log in.';
            } else {
                $error = 'Something went wrong. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - BookBase</title>
    <link rel="stylesheet" href="asset/index.css">
</head>

<body class="bg-[#0b0f19] min-h-screen w-full flex justify-center pt-16 m-0 p-4">
    <div class="auth-container">
        <h2>Create  Account</h2>
        <?php if (!empty($error)): ?>
            <div class="alert-error"><?= $error; ?></div><?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert-success"><?= $success; ?></div><?php endif; ?>

        <form action="user_register.php" method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="auth-btn">Register</button>
        </form>
        <div class="auth-link">
            Already Have an Account? <a href="user_login.php">Log In Here</a>
        </div>
    </div>
</body>

</html>