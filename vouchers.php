<?php
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/functions.php";
require_once __DIR__ . "/includes/auth-check.php";

if (isAdmin()) {
    $_SESSION['error_message'] = "Admins do not use customer vouchers.";
    redirect("/lilian-online-store/admin/dashboard.php");
}

$userId = (int) $_SESSION['user_id'];

$availableVouchers = [];
$claimedVouchers = [];

$availableStmt = $conn->prepare("
    SELECT
        v.id,
        v.code,
        v.title,
        v.description,
        v.discount_type,
        v.discount_value,
        v.min_order_amount,
        v.is_active,
        uv.id AS user_voucher_id,
        uv.is_used
    FROM vouchers v
    LEFT JOIN user_vouchers uv
        ON v.id = uv.voucher_id AND uv.user_id = ?
    ORDER BY v.is_active DESC, v.id DESC
");
$availableStmt->bind_param("i", $userId);
$availableStmt->execute();
$availableResult = $availableStmt->get_result();

while ($row = $availableResult->fetch_assoc()) {
    $availableVouchers[] = $row;

    if (!empty($row['user_voucher_id'])) {
        $claimedVouchers[] = $row;
    }
}

function voucherDiscountLabel($type, $value) {
    if ($type === 'percent') {
        return rtrim(rtrim(number_format((float)$value, 2, '.', ''), '0'), '.') . '% OFF';
    }

    return '₱' . number_format((float)$value, 2) . ' OFF';
}

include __DIR__ . "/includes/header.php";
?>

<link rel="stylesheet" href="/lilian-online-store/assets/css/vouchers.css" />

<section class="vouchers-section">
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

        <div class="vouchers-layout">
            <section class="vouchers-panel">
                <div class="vouchers-panel-head">
                    <div>
                        <h2>Available Vouchers</h2>
                        <p>Browse all store vouchers and claim the ones you can use at checkout.</p>
                    </div>
                </div>

                <?php if (!empty($availableVouchers)): ?>
                    <div class="voucher-grid">
                        <?php foreach ($availableVouchers as $voucher): ?>
                            <article class="voucher-card">
                                <div class="voucher-card-top">
                                    <span class="voucher-badge"><?= htmlspecialchars(voucherDiscountLabel($voucher['discount_type'], $voucher['discount_value'])) ?></span>
                                    <h3 class="voucher-title"><?= htmlspecialchars($voucher['title']) ?></h3>
                                    <div class="voucher-code"><?= htmlspecialchars($voucher['code']) ?></div>
                                    <p class="voucher-desc"><?= htmlspecialchars($voucher['description'] ?? 'No description available.') ?></p>
                                </div>

                                <div class="voucher-card-body">
                                    <div class="voucher-meta-grid">
                                        <div class="voucher-meta-box">
                                            <div class="voucher-meta-label">Minimum Order</div>
                                            <div class="voucher-meta-value"><?= formatPeso($voucher['min_order_amount']) ?></div>
                                        </div>

                                        <div class="voucher-meta-box">
                                            <div class="voucher-meta-label">Status</div>
                                            <?php if ((int)$voucher['is_active'] !== 1): ?>
                                                <div class="voucher-status inactive">Inactive</div>
                                            <?php elseif (!empty($voucher['user_voucher_id']) && (int)$voucher['is_used'] === 1): ?>
                                                <div class="voucher-status used">Used</div>
                                            <?php elseif (!empty($voucher['user_voucher_id'])): ?>
                                                <div class="voucher-status claimed">Claimed</div>
                                            <?php else: ?>
                                                <div class="voucher-status available">Available</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="voucher-actions">
                                        <?php if ((int)$voucher['is_active'] !== 1): ?>
                                            <span class="voucher-note">This voucher is currently inactive.</span>
                                        <?php elseif (!empty($voucher['user_voucher_id']) && (int)$voucher['is_used'] === 1): ?>
                                            <span class="voucher-note">Already used on a previous order.</span>
                                        <?php elseif (!empty($voucher['user_voucher_id'])): ?>
                                            <a href="/lilian-online-store/checkout.php?voucher_code=<?= urlencode($voucher['code']) ?>" class="btn btn-secondary">
                                                Use at Checkout
                                            </a>
                                        <?php else: ?>
                                            <form action="/lilian-online-store/actions/claim-voucher.php" method="POST">
                                                <input type="hidden" name="voucher_id" value="<?= (int)$voucher['id'] ?>">
                                                <button type="submit" class="btn btn-primary">Claim Voucher</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="voucher-empty">
                        <h3>No vouchers available</h3>
                        <p>There are no vouchers in the store yet. Available vouchers will appear here once added.</p>
                    </div>
                <?php endif; ?>
            </section>

            <section class="vouchers-panel">
                <div class="vouchers-panel-head">
                    <div>
                        <h2>Your Collected Vouchers</h2>
                        <p>These are the vouchers you have already claimed and can use if still eligible.</p>
                    </div>
                </div>

                <?php if (!empty($claimedVouchers)): ?>
                    <div class="voucher-grid">
                        <?php foreach ($claimedVouchers as $voucher): ?>
                            <article class="voucher-card">
                                <div class="voucher-card-top">
                                    <span class="voucher-badge"><?= htmlspecialchars(voucherDiscountLabel($voucher['discount_type'], $voucher['discount_value'])) ?></span>
                                    <h3 class="voucher-title"><?= htmlspecialchars($voucher['title']) ?></h3>
                                    <div class="voucher-code"><?= htmlspecialchars($voucher['code']) ?></div>
                                    <p class="voucher-desc"><?= htmlspecialchars($voucher['description'] ?? 'No description available.') ?></p>
                                </div>

                                <div class="voucher-card-body">
                                    <div class="voucher-meta-grid">
                                        <div class="voucher-meta-box">
                                            <div class="voucher-meta-label">Minimum Order</div>
                                            <div class="voucher-meta-value"><?= formatPeso($voucher['min_order_amount']) ?></div>
                                        </div>

                                        <div class="voucher-meta-box">
                                            <div class="voucher-meta-label">Claim Status</div>
                                            <?php if ((int)$voucher['is_used'] === 1): ?>
                                                <div class="voucher-status used">Used</div>
                                            <?php else: ?>
                                                <div class="voucher-status claimed">Claimed</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="voucher-actions">
                                        <?php if ((int)$voucher['is_used'] === 1): ?>
                                            <span class="voucher-note">This voucher was already used.</span>
                                        <?php else: ?>
                                            <a href="/lilian-online-store/checkout.php?voucher_code=<?= urlencode($voucher['code']) ?>" class="btn btn-primary">
                                                Apply in Checkout
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="voucher-empty">
                        <h3>No collected vouchers yet</h3>
                        <p>Claim available vouchers above so they can appear in your collection.</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</section>

<?php include __DIR__ . "/includes/footer.php"; ?>