<?php
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/functions.php";

$featuredProducts = [];
$featuredQuery = "SELECT * FROM products WHERE is_featured = 1 ORDER BY id ASC LIMIT 6";
$featuredResult = $conn->query($featuredQuery);

if ($featuredResult && $featuredResult->num_rows > 0) {
    while ($row = $featuredResult->fetch_assoc()) {
        $featuredProducts[] = $row;
    }
}

include __DIR__ . "/includes/header.php";
?>

<section class="hero-section">
    <div class="container">
        <div class="hero-wrap">
            <div class="hero-content">
                <span class="eyebrow">Your neighborhood essentials store</span>
                <h1>Welcome to Lilian Sari-Sari Store - Your one-stop shop for daily essentials!</h1>
                <p>
                    Browse household favorites, snacks, drinks, and everyday needs in one convenient place.
                    Friendly service and practical shopping, now made easier online.
                </p>
                <div class="hero-actions">
                    <a href="/lilian-online-store/shop.php" class="btn btn-primary">Shop Now</a>
                    <a href="/lilian-online-store/about.php" class="btn btn-secondary">Learn More</a>
                </div>
            </div>

            <div class="hero-visual">
                <div class="hero-card">
                    <img src="/lilian-online-store/assets/images/hero-storyset.png" alt="Store hero illustration" />
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <h2>Featured Products</h2>
            <p>Chosen from different product categories to give customers a balanced first look at the store.</p>
        </div>

        <?php if (!empty($featuredProducts)): ?>
            <div class="product-grid">
                <?php foreach ($featuredProducts as $product): ?>
                    <article class="product-card">
                        <div class="product-image-wrap">
                            <img
                                src="/lilian-online-store/<?= htmlspecialchars($product['image']) ?>"
                                alt="<?= htmlspecialchars($product['name']) ?>"
                                class="product-image"
                            />
                        </div>

                        <div class="product-body">
                            <span class="product-category"><?= htmlspecialchars($product['category']) ?></span>
                            <h3 class="product-title"><?= htmlspecialchars($product['name']) ?></h3>
                            <div class="product-rating">★ <?= number_format((float)$product['avg_rating'], 1) ?></div>
                            <p class="product-desc"><?= htmlspecialchars($product['description']) ?></p>

                            <div class="product-bottom">
                                <span class="product-price"><?= formatPeso($product['price']) ?></span>
                                <a href="/lilian-online-store/shop.php" class="product-view-btn">View in Shop</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h3>No featured products yet</h3>
                <p>Please add products first so the featured section can appear here.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <h2>Why Shop With Us?</h2>
            <p>Simple, practical, and community-focused shopping for daily needs.</p>
        </div>

        <div class="why-grid">
            <article class="info-card">
                <div class="info-image-wrap">
                    <img src="/lilian-online-store/assets/icons/package.png" alt="Quality products" class="info-image" />
                </div>
                <div class="info-content">
                    <h3>Quality Products</h3>
                    <p>We provide carefully selected items for your home, family, and everyday needs.</p>
                </div>
            </article>

            <article class="info-card">
                <div class="info-image-wrap">
                    <img src="/lilian-online-store/assets/icons/delivery-bike.png" alt="Fast delivery" class="info-image" />
                </div>
                <div class="info-content">
                    <h3>Fast Delivery</h3>
                    <p>Get your essentials quickly and conveniently with reliable delivery service.</p>
                </div>
            </article>

            <article class="info-card">
                <div class="info-image-wrap">
                    <img src="/lilian-online-store/assets/icons/friends.png" alt="Friendly support" class="info-image" />
                </div>
                <div class="info-content">
                    <h3>Friendly Support</h3>
                    <p>We are always ready to help and make your shopping experience smooth and easy.</p>
                </div>
            </article>
        </div>
    </div>
</section>

<?php include __DIR__ . "/includes/footer.php"; ?>