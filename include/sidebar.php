<div class="sidebar">
    <div>
        <h2>Admin Panel</h2>
        <nav>
            <a href="admin.php?view=inventory" class="<?= ($current_view ?? 'inventory') === 'inventory' ? 'active' : ''; ?>">📁 Inventory</a>
            <a href="admin.php?view=transactions" class="<?= ($current_view ?? 'inventory') === 'transactions' ? 'active' : ''; ?>">📋 Transactions</a>
        </nav>
    </div>
    <a href="actions/logout.php" class="logout-btn">← Log Out</a>
</div>