<?php
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/functions.php";
require_once __DIR__ . "/includes/auth-check.php";

if (isAdmin()) {
    $_SESSION['error_message'] = "Admins do not use the customer rating page.";
    redirect("/lilian-online-store/admin/dashboard.php");
}

$userId = (int) $_SESSION['user_id'];
$orderId = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;

if ($orderId <= 0) {
    $_SESSION['error_message'] = "Invalid order selected.";
    redirect("/lilian-online-store/orders.php?status=to-rate");
}

$orderStmt = $conn->prepare("
    SELECT id, order_number, status, total_amount, created_at
    FROM orders
    WHERE id = ? AND user_id = ?
    LIMIT 1
");
$orderStmt->bind_param("ii", $orderId, $userId);
$orderStmt->execute();
$orderResult = $orderStmt->get_result();

if ($orderResult->num_rows === 0) {
    $_SESSION['error_message'] = "Order not found.";
    redirect("/lilian-online-store/orders.php?status=to-rate");
}

$order = $orderResult->fetch_assoc();

$ratingProgress = getOrderRatingProgress($conn, $userId, $orderId);
$isFullyRated = $ratingProgress['is_fully_rated'];

if ($order['status'] !== 'delivered') {
    $_SESSION['error_message'] = "Only delivered orders can be rated.";
    redirect("/lilian-online-store/orders.php");
}

$itemStmt = $conn->prepare("
    SELECT oi.product_id, oi.product_name, oi.product_price, oi.product_image, oi.quantity
    FROM order_items oi
    WHERE oi.order_id = ?
    ORDER BY oi.id ASC
");
$itemStmt->bind_param("i", $orderId);
$itemStmt->execute();
$itemResult = $itemStmt->get_result();

$items = [];
while ($item = $itemResult->fetch_assoc()) {
    $item['already_rated'] = hasUserRatedProductInOrder($conn, $userId, $orderId, (int)$item['product_id']);

    $existingStmt = $conn->prepare("
        SELECT rating, review
        FROM ratings
        WHERE user_id = ? AND order_id = ? AND product_id = ?
        LIMIT 1
    ");
    $existingStmt->bind_param("iii", $userId, $orderId, $item['product_id']);
    $existingStmt->execute();
    $existingResult = $existingStmt->get_result();
    $item['existing_rating'] = $existingResult->num_rows > 0 ? $existingResult->fetch_assoc() : null;

    $items[] = $item;
}

include __DIR__ . "/includes/header.php";
?>

<link rel="stylesheet" href="/lilian-online-store/assets/css/auth.css" />
<link rel="stylesheet" href="/lilian-online-store/assets/css/rating.css" />

<section class="rating-section">
    <div class="container">
        <?php
renderFlashMessages([
    'success_title' => 'Success',
    'success_heading' => 'Action completed successfully',
    'error_title' => 'Something went wrong',
    'error_heading' => 'We couldn’t complete your request'
]);
?>

        <div class="rating-layout">
            <section class="rating-card">
                <div class="rating-card-head">
                    <div>
                        <h2><?= $isFullyRated ? 'Rated Products' : 'Rate Your Order' ?></h2>
                        <p>
                            <?= $isFullyRated
                                ? 'Review the products you already rated from this delivered order.'
                                : 'Share feedback for products from your delivered order.' ?>
                        </p>
                    </div>
                    <a
                        href="/lilian-online-store/orders.php?status=<?= $isFullyRated ? 'rated' : 'to-rate' ?>"
                        class="btn btn-secondary"
                    >
                        Back to <?= $isFullyRated ? 'Rated' : 'To Rate' ?>
                    </a>
                </div>

                <div class="rating-order-meta">
                    <div class="rating-meta-box">
                        <div class="rating-meta-label">Order Number</div>
                        <div class="rating-meta-value"><?= htmlspecialchars($order['order_number']) ?></div>
                    </div>

                    <div class="rating-meta-box">
                        <div class="rating-meta-label">Order Date</div>
                        <div class="rating-meta-value"><?= date("F j, Y", strtotime($order['created_at'])) ?></div>
                    </div>

                    <div class="rating-meta-box">
                        <div class="rating-meta-label">Total Amount</div>
                        <div class="rating-meta-value"><?= formatPeso($order['total_amount']) ?></div>
                    </div>
                </div>
            </section>

            <?php if (!empty($items)): ?>
                <section class="rating-products">
                    <?php foreach ($items as $item): ?>
                        <article class="rating-product-card">
                            <div class="rating-product-top">
                                <img
                                    src="/lilian-online-store/<?= htmlspecialchars($item['product_image']) ?>"
                                    alt="<?= htmlspecialchars($item['product_name']) ?>"
                                >

                                <div>
                                    <div class="rating-product-title"><?= htmlspecialchars($item['product_name']) ?></div>
                                    <div class="rating-product-meta">
                                        Qty: <?= (int)$item['quantity'] ?> • Unit Price: <?= formatPeso($item['product_price']) ?>
                                    </div>
                                </div>

                                <div class="rating-product-price"><?= formatPeso($item['product_price']) ?></div>
                            </div>

                            <div class="rating-product-body">
                                <?php if ($item['already_rated'] && !empty($item['existing_rating'])): ?>
                                    <div class="rated-badge">Already Rated</div>
                                    <div class="rating-note">
                                        Your rating: <strong><?= (int)$item['existing_rating']['rating'] ?>/5</strong>
                                    </div>
                                    <?php if (!empty($item['existing_rating']['review'])): ?>
                                        <div class="checkout-note" style="margin-top: 0.5rem;">
                                            “<?= htmlspecialchars($item['existing_rating']['review']) ?>”
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <form action="/lilian-online-store/actions/submit-rating.php" method="POST" class="rating-form-grid">
                                        <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                                        <input type="hidden" name="product_id" value="<?= (int)$item['product_id'] ?>">

                                        <div>
                                            <label style="display:block; font-weight:700; margin-bottom:0.55rem;">Select Rating</label>
                                            <div class="rating-stars">
                                                <?php for ($star = 1; $star <= 5; $star++): ?>
                                                    <label>
                                                        <input type="radio" name="rating" value="<?= $star ?>" required>
                                                        <span class="rating-star-pill"><?= $star ?> ★</span>
                                                    </label>
                                                <?php endfor; ?>
                                            </div>
                                        </div>

                                        <div>
                                            <label for="review-<?= (int)$item['product_id'] ?>" style="display:block; font-weight:700; margin-bottom:0.45rem;">Review</label>
                                            <textarea
                                                id="review-<?= (int)$item['product_id'] ?>"
                                                name="review"
                                                class="form-textarea"
                                                placeholder="Share your experience with this product"
                                            ></textarea>
                                        </div>

                                        <div class="rating-note">
                                            Ratings are available only for delivered orders.
                                        </div>

                                        <button type="submit" class="btn btn-primary">Submit Rating</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php else: ?>
                <div class="rating-empty">
                    <h3>No products found for rating</h3>
                    <p>This order does not have any items available for rating.</p>
                    <a href="/lilian-online-store/orders.php?status=to-rate" class="btn btn-primary">Back to Orders</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . "/includes/footer.php"; ?>