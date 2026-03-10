<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/functions.php";
require_once __DIR__ . "/../includes/admin-check.php";

$currentPage = basename($_SERVER['PHP_SELF']);
$currentFilter = trim($_GET['type'] ?? 'all');

$allowedFilters = ['all', 'new_order', 'order_received', 'order_cancelled'];
if (!in_array($currentFilter, $allowedFilters, true)) {
    $currentFilter = 'all';
}

$countMap = [
    'all' => 0,
    'new_order' => 0,
    'order_received' => 0,
    'order_cancelled' => 0
];

$countResult = $conn->query("
    SELECT type, COUNT(*) AS total
    FROM notifications
    GROUP BY type
");

if ($countResult) {
    while ($row = $countResult->fetch_assoc()) {
        $countMap['all'] += (int)$row['total'];

        if (isset($countMap[$row['type']])) {
            $countMap[$row['type']] = (int)$row['total'];
        }
    }
}

$sql = "
    SELECT id, type, message, related_order_id, is_read, created_at
    FROM notifications
";

if ($currentFilter !== 'all') {
    $sql .= " WHERE type = ?";
}

$sql .= " ORDER BY created_at DESC, id DESC";

if ($currentFilter !== 'all') {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $currentFilter);
    $stmt->execute();
    $notifResult = $stmt->get_result();
} else {
    $notifResult = $conn->query($sql);
}

$notifications = [];
if ($notifResult) {
    while ($row = $notifResult->fetch_assoc()) {
        $notifications[] = $row;
    }
}

function notifTitle($type) {
    return match ($type) {
        'new_order' => 'New Order Placed',
        'order_received' => 'Order Marked Received',
        'order_cancelled' => 'Order Cancelled',
        default => 'Notification'
    };
}

function notifTypeClass($type) {
    return match ($type) {
        'new_order' => 'new-order',
        'order_received' => 'received',
        'order_cancelled' => 'cancelled',
        default => ''
    };
}

function notifTypeLabel($type) {
    return match ($type) {
        'new_order' => 'Placed Order',
        'order_received' => 'Marked Received',
        'order_cancelled' => 'Cancelled Order',
        default => 'Notification'
    };
}

function notifIcon($type) {
    return match ($type) {
        'new_order' => '🛍',
        'order_received' => '✅',
        'order_cancelled' => '✖',
        default => '•'
    };
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
                        <a href="/lilian-online-store/admin/notifications.php" class="<?= $currentPage === 'notifications.php' ? 'active' : '' ?>">
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
                <section class="admin-main-card">
                    <div class="admin-main-head">
                        <div>
                            <h1>Notifications</h1>
                            <p>Track important customer actions across the store.</p>
                        </div>
                        <div class="admin-date-chip"><?= $countMap['all'] ?> total</div>
                    </div>
                </section>

                <section class="admin-main-card">
                    <div class="admin-card-head">
                        <div>
                            <h2>Filter Notifications</h2>
                            <p>View all customer-triggered events required by the system.</p>
                        </div>
                    </div>

                    <div class="admin-filter-row">
                        <a href="/lilian-online-store/admin/notifications.php?type=all" class="admin-filter-chip <?= $currentFilter === 'all' ? 'active' : '' ?>">
                            All (<?= $countMap['all'] ?>)
                        </a>
                        <a href="/lilian-online-store/admin/notifications.php?type=new_order" class="admin-filter-chip <?= $currentFilter === 'new_order' ? 'active' : '' ?>">
                            Placed Orders (<?= $countMap['new_order'] ?>)
                        </a>
                        <a href="/lilian-online-store/admin/notifications.php?type=order_received" class="admin-filter-chip <?= $currentFilter === 'order_received' ? 'active' : '' ?>">
                            Marked Received (<?= $countMap['order_received'] ?>)
                        </a>
                        <a href="/lilian-online-store/admin/notifications.php?type=order_cancelled" class="admin-filter-chip <?= $currentFilter === 'order_cancelled' ? 'active' : '' ?>">
                            Cancelled Orders (<?= $countMap['order_cancelled'] ?>)
                        </a>
                    </div>
                </section>

                <section class="admin-main-card">
                    <div class="admin-card-head">
                        <div>
                            <h2>Notification Feed</h2>
                            <p>Latest system events from customer actions.</p>
                        </div>
                    </div>

                    <?php if (!empty($notifications)): ?>
                        <div class="admin-notif-list">
                            <?php foreach ($notifications as $notif): ?>
                                <?php $class = notifTypeClass($notif['type']); ?>
                                <article class="admin-notif-card">
                                    <div class="admin-notif-icon <?= $class ?>">
                                        <?= notifIcon($notif['type']) ?>
                                    </div>

                                    <div class="admin-notif-body">
                                        <div class="admin-notif-title"><?= htmlspecialchars(notifTitle($notif['type'])) ?></div>
                                        <div class="admin-notif-message"><?= htmlspecialchars($notif['message']) ?></div>
                                        <div class="admin-notif-meta">
                                            <?php if (!empty($notif['related_order_id'])): ?>
                                                Related Order ID: #<?= (int)$notif['related_order_id'] ?>
                                            <?php else: ?>
                                                No linked order
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="admin-notif-side">
                                        <span class="admin-notif-type <?= $class ?>">
                                            <?= htmlspecialchars(notifTypeLabel($notif['type'])) ?>
                                        </span>
                                        <div class="admin-notif-time">
                                            <?= date("F j, Y g:i A", strtotime($notif['created_at'])) ?>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="admin-empty">
                            <h3>No notifications found</h3>
                            <p>
                                Notifications will appear here when a customer places an order,
                                marks it as received, or cancels an order.
                            </p>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . "/../includes/footer.php"; ?>