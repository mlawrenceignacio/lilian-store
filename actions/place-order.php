<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/functions.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect("/lilian-online-store/checkout.php");
}

if (!isLoggedIn() || isAdmin()) {
    $_SESSION['error_message'] = "Please login as a customer first.";
    redirect("/lilian-online-store/auth/login.php");
}

$userId = (int) $_SESSION['user_id'];

$fullName = sanitize($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');
$address = sanitize($_POST['address'] ?? '');
$paymentMethod = trim($_POST['payment_method'] ?? 'COD');
$shippingFee = (float)($_POST['shipping_fee'] ?? 0);
$voucherCode = trim($_POST['voucher_code_applied'] ?? '');
$voucherDiscount = 0.00;
$appliedVoucherId = null;

if ($fullName === '' || $email === '' || $phone === '' || $address === '') {
    $_SESSION['error_message'] = "Please complete all checkout fields.";
    redirect("/lilian-online-store/checkout.php");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error_message'] = "Please enter a valid email address.";
    redirect("/lilian-online-store/checkout.php");
}

$allowedPaymentMethods = ['COD', 'GCash', 'GoTyme'];
if (!in_array($paymentMethod, $allowedPaymentMethods, true)) {
    $_SESSION['error_message'] = "Invalid payment method selected.";
    redirect("/lilian-online-store/checkout.php");
}

$cartItems = getUserCartItems($conn, $userId);

if (empty($cartItems)) {
    $_SESSION['just_ordered'] = true;
    redirect("/lilian-online-store/cart.php");
}

$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += (float)$item['item_subtotal'];
}

if ($shippingFee < 0) {
    $shippingFee = 0;
}

if ($voucherCode !== '') {
    $voucherStmt = $conn->prepare("
        SELECT
            v.id,
            v.code,
            v.discount_type,
            v.discount_value,
            v.min_order_amount,
            uv.id AS user_voucher_id,
            uv.is_used
        FROM vouchers v
        INNER JOIN user_vouchers uv ON v.id = uv.voucher_id
        WHERE v.code = ?
          AND v.is_active = 1
          AND uv.user_id = ?
          AND uv.is_used = 0
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

            $appliedVoucherId = (int)$voucher['id'];
        } else {
            $voucherDiscount = 0.00;
            $voucherCode = '';
        }
    } else {
        $voucherDiscount = 0.00;
        $voucherCode = '';
    }
}

$totalAmount = $subtotal + $shippingFee - $voucherDiscount;

$paymentProofPath = null;

if ($paymentMethod === 'GCash' || $paymentMethod === 'GoTyme') {
    $targetFolder = dirname(__DIR__) . "/assets/uploads/payment_proofs";
    [$success, $uploadedPath, $error] = uploadPaymentProof($_FILES['payment_proof'] ?? null, $targetFolder);

    if (!$success) {
        $_SESSION['error_message'] = $error;
        redirect("/lilian-online-store/checkout.php");
    }

    $paymentProofPath = $uploadedPath;
}

$conn->begin_transaction();

try {
    $orderNumber = generateOrderNumber();

    $status = ($paymentMethod === 'COD') ? 'to ship' : 'to pay';

    $orderStmt = $conn->prepare("
        INSERT INTO orders (
            user_id, order_number, full_name, email, phone, address,
            payment_method, payment_proof, subtotal, shipping_fee,
            discount, total_amount, voucher_code, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $orderStmt->bind_param(
        "isssssssddddss",
        $userId,
        $orderNumber,
        $fullName,
        $email,
        $phone,
        $address,
        $paymentMethod,
        $paymentProofPath,
        $subtotal,
        $shippingFee,
        $voucherDiscount,
        $totalAmount,
        $voucherCode,
        $status
    );
    $orderStmt->execute();

    $orderId = $orderStmt->insert_id;

    foreach ($cartItems as $item) {
        $itemStmt = $conn->prepare("
            INSERT INTO order_items (
                order_id, product_id, product_name, product_price,
                product_image, quantity, item_total
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $itemStmt->bind_param(
            "iisdsid",
            $orderId,
            $item['product_id'],
            $item['name'],
            $item['price'],
            $item['image'],
            $item['quantity'],
            $item['item_subtotal']
        );
        $itemStmt->execute();
    }

    if ($voucherCode !== '') {
        $voucherFindStmt = $conn->prepare("SELECT id FROM vouchers WHERE code = ? LIMIT 1");
        $voucherFindStmt->bind_param("s", $voucherCode);
        $voucherFindStmt->execute();
        $voucherResult = $voucherFindStmt->get_result();

        if ($voucherResult->num_rows > 0) {
            $voucher = $voucherResult->fetch_assoc();

            $claimStmt = $conn->prepare("
                SELECT id
                FROM user_vouchers
                WHERE user_id = ? AND voucher_id = ? AND is_used = 0
                LIMIT 1
            ");
            $claimStmt->bind_param("ii", $userId, $voucher['id']);
            $claimStmt->execute();
            $claimResult = $claimStmt->get_result();

            if ($claimResult->num_rows > 0) {
                $claimed = $claimResult->fetch_assoc();
                $useStmt = $conn->prepare("UPDATE user_vouchers SET is_used = 1 WHERE id = ?");
                $useStmt->bind_param("i", $claimed['id']);
                $useStmt->execute();
            }
        }
    }

    $clearCartStmt = $conn->prepare("
        DELETE ci
        FROM cart_items ci
        INNER JOIN carts c ON ci.cart_id = c.id
        WHERE c.user_id = ?
    ");
    $clearCartStmt->bind_param("i", $userId);
    $clearCartStmt->execute();

    addNotification(
        $conn,
        'new_order',
        "A new order has been placed by {$fullName}. Order #{$orderNumber}.",
        $orderId
    );

    $conn->commit();

    $_SESSION['success_message'] = "Order placed successfully. Your order number is {$orderNumber}.";
    redirect("/lilian-online-store/orders.php");
} catch (Throwable $e) {
    $conn->rollback();
    $_SESSION['error_message'] = "Failed to place order. Please try again.";
    redirect("/lilian-online-store/checkout.php");
}
?>