<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/functions.php";
require_once __DIR__ . "/../includes/admin-check.php";

$currentPage = basename($_SERVER['PHP_SELF']);
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'all');

$validStatuses = ['all', 'to pay', 'to ship', 'to receive', 'delivered', 'cancelled'];
if (!in_array($statusFilter, $validStatuses, true)) {
    $statusFilter = 'all';
}

$countMap = [
    'all' => 0,
    'to pay' => 0,
    'to ship' => 0,
    'to receive' => 0,
    'delivered' => 0,
    'cancelled' => 0
];

$countResult = $conn->query("
    SELECT status, COUNT(*) AS total
    FROM orders
    GROUP BY status
");

if ($countResult) {
    while ($row = $countResult->fetch_assoc()) {
        $countMap['all'] += (int)$row['total'];
        if (isset($countMap[$row['status']])) {
            $countMap[$row['status']] = (int)$row['total'];
        }
    }
}

$sql = "
    SELECT *
    FROM orders
    WHERE 1=1
";
$params = [];
$types = "";

if ($search !== '') {
    $sql .= " AND (
        order_number LIKE ?
        OR full_name LIKE ?
        OR email LIKE ?
        OR phone LIKE ?
        OR address LIKE ?
    )";
    $like = "%" . $search . "%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sssss";
}

