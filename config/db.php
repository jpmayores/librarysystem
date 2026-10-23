<?php
$host = getenv('DB_HOST') ?: 'aws-1-ap-northeast-1.pooler.supabase.com';
$port = getenv('DB_PORT') ?: '5432';
$db   = getenv('DB_NAME') ?: 'postgres';
$user = getenv('DB_USER') ?: 'postgres.pkqtflczadmejdfzyxxu';
$pass = getenv('DB_PASS') ?: 'mayoresphilip2006';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;";
    // Pinalitan ang $pdo papuntang $conn dito:
    $conn = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Connection Failed: " . $e->getMessage());
}
?>