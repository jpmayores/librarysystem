<?php
session_start();
require_once 'config/db.php';

$error_message = '';
$max_attempts = 3;       // Maximum failed attempts before lockout
$lockout_duration = 30;  // Lockout duration in seconds

// 1. Check if the user is currently locked out
if (isset($_SESSION['lockout_time']) && time() < $_SESSION['lockout_time']) {
    $remaining_time = $_SESSION['lockout_time'] - time();
    $error_message = "Too many failed attempts. Please wait for $remaining_time seconds.";
} else {
    // Reset counters if lockout time has expired
    if (isset($_SESSION['lockout_time']) && time() >= $_SESSION['lockout_time']) {
        unset($_SESSION['lockout_time']);
        $_SESSION['failed_attempts'] = 0;
    }

    // Kapag naka-login na, re-direct agad sa admin dashboard
    if (isset($_SESSION['isAdminLoggedIn']) && $_SESSION['isAdminLoggedIn'] === true) {
        header("Location: admin.php");
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (!empty($username) && !empty($password)) {
            // Prepared Statement gamit ang PDO
            $stmt = $conn->prepare("SELECT id, password FROM admins WHERE username = :username");
            $stmt->execute([':username' => $username]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                // Tignan kung tama ang password (pwedeng palitan ng password_verify kung hashed na)
                if ($password === $row['password']) {
                    // Success: Clear failed attempts and set session
                    unset($_SESSION['failed_attempts']);
                    unset($_SESSION['lockout_time']);
                    
                    $_SESSION['isAdminLoggedIn'] = true;
                    $_SESSION['admin_id'] = $row['id'];
                    $_SESSION['admin_username'] = $username;

                    header("Location: admin.php");
                    exit();
                } else {
                    // Maling password: Dagdagan ang failed attempts counter
                    $_SESSION['failed_attempts'] = ($_SESSION['failed_attempts'] ?? 0) + 1;
                    $remaining = $max_attempts - $_SESSION['failed_attempts'];

                    if ($_SESSION['failed_attempts'] >= $max_attempts) {
                        $_SESSION['lockout_time'] = time() + $lockout_duration;
                        $error_message = "Too many failed attempts. System locked for $lockout_duration seconds.";
                    } else {
                        $error_message = "Incorrect password! You have $remaining attempt(s) remaining.";
                    }
                }
            } else {
                // Hindi nahanap ang username
                $_SESSION['failed_attempts'] = ($_SESSION['failed_attempts'] ?? 0) + 1;
                $error_message = "Username not found!";
            }
        } else {
            $error_message = "Please fill up all fields.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Portal</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#0b0f19] flex items-center justify-center min-h-screen text-slate-100 font-sans">

    <div class="bg-[#1e293b] p-8 rounded-2xl shadow-2xl w-full max-w-md border border-slate-800">
        <h2 class="text-2xl font-bold text-center text-slate-100 mb-6 tracking-wide">Admin Portal</h2>

        <!-- Error Message Alert -->
        <?php if (!empty($error_message)): ?>
            <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 text-red-400 text-sm rounded-xl text-center font-medium">
                <?= htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="admin_login.php">
            <!-- Username Field -->
            <div class="mb-4">
                <label for="username" class="block text-sm font-medium text-slate-400 mb-2">Username</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    required 
                    placeholder="Enter admin username"
                    class="w-full px-4 py-3 bg-[#0f172a] border border-slate-700 rounded-xl text-slate-100 placeholder-slate-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                />
            </div>

            <!-- Password Field -->
            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-slate-400 mb-2">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required 
                    placeholder="Enter password"
                    class="w-full px-4 py-3 bg-[#0f172a] border border-slate-700 rounded-xl text-slate-100 placeholder-slate-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                />
            </div>

            <!-- Submit Button -->
            <button 
                type="submit" 
                class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 text-white font-semibold py-3 rounded-xl shadow-lg shadow-blue-600/20 transition-all duration-150 cursor-pointer"
            >
                Login
            </button>
        </form>

        <!-- Back Link -->
     <div class="mt-6 text-center">
    <a href="index.php" class="inline-flex items-center gap-2 text-sm text-slate-400 hover:text-slate-200 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Back to Main Page
    </a>
</div>

</body>
</html>