<?php
// users_logic.php (PDO Version)
$limit = 10; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $limit;

$search = isset($_GET['search_user']) ? trim($_GET['search_user']) : '';

if (!empty($search)) {
    $searchTerm = "%" . $search . "%";
    
    // Total rows para sa search
    $stmtTotal = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE username LIKE ? OR email LIKE ?");
    $stmtTotal->execute([$searchTerm, $searchTerm]);
    $totalRows = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Users list para sa current page na may search
    $stmt = $conn->prepare("SELECT * FROM users WHERE username LIKE ? OR email LIKE ? LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $searchTerm, PDO::PARAM_STR);
    $stmt->bindValue(2, $searchTerm, PDO::PARAM_STR);
    $stmt->bindValue(3, (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(4, (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $usersList = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Total rows para sa lahat ng users
    $totalResult = $conn->query("SELECT COUNT(*) as total FROM users");
    $totalRows = $totalResult->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Users list para sa current page
    $stmt = $conn->prepare("SELECT * FROM users LIMIT ? OFFSET ?");
    $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(2, (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $usersList = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$totalPages = ceil($totalRows / $limit);
?>