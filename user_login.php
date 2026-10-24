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
<body style="background-color: #0b0f19; margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;">    <div class="auth-container">
        
        <!-- Brand Logo & Header -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-blue-600/20 text-blue-500 mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-slate-100 tracking-wide">PageFlow</h2>
            <p class="text-xs text-slate-400 mt-1">Member Portal Login</p>
        </div>

        <!-- Error Message kung meron man -->
        <?php if (!empty($error)): ?>
            <div class="bg-red-500/10 border border-red-500 text-red-400 text-xs p-2.5 rounded-lg mb-4 text-center">
                <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form action="user_login.php" method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <button type="submit" class="auth-btn">
                Login
            </button>
        </form>

        <!-- Footer Link -->
        <p class="auth-link">
            No account? <a href="user_register.php">Create Here</a>
        </p>

    </div>

</body>
</html>