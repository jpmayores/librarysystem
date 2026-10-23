<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: user_login.php');
    exit();
}
require_once 'config/db.php';
require_once 'actions/send_borrow_email.php'; 

if (!isset($_SESSION['user_id'])) {
    header('Location: user_login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'borrow_book') {
    $book_id = intval($_POST['book_id'] ?? 0);
    $borrow_days = intval($_POST['borrow_days'] ?? 7);
    $user_id = $_SESSION['user_id'];

    $borrow_date = date('Y-m-d H:i:s');
    $due_date = date('Y-m-d H:i:s', strtotime("+{$borrow_days} days"));

    try {
        $conn->beginTransaction();

        // I-insert ang record sa transactions table
        $trans_stmt = $conn->prepare("INSERT INTO transactions (book_id, member_id, borrow_date, due_date, return_date) VALUES (?, ?, ?, ?, NULL)");
        $trans_stmt->execute([$book_id, $user_id, $borrow_date, $due_date]);

        // I-update ang status ng libro sa books table para maging 'Borrowed'
        $book_stmt = $conn->prepare("UPDATE books SET status = 'Borrowed' WHERE id = ?");
        $book_stmt->execute([$book_id]);

        // Kunin ang info ng user at book para sa email
        $userEmailQuery = $conn->prepare("SELECT name, email FROM members WHERE id = ?");
        $userEmailQuery->execute([$user_id]);
        $userInfo = $userEmailQuery->fetch(PDO::FETCH_ASSOC);

        $bookTitleQuery = $conn->prepare("SELECT title FROM books WHERE id = ?");
        $bookTitleQuery->execute([$book_id]);
        $bookInfo = $bookTitleQuery->fetch(PDO::FETCH_ASSOC);

        if ($userInfo && $bookInfo) {
            // Tawagin na ang function dahil nakarehistro na ito sa itaas
            $emailResult = sendBorrowConfirmationEmail(
                $userInfo['email'], 
                $userInfo['name'], 
                $bookInfo['title'], 
                $borrow_days, 
                $due_date
            );

            if ($emailResult !== true) {
                // I-log kung sakaling may issue sa pagpapadala
                error_log("Email sending failed: " . $emailResult);
            }
        }

        $conn->commit();
        header('Location: user_dashboard.php?borrow=success');
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        echo "Error: " . $e->getMessage();
    }
}

// Kunin ang listahan ng mga libro
try {
    $stmt = $conn->prepare("SELECT * FROM books");
    $stmt->execute();
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $books = [];
}
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

// Settings para sa Pagination
$limit = 6; // Bilang ng libro bawat pahina
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1)
    $page = 1;
$offset = ($page - 1) * $limit;

try {
    // Kunin ang lahat ng available na kategorya para sa Dropdown
    $cat_stmt = $conn->query("SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND category != ''");
    $categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);

    // Bumuo ng Dynamic SQL Query
    $whereClauses = [];
    $params = [];

    if (!empty($search)) {
        $whereClauses[] = "(title LIKE ? OR author LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if (!empty($category)) {
        $whereClauses[] = "category = ?";
        $params[] = $category;
    }

    $whereSQL = '';
    if (count($whereClauses) > 0) {
        $whereSQL = " WHERE " . implode(" AND ", $whereClauses);
    }

    // 1. Bilangin ang kabuuang rekord para sa Pagination calculation
    $count_stmt = $conn->prepare("SELECT COUNT(*) FROM books" . $whereSQL);
    $count_stmt->execute($params);
    $totalBooks = $count_stmt->fetchColumn();
    $totalPages = ceil($totalBooks / $limit);

    // 2. Kunin ang mga libro para sa kasalukuyang pahina (LIMIT at OFFSET)
    $sql = "SELECT * FROM books" . $whereSQL . " LIMIT $limit OFFSET $offset";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $books = [];
    $categories = ['Horror', 'Fantasy', 'Food'];
    $totalPages = 1;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Catalog - BookBase</title>
    <link rel="stylesheet" href="asset/index.css">

</head>

<body>

    <!-- Header Navigation -->
    <header class="portal-header">
        <div class="portal-logo">BookBase</div>
        <div class="portal-header-actions">
            <a href="user_dashboard.php" class="portal-user-icon-btn" title="My Dashboard">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </a>
            <button id="darkModeToggle" class="theme-toggle-btn">Light Mode</button>
            <a href="user_logout.php" class="portal-borrow-btn"
                style="background-color: #dc3545; text-decoration: none;">Logout</a>
        </div>
    </header>

    <!-- Main Catalog Container -->
    <main class="portal-main-container">
        <div class="portal-catalog-header">
            <h2 class="portal-catalog-title">Library Catalog</h2>
        </div>

        <!-- SEARCH AND CATEGORY FILTER FORM -->
        <form method="GET" action="index.php" class="catalog-filter-bar">
            <input type="text" name="search" placeholder="Search by Title or Author..."
                value="<?= htmlspecialchars($search); ?>">

            <select name="category">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat); ?>" <?= $category === $cat ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($cat); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="filter-btn">Search</button>
            <?php if (!empty($search) || !empty($category)): ?>
                <a href="index.php" class="reset-btn">Reset</a>
            <?php endif; ?>
        </form>

        <!-- BOOK GRID -->
        <div class="portal-book-grid">
            <?php if (!empty($books)): ?>
                <?php foreach ($books as $book): ?>
                    <div class="portal-book-card">
                        <?php
                        $imgUrl = $book['image_url'] ?? '';
                        if (!empty($imgUrl)) {
                            if (strpos($imgUrl, 'http') === 0 || strpos($imgUrl, 'uploads/') === 0) {
                                $coverImg = $imgUrl;
                            } else {
                                $coverImg = 'uploads/' . ltrim($imgUrl, '/');
                            }
                        } else {
                            $coverImg = 'https://via.placeholder.com/200x300?text=No+Cover';
                        }
                        ?>
                        <img src="<?= htmlspecialchars($coverImg); ?>" alt="Book Cover" class="portal-book-cover">
                        <div class="portal-book-info">
                            <div class="portal-book-title"><?= htmlspecialchars($book['title']); ?></div>
                            <div class="portal-book-author">by <?= htmlspecialchars($book['author']); ?></div>
                            <?php if (!empty($book['category'])): ?>
                                <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
                                    Category: <strong><?= htmlspecialchars($book['category']); ?></strong>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="portal-book-meta">
                            <?php if ($book['status'] === 'Available'): ?>
                                <span class="portal-status-tag available">Available</span>
                                <button type="button" class="portal-borrow-btn"
                                    onclick="openBorrowModal(<?= $book['id']; ?>, '<?= htmlspecialchars($book['title'], ENT_QUOTES); ?>')">Borrow</button>
                            <?php else: ?>
                                <span class="portal-status-tag borrowed">Borrowed</span>
                                <button class="portal-borrow-btn disabled" disabled>Borrowed</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="portal-empty-state"
                    style="grid-column: 1 / -1; text-align: center; padding: 40px; color: var(--text-muted);">
                    <p>No books found matching your criteria.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- PAGINATION CONTROLS -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination-container">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="index.php?page=<?= $i; ?>&search=<?= urlencode($search); ?>&category=<?= urlencode($category); ?>"
                        class="pagination-link <?= $page == $i ? 'active' : ''; ?>">
                        <?= $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- POPUP MODAL FOR BORROW DURATION -->
    <div id="borrowModal" class="modal-overlay">
        <div class="modal-content">
            <h3 id="modalBookTitle">Borrow Book</h3>
            <form action="index.php" method="POST">
                <input type="hidden" name="action" value="borrow_book">
                <input type="hidden" id="modalBookId" name="book_id" value="">

                <div style="margin-bottom: 15px;">
                    <label>Select Borrow Duration (Days):</label>
                    <select name="borrow_days" required>
                        <option value="7">7 Days (1 Week)</option>
                        <option value="14">14 Days (2 Weeks)</option>
                        <option value="30">30 Days (1 Month)</option>
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="button" class="modal-btn-cancel" onclick="closeBorrowModal()">Cancel</button>
                    <button type="submit" class="modal-btn-submit">Confirm Borrow</button>
                </div>
            </form>
        </div>
    </div>


    <script src="asset/script.js">
    </script>
</body>

</html>