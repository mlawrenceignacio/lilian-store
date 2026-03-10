<?php
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/functions.php";
require_once __DIR__ . "/includes/auth-check.php";

if (isAdmin()) {
    $_SESSION['error_message'] = "Admins do not have a shopping cart.";
    redirect("/lilian-online-store/admin/dashboard.php");
}

$userId = (int) $_SESSION['user_id'];
$cartItems = [];
$itemCount = 0;
$subtotal = 0;

$stmt = $conn->prepare("
    SELECT
        ci.id AS cart_item_id,
        ci.quantity,
        p.id AS product_id,
        p.name,
        p.category,
        p.price,
        p.description,
        p.image,
        p.avg_rating
    FROM carts c
    INNER JOIN cart_items ci ON c.id = ci.cart_id
    INNER JOIN products p ON ci.product_id = p.id
    WHERE c.user_id = ?
    ORDER BY ci.id DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $row['item_subtotal'] = (float) $row['price'] * (int) $row['quantity'];
    $subtotal += $row['item_subtotal'];
    $itemCount += (int) $row['quantity'];
    $cartItems[] = $row;
}

include __DIR__ . "/includes/header.php";
?>

<link rel="stylesheet" href="/lilian-online-store/assets/css/cart.css" />

<section class="cart-section">
    <div class="container">
        <?php if (isset($_SESSION['success_message'])): ?>
    <div class="cart-feedback-banner cart-feedback-success">
        <div class="cart-feedback-icon">✓</div>
        <div class="cart-feedback-content">
            <span class="cart-feedback-label">Order Successful</span>
            <h3>Your order has been placed successfully!</h3>
            <p><?= htmlspecialchars($_SESSION['success_message']) ?></p>
            <div class="cart-feedback-actions">
                <a href="/lilian-online-store/orders.php" class="btn btn-primary">View My Orders</a>
                <a href="/lilian-online-store/shop.php" class="btn btn-secondary">Shop Again</a>
            </div>
        </div>
    </div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

        <?php if (!empty($cartItems)): ?>
            <div class="cart-layout">
                <div class="cart-list">
                    <div class="cart-list-head">
                        <div>
                            <h2>Your Cart</h2>
                            <p>Review your selected items before checkout.</p>
                        </div>
                        <div class="shop-results">
                            <?= $itemCount ?> item<?= $itemCount !== 1 ? 's' : '' ?>
                        </div>
                    </div>

                    <div class="cart-items">
                        <?php foreach ($cartItems as $item): ?>
                            <article class="cart-item">
                                <div class="cart-item-media">
                                    <img
                                        src="/lilian-online-store/<?= htmlspecialchars($item['image']) ?>"
                                        alt="<?= htmlspecialchars($item['name']) ?>"
                                    />
                                </div>

                                <div class="cart-item-main">
                                    <div class="cart-item-top">
                                        <div>
                                            <h3 class="cart-item-title"><?= htmlspecialchars($item['name']) ?></h3>
                                            <span class="cart-item-category"><?= htmlspecialchars($item['category']) ?></span>
                                        </div>

                                        <div class="cart-item-unit-price">
                                            <?= formatPeso($item['price']) ?><br>
                                            <small>each</small>
                                        </div>
                                    </div>

                                    <p class="cart-item-desc"><?= htmlspecialchars($item['description']) ?></p>

                                    <div class="shop-card-rating" style="margin-bottom: 0.9rem;">
                                        ★ <?= number_format((float) $item['avg_rating'], 1) ?>
                                    </div>

                                    <div class="cart-item-actions">
                                        <form action="/lilian-online-store/actions/update-cart.php" method="POST" class="quantity-form">
                                            <input type="hidden" name="cart_item_id" value="<?= (int) $item['cart_item_id'] ?>">
                                            <label for="qty-<?= (int) $item['cart_item_id'] ?>">Quantity</label>
                                            <input
                                                type="number"
                                                id="qty-<?= (int) $item['cart_item_id'] ?>"
                                                name="quantity"
                                                class="qty-input"
                                                min="1"
                                                max="99"
                                                value="<?= (int) $item['quantity'] ?>"
                                            >
                                            <button type="submit" class="btn btn-secondary">Update</button>
                                        </form>

                                        <div class="cart-item-subtotal">
                                            Subtotal: <?= formatPeso($item['item_subtotal']) ?>
                                        </div>

                                        <form action="/lilian-online-store/actions/remove-cart-item.php" method="POST" class="remove-form">
                                            <input type="hidden" name="cart_item_id" value="<?= (int) $item['cart_item_id'] ?>">
                                            <button type="submit" class="btn btn-danger-outline">Remove</button>
                                        </form>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>

                <aside class="cart-summary-card">
                    <h3>Cart Summary</h3>

                    <div class="summary-lines">
                        <div class="summary-line muted">
                            <span>Total Items</span>
                            <span><?= $itemCount ?></span>
                        </div>

                        <div class="summary-line muted">
                            <span>Subtotal</span>
                            <span><?= formatPeso($subtotal) ?></span>
                        </div>

                        <div class="summary-line muted">
                            <span>Shipping Fee</span>
                            <span>To be calculated at checkout</span>
                        </div>

                        <div class="summary-line muted">
                            <span>Voucher Discount</span>
                            <span>To be applied at checkout</span>
                        </div>

                        <div class="summary-line total">
                            <span>Estimated Total</span>
                            <span><?= formatPeso($subtotal) ?></span>
                        </div>
                    </div>

                    <p class="summary-note">
                        Continue to checkout to confirm delivery address, shipping fee,
                        voucher discount, payment method, and place your order.
                    </p>

                    <div class="summary-actions">
                        <a href="/lilian-online-store/checkout.php" class="btn btn-primary">Proceed to Checkout</a>
                        <a href="/lilian-online-store/shop.php" class="btn btn-secondary">Continue Shopping</a>
                    </div>
                </aside>
            </div>
        <?php else: ?>
    <div class="cart-empty-wrap">
        <div class="cart-empty-card <?= isset($_SESSION['just_ordered']) ? 'cart-empty-success' : '' ?>">
            <div class="cart-empty-icon"><?= isset($_SESSION['just_ordered']) ? '✓' : '🛒' ?></div>

            <?php if (isset($_SESSION['just_ordered'])): ?>
                <span class="cart-empty-badge">Thank you for your order</span>
                <h2>Your cart is now cleared</h2>
                <p>
                    Your order has been placed successfully. You can track your order status,
                    review your purchase, or continue shopping for more items.
                </p>
                <div class="cart-empty-actions">
                    <a href="/lilian-online-store/orders.php" class="btn btn-primary">View My Orders</a>
                    <a href="/lilian-online-store/shop.php" class="btn btn-secondary">Continue Shopping</a>
                </div>
                <?php unset($_SESSION['just_ordered']); ?>
            <?php else: ?>
                <h2>Your cart is empty</h2>
                <p>
                    You haven’t added any items yet. Browse the shop and add your daily
                    essentials before proceeding to checkout.
                </p>
                <a href="/lilian-online-store/shop.php" class="btn btn-primary">Go to Shop</a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
    </div>
</section>

<?php include __DIR__ . "/includes/footer.php"; ?>