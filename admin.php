<?php
session_start();
require_once 'config/db.php';
if (!isset($_SESSION['isAdminLoggedIn']) || $_SESSION['isAdminLoggedIn'] !== true) {
    header("Location: admin_login.php");
    exit();
}
$logs = [];
if (isset($_GET['view']) && $_GET['view'] === 'audit_logs') {
    try {
        $stmtLogs = $conn->query("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 50");
        $logs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $logs = [];
    }
}
$current_view = $_GET['view'] ?? 'inventory';


$usersList = [];
if ($current_view === 'users') {
    try {
        $stmt = $conn->query("SELECT * FROM users ORDER BY id DESC");
        $usersList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $usersList = [];
    }
}
// Kunin ang mga libro
$stmtBooks = $conn->query("SELECT * FROM books ORDER BY id DESC");
$books = $stmtBooks->fetchAll(PDO::FETCH_ASSOC);

$total_books = count($books);
$available_books = 0;
$borrowed_books = 0;

foreach ($books as $book) {
    $status = strtolower($book['status'] ?? 'available');
    if ($status === 'available') {
        $available_books++;
    } else {
        $borrowed_books++;
    }
}

// Kunin ang transactions
$transactions = [];
try {
    $stmtTxn = $conn->query("
        SELECT 
            t.id AS txn_id, 
            t.user_id, 
            t.book_id,
            b.title AS book_title, 
            t.due_date, 
            t.status, 
            t.fine AS overdue_fine 
        FROM transactions t 
        LEFT JOIN books b ON t.book_id = b.id 
        ORDER BY t.id DESC
    ");
    $transactions = $stmtTxn->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $transactions = [];
}
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status_filter'] ?? '';

if ($current_view === 'users') {
    $stmt = $conn->query("SELECT * FROM users ORDER BY id DESC");
    $usersList = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($current_view === 'transactions') {
    $query = "SELECT t.id as txn_id, t.user_id, b.title as book_title, t.due_date, t.status, t.fine as overdue_fine 
              FROM transactions t 
              JOIN books b ON t.book_id = b.id WHERE 1=1";
    $params = [];

    if (!empty($search)) {
        $query .= " AND (t.user_id LIKE ? OR b.title LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if (!empty($statusFilter)) {
        $query .= " AND t.status = ?";
        $params[] = $statusFilter;
    }

    $query .= " ORDER BY t.borrow_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    if (!empty($search)) {
        $stmt = $conn->prepare("SELECT * FROM books WHERE title LIKE ? OR author LIKE ? ORDER BY id DESC");
        $stmt->execute(["%$search%", "%$search%"]);
    } else {
        $stmt = $conn->query("SELECT * FROM books ORDER BY id DESC");
    }
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$limit = 5; // Bilang ng rows bawat page
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Para sa Inventory Pagination
if ($current_view === 'inventory') {
    // Bilangin ang kabuuang bilang ng libro para sa pagination links
    $countStmt = $conn->query("SELECT COUNT(*) FROM books");
    $totalBooks = $countStmt->fetchColumn();
    $totalPages = ceil($totalBooks / $limit);

    if (!empty($search)) {
        $stmt = $conn->prepare("SELECT * FROM books WHERE title LIKE ? OR author LIKE ? ORDER BY id DESC LIMIT $limit OFFSET $offset");
        $stmt->execute(["%$search%", "%$search%"]);
    } else {
        $stmt = $conn->query("SELECT * FROM books ORDER BY id DESC LIMIT $limit OFFSET $offset");
    }
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Panel - Library System</title>

    <link rel="stylesheet" href="asset/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.card {
    background: #ffffff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.04);
    display: flex;
    align-items: center;
    gap: 20px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border-left: 5px solid #cbd5e1;
}

.card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 12px rgba(0, 0, 0, 0.08);
}

/* Mga Kulay at Border Accent para sa bawat Card */
.card.stat-total { border-left-color: #3b82f6; }
.card.stat-available { border-left-color: #10b981; }
.card.stat-borrowed { border-left-color: #f59e0b; }
.card.stat-fines { border-left-color: #ef4444; }

/* Disenyo ng Icon sa loob ng Card */
.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.stat-total .stat-icon { background: #eff6ff; color: #3b82f6; }
.stat-available .stat-icon { background: #ecfdf5; color: #10b981; }
.stat-borrowed .stat-icon { background: #fffbeb; color: #f59e0b; }
.stat-fines .stat-icon { background: #fef2f2; color: #ef4444; }

/* Teksto sa loob ng Stats */
.stat-info span {
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-info h3 {
    margin: 5px 0 0 0;
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
}
</style>

<body>

    <!-- Left Sidebar -->
    <div class="sidebar">
        <div>
            <h2>Admin Panel</h2>
            <nav>
                <a href="admin.php?view=inventory" class="<?= ($current_view === 'inventory') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-boxes-stacked"></i> Inventory
                </a>
                <a href="admin.php?view=transactions"
                    class="<?= ($current_view === 'transactions') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-right-left"></i> Transactions
                </a>
                <a href="admin.php?view=users" class="<?= ($current_view === 'users') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-users"></i> Users Management
                </a>
                <a href="admin.php?view=audit_logs" class="<?= ($current_view === 'audit_logs') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-clock-rotate-left"></i> Audit Logs
                </a>
            </nav>
        </div>
        <a href="actions/logout.php" class="logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i> Log Out
        </a>
    </div>

    <!-- Main Content Area -->
  <?php if ($current_view === 'inventory'): ?>
        <div class="main-content">
            <div class="header-container">
                <div>
                    <h1>Control Dashboard</h1>
                    <p>Manage system records, books, and metrics.</p>
                </div>
                <button type="button" class="btn-primary" onclick="openAddModal()">+ Add New Book</button>
            </div>
        <?php endif; ?>
        <!-- 4 Cards sa Itaas -->
      <?php if ($current_view === 'inventory'): ?>
           <div class="stats-grid">
    <div class="card stat-total">
        <div class="stat-icon"><i class="fa-solid fa-book"></i></div>
        <div class="stat-info">
            <span>Total Books</span>
            <h3><?= $total_books; ?></h3>
        </div>
    </div>
    
    <div class="card stat-available">
        <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
        <div class="stat-info">
            <span>Available</span>
            <h3><?= $available_books; ?></h3>
        </div>
    </div>
    
    <div class="card stat-borrowed">
        <div class="stat-icon"><i class="fa-solid fa-book-bookmark"></i></div>
        <div class="stat-info">
            <span>Borrowed</span>
            <h3><?= $borrowed_books; ?></h3>
        </div>
    </div>
    
    <div class="card stat-fines">
        <div class="stat-icon"><i class="fa-solid fa-peso-sign"></i></div>
        <div class="stat-info">
            <span>Pending Fines</span>
            <h3>₱0</h3>
        </div>
    </div>
</div>
        <?php endif; ?>
        <div class="admin-main" style="flex: 1; padding: 30px; background: #f8fafc;">

            <!-- 1. INVENTORY VIEW -->
            <?php if ($current_view === 'inventory'): ?>
                <form method="GET" action="admin.php" style="margin-bottom: 15px; display: flex; gap: 10px;">
                    <input type="hidden" name="view" value="inventory">
                    <input type="text" name="search" placeholder="Search for (Title o Author)..."
                        value="<?= htmlspecialchars($_GET['search'] ?? ''); ?>"
                        style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; width: 300px;">
                    <button type="submit"
                        style="padding: 8px 16px; background: #0f213d; color: white; border: none; border-radius: 6px; cursor: pointer;">Search</button>
                    <?php if (!empty($_GET['search'])): ?>
                        <a href="admin.php?view=inventory"
                            style="padding: 8px 12px; background: #64748b; color: white; text-decoration: none; border-radius: 6px; display: flex; align-items: center;">Done</a>
                    <?php endif; ?>
                </form>

                <?php if (file_exists('include/add_book_modal.php'))
                    include 'include/add_book_modal.php'; ?>
                <div class="content-card"
                    style="background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                    <h3>Book Inventory Management</h3>
                    <table class="data-table" style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                        <thead>
                            <tr style="border-bottom: 1px solid #e2e8f0; text-align: left;">
                                <th style="padding: 10px;">Book ID</th>
                                <th style="padding: 10px;">Title</th>
                                <th style="padding: 10px;">Author</th>
                                <th style="padding: 10px;">Genre</th>
                                <th style="padding: 10px;">Status</th>
                                <th style="padding: 10px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($books) > 0): ?>
                                <?php foreach ($books as $book): ?>
                                    <tr style="border-bottom: 1px solid #f1f5f9;">
                                        <td style="padding: 10px;"><?= htmlspecialchars($book['id']); ?></td>
                                        <td style="padding: 10px;"><strong><?= htmlspecialchars($book['title']); ?></strong></td>
                                        <td style="padding: 10px;"><?= htmlspecialchars($book['author'] ?? 'N/A'); ?></td>
                                        <td style="padding: 10px;"><?= htmlspecialchars($book['category'] ?? 'General'); ?></td>
                                        <td style="padding: 10px;">
                                            <?php
                                            $bookStatus = $book['status'] ?? 'Available';
                                            $badgeClass = strtolower($bookStatus) === 'available' ? 'available' : 'borrowed';
                                            ?>
                                            <span class="badge <?= $badgeClass; ?>"><?= htmlspecialchars($bookStatus); ?></span>
                                        </td>
                                        <td style="padding: 10px; display: flex; gap: 10px;">
                                            <!-- Edit Trigger -->
                                            <a href="#"
                                                onclick="openEditModal('<?= $book['id']; ?>', '<?= htmlspecialchars($book['title'], ENT_QUOTES); ?>', '<?= htmlspecialchars($book['author'] ?? '', ENT_QUOTES); ?>', '<?= htmlspecialchars($book['category'] ?? '', ENT_QUOTES); ?>', '<?= $book['status'] ?? 'Available'; ?>')"
                                                style="color: #3b82f6; text-decoration: none;">Edit</a>

                                            <!-- Delete Button -->
                                            <a href="actions/delete_book.php?id=<?= $book['id']; ?>"
                                                style="color: #ef4444; text-decoration: none;"
                                                onclick="return confirm('Sigurado ka bang gusto mong burahin ang librong ito?');">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 20px; color: #64748b;">No Books Found in
                                        Database
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            <?php if (isset($totalPages) && $totalPages > 1): ?>
                <div style="display: flex; justify-content: center; gap: 5px; margin-top: 20px;">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="admin.php?view=inventory&page=<?= $i; ?><?= !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                            style="padding: 6px 12px; border-radius: 6px; text-decoration: none; background: <?= ($page == $i) ? '#3b82f6' : '#e2e8f0'; ?>; color: <?= ($page == $i) ? '#fff' : '#1e293b'; ?>;">
                            <?= $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
            <!-- 2. TRANSACTIONS VIEW -->
            <?php if ($current_view === 'transactions'): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 style="margin: 0;">Transaction & Fine Monitor</h3>
                    <div style="display: flex; gap: 10px;">
                        <!-- Button para sa Fine/Reminders check -->
                        <a href="actions/send_reminder.php" class="action-btn"
                            style="background: #3b82f6; color: #fff; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 14px;"
                            onclick="return confirm('Gusto mo bang suriin at i-update ang mga overdue fines?');">
                            Update Fines
                        </a>

                        <!-- Button para sa CSV Export -->
                        <a href="actions/export_transactions.php"
                            style="padding: 8px 16px; background: #10b981; color: white; text-decoration: none; border-radius: 6px; font-weight: 500; font-size: 14px;">
                            I-export sa Excel (CSV)
                        </a>
                    </div>
                </div>
                <table class="data-table" style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                    <thead>
                        <tr style="border-bottom: 1px solid #e2e8f0; text-align: left;">
                            <th style="padding: 10px;">TXN ID</th>
                            <th style="padding: 10px;">MEMBER</th>
                            <th style="padding: 10px;">BOOK TITLE</th>
                            <th style="padding: 10px;">DUE DATE</th>
                            <th style="padding: 10px;">STATUS</th>
                            <th style="padding: 10px;">OVERDUE FINE</th>
                            <th style="padding: 10px;">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($transactions) > 0): ?>
                            <?php foreach ($transactions as $txn): ?>
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 10px;">#<?= htmlspecialchars($txn['txn_id']); ?></td>
                                    <td style="padding: 10px;"><strong><?= htmlspecialchars($txn['user_id']); ?></strong></td>
                                    <td style="padding: 10px;"><?= htmlspecialchars($txn['book_title']); ?></td>
                                    <td style="padding: 10px;"><?= htmlspecialchars($txn['due_date']); ?></td>
                                    <td style="padding: 10px;">
                                        <span class="badge"><?= htmlspecialchars($txn['status']); ?></span>
                                    </td>
                                    <td style="padding: 10px;">₱<?= number_format($txn['overdue_fine'] ?? 0, 2); ?></td>
                                    <td style="padding: 10px;">
                                        <?php if ($txn['status'] === 'Borrowed'): ?>
                                            <?php
                                            // Siguraduhing nasasama ang tamang book_id mula sa $txn array
                                            $currentTxnId = $txn['id'] ?? $txn['txn_id'] ?? '';
                                            // Kung sakaling ang column sa database ay 'book_id' o kaya ay 'id' ng libro
                                            $currentBookId = $txn['book_id'] ?? $txn['id'] ?? '';
                                            ?>
                                            <a href="#"
                                                onclick="openReturnModal('<?= $currentTxnId; ?>', '<?= $currentBookId; ?>'); return false;"
                                                style="color: #3b82f6; text-decoration: none; font-weight: 500;">Manage</a>
                                        <?php else: ?>
                                            <span style="color: #64748b;">Completed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 20px; color: #64748b;">No Record Found in
                                    Transactions
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- 3. USERS VIEW -->
        <?php if ($current_view === 'users'): ?>
           <div class="portal-catalog-header" style="margin-bottom: 20px;">
    <h2 class="portal-catalog-title" style="display: flex; align-items: center; gap: 10px;">
        <i class="fa-solid fa-users" style="color: #4f46e5;"></i> Registered Users Management
    </h2>
</div>

      <!-- Search Bar -->
        <form method="GET" action="admin.php" style="display: flex; gap: 10px; margin: 0;">
            <input type="hidden" name="view" value="users">
            <input type="text" name="search_user" placeholder="Search username or email..." 
                   value="<?= htmlspecialchars($_GET['search_user'] ?? '') ?>" 
                   style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; width: 250px;">
            <button type="submit" class="btn-primary" style="padding: 8px 16px; background: #1e293b; color: #fff; border: none; border-radius: 6px; cursor: pointer;">Search</button>
        </form>
    </div>

    <!-- Stats Card -->
    <div style="margin-bottom: 20px;">
        <div style="background: #fff; padding: 15px 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); display: inline-block; min-width: 200px;">
            <span style="color: #64748b; font-size: 14px;">Total Registered Users</span>
            <h3 style="margin: 5px 0 0 0; color: #1e293b;"><?= count($usersList); ?></h3>
        </div>
    </div>

    <div class="history-card-container" style="background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
        <table class="history-table" style="width: 100%; border-collapse: collapse;">
            <tr style="border-bottom: 1px solid #e2e8f0; text-align: left;">
                <th style="padding: 10px;">User ID</th>
                <th style="padding: 10px;">Username</th>
                <th style="padding: 10px;">Email / Details</th>
                <th style="padding: 10px;">Date Registered</th>
            </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($usersList)): ?>
                            <?php foreach ($usersList as $usr): ?>
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 10px;"><?= htmlspecialchars($usr['id']); ?></td>
                                    <td style="padding: 10px; font-weight: 600;"><?= htmlspecialchars($usr['username']); ?></td>
                                    <td style="padding: 10px;">
                                        <?= isset($usr['email']) ? htmlspecialchars($usr['email']) : 'N/A'; ?>
                                    </td>
                                    <td style="padding: 10px;">
                                        <?= isset($usr['created_at']) ? htmlspecialchars($usr['created_at']) : 'N/A'; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 20px; color: #64748b;">No Registered User
                                    Found
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>
    </div>
    <?php if (isset($_GET['view']) && $_GET['view'] === 'audit_logs'): ?>

        <div class="audit-logs-container">
            <h3 class="audit-logs-title">Admin Audit Logs</h3>
            <table class="audit-logs-table">
                <thead>
                    <tr>
                        <th class="col-id">ID</th>
                        <th class="col-action">Aksyon</th>
                        <th class="col-details">Detalye</th>
                        <th class="col-date">Oras</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmtLogs = $conn->query("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 50");
                    $logs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <?php if (!empty($logs) && count($logs) > 0): ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td class="col-id">#<?= $log['id']; ?></td>
                                <td class="col-action"><?= htmlspecialchars($log['action']); ?></td>
                                <td class="col-details"><?= htmlspecialchars($log['details']); ?></td>
                                <td class="col-date"><?= $log['created_at']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="audit-logs-empty">Wala pang naka-record na log.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>

    <!-- Edit Book Modal -->
    <div id="editBookModal"
        style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center;">
        <div
            style="background: #fff; padding: 30px; border-radius: 12px; width: 400px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0;">Edit Book Details</h3>
            <form action="actions/edit_book.php" method="POST">
                <input type="hidden" name="id" id="edit_book_id">

                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-size: 14px; font-weight: 500;">Title</label>
                    <input type="text" name="title" id="edit_book_title" required
                        style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-size: 14px; font-weight: 500;">Author</label>
                    <input type="text" name="author" id="edit_book_author"
                        style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-size: 14px; font-weight: 500;">Genre /
                        Category</label>
                    <input type="text" name="category" id="edit_book_category"
                        style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-size: 14px; font-weight: 500;">Status</label>
                    <select name="status" id="edit_book_status"
                        style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        <option value="Available">Available</option>
                        <option value="Borrowed">Borrowed</option>
                    </select>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeEditModal()"
                        style="padding: 8px 16px; background: #64748b; color: white; border: none; border-radius: 6px; cursor: pointer;">Cancel</button>
                    <button type="submit"
                        style="padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer;">Save
                        Changes</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Return & Damage Inspection Modal -->
    <div id="returnBookModal"
        style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000;">
        <div
            style="background: #fff; padding: 30px; border-radius: 12px; width: 400px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0; color: #1e293b;">Process Book Return</h3>
            <p style="font-size: 14px; color: #64748b; margin-bottom: 20px;">Suriin ang kondisyon ng isinasuling libro
                bago tapusin ang transaksyon.</p>

            <form action="actions/return_book.php" method="POST">
                <input type="hidden" name="txn_id" id="return_txn_id">
                <input type="hidden" name="book_id" id="return_book_id">

                <div style="margin-bottom: 15px;">
                    <label
                        style="display: block; margin-bottom: 5px; font-size: 14px; font-weight: 500; color: #334155;">Kondisyon
                        ng Libro</label>
                    <!-- TINGNAN DITO: Nakalagay na ang onchange="toggleDamageFee()" -->
                    <select name="condition_status" id="condition_status" onchange="toggleDamageFee()"
                        style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        <option value="Good">Good (Maayos)</option>
                        <option value="Damaged">Damaged (May Sira / Nasira)</option>
                    </select>
                </div>

                <div id="damageFeeContainer" style="display: none; margin-bottom: 20px;">
                    <label
                        style="display: block; margin-bottom: 5px; font-size: 14px; font-weight: 500; color: #334155;">Karagdagang
                        Multa sa Sira (Damage Fee - PHP)</label>
                    <input type="number" name="damage_fee" step="0.01" value="0.00"
                        style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeReturnModal()"
                        style="padding: 8px 16px; background: #64748b; color: white; border: none; border-radius: 6px; cursor: pointer;">Cancel</button>
                    <button type="submit"
                        style="padding: 8px 16px; background: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer;">Confirm
                        Return</button>
                </div>
            </form>
        </div>
    </div>
    <script src="asset/script.js"></script>
    <script>
        function openReturnModal(txnId, bookId) {
            // Kunin ang modal at mga hidden input
            const modal = document.getElementById('returnBookModal');
            const txnInput = document.getElementById('return_txn_id');
            const bookInput = document.getElementById('return_book_id');

            // Ipasok ang mga ID
            if (txnInput) txnInput.value = txnId;
            if (bookInput) bookInput.value = bookId;

            // Ipakita ang modal
            if (modal) {
                modal.style.display = 'flex';
            }
        }

        function closeReturnModal() {
            const modal = document.getElementById('returnBookModal');
            if (modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>

</html>