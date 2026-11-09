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

// DEBUGGER: I-check kung ano ang nakuha mula sa URL
$searchUser = isset($_GET['search_user']) ? trim($_GET['search_user']) : '';

echo "<div style='background: #1e293b; color: #38bdf8; padding: 10px; margin: 10px; font-family: monospace; z-index: 9999; position: relative;'>";
echo "<strong>DEBUGGER INFO:</strong><br>";
echo "Search Input Value: '" . htmlspecialchars($searchUser) . "'<br>";

$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $limit;

if ($searchUser !== '') {
    $searchTerm = "%" . $searchUser . "%";
    echo "SQL Mode: FILTERED (Searching for: $searchTerm)<br>";
    
    $countStmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username LIKE ? OR email LIKE ?");
    $countStmt->execute([$searchTerm, $searchTerm]);
    $totalUsers = $countStmt->fetchColumn();
    echo "Total Filtered Users Found: " . $totalUsers . "<br>";

    $stmt = $conn->prepare("SELECT * FROM users WHERE username LIKE ? OR email LIKE ? ORDER BY id DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $searchTerm, PDO::PARAM_STR);
    $stmt->bindValue(2, $searchTerm, PDO::PARAM_STR);
    $stmt->bindValue(3, $limit, PDO::PARAM_INT);
    $stmt->bindValue(4, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $usersList = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    echo "SQL Mode: ALL USERS (No search query)<br>";
    
    $countStmt = $conn->query("SELECT COUNT(*) FROM users");
    $totalUsers = $countStmt->fetchColumn();

    $stmt = $conn->prepare("SELECT * FROM users ORDER BY id DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $usersList = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
echo "Users fetched in array: " . count($usersList) . "<br>";
echo "</div>";

$totalPages = ceil($totalUsers / $limit);
?>