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


if (isset($_GET['delete_id'])) {
    $deleteId = (int)$_GET['delete_id'];
    
    try {
        $delStmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $delStmt->execute([$deleteId]);
        
        // I-redirect pabalik sa users view para mawala ang URL parameter ng delete
        header("Location: admin.php?view=users");
        exit();
    } catch (PDOException $e) {
        // Ipakita ang error kung sakaling may issue sa pagbura
        $error = "Hindi ma-delete ang user: " . $e->getMessage();
    }
}

// 1. Kunin ang search value
$searchUser = isset($_GET['search_user']) ? trim($_GET['search_user']) : '';

$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $limit;

if ($searchUser !== '') {
    $searchTerm = "%" . $searchUser . "%";
    
    // Bilangin ang filtered records
    $countStmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username LIKE ? OR email LIKE ?");
    $countStmt->execute([$searchTerm, $searchTerm]);
    $totalUsers = $countStmt->fetchColumn();

    // Kunin ang mga filtered users para sa table
    $stmt = $conn->prepare("SELECT * FROM users WHERE username LIKE ? OR email LIKE ? ORDER BY id DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $searchTerm, PDO::PARAM_STR);
    $stmt->bindValue(2, $searchTerm, PDO::PARAM_STR);
    $stmt->bindValue(3, $limit, PDO::PARAM_INT);
    $stmt->bindValue(4, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $usersList = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Kunin ang lahat kung walang search
    $countStmt = $conn->query("SELECT COUNT(*) FROM users");
    $totalUsers = $countStmt->fetchColumn();

    $stmt = $conn->prepare("SELECT * FROM users ORDER BY id DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $usersList = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$totalPages = ceil($totalUsers / $limit);
?>