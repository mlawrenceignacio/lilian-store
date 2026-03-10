<?php
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/functions.php";
require_once __DIR__ . "/includes/auth-check.php";

if (isAdmin()) {
    $_SESSION['error_message'] = "Admins cannot checkout customer orders.";
    redirect("/lilian-online-store/admin/dashboard.php");
}

$userId = (int) $_SESSION['user_id'];

$userStmt = $conn->prepare("
    SELECT full_name, email, phone, address
    FROM users
    WHERE id = ?
    LIMIT 1
");
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();

$cartItems = getUserCartItems($conn, $userId);

if (empty($cartItems)) {
    $_SESSION['error_message'] = "Your cart is empty.";
    redirect("/lilian-online-store/cart.php");
}

$subtotal = 0;
$itemCount = 0;

foreach ($cartItems as $item) {
    $subtotal += (float)$item['item_subtotal'];
    $itemCount += (int)$item['quantity'];
}

$shippingFee = 50.00;
$voucherCode = trim($_GET['voucher_code'] ?? '');
$voucherDiscount = 0.00;
$appliedVoucher = null;
$voucherMessage = '';

if ($voucherCode !== '') {
    $voucherStmt = $conn->prepare("
    SELECT
        v.id,
        v.code,
        v.title,
        v.description,
        v.discount_type,
        v.discount_value,
        v.min_order_amount,
        uv.id AS user_voucher_id,
        uv.is_used
    FROM vouchers v
    INNER JOIN user_vouchers uv ON v.id = uv.voucher_id
    WHERE v.code = ? AND v.is_active = 1 AND uv.user_id = ? AND uv.is_used = 0
    LIMIT 1
");
$voucherStmt->bind_param("si", $voucherCode, $userId);
    $voucherStmt->execute();
    $voucherResult = $voucherStmt->get_result();

    if ($voucherResult->num_rows > 0) {
        $voucher = $voucherResult->fetch_assoc();

        if ($subtotal >= (float)$voucher['min_order_amount']) {
            if ($voucher['discount_type'] === 'fixed') {
                $voucherDiscount = (float)$voucher['discount_value'];
            } else {
                $voucherDiscount = $subtotal * ((float)$voucher['discount_value'] / 100);
            }

            if ($voucherDiscount > $subtotal) {
                $voucherDiscount = $subtotal;
            }

            $appliedVoucher = $voucher;
            $voucherMessage = "Voucher applied successfully.";
        } else {
            $voucherMessage = "This voucher requires a minimum order of " . formatPeso($voucher['min_order_amount']) . ".";
        }
    } else {
        $voucherMessage = "Voucher code is invalid, inactive, not claimed, or already used.";
    }
}

$totalAmount = $subtotal + $shippingFee - $voucherDiscount;

include __DIR__ . "/includes/header.php";
?>

<link rel="stylesheet" href="/lilian-online-store/assets/css/auth.css" />
<link rel="stylesheet" href="/lilian-online-store/assets/css/checkout.css" />

<section class="checkout-section">
    <div class="container">
        <?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-banner">
        <div class="alert-icon">✓</div>
        <div class="alert-content">
            <strong>Success</strong>
            <p><?= htmlspecialchars($_SESSION['success_message']) ?></p>
        </div>
    </div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-error alert-banner">
        <div class="alert-icon">!</div>
        <div class="alert-content">
            <strong>Something went wrong</strong>
            <p><?= htmlspecialchars($_SESSION['error_message']) ?></p>
        </div>
    </div>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

        <div class="section-head">
            <h2>Checkout</h2>
            <p>Confirm your address, payment method, and order details before placing your order.</p>
        </div>

        <div class="checkout-layout">
            <div class="checkout-form-card">
                <form action="/lilian-online-store/actions/place-order.php" method="POST" enctype="multipart/form-data">
                    <div class="checkout-group">
                        <h3>Delivery Information</h3>
                        <div class="checkout-grid">
                            <div>
                                <label for="full_name">Full Name</label>
                                <input type="text" id="full_name" name="full_name" class="form-input" required
                                    value="<?= htmlspecialchars($user['full_name'] ?? '') ?>">
                            </div>

                            <div>
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" class="form-input" required
                                    value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                            </div>

                            <div>
                                <label for="phone">Contact Number</label>
                                <input type="text" id="phone" name="phone" class="form-input" required
                                    value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                            </div>

                            <div class="checkout-box">
                                <strong>Shipping Fee</strong>
                                <div><?= formatPeso($shippingFee) ?></div>
                                <div class="checkout-note">Flat delivery fee for the checkout flow.</div>
                            </div>

                            <div class="full-span">
                                <label for="address">Delivery Address</label>
                                <textarea id="address" name="address" class="form-textarea" required><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="checkout-group">
                        <h3>Voucher</h3>
                        <div class="checkout-grid">
                            <div class="full-span">
                                <div class="voucher-row">
                                    <input
                                        type="text"
                                        name="voucher_code"
                                        class="form-input"
                                        placeholder="Enter voucher code"
                                        value="<?= htmlspecialchars($voucherCode) ?>"
                                    >
                                    <button type="submit" formaction="/lilian-online-store/checkout.php" formmethod="GET" class="btn btn-secondary">
                                        Apply Voucher
                                    </button>
                                </div>

                                <?php if ($voucherMessage !== ''): ?>
    <div class="voucher-feedback <?= $appliedVoucher ? 'voucher-success' : 'voucher-error' ?>">
        <span class="voucher-feedback-icon"><?= $appliedVoucher ? '✓' : '!' ?></span>
        <div>
            <strong><?= $appliedVoucher ? 'Voucher Applied' : 'Voucher Notice' ?></strong>
            <p><?= htmlspecialchars($voucherMessage) ?></p>
        </div>
    </div>
<?php endif; ?>

<?php if ($appliedVoucher): ?>
    <div class="applied-voucher-card">
        <div class="applied-voucher-badge">Applied Voucher</div>
        <div class="applied-voucher-code"><?= htmlspecialchars($appliedVoucher['code']) ?></div>
        <div class="applied-voucher-title"><?= htmlspecialchars($appliedVoucher['title']) ?></div>
        <?php if (!empty($appliedVoucher['description'])): ?>
            <div class="applied-voucher-desc"><?= htmlspecialchars($appliedVoucher['description']) ?></div>
        <?php endif; ?>
    </div>
<?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="checkout-group">
                        <h3>Payment Method</h3>
                        <div class="payment-options">
                            <div class="payment-option">
                                <label>
                                    <input type="radio" name="payment_method" value="COD" checked>
                                    <div>
                                        <div class="payment-title">Cash on Delivery (COD)</div>
                                        <div class="payment-desc">Pay in cash once the order arrives.</div>
                                    </div>
                                </label>
                            </div>

                            <div class="payment-option">
                                <label>
                                    <input type="radio" name="payment_method" value="GCash">
                                    <div>
                                        <div class="payment-title">GCash</div>
                                        <div class="payment-desc">Upload your payment proof manually during checkout.</div>
                                    </div>
                                </label>
                            </div>

                            <div class="payment-option">
                                <label>
                                    <input type="radio" name="payment_method" value="GoTyme">
                                    <div>
                                        <div class="payment-title">GoTyme</div>
                                        <div class="payment-desc">Upload your payment proof manually during checkout.</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div id="paymentProofWrap" class="checkout-group payment-proof-wrap hidden">
    <div class="payment-proof-card">
        <div class="payment-proof-head">
            <div>
                <h4>Upload Payment Proof</h4>
                <p>Required for GCash and GoTyme payments.</p>
            </div>
            <div class="payment-proof-badge">Required</div>
        </div>

        <label for="payment_proof" class="payment-upload-box">
            <div class="upload-box-icon">↑</div>
            <div class="upload-box-text">
                <strong>Choose an image file</strong>
                <span>Upload your receipt or screenshot of payment confirmation</span>
            </div>
            <div class="upload-box-meta">JPG, JPEG, PNG, WEBP • Max 5MB</div>
        </label>

        <input
            type="file"
            id="payment_proof"
            name="payment_proof"
            class="payment-file-input"
            accept=".jpg,.jpeg,.png,.webp"
        >

        <div id="selectedFileName" class="selected-file-name">No file selected yet.</div>

        <div class="file-help">
            Make sure the reference number, amount, and sender details are visible in the screenshot.
        </div>
    </div>
</div>
                    </div>

                    <input type="hidden" name="shipping_fee" value="<?= htmlspecialchars(number_format($shippingFee, 2, '.', '')) ?>">
                    <input type="hidden" name="voucher_discount" value="<?= htmlspecialchars(number_format($voucherDiscount, 2, '.', '')) ?>">
                    <input type="hidden" name="voucher_code_applied" value="<?= htmlspecialchars($appliedVoucher['code'] ?? '') ?>">

                    <div class="checkout-group">
                        <button type="submit" class="btn btn-primary auth-submit">Place Order</button>
                    </div>
                </form>
            </div>

            <aside class="checkout-summary-card">
                <h3>Order Summary</h3>

                <div class="order-preview-list">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="order-preview-item">
                            <img src="/lilian-online-store/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                            <div>
                                <div class="order-preview-title"><?= htmlspecialchars($item['name']) ?></div>
                                <div class="order-preview-meta">
                                    Qty: <?= (int)$item['quantity'] ?> • <?= htmlspecialchars($item['category']) ?>
                                </div>
                            </div>
                            <div class="order-preview-price"><?= formatPeso($item['item_subtotal']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="summary-lines">
                    <div class="summary-line">
                        <span>Items</span>
                        <span><?= $itemCount ?></span>
                    </div>

                    <div class="summary-line">
                        <span>Subtotal</span>
                        <span><?= formatPeso($subtotal) ?></span>
                    </div>

                    <div class="summary-line">
                        <span>Shipping Fee</span>
                        <span><?= formatPeso($shippingFee) ?></span>
                    </div>

                    <div class="summary-line">
                        <span>Voucher Discount</span>
                        <span>- <?= formatPeso($voucherDiscount) ?></span>
                    </div>

                    <div class="summary-line total">
                        <span>Total</span>
                        <span><?= formatPeso($totalAmount) ?></span>
                    </div>
                </div>

                <p class="summary-note">
                    Review your order carefully before placing it.
                </p>
            </aside>
        </div>
    </div>
</section>

<script src="/lilian-online-store/assets/js/checkout.js"></script>

<?php include __DIR__ . "/includes/footer.php"; ?>