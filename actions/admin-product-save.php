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

$productId = isset($_POST['product_id']) && $_POST['product_id'] !== ''
    ? (int) $_POST['product_id']
    : null;

$name = sanitize($_POST['name'] ?? '');
$category = sanitize($_POST['category'] ?? '');
$price = isset($_POST['price']) ? (float) $_POST['price'] : -1;
$description = sanitize($_POST['description'] ?? '');
$image = trim($_POST['image'] ?? '');
$isFeatured = isset($_POST['is_featured']) ? 1 : 0;

if ($name === '' || $category === '' || $description === '' || $image === '') {
    $_SESSION['error_message'] = "Please complete all required product fields.";
    redirect("/lilian-online-store/admin/products.php");
}

if ($price < 0) {
    $_SESSION['error_message'] = "Please enter a valid price.";
    redirect("/lilian-online-store/admin/products.php");
}

if (strpos($image, 'assets/images/') !== 0) {
    $_SESSION['error_message'] = "Image path must start with assets/images/.";
    redirect("/lilian-online-store/admin/products.php");
}

if ($productId !== null) {
    $checkStmt = $conn->prepare("SELECT id, name FROM products WHERE id = ? LIMIT 1");
    $checkStmt->bind_param("i", $productId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows === 0) {
        $_SESSION['error_message'] = "Product not found.";
        redirect("/lilian-online-store/admin/products.php");
    }

    $existing = $checkResult->fetch_assoc();

    $stmt = $conn->prepare("
        UPDATE products
        SET name = ?, category = ?, price = ?, description = ?, image = ?, is_featured = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ssdssii", $name, $category, $price, $description, $image, $isFeatured, $productId);
    $stmt->execute();

    logAdminActivity(
        $conn,
        $adminUserId,
        "Admin updated product #{$productId} ({$existing['name']}) in the product list."
    );

    $_SESSION['success_message'] = "Product updated successfully.";
    redirect("/lilian-online-store/admin/products.php");
}

$idCheck = $conn->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM products");
$nextIdRow = $idCheck->fetch_assoc();
$newId = (int) ($nextIdRow['next_id'] ?? 1);

$stmt = $conn->prepare("
    INSERT INTO products (id, name, category, price, description, image, avg_rating, is_featured)
    VALUES (?, ?, ?, ?, ?, ?, 0.00, ?)
");
$stmt->bind_param("issdssi", $newId, $name, $category, $price, $description, $image, $isFeatured);
$stmt->execute();

logAdminActivity(
    $conn,
    $adminUserId,
    "Admin added a new product in the product list: {$name} (#{$newId})."
);

$_SESSION['success_message'] = "New product added successfully.";
redirect("/lilian-online-store/admin/products.php");
?>