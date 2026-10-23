<?php
session_start();

// 1. I-clear ang session variables
$_SESSION = array();

// 2. KUNIN MUNA ANG COOKIE PARAMS BAGO GAMITIN SA SETCOOKIE
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params(); // <-- ETO ANG NAWALA KANINA
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"], 
        $params["secure"], 
        $params["httponly"]
    );
}

// 3. Sirain ang session
session_destroy();

// 4. I-redirect pabalik sa login page
header("Location: ../admin_login.php");
exit();
?>