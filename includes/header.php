<?php
require_once __DIR__ . "/session.php";
require_once __DIR__ . "/functions.php";

$currentPage = basename($_SERVER['PHP_SELF']);
$loggedIn = isLoggedIn();
$isAdminUser = isAdmin();
$cartCount = 0;

if ($loggedIn && !$isAdminUser) {
    $cartCount = getCartCount($conn, $_SESSION['user_id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Lilian Sari-Sari Store</title>
    <link rel="stylesheet" href="/lilian-online-store/assets/css/style.css" />
    <link rel="stylesheet" href="/lilian-online-store/assets/css/responsive.css" />
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a href="/lilian-online-store/index.php" class="brand">
            <img src="/lilian-online-store/assets/images/logo.png" alt="Lilian Sari-Sari Store Logo" class="brand-logo" />
            <div class="brand-text">
                <span class="brand-title">Lilian Sari-Sari Store</span>
                <span class="brand-subtitle">Daily essentials, made convenient</span>
            </div>
        </a>

        <button class="menu-toggle" id="menuToggle" aria-label="Toggle navigation">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <nav class="main-nav" id="mainNav">
            <a href="/lilian-online-store/index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">Home</a>
            <a href="/lilian-online-store/shop.php" class="<?= $currentPage === 'shop.php' ? 'active' : '' ?>">Shop</a>
            <a href="/lilian-online-store/about.php" class="<?= $currentPage === 'about.php' ? 'active' : '' ?>">About</a>

            <?php if ($loggedIn && !$isAdminUser): ?>
                <a href="/lilian-online-store/profile.php" class="<?= $currentPage === 'profile.php' ? 'active' : '' ?>">Profile</a>
                <a href="/lilian-online-store/orders.php" class="<?= $currentPage === 'orders.php' ? 'active' : '' ?>">Orders</a>
                <a href="/lilian-online-store/cart.php" class="cart-link <?= $currentPage === 'cart.php' ? 'active' : '' ?>">
                    <span>Cart</span>
                    <span class="cart-badge"><?= $cartCount ?></span>
                </a>
                <a href="/lilian-online-store/auth/logout.php" class="nav-btn nav-btn-outline">Logout</a>
            <?php elseif ($loggedIn && $isAdminUser): ?>
                <a href="/lilian-online-store/admin/dashboard.php" class="<?= str_contains($_SERVER['PHP_SELF'], '/admin/') ? 'active' : '' ?>">Admin Dashboard</a>
                <a href="/lilian-online-store/auth/logout.php" class="nav-btn nav-btn-outline">Logout</a>
            <?php else: ?>
                <a href="/lilian-online-store/auth/login.php" class="nav-btn nav-btn-solid">Login</a>
                <a href="/lilian-online-store/auth/register.php" class="nav-btn nav-btn-outline">Register</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="page-shell">