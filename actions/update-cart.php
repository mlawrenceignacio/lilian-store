<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/functions.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect("/lilian-online-store/cart.php");
}

if (!isLoggedIn() || isAdmin()) {
    $_SESSION['error_message'] = "Please login as a customer first.";
    redirect("/lilian-online-store/auth/login.php");
}

$cartItemId = isset($_POST['cart_item_id']) ? (int) $_POST['cart_item_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 1;
$userId = (int) $_SESSION['user_id'];

if ($cartItemId <= 0) {
    $_SESSION['error_message'] = "Invalid cart item.";
    redirect("/lilian-online-store/cart.php");
}

if ($quantity < 1) {
    $quantity = 1;
}

if ($quantity > 99) {
    $quantity = 99;
}

$stmt = $conn->prepare("
    SELECT ci.id
    FROM cart_items ci
    INNER JOIN carts c ON ci.cart_id = c.id
    WHERE ci.id = ? AND c.user_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $cartItemId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Cart item not found.";
    redirect("/lilian-online-store/cart.php");
}

$updateStmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
$updateStmt->bind_param("ii", $quantity, $cartItemId);
$updateStmt->execute();

$_SESSION['success_message'] = "Cart quantity updated successfully.";
redirect("/lilian-online-store/cart.php");
?>