if ($statusFilter !== 'all') {
    $sql .= " AND status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

$sql .= " ORDER BY created_at DESC, id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($order = $result->fetch_assoc()) {
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

function adminOrderStatusPillClass($status) {
    return match ($status) {
        'to pay' => 'pill-to-pay',
        'to ship' => 'pill-to-ship',
        'to receive' => 'pill-to-receive',
        'delivered' => 'pill-delivered',
        'cancelled' => 'pill-cancelled',
        default => ''
    };
}

function adminOrderStatusLabel($status) {
    return match ($status) {
        'to pay' => 'To Pay',
        'to ship' => 'To Ship',
        'to receive' => 'To Receive',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        default => ucfirst($status)
    };
}

include __DIR__ . "/../includes/header.php";
?>

<link rel="stylesheet" href="/lilian-online-store/assets/css/admin.css" />

<section class="admin-section">
    <div class="container">
        <?php
renderFlashMessages([
    'success_title' => 'Success',
    'success_heading' => 'Admin action completed successfully',
    'error_title' => 'Something went wrong',
    'error_heading' => 'We couldn’t complete the admin request'
]);
?>

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
                        <a href="/lilian-online-store/admin/orders.php" class="<?= $currentPage === 'orders.php' ? 'active' : '' ?>">
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
                            <h1>Orders Management</h1>
                            <p>View customer orders, cancelled transactions, and update fulfillment progress.</p>
                        </div>
                        <div class="admin-date-chip"><?= $countMap['all'] ?> total orders</div>
                    </div>
                </section>

                <section class="admin-main-card admin-toolbar">
                    <div class="admin-card-head">
                        <div>
                            <h2>Search and Filter</h2>
                            <p>Search by customer name, order number, email, phone, or address.</p>
                        </div>
                    </div>

                    <form action="/lilian-online-store/admin/orders.php" method="GET" class="admin-toolbar-grid">
                        <div>
                            <label for="search">Search</label>
                            <input
                                type="text"
                                id="search"
                                name="search"
                                class="form-input"
                                placeholder="Customer or order details"
                                value="<?= htmlspecialchars($search) ?>"
                            >
                        </div>

                        <div>
                            <label for="status">Status</label>
                            <select id="status" name="status" class="filter-select">
                                <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                                <option value="to pay" <?= $statusFilter === 'to pay' ? 'selected' : '' ?>>To Pay</option>
                                <option value="to ship" <?= $statusFilter === 'to ship' ? 'selected' : '' ?>>To Ship</option>
                                <option value="to receive" <?= $statusFilter === 'to receive' ? 'selected' : '' ?>>To Receive</option>
                                <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>

                        <div class="admin-inline-row admin-filter-actions">
    <button type="submit" class="btn btn-primary">Apply</button>
    <a href="/lilian-online-store/admin/orders.php" class="btn btn-secondary">Reset</a>
</div>
                    </form>
                </section>

                <section class="admin-main-card">
                    <div class="admin-filter-row">
                        <a href="/lilian-online-store/admin/orders.php?status=all&search=<?= urlencode($search) ?>" class="admin-filter-chip <?= $statusFilter === 'all' ? 'active' : '' ?>">
                            All (<?= $countMap['all'] ?>)
                        </a>
                        <a href="/lilian-online-store/admin/orders.php?status=to%20pay&search=<?= urlencode($search) ?>" class="admin-filter-chip <?= $statusFilter === 'to pay' ? 'active' : '' ?>">
                            To Pay (<?= $countMap['to pay'] ?>)
                        </a>
                        <a href="/lilian-online-store/admin/orders.php?status=to%20ship&search=<?= urlencode($search) ?>" class="admin-filter-chip <?= $statusFilter === 'to ship' ? 'active' : '' ?>">
                            To Ship (<?= $countMap['to ship'] ?>)
                        </a>
                        <a href="/lilian-online-store/admin/orders.php?status=to%20receive&search=<?= urlencode($search) ?>" class="admin-filter-chip <?= $statusFilter === 'to receive' ? 'active' : '' ?>">
                            To Receive (<?= $countMap['to receive'] ?>)
                        </a>
                        <a href="/lilian-online-store/admin/orders.php?status=delivered&search=<?= urlencode($search) ?>" class="admin-filter-chip <?= $statusFilter === 'delivered' ? 'active' : '' ?>">
                            Delivered (<?= $countMap['delivered'] ?>)
                        </a>
                        <a href="/lilian-online-store/admin/orders.php?status=cancelled&search=<?= urlencode($search) ?>" class="admin-filter-chip <?= $statusFilter === 'cancelled' ? 'active' : '' ?>">
                            Cancelled (<?= $countMap['cancelled'] ?>)
                        </a>
                    </div>
                </section>

                <section class="admin-main-card">
                    <div class="admin-card-head">
                        <div>
                            <h2>Orders List</h2>
                            <p>Includes customer identity, payment details, order items, and cancelled-order ownership.</p>
                        </div>
                    </div>

                    <?php if (!empty($orders)): ?>
                        <div class="admin-table-list">
                            <?php foreach ($orders as $order): ?>
                                <article class="admin-order-full-card">
                                    <div class="admin-order-full-head">
                                        <div>
                                            <div class="admin-order-full-title">Order #<?= htmlspecialchars($order['order_number']) ?></div>
                                            <div class="admin-order-full-meta">
                                                <?= htmlspecialchars($order['full_name']) ?> •
                                                <?= htmlspecialchars($order['email']) ?> •
                                                <?= date("F j, Y g:i A", strtotime($order['created_at'])) ?>
                                            </div>
                                        </div>

                                        <span class="admin-status-pill <?= adminOrderStatusPillClass($order['status']) ?>">
                                            <?= adminOrderStatusLabel($order['status']) ?>
                                        </span>
                                    </div>

                                    <div class="admin-order-full-body">
                                        <div class="admin-order-info-grid">
                                            <div class="admin-info-box">
                                                <div class="admin-info-label">Customer</div>
                                                <div class="admin-info-value"><?= htmlspecialchars($order['full_name']) ?></div>
                                            </div>

                                            <div class="admin-info-box">
                                                <div class="admin-info-label">Phone</div>
                                                <div class="admin-info-value"><?= htmlspecialchars($order['phone']) ?></div>
                                            </div>

                                            <div class="admin-info-box">
                                                <div class="admin-info-label">Payment Method</div>
                                                <div class="admin-info-value"><?= htmlspecialchars($order['payment_method']) ?></div>
                                            </div>

                                            <div class="admin-info-box">
                                                <div class="admin-info-label">Total Amount</div>
                                                <div class="admin-info-value"><?= formatPeso($order['total_amount']) ?></div>
                                            </div>

                                            <div class="admin-info-box">
                                                <div class="admin-info-label">Subtotal</div>
                                                <div class="admin-info-value"><?= formatPeso($order['subtotal']) ?></div>
                                            </div>

                                            <div class="admin-info-box">
                                                <div class="admin-info-label">Shipping Fee</div>
                                                <div class="admin-info-value"><?= formatPeso($order['shipping_fee']) ?></div>
                                            </div>

                                            <div class="admin-info-box">
                                                <div class="admin-info-label">Discount</div>
                                                <div class="admin-info-value">- <?= formatPeso($order['discount']) ?></div>
                                            </div>

                                            <div class="admin-info-box">
                                                <div class="admin-info-label">Voucher Code</div>
                                                <div class="admin-info-value"><?= !empty($order['voucher_code']) ? htmlspecialchars($order['voucher_code']) : 'None' ?></div>
                                            </div>
                                        </div>

                                        <div class="admin-info-box">
                                            <div class="admin-info-label">Delivery Address</div>
                                            <div class="admin-info-value"><?= htmlspecialchars($order['address']) ?></div>
                                        </div>

                                        <?php if (!empty($order['payment_proof'])): ?>
                                            <div class="admin-info-box">
                                                <div class="admin-info-label">Payment Proof</div>
                                                <div class="admin-info-value">
                                                    <a class="admin-proof-link" href="/lilian-online-store/<?= htmlspecialchars($order['payment_proof']) ?>" target="_blank" rel="noopener noreferrer">
                                                        View Uploaded Payment Proof
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($order['status'] === 'cancelled'): ?>
                                            <div class="admin-order-warning">
                                                Cancelled by: <strong><?= htmlspecialchars($order['full_name']) ?></strong>
                                                <?php if (!empty($order['cancellation_reason'])): ?>
                                                    <br>Reason: <?= htmlspecialchars($order['cancellation_reason']) ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="admin-order-products">
                                            <?php foreach ($order['items'] as $item): ?>
                                                <div class="admin-order-product-row">
                                                    <img
                                                        src="/lilian-online-store/<?= htmlspecialchars($item['product_image']) ?>"
                                                        alt="<?= htmlspecialchars($item['product_name']) ?>"
                                                    >
                                                    <div>
                                                        <div class="admin-order-product-title"><?= htmlspecialchars($item['product_name']) ?></div>
                                                        <div class="admin-order-product-meta">
                                                            Qty: <?= (int)$item['quantity'] ?> • Unit Price: <?= formatPeso($item['product_price']) ?>
                                                        </div>
                                                    </div>
                                                    <div class="admin-order-product-total"><?= formatPeso($item['item_total']) ?></div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>

                                        <div class="admin-order-actions-box">
                                            <form action="/lilian-online-store/actions/admin-update-order.php" method="POST" class="admin-order-actions-grid">
                                                <div class="admin-inline-form">
                                                    <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">

                                                    <div>
                                                        <label for="status-<?= (int)$order['id'] ?>">Update Status</label>
                                                        <select id="status-<?= (int)$order['id'] ?>" name="status" class="filter-select">
                                                            <option value="to pay" <?= $order['status'] === 'to pay' ? 'selected' : '' ?>>To Pay</option>
                                                            <option value="to ship" <?= $order['status'] === 'to ship' ? 'selected' : '' ?>>To Ship</option>
                                                            <option value="to receive" <?= $order['status'] === 'to receive' ? 'selected' : '' ?>>To Receive</option>
                                                            <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                                            <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                        </select>
                                                    </div>

                                                    <div class="admin-inline-row">
                                                        <label style="font-weight:700;">
                                                            <input
                                                                type="checkbox"
                                                                name="customer_received_enabled"
                                                                value="1"
                                                                <?= (int)$order['customer_received_enabled'] === 1 ? 'checked' : '' ?>
                                                                <?= $order['status'] !== 'to receive' ? '' : '' ?>
                                                            >
                                                            Enable customer "Order Received" button
                                                        </label>
                                                    </div>
                                                </div>

                                                <div class="admin-inline-row">
                                                    <button type="submit" class="btn btn-primary">Save Update</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="admin-empty">
                            <h3>No orders found</h3>
                            <p>
                                No customer orders match the current search or filter.
                                Orders and cancelled transactions will appear here once available.
                            </p>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . "/../includes/footer.php"; ?>