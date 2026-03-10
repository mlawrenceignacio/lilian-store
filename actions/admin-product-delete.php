<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/functions.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect("/lilian-online-store/admin/products.php");
}

if (!isLoggedIn() || !isAdmin()) {
    $_SESSION['error_message'] = "You are not allowed to perform that action.";
    redirect("/lilian-online-store/auth/login.php");
}

$adminUserId = (int) $_SESSION['user_id'];
$productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;

if ($productId <= 0) {
    $_SESSION['error_message'] = "Invalid product selected.";
    redirect("/lilian-online-store/admin/products.php");
}

$checkStmt = $conn->prepare("SELECT id, name FROM products WHERE id = ? LIMIT 1");
$checkStmt->bind_param("i", $productId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows === 0) {
    $_SESSION['error_message'] = "Product not found.";
    redirect("/lilian-online-store/admin/products.php");
}

$product = $checkResult->fetch_assoc();

$deleteStmt = $conn->prepare("DELETE FROM products WHERE id = ?");
$deleteStmt->bind_param("i", $productId);
$deleteStmt->execute();

logAdminActivity(
    $conn,
    $adminUserId,
    "Admin removed product #{$productId} ({$product['name']}) from the product list."
);

$_SESSION['success_message'] = "Product removed successfully.";
redirect("/lilian-online-store/admin/products.php");
?>