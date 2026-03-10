<?php
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/functions.php";
require_once __DIR__ . "/includes/auth-check.php";

if (isAdmin()) {
    $_SESSION['error_message'] = "Admins use the admin profile page.";
    redirect("/lilian-online-store/admin/dashboard.php");
}

$userId = (int) $_SESSION['user_id'];

$userStmt = $conn->prepare("
    SELECT id, full_name, email, phone, address, created_at
    FROM users
    WHERE id = ?
    LIMIT 1
");
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();

$statusCounts = [
    'to pay' => 0,
    'to ship' => 0,
    'to receive' => 0,
    'delivered' => 0
];

$countStmt = $conn->prepare("
    SELECT status, COUNT(*) AS total
    FROM orders
    WHERE user_id = ?
    GROUP BY status
");
$countStmt->bind_param("i", $userId);
$countStmt->execute();
$countResult = $countStmt->get_result();

while ($row = $countResult->fetch_assoc()) {
    if (array_key_exists($row['status'], $statusCounts)) {
        $statusCounts[$row['status']] = (int)$row['total'];
    }
}

function getProfileOrdersByStatus($conn, $userId, $status, $limit = 2) {
    $stmt = $conn->prepare("
        SELECT id, order_number, total_amount, created_at
        FROM orders
        WHERE user_id = ? AND status = ?
        ORDER BY created_at DESC, id DESC
        LIMIT ?
    ");
    $stmt->bind_param("isi", $userId, $status, $limit);
    $stmt->execute();
    $ordersResult = $stmt->get_result();

    $orders = [];

    while ($order = $ordersResult->fetch_assoc()) {
        $itemStmt = $conn->prepare("
            SELECT product_name, product_image, quantity
            FROM order_items
            WHERE order_id = ?
            ORDER BY id ASC
            LIMIT 1
        ");
        $itemStmt->bind_param("i", $order['id']);
        $itemStmt->execute();
        $item = $itemStmt->get_result()->fetch_assoc();

        $order['preview_item'] = $item ?: null;
        $orders[] = $order;
    }

    return $orders;
}

$previewGroups = [
    'to pay' => getProfileOrdersByStatus($conn, $userId, 'to pay'),
    'to ship' => getProfileOrdersByStatus($conn, $userId, 'to ship'),
    'to receive' => getProfileOrdersByStatus($conn, $userId, 'to receive'),
    'delivered' => getProfileOrdersByStatus($conn, $userId, 'delivered')
];

$initial = strtoupper(substr(trim($user['full_name'] ?? 'U'), 0, 1));

include __DIR__ . "/includes/header.php";
?>

<link rel="stylesheet" href="/lilian-online-store/assets/css/profile.css" />

<section class="profile-section">
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

        <div class="profile-layout">
            <aside class="profile-card">
                <div class="profile-avatar"><?= htmlspecialchars($initial) ?></div>

                <div class="profile-name"><?= htmlspecialchars($user['full_name'] ?? '') ?></div>
                <div class="profile-role">Customer Account</div>

                <div class="profile-info-list">
                    <div class="profile-info-item">
                        <div class="profile-info-label">Email Address</div>
                        <div class="profile-info-value"><?= htmlspecialchars($user['email'] ?? '') ?></div>
                    </div>

                    <div class="profile-info-item">
                        <div class="profile-info-label">Contact Number</div>
                        <div class="profile-info-value"><?= htmlspecialchars($user['phone'] ?? 'Not set') ?></div>
                    </div>

                    <div class="profile-info-item">
                        <div class="profile-info-label">Delivery Address</div>
                        <div class="profile-info-value"><?= htmlspecialchars($user['address'] ?? 'Not set') ?></div>
                    </div>

                    <div class="profile-info-item">
                        <div class="profile-info-label">Member Since</div>
                        <div class="profile-info-value">
                            <?= !empty($user['created_at']) ? date("F j, Y", strtotime($user['created_at'])) : '—' ?>
                        </div>
                    </div>
                </div>

                <div class="profile-actions">
                    <a href="/lilian-online-store/orders.php" class="btn btn-primary">View All Orders</a>
                    <a href="/lilian-online-store/vouchers.php" class="btn btn-secondary">View Vouchers</a>
                </div>
            </aside>

            <div class="profile-main">
                <section class="profile-panel">
                    <div class="profile-panel-head">
                        <div>
                            <h2>Order Tracking</h2>
                            <p>Monitor your current order progress by status.</p>
                        </div>
                    </div>

                    <div class="tracking-grid">
                        <div class="tracking-card">
                            <div class="tracking-label">To Pay</div>
                            <div class="tracking-value"><?= $statusCounts['to pay'] ?></div>
                            <a href="/lilian-online-store/orders.php?status=to-pay" class="tracking-link">View orders</a>
                        </div>

                        <div class="tracking-card">
                            <div class="tracking-label">To Ship</div>
                            <div class="tracking-value"><?= $statusCounts['to ship'] ?></div>
                            <a href="/lilian-online-store/orders.php?status=to-ship" class="tracking-link">View orders</a>
                        </div>

                        <div class="tracking-card">
                            <div class="tracking-label">To Receive</div>
                            <div class="tracking-value"><?= $statusCounts['to receive'] ?></div>
                            <a href="/lilian-online-store/orders.php?status=to-receive" class="tracking-link">View orders</a>
                        </div>

                        <div class="tracking-card">
                            <div class="tracking-label">To Rate</div>
                            <div class="tracking-value"><?= $statusCounts['delivered'] ?></div>
                            <a href="/lilian-online-store/orders.php?status=to-rate" class="tracking-link">View orders</a>
                        </div>
                    </div>
                </section>

                <section class="profile-panel">
                    <div class="profile-panel-head">
                        <div>
                            <h2>Order Status Preview</h2>
                            <p>Quick look at your latest orders under each status.</p>
                        </div>
                    </div>

                    <div class="status-preview-grid">
                        <?php
                        $statusConfig = [
                            'to pay' => ['title' => 'To Pay', 'link' => '/lilian-online-store/orders.php?status=to-pay'],
                            'to ship' => ['title' => 'To Ship', 'link' => '/lilian-online-store/orders.php?status=to-ship'],
                            'to receive' => ['title' => 'To Receive', 'link' => '/lilian-online-store/orders.php?status=to-receive'],
                            'delivered' => ['title' => 'To Rate', 'link' => '/lilian-online-store/orders.php?status=to-rate']
                        ];
                        ?>

                        <?php foreach ($statusConfig as $statusKey => $config): ?>
                            <article class="profile-status-card">
                                <div class="profile-status-head">
                                    <h3><?= htmlspecialchars($config['title']) ?></h3>
                                    <span class="profile-count-badge"><?= $statusCounts[$statusKey] ?? 0 ?></span>
                                </div>

                                <?php if (!empty($previewGroups[$statusKey])): ?>
                                    <div class="profile-order-list">
                                        <?php foreach ($previewGroups[$statusKey] as $order): ?>
                                            <div class="profile-order-item">
                                                <?php if (!empty($order['preview_item']['product_image'])): ?>
                                                    <img
                                                        src="/lilian-online-store/<?= htmlspecialchars($order['preview_item']['product_image']) ?>"
                                                        alt="<?= htmlspecialchars($order['preview_item']['product_name'] ?? 'Order item') ?>"
                                                    >
                                                <?php else: ?>
                                                    <img
                                                        src="/lilian-online-store/assets/images/logo.png"
                                                        alt="Order item"
                                                    >
                                                <?php endif; ?>

                                                <div>
                                                    <div class="profile-order-title">
                                                        <?= htmlspecialchars($order['preview_item']['product_name'] ?? $order['order_number']) ?>
                                                    </div>
                                                    <div class="profile-order-meta">
                                                        Order #<?= htmlspecialchars($order['order_number']) ?>
                                                        <?php if (!empty($order['preview_item']['quantity'])): ?>
                                                            • Qty: <?= (int)$order['preview_item']['quantity'] ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="profile-order-meta">
                                                        <?= date("F j, Y", strtotime($order['created_at'])) ?>
                                                    </div>
                                                </div>

                                                <div class="profile-order-amount">
                                                    <?= formatPeso($order['total_amount']) ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <div class="profile-status-footer">
                                        <a href="<?= htmlspecialchars($config['link']) ?>" class="btn btn-secondary">
    <?= $statusKey === 'delivered' ? 'Open To Rate Orders' : 'See More' ?>
</a>
                                    </div>
                                <?php else: ?>
                                    <div class="profile-empty">
                                        <h4>No <?= htmlspecialchars($config['title']) ?> orders</h4>
                                        <p>Your orders under this status will appear here once available.</p>
                                    </div>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . "/includes/footer.php"; ?>