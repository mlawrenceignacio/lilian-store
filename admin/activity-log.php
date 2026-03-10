<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/functions.php";
require_once __DIR__ . "/../includes/admin-check.php";

$currentPage = basename($_SERVER['PHP_SELF']);
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT
        aal.id,
        aal.activity,
        aal.created_at,
        u.full_name,
        a.username
    FROM admin_activity_logs aal
    INNER JOIN users u ON aal.admin_user_id = u.id
    LEFT JOIN admins a ON aal.admin_user_id = a.user_id
    WHERE 1=1
";

$params = [];
$types = "";

if ($search !== '') {
    $sql .= " AND (
        aal.activity LIKE ?
        OR u.full_name LIKE ?
        OR a.username LIKE ?
    )";
    $like = "%" . $search . "%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

$sql .= " ORDER BY aal.created_at DESC, aal.id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$logs = [];
while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}

include __DIR__ . "/../includes/header.php";
?>

<link rel="stylesheet" href="/lilian-online-store/assets/css/admin.css" />

<section class="admin-section">
    <div class="container">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_SESSION['success_message']) ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($_SESSION['error_message']) ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <div class="admin-layout">
            <aside class="admin-sidebar">
                <div class="admin-sidebar-card">
                    <div class="admin-sidebar-top">
                        <h2>Admin Panel</h2>
                        <p>Manage orders, products, notifications, and store activity.</p>
                    </div>

                    <nav class="admin-nav">
                        <a href="/lilian-online-store/admin/dashboard.php">
                            <span>Dashboard</span>
                            <span>→</span>
                        </a>
                        <a href="/lilian-online-store/admin/notifications.php">
                            <span>Notifications</span>
                            <span>→</span>
                        </a>
                        <a href="/lilian-online-store/admin/orders.php">
                            <span>Orders</span>
                            <span>→</span>
                        </a>
                        <a href="/lilian-online-store/admin/products.php">
                            <span>Products</span>
                            <span>→</span>
                        </a>
                        <a href="/lilian-online-store/admin/activity-log.php" class="<?= $currentPage === 'activity-log.php' ? 'active' : '' ?>">
                            <span>Activity Log</span>
                            <span>→</span>
                        </a>
                        <a href="/lilian-online-store/admin/admins.php">
                            <span>Admin Accounts</span>
                            <span>→</span>
                        </a>
                        <a href="/lilian-online-store/admin/profile.php">
                            <span>Admin Profile</span>
                            <span>→</span>
                        </a>
                    </nav>
                </div>
            </aside>

            <div class="admin-main">
                <section class="admin-main-card">
                    <div class="admin-main-head">
                        <div>
                            <h1>Activity Log</h1>
                            <p>Review admin actions across the store with clear timestamps.</p>
                        </div>
                        <div class="admin-date-chip"><?= count($logs) ?> result<?= count($logs) !== 1 ? 's' : '' ?></div>
                    </div>
                </section>

                <section class="admin-main-card admin-toolbar">
                    <div class="admin-card-head">
                        <div>
                            <h2>Search Logs</h2>
                            <p>Find activity by admin name, username, or action text.</p>
                        </div>
                    </div>

                    <form action="/lilian-online-store/admin/activity-log.php" method="GET" class="admin-toolbar-grid" style="grid-template-columns: 1fr auto;">
                        <div>
                            <label for="search">Search</label>
                            <input
                                type="text"
                                id="search"
                                name="search"
                                class="form-input"
                                placeholder="Search admin activity"
                                value="<?= htmlspecialchars($search) ?>"
                            >
                        </div>

                        <div class="admin-inline-row" style="align-self:end;">
                            <button type="submit" class="btn btn-primary">Apply</button>
                            <a href="/lilian-online-store/admin/activity-log.php" class="btn btn-secondary">Reset</a>
                        </div>
                    </form>
                </section>

                <section class="admin-main-card">
                    <div class="admin-card-head">
                        <div>
                            <h2>Admin Activity Feed</h2>
                            <p>Includes status updates, logins, profile edits, and future product changes.</p>
                        </div>
                    </div>

                    <?php if (!empty($logs)): ?>
                        <div class="admin-log-list">
                            <?php foreach ($logs as $log): ?>
                                <article class="admin-log-card">
                                    <div class="admin-log-icon">📝</div>

                                    <div class="admin-log-body">
                                        <div class="admin-log-title">Admin Activity</div>
                                        <div class="admin-log-message"><?= htmlspecialchars($log['activity']) ?></div>
                                        <div class="admin-log-meta">
                                            Full Name: <?= htmlspecialchars($log['full_name']) ?>
                                            <?php if (!empty($log['username'])): ?>
                                                • Username: <?= htmlspecialchars($log['username']) ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="admin-log-side">
                                        <span class="admin-log-admin">
                                            <?= htmlspecialchars($log['username'] ?: $log['full_name']) ?>
                                        </span>
                                        <div class="admin-log-time">
                                            <?= date("F j, Y g:i A", strtotime($log['created_at'])) ?>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="admin-empty">
                            <h3>No activity logs found</h3>
                            <p>
                                Admin actions such as logins, order updates, product changes,
                                and profile updates will appear here with their dates.
                            </p>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . "/../includes/footer.php"; ?>