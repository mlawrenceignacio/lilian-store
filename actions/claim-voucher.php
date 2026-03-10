<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/functions.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect("/lilian-online-store/vouchers.php");
}

if (!isLoggedIn() || isAdmin()) {
    $_SESSION['error_message'] = "Please login as a customer first.";
    redirect("/lilian-online-store/auth/login.php");
}

$userId = (int) $_SESSION['user_id'];
$voucherId = isset($_POST['voucher_id']) ? (int) $_POST['voucher_id'] : 0;

if ($voucherId <= 0) {
    $_SESSION['error_message'] = "Invalid voucher selected.";
    redirect("/lilian-online-store/vouchers.php");
}

$voucherStmt = $conn->prepare("
    SELECT id, code, title, is_active
    FROM vouchers
    WHERE id = ?
    LIMIT 1
");
$voucherStmt->bind_param("i", $voucherId);
$voucherStmt->execute();
$voucherResult = $voucherStmt->get_result();

if ($voucherResult->num_rows === 0) {
    $_SESSION['error_message'] = "Voucher not found.";
    redirect("/lilian-online-store/vouchers.php");
}

$voucher = $voucherResult->fetch_assoc();

if ((int)$voucher['is_active'] !== 1) {
    $_SESSION['error_message'] = "This voucher is not active.";
    redirect("/lilian-online-store/vouchers.php");
}

$checkStmt = $conn->prepare("
    SELECT id, is_used
    FROM user_vouchers
    WHERE user_id = ? AND voucher_id = ?
    LIMIT 1
");
$checkStmt->bind_param("ii", $userId, $voucherId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    $existing = $checkResult->fetch_assoc();

    if ((int)$existing['is_used'] === 1) {
        $_SESSION['error_message'] = "You already claimed and used this voucher.";
    } else {
        $_SESSION['error_message'] = "You already claimed this voucher.";
    }

    redirect("/lilian-online-store/vouchers.php");
}

$insertStmt = $conn->prepare("
    INSERT INTO user_vouchers (user_id, voucher_id, is_used)
    VALUES (?, ?, 0)
");
$insertStmt->bind_param("ii", $userId, $voucherId);
$insertStmt->execute();

$_SESSION['success_message'] = "Voucher claimed successfully.";
redirect("/lilian-online-store/vouchers.php");
?>