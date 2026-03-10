<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/functions.php";
require_once __DIR__ . "/../includes/admin-check.php";

$currentPage = basename($_SERVER['PHP_SELF']);

function getSingleCount($conn, $sql) {
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        return (int)($row['total'] ?? 0);
    }
    return 0;
}

$totalOrders = getSingleCount($conn, "SELECT COUNT(*) AS total FROM orders");
$totalToShip = getSingleCount($conn, "SELECT COUNT(*) AS total FROM orders WHERE status = 'to ship'");
$totalToReceive = getSingleCount($conn, "SELECT COUNT(*) AS total FROM orders WHERE status = 'to receive'");
$totalDelivered = getSingleCount($conn, "SELECT COUNT(*) AS total FROM orders WHERE status = 'delivered'");
$totalCustomers = getSingleCount($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'customer'");

$graphData = [
    ['label' => 'Total Orders', 'value' => $totalOrders],
    ['label' => 'To Ship', 'value' => $totalToShip],
    ['label' => 'To Receive', 'value' => $totalToReceive],
    ['label' => 'Delivered', 'value' => $totalDelivered],
    ['label' => 'Customers', 'value' => $totalCustomers],
];

$maxGraphValue = 1;
foreach ($graphData as $entry) {
    if ($entry['value'] > $maxGraphValue) {
        $maxGraphValue = $entry['value'];
    }
}

$recentOrders = [];
$recentStmt = $conn->prepare("
    SELECT id, order_number, full_name, total_amount, status, created_at
    FROM orders
    ORDER BY created_at DESC, id DESC
    LIMIT 5
");
$recentStmt->execute();
$recentResult = $recentStmt->get_result();

while ($row = $recentResult->fetch_assoc()) {
    $recentOrders[] = $row;
}

function adminStatusPillClass($status) {
    return match ($status) {
        'to pay' => 'pill-to-pay',
        'to ship' => 'pill-to-ship',
        'to receive' => 'pill-to-receive',
        'delivered' => 'pill-delivered',
        'cancelled' => 'pill-cancelled',
        default => ''
    };
}

function adminStatusLabel($status) {
    return match ($status) {
        'to pay' => 'To Pay',
        'to ship' => 'To Ship',
        'to receive' => 'To Receive',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        default => ucfirst($status)
    };
}

$adminName = trim($_SESSION['full_name'] ?? 'Admin');
$adminFirstName = explode(' ', $adminName)[0] ?: 'Admin';

include __DIR__ . "/../includes/header.php";
?>

<link rel="stylesheet" href="/lilian-online-store/assets/css/admin.css" />

<section class="admin-section">
    <div class="container">
        <?php if (isset($_SESSION['success_message'])): ?>
    <div class="admin-feedback-banner admin-feedback-success">
        <div class="admin-feedback-icon">✓</div>
        <div class="admin-feedback-content">
            <span class="admin-feedback-label">Success</span>
            <h3>Action completed successfully</h3>
            <p><?= htmlspecialchars($_SESSION['success_message']) ?></p>
        </div>
    </div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="admin-feedback-banner admin-feedback-error">
        <div class="admin-feedback-icon">!</div>
        <div class="admin-feedback-content">
            <span class="admin-feedback-label">Something went wrong</span>
            <h3>We couldn’t complete your request</h3>
            <p><?= htmlspecialchars($_SESSION['error_message']) ?></p>
        </div>
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
                        <a href="/lilian-online-store/admin/dashboard.php" class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
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
                        <a href="/lilian-online-store/admin/activity-log.php">
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
                <section class="admin-main-card admin-hero-card">
                    <div class="admin-main-head admin-hero-head">
                        <div class="admin-hero-copy">
                            <span class="admin-hero-badge">Admin Dashboard</span>
                            <h1>Welcome back, <?= htmlspecialchars($adminFirstName) ?>!</h1>
                            <p>
                                Here’s your store overview for today. Monitor orders, customers,
                                delivery progress, and recent activity from one place.
                            </p>
                        </div>

                        <div class="admin-hero-side">
                            <div class="admin-date-chip"><?= date("F j, Y") ?></div>
                            <div class="admin-hero-mini">
                                <strong><?= $totalOrders ?></strong>
                                <span>Total Orders</span>
                            </div>
                        </div>
                    </div>
            </section>

                <section class="admin-stats-grid">
                    <article class="admin-stat-card">
                        <div class="admin-stat-label">Total Orders</div>
                        <div class="admin-stat-value"><?= $totalOrders ?></div>
                        <div class="admin-stat-note">All recorded customer orders</div>
                    </article>

                    <article class="admin-stat-card">
                        <div class="admin-stat-label">Current To Ship</div>
                        <div class="admin-stat-value"><?= $totalToShip ?></div>
                        <div class="admin-stat-note">Orders ready for shipping workflow</div>
                    </article>

                    <article class="admin-stat-card">
                        <div class="admin-stat-label">Current To Receive</div>
                        <div class="admin-stat-value"><?= $totalToReceive ?></div>
                        <div class="admin-stat-note">Orders currently out for customer receipt</div>
                    </article>

                    <article class="admin-stat-card">
                        <div class="admin-stat-label">Delivered</div>
                        <div class="admin-stat-value"><?= $totalDelivered ?></div>
                        <div class="admin-stat-note">Completed customer orders</div>
                    </article>

                    <article class="admin-stat-card">
                        <div class="admin-stat-label">Registered Customers</div>
                        <div class="admin-stat-value"><?= $totalCustomers ?></div>
                        <div class="admin-stat-note">Customer accounts in the system</div>
                    </article>
                </section>

                <section class="admin-graph-card">
                    <div class="admin-card-head">
                        <div>
                            <h2>Dashboard Bar Graph</h2>
                            <p>Visual summary of key store counts required for the admin overview.</p>
                        </div>
                    </div>

                    <div class="bar-graph">
                        <?php foreach ($graphData as $bar): ?>
                            <?php
                                $heightPercent = $maxGraphValue > 0
                                    ? max(8, ($bar['value'] / $maxGraphValue) * 100)
                                    : 8;
                            ?>
                            <div class="bar-item">
                                <div class="bar-outer">
                                    <div class="bar-fill" style="height: <?= $heightPercent ?>%;"></div>
                                </div>
                                <div class="bar-value"><?= $bar['value'] ?></div>
                                <div class="bar-label"><?= htmlspecialchars($bar['label']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="admin-two-col">
                    <div class="admin-main-card">
                        <div class="admin-card-head">
                            <div>
                                <h2>Recent Orders</h2>
                                <p>Latest customer orders across the store.</p>
                            </div>
                            <a href="/lilian-online-store/admin/orders.php" class="btn btn-secondary">View All Orders</a>
                        </div>

                        <?php if (!empty($recentOrders)): ?>
                            <div class="admin-orders-list">
                                <?php foreach ($recentOrders as $order): ?>
                                    <article class="admin-order-card">
                                        <div class="admin-order-top">
                                            <div>
                                                <div class="admin-order-title">Order #<?= htmlspecialchars($order['order_number']) ?></div>
                                                <div class="admin-order-meta">
                                                    <?= htmlspecialchars($order['full_name']) ?> •
                                                    <?= date("F j, Y g:i A", strtotime($order['created_at'])) ?>
                                                </div>
                                                <span class="admin-status-pill <?= adminStatusPillClass($order['status']) ?>">
                                                    <?= adminStatusLabel($order['status']) ?>
                                                </span>
                                            </div>

                                            <div class="admin-order-total"><?= formatPeso($order['total_amount']) ?></div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="admin-empty">
                                <h3>No orders yet</h3>
                                <p>Once customers place orders, the latest ones will appear here.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="admin-main-card">
                        <div class="admin-card-head">
                            <div>
                                <h2>Quick Access</h2>
                                <p>Open the main admin tools from here.</p>
                            </div>
                        </div>

                        <div class="admin-quick-grid">
                            <a href="/lilian-online-store/admin/notifications.php" class="quick-link-card">
                                <div class="quick-link-title">Notifications</div>
                                <div class="quick-link-desc">See order placements, received confirmations, and cancellations.</div>
                            </a>

                            <a href="/lilian-online-store/admin/orders.php" class="quick-link-card">
                                <div class="quick-link-title">Orders Management</div>
                                <div class="quick-link-desc">View orders, update statuses, and check cancelled transactions.</div>
                            </a>

                            <a href="/lilian-online-store/admin/products.php" class="quick-link-card">
                                <div class="quick-link-title">Products</div>
                                <div class="quick-link-desc">Add, edit, and remove products except user-generated ratings.</div>
                            </a>

                            <a href="/lilian-online-store/admin/activity-log.php" class="quick-link-card">
                                <div class="quick-link-title">Activity Log</div>
                                <div class="quick-link-desc">Review admin actions with timestamps in one place.</div>
                            </a>

                            <a href="/lilian-online-store/admin/admins.php" class="quick-link-card">
                                <div class="quick-link-title">Admin Accounts</div>
                                <div class="quick-link-desc">View all registered admin accounts in the system.</div>
                            </a>

                            <a href="/lilian-online-store/admin/profile.php" class="quick-link-card">
                                <div class="quick-link-title">Admin Profile</div>
                                <div class="quick-link-desc">Set the admin username and manage profile details later.</div>
                            </a>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . "/../includes/footer.php"; ?>