<?php
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/functions.php";
require_once __DIR__ . "/includes/auth-check.php";

if (isAdmin()) {
    $_SESSION['error_message'] = "Admins do not use the customer orders page.";
    redirect("/lilian-online-store/admin/dashboard.php");
}

$userId = (int) $_SESSION['user_id'];
$currentFilter = trim($_GET['status'] ?? 'all');

$counts = [
    'all' => 0,
    'to-pay' => 0,
    'to-ship' => 0,
    'to-receive' => 0,
    'to-rate' => 0,
    'cancelled' => 0
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
    $status = $row['status'];
    $total = (int)$row['total'];
    $counts['all'] += $total;

    if ($status === 'to pay') $counts['to-pay'] = $total;
    if ($status === 'to ship') $counts['to-ship'] = $total;
    if ($status === 'to receive') $counts['to-receive'] = $total;
    if ($status === 'delivered') $counts['to-rate'] = $total;
    if ($status === 'cancelled') $counts['cancelled'] = $total;
}

$sql = "
    SELECT *
    FROM orders
    WHERE user_id = ?
";
$params = [$userId];
$types = "i";

if ($currentFilter === 'to-pay') {
    $sql .= " AND status = 'to pay'";
} elseif ($currentFilter === 'to-ship') {
    $sql .= " AND status = 'to ship'";
} elseif ($currentFilter === 'to-receive') {
    $sql .= " AND status = 'to receive'";
} elseif ($currentFilter === 'to-rate') {
    $sql .= " AND status = 'delivered'";
} elseif ($currentFilter === 'cancelled') {
    $sql .= " AND status = 'cancelled'";
}

$sql .= " ORDER BY created_at DESC, id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$orderResult = $stmt->get_result();

