<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: user_login.php');
    exit;
}

require_once 'config/db.php';
$user_id = $_SESSION['user_id'] ?? null;

// 1. I-handle ang POST request para sa pag-return ng libro nang ligtas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'return_book') {
    $book_id = $_POST['book_id'] ?? null;

    if (!empty($book_id) && $user_id) {
        try {
            // Gamitin ang transaction para sabay mag-update ang transactions at books table
            $conn->beginTransaction();

            // 1. I-update ang status at return_date sa transactions table
            $trans_stmt = $conn->prepare("
                UPDATE transactions 
                SET status = 'Returned', return_date = NOW() 
                WHERE book_id = ? AND member_id = ? AND LOWER(status) = 'borrowed'
            ");
            $trans_stmt->execute([$book_id, $user_id]);

            // 2. I-update ang status ng mismong libro sa books table patungong 'Available'
            $book_stmt = $conn->prepare("
                UPDATE books 
                SET status = 'Available' 
                WHERE id = ?
            ");
            $book_stmt->execute([$book_id]);

            $conn->commit();
            header("Location: user_dashboard.php");
            exit();

        } catch (Exception $e) {
            $conn->rollBack();
            echo "Error sa pag-return: " . $e->getMessage();
            exit();
        }
    }
}
try {
    $user_id = $_SESSION['user_id'];

    // 1. Kunin ang mga currently borrowed books para sa variable na `$borrowedBooks`
// 1. Kunin ang mga currently borrowed books at i-save sa variable na `$currently_borrowed`
    $stmt = $conn->prepare("
    SELECT t.*, b.title, b.author, b.image_url, b.id AS book_id 
    FROM transactions t 
    JOIN books b ON t.book_id = b.id 
    WHERE t.member_id = ? AND LOWER(t.status) = 'borrowed'
");
    $stmt->execute([$user_id]);
    $currently_borrowed = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Kunin din ang borrowing history para naman sa history table
    $history_stmt = $conn->prepare("
    SELECT t.*, b.title, b.author 
    FROM transactions t 
    JOIN books b ON t.book_id = b.id 
    WHERE t.member_id = ? 
    ORDER BY t.borrow_date DESC
");
    $history_stmt->execute([$user_id]);
    $historyBooks = $history_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Stats calculation
    $activeCount = count($currently_borrowed);
    $historyCount = count($historyBooks);
    $totalFines = array_sum(array_column($historyBooks, 'fine'));

} catch (PDOException $e) {
    $borrowedBooks = [];
    $historyBooks = [];
    $activeCount = 0;
    $historyCount = 0;
    $totalFines = 0;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - BookBase</title>
    <link rel="stylesheet" href="asset/index.css">
    <style>
 .btn-return {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: #ffffff;
    border: none;
    padding: 6px 16px;
    font-size: 13px;
    font-weight: 600;
    border-radius: 6px;
    cursor: pointer;
    transition: transform 0.1s ease, box-shadow 0.2s ease;
}

.btn-return:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
}


    </style>
</head>

<body>

    <!-- Header Navigation -->
    <header class="portal-header">
        <div class="portal-logo">BookBase</div>
        <div class="portal-header-actions">
            <a href="index.php" class="portal-borrow-btn" style="text-decoration: none;">Back to Catalog</a>
            <button id="darkModeToggle" class="theme-toggle-btn">Light Mode</button>
            <a href="user_logout.php" class="portal-borrow-btn"
                style="background-color: #dc3545; text-decoration: none;">Logout</a>
        </div>
    </header>

    <!-- Main Container -->
    <main class="portal-main-container">
        <div class="portal-catalog-header" style="margin-bottom: 25px;">
            <h2 class="portal-catalog-title">Welcome, <?= htmlspecialchars($_SESSION['username']); ?></h2>
            <p style="color: var(--text-muted); margin-top: 4px; font-size: 14px;">Manage your active loans and viewing
                history</p>
        </div>

        <!-- STATS OVERVIEW CARDS -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-title">Active Borrows</span>
                <span class="stat-value"><?= $activeCount; ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-title">Total Returned</span>
                <span class="stat-value"><?= $historyCount; ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-title">Unpaid Fines</span>
                <span class="stat-value"
                    style="color: <?= $totalFines > 0 ? '#ef4444' : 'inherit'; ?>;">₱<?= number_format($totalFines, 2); ?></span>
            </div>
        </div>

        <!-- SECTION 1: CURRENTLY BORROWED BOOKS -->
        <!-- SECTION 1: CURRENTLY BORROWED BOOKS -->
        <h3 style="color: var(--text-color); margin-bottom: 16px; font-size: 18px; font-weight: 600;">Currently Borrowed
        </h3>
        <div class="portal-book-grid" style="margin-bottom: 40px;">
            <?php if (!empty($currently_borrowed)): ?>
                <?php foreach ($currently_borrowed as $borrowed): ?>
                    <div class="portal-book-card">
                        <?php
                        $coverImg = !empty($borrowed['image_url']) ? htmlspecialchars($borrowed['image_url']) : 'https://via.placeholder.com/150';
                        $dueDate = !empty($borrowed['due_date']) ? date('M d, Y', strtotime($borrowed['due_date'])) : 'N/A';
                        ?>
                        <img src="<?= $coverImg; ?>" alt="<?= htmlspecialchars($borrowed['title']); ?>"
                            class="portal-book-cover">

                        <div class="portal-book-info">
                            <div class="portal-book-title"><?= htmlspecialchars($borrowed['title']); ?></div>
                            <div class="portal-book-author">by <?= htmlspecialchars($borrowed['author']); ?></div>

                            <div
                                style="margin-top: 12px; font-size: 13px; color: var(--text-muted); display: flex; flex-direction: column; gap: 4px;">
                                <span><strong>Borrower:</strong> <?= htmlspecialchars($_SESSION['username'] ?? ''); ?></span>
                                <span><strong>Due Date:</strong> <?= $dueDate; ?></span>
                            </div>
                        </div>

                        <div class="portal-book-meta" style="margin-top: 12px; display: flex; gap: 8px; align-items: center;">
                            <span class="portal-status-tag borrowed">Borrowed</span>

                            <form method="POST" action="user_dashboard.php">
                                <input type="hidden" name="book_id"
                                    value="<?= htmlspecialchars($borrowed['book_id'] ?? $borrowed['id'] ?? ''); ?>">
                                <input type="hidden" name="action" value="return_book">
                                <button type="submit" class="btn-return">Return</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="portal-empty-state"
                    style="grid-column: 1 / -1; background: var(--card-bg); padding: 24px; border-radius: 12px; text-align: center; color: var(--text-muted);">
                    <p>You currently have no borrowed books.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- SECTION 2: BORROWING HISTORY TABLE -->
        <h3 style="color: var(--text-color); margin-bottom: 16px; font-size: 18px; font-weight: 600;">Borrowing
            History
        </h3>
        <div class="history-card-container">
            <?php if (!empty($historyBooks)): ?>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Book Title</th>
                            <th>Author</th>
                            <th>Borrowed Date</th>
                            <th>Returned Date</th>
                            <th>Fine</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historyBooks as $history): ?>
                            <tr>
                                <td style="font-weight: 600;"><?= htmlspecialchars($history['title']); ?></td>
                                <td style="color: var(--text-muted);"><?= htmlspecialchars($history['author']); ?></td>
                                <td><?= date('M d, Y', strtotime($history['borrow_date'])); ?></td>
                                <td>
                                    <?php if (!empty($history['return_date'])): ?>
                                        <?= date('M d, Y', strtotime($history['return_date'])); ?>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>₱<?= number_format($history['fine'], 2); ?></td>
                                <td>
                                    <?php if (strtolower($history['status']) === 'returned'): ?>
                                        <span class="badge-returned">Returned</span>
                                    <?php else: ?>
                                        <span class="portal-status-tag borrowed">Borrowed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="padding: 24px; text-align: center; color: var(--text-muted);">
                    <p>No borrowing history found.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Dark Mode Script -->
    <script>
        const darkModeToggle = document.getElementById('darkModeToggle');
        const body = document.body;

        const currentTheme = localStorage.getItem('theme');
        if (currentTheme === 'dark') {
            body.classList.add('dark-mode');
            darkModeToggle.textContent = 'Light Mode';
        } else if (currentTheme === 'light') {
            body.classList.remove('dark-mode');
            darkModeToggle.textContent = 'Dark Mode';
        }

        darkModeToggle.addEventListener('click', () => {
            body.classList.toggle('dark-mode');
            let theme = 'light';
            if (body.classList.contains('dark-mode')) {
                theme = 'dark';
                darkModeToggle.textContent = 'Light Mode';
            } else {
                darkModeToggle.textContent = 'Dark Mode';
            }
            localStorage.setItem('theme', theme);
        });
    </script>
</body>

</html>