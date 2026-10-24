<?php
session_start();
require_once 'config/db.php';

$error = '';

// RATE LIMITING CONFIGURATION
$max_attempts = 3; // Max na maling login attempts
$lockout_time = 60; // Oras ng pagka-lock sa segundo (1 minuto)

// I-initialize ang tracking sa session kung wala pa
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['lockout_time'] = 0;
}

// Suriin kung naka-lockout pa ang user
if ($_SESSION['login_attempts'] >= $max_attempts) {
    $time_left = $_SESSION['lockout_time'] - time();
    if ($time_left > 0) {
        $error = "Tinatangka kang mag-log in nang masyadong madalas. Subukan muli pagkatapos ng " . $time_left . " segundo.";
    } else {
        // Reset kapag lumipas na ang lockout time
        $_SESSION['login_attempts'] = 0;
        $_SESSION['lockout_time'] = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['login_attempts'] < $max_attempts) {
    // Linisin ang input para maiwasan ang XSS / maling datos
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'Punan ang lahat ng fields.';
    } else {
        // SQL INJECTION PROTECTION: Gumagamit ng PDO Prepared Statements
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // I-reset ang login attempts kapag nagtagumpay
            $_SESSION['login_attempts'] = 0;
            $_SESSION['lockout_time'] = 0;

            // Session fixation protection
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            // XSS PROTECTION: Gamitan ng htmlspecialchars kapag binabasa o ipinapakita ang username
            $_SESSION['username'] = htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8');

            header('Location: index.php');
            exit;
        } else {
            // Dagdagan ang failed attempts para sa Rate Limiting
            $_SESSION['login_attempts']++;
            if ($_SESSION['login_attempts'] >= $max_attempts) {
                $_SESSION['lockout_time'] = time() + $lockout_time;
                $error = "Tumaas ang bilang ng maling pagsubok. Ang form na ito ay pansamantalang naka-lock ng 1 minuto.";
            } else {
                $rem = $max_attempts - $_SESSION['login_attempts'];
                $error = "Maling username o password. Mayroon ka pang {$rem} na natitirang pagsubok.";
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
    <title>User Login - BookBase</title>
    <link rel="stylesheet" href="asset/index.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body style="background-color: #0b0f19; margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;">
    <!-- Card Container -->
    <div style="width: 100%; max-width: 400px; background: #1e293b; padding: 32px; border-radius: 16px; border: 1px solid #334155; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5); box-sizing: border-box;">
        
        <!-- Brand Logo & Header -->
        <div style="text-align: center; margin-bottom: 24px;">
            <div style="display: inline-flex; align-items: center; justify-content: center; width: 48px; height: 48px; background: rgba(37, 99, 235, 0.2); color: #3b82f6; border-radius: 12px; margin-bottom: 12px;">
                <svg style="width: 24px; height: 24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
            </div>
            <h2 style="font-size: 24px; font-weight: bold; color: #f8fafc; margin: 0; letter-spacing: 0.5px;">PageFlow</h2>
            <p style="font-size: 12px; color: #94a3b8; margin-top: 4px;">Member Portal Login</p>
        </div>

        <!-- Error Message kung meron man -->
        <?php if (!empty($error)): ?>
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #f87171; font-size: 13px; padding: 10px; border-radius: 8px; margin-bottom: 16px; text-align: center;">
                <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form action="user_login.php" method="POST">
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 500; color: #cbd5e1; margin-bottom: 6px;">Username</label>
                <input type="text" name="username" required 
                    style="width: 100%; padding: 10px 14px; border-radius: 8px; background: #0f172a; border: 1px solid #334155; color: #f8fafc; font-size: 14px; box-sizing: border-box; outline: none;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 12px; font-weight: 500; color: #cbd5e1; margin-bottom: 6px;">Password</label>
                <input type="password" name="password" required 
                    style="width: 100%; padding: 10px 14px; border-radius: 8px; background: #0f172a; border: 1px solid #334155; color: #f8fafc; font-size: 14px; box-sizing: border-box; outline: none;">
            </div>

            <button type="submit" 
                style="width: 100%; padding: 11px; background: #2563eb; color: white; font-weight: 500; border-radius: 8px; border: none; font-size: 14px; cursor: pointer;">
                Login
            </button>
        </form>

        <!-- Footer Link -->
        <p style="text-align: center; font-size: 12px; color: #94a3b8; margin-top: 20px; margin-bottom: 0;">
            No account? <a href="user_register.php" style="color: #60a5fa; text-decoration: none;">Create Here</a>
        </p>

    </div>

</body>
</html>