$orders = [];
while ($order = $orderResult->fetch_assoc()) {
    $itemStmt = $conn->prepare("
        SELECT product_name, product_price, product_image, quantity, item_total
        FROM order_items
        WHERE order_id = ?
        ORDER BY id ASC
    ");
    $itemStmt->bind_param("i", $order['id']);
    $itemStmt->execute();
    $itemsResult = $itemStmt->get_result();

    $order['items'] = [];
    while ($item = $itemsResult->fetch_assoc()) {
        $order['items'][] = $item;
    }

    $orders[] = $order;
}

function orderStatusClass($status) {
    return match ($status) {
        'to pay' => 'status-to-pay',
        'to ship' => 'status-to-ship',
        'to receive' => 'status-to-receive',
        'delivered' => 'status-delivered',
        'cancelled' => 'status-cancelled',
        default => ''
    };
}

function orderStatusLabel($status) {
    return match ($status) {
        'to pay' => 'To Pay',
        'to ship' => 'To Ship',
        'to receive' => 'To Receive',
        'delivered' => 'To Rate',
        'cancelled' => 'Cancelled',
        default => ucfirst($status)
    };
}

include __DIR__ . "/includes/header.php";
?>

<link rel="stylesheet" href="/lilian-online-store/assets/css/orders.css" />

<section class="orders-section">
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

        <div class="section-head">
            <h2>Your Orders</h2>
            <p>View your current and past orders, track progress, and manage eligible actions.</p>
        </div>

        <div class="orders-layout">
            <aside class="orders-sidebar">
                <div class="orders-panel">
                    <h3>Order Tracking</h3>

                    <div class="order-filter-list">
                        <a href="/lilian-online-store/orders.php?status=all" class="order-filter-link <?= $currentFilter === 'all' ? 'active' : '' ?>">
                            <span>All Orders</span>
                            <span class="filter-count"><?= $counts['all'] ?></span>
                        </a>

                        <a href="/lilian-online-store/orders.php?status=to-pay" class="order-filter-link <?= $currentFilter === 'to-pay' ? 'active' : '' ?>">
                            <span>To Pay</span>
                            <span class="filter-count"><?= $counts['to-pay'] ?></span>
                        </a>

                        <a href="/lilian-online-store/orders.php?status=to-ship" class="order-filter-link <?= $currentFilter === 'to-ship' ? 'active' : '' ?>">
                            <span>To Ship</span>
                            <span class="filter-count"><?= $counts['to-ship'] ?></span>
                        </a>

                        <a href="/lilian-online-store/orders.php?status=to-receive" class="order-filter-link <?= $currentFilter === 'to-receive' ? 'active' : '' ?>">
                            <span>To Receive</span>
                            <span class="filter-count"><?= $counts['to-receive'] ?></span>
                        </a>

                        <a href="/lilian-online-store/orders.php?status=to-rate" class="order-filter-link <?= $currentFilter === 'to-rate' ? 'active' : '' ?>">
                            <span>To Rate</span>
                            <span class="filter-count"><?= $counts['to-rate'] ?></span>
                        </a>

                        <a href="/lilian-online-store/orders.php?status=cancelled" class="order-filter-link <?= $currentFilter === 'cancelled' ? 'active' : '' ?>">
                            <span>Cancelled</span>
                            <span class="filter-count"><?= $counts['cancelled'] ?></span>
                        </a>
                    </div>
                </div>
            </aside>

            <div class="orders-main">
                <div class="orders-topbar">
                    <div class="orders-topbar-inner">
                        <div>
                            <strong>Current View:</strong>
                            <?= htmlspecialchars(match ($currentFilter) {
                                'all' => 'All Orders',
                                'to-pay' => 'To Pay',
                                'to-ship' => 'To Ship',
                                'to-receive' => 'To Receive',
                                'to-rate' => 'To Rate',
                                'cancelled' => 'Cancelled',
                                default => 'All Orders'
                            }) ?>
                        </div>

                        <div class="orders-note">
                            <?= count($orders) ?> order<?= count($orders) !== 1 ? 's' : '' ?> found
                        </div>
                    </div>
                </div>

                <?php if (!empty($orders)): ?>
                    <div class="orders-list">
                        <?php foreach ($orders as $order): ?>
                            <article class="order-card">
                                <div class="order-card-head">
                                    <div class="order-card-head-left">
                                        <div class="order-number">Order #<?= htmlspecialchars($order['order_number']) ?></div>
                                        <div class="order-date">
                                            Placed on <?= date("F j, Y g:i A", strtotime($order['created_at'])) ?>
                                        </div>
                                    </div>

                                    <span class="status-badge <?= orderStatusClass($order['status']) ?>">
                                        <?= orderStatusLabel($order['status']) ?>
                                    </span>
                                </div>

                                <div class="order-card-body">
                                    <div class="order-products">
                                        <?php foreach ($order['items'] as $item): ?>
                                            <div class="order-product">
                                                <img
                                                    src="/lilian-online-store/<?= htmlspecialchars($item['product_image']) ?>"
                                                    alt="<?= htmlspecialchars($item['product_name']) ?>"
                                                >

                                                <div>
                                                    <div class="order-product-title"><?= htmlspecialchars($item['product_name']) ?></div>
                                                    <div class="order-product-meta">
                                                        Qty: <?= (int)$item['quantity'] ?> • Unit Price: <?= formatPeso($item['product_price']) ?>
                                                    </div>
                                                </div>

                                                <div class="order-product-total"><?= formatPeso($item['item_total']) ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <div class="order-summary-grid">
                                        <div class="order-summary-box">
                                            <div class="order-summary-label">Payment Method</div>
                                            <div class="order-summary-value"><?= htmlspecialchars($order['payment_method']) ?></div>
                                        </div>

                                        <div class="order-summary-box">
                                            <div class="order-summary-label">Shipping Fee</div>
                                            <div class="order-summary-value"><?= formatPeso($order['shipping_fee']) ?></div>
                                        </div>

                                        <div class="order-summary-box">
                                            <div class="order-summary-label">Discount</div>
                                            <div class="order-summary-value">- <?= formatPeso($order['discount']) ?></div>
                                        </div>

                                        <div class="order-summary-box">
                                            <div class="order-summary-label">Total Amount</div>
                                            <div class="order-summary-value"><?= formatPeso($order['total_amount']) ?></div>
                                        </div>
                                    </div>

                                    <div class="order-extra">
                                        <div><strong>Delivery Address:</strong> <?= htmlspecialchars($order['address']) ?></div>

                                        <?php if (!empty($order['voucher_code'])): ?>
                                            <div><strong>Voucher Used:</strong> <?= htmlspecialchars($order['voucher_code']) ?></div>
                                        <?php endif; ?>

                                        <?php if (!empty($order['payment_proof'])): ?>
                                            <div>
                                                <strong>Payment Proof:</strong>
                                                <a href="/lilian-online-store/<?= htmlspecialchars($order['payment_proof']) ?>" target="_blank" rel="noopener noreferrer">
                                                    View Uploaded Proof
                                                </a>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($order['status'] === 'cancelled' && !empty($order['cancellation_reason'])): ?>
                                            <div><strong>Cancellation Reason:</strong> <?= htmlspecialchars($order['cancellation_reason']) ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="order-actions">
                                        <?php if (in_array($order['status'], ['to pay', 'to ship'], true)): ?>
                                            <form action="/lilian-online-store/actions/cancel-order.php" method="POST">
                                                <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                                                <input type="hidden" name="cancellation_reason" value="Cancelled by customer.">
                                                <button type="submit" class="btn btn-warning-outline">Cancel Order</button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($order['status'] === 'to receive' && (int)$order['customer_received_enabled'] === 1): ?>
                                            <form action="/lilian-online-store/actions/mark-received.php" method="POST">
                                                <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                                                <button type="submit" class="btn btn-success-outline">Order Received</button>
                                            </form>
                                        <?php elseif ($order['status'] === 'to receive'): ?>
                                            <div class="orders-note">Waiting for admin to enable the Order Received button.</div>
                                        <?php endif; ?>

                                        <?php if ($order['status'] === 'delivered'): ?>
    <a href="/lilian-online-store/rate-order.php?order_id=<?= (int)$order['id'] ?>" class="btn btn-secondary">Rate Products</a>
<?php endif; ?>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="orders-empty">
                        <h3>No orders found</h3>
                        <p>
                            There are no orders under this status yet. Once you place orders,
                            they will appear here with their tracking progress and available actions.
                        </p>
                        <a href="/lilian-online-store/shop.php" class="btn btn-primary">Continue Shopping</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . "/includes/footer.php"; ?>