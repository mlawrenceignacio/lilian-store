<?php
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/functions.php";

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : 'All';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'default';

$categories = [];
$categoryQuery = "SELECT DISTINCT category FROM products ORDER BY category ASC";
$categoryResult = $conn->query($categoryQuery);

if ($categoryResult && $categoryResult->num_rows > 0) {
    while ($row = $categoryResult->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}

$sql = "SELECT * FROM products WHERE 1=1";
$params = [];
$types = "";

if ($search !== '') {
    $sql .= " AND (name LIKE ? OR description LIKE ? OR category LIKE ?)";
    $searchLike = "%" . $search . "%";
    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;
    $types .= "sss";
}

if ($category !== '' && $category !== 'All') {
    $sql .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}

switch ($sort) {
    case 'name_asc':
        $sql .= " ORDER BY name ASC";
        break;
    case 'name_desc':
        $sql .= " ORDER BY name DESC";
        break;
    case 'price_asc':
        $sql .= " ORDER BY price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY price DESC";
        break;
    case 'rating_desc':
        $sql .= " ORDER BY avg_rating DESC, name ASC";
        break;
    default:
        $sql .= " ORDER BY category ASC, name ASC";
        break;
}

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

include __DIR__ . "/includes/header.php";
?>

<link rel="stylesheet" href="/lilian-online-store/assets/css/shop.css" />

<section class="shop-section">
    <div class="container">
        <?php
renderFlashMessages([
    'success_title' => 'Success',
    'success_heading' => 'Action completed successfully',
    'error_title' => 'Something went wrong',
    'error_heading' => 'We couldn’t complete your request'
]);
?>

        <div class="shop-hero">
            <div class="shop-hero-content">
                <span class="shop-eyebrow">Lilian Sari-Sari Store</span>
                <h1>Shop Daily Essentials</h1>
                <p>
                    Browse snacks, drinks, household items, and everyday needs in one convenient place.
                    Use the filters to quickly find what you need.
                </p>
            </div>
        </div>

        <div class="shop-layout">
            <aside class="shop-sidebar">
                <div class="shop-panel">
                    <div class="panel-head">
                        <h3>Find Products</h3>
                        <p>Search and filter products easily.</p>
                    </div>

                    <form action="/lilian-online-store/shop.php" method="GET" class="shop-filter-form">
                        <div class="form-group">
                            <label for="search">Search</label>
                            <input
                                type="text"
                                id="search"
                                name="search"
                                class="search-input"
                                placeholder="Search by name, description, or category"
                                value="<?= htmlspecialchars($search) ?>"
                            />
                        </div>

                        <div class="form-group">
                            <label for="category">Category</label>
                            <div class="select-wrap">
                                <select name="category" id="category" class="filter-select">
                                    <option value="All" <?= $category === 'All' ? 'selected' : '' ?>>All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="sort">Sort By</label>
                            <div class="select-wrap">
                                <select name="sort" id="sort" class="filter-select">
                                    <option value="default" <?= $sort === 'default' ? 'selected' : '' ?>>Default</option>
                                    <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name: A to Z</option>
                                    <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Name: Z to A</option>
                                    <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                                    <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                                    <option value="rating_desc" <?= $sort === 'rating_desc' ? 'selected' : '' ?>>Top Rated</option>
                                </select>
                            </div>
                        </div>

                        <div class="shop-filter-actions">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                        </div>
                    </form>
                </div>
            </aside>

            <div class="shop-main">
                <div class="shop-topbar">
                    <div class="shop-topbar-left">
                        <div class="shop-current-filter">
                            <span class="meta-label">Category</span>
                            <strong><?= $category === 'All' ? 'All Categories' : htmlspecialchars($category) ?></strong>
                        </div>

                        <div class="shop-current-filter">
                            <span class="meta-label">Sort</span>
                            <strong>
                                <?php
                                switch ($sort) {
                                    case 'name_asc':
                                        echo 'Name: A to Z';
                                        break;
                                    case 'name_desc':
                                        echo 'Name: Z to A';
                                        break;
                                    case 'price_asc':
                                        echo 'Price: Low to High';
                                        break;
                                    case 'price_desc':
                                        echo 'Price: High to Low';
                                        break;
                                    case 'rating_desc':
                                        echo 'Top Rated';
                                        break;
                                    default:
                                        echo 'Default';
                                        break;
                                }
                                ?>
                            </strong>
                        </div>
                    </div>

                    <div class="shop-results">
                        <?= count($products) ?> product<?= count($products) !== 1 ? 's' : '' ?> found
                    </div>
                </div>

                <?php if (!empty($products)): ?>
                    <div class="shop-grid">
                        <?php foreach ($products as $product): ?>
                            <article class="shop-card">
                                <div class="shop-card-media">
                                    <img
                                        src="/lilian-online-store/<?= htmlspecialchars($product['image']) ?>"
                                        alt="<?= htmlspecialchars($product['name']) ?>"
                                    />
                                </div>

                                <div class="shop-card-body">
                                    <span class="shop-card-category"><?= htmlspecialchars($product['category']) ?></span>
                                    <h3 class="shop-card-title"><?= htmlspecialchars($product['name']) ?></h3>
                                    <div class="shop-card-rating">★ <?= number_format((float) $product['avg_rating'], 1) ?></div>
                                    <p class="shop-card-desc"><?= htmlspecialchars($product['description']) ?></p>

                                    <div class="shop-card-bottom">
    <div class="shop-card-price-row">
        <div class="shop-card-price"><?= formatPeso($product['price']) ?></div>
    </div>

    <?php if (isLoggedIn() && !isAdmin()): ?>
        <form action="/lilian-online-store/actions/add-to-cart.php" method="POST" class="cart-form">
            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>" />

            <div class="qty-group">
                <label for="qty-<?= (int) $product['id'] ?>" class="sr-only">Quantity</label>
                <span class="qty-label">Qty</span>
                <input
                    type="number"
                    id="qty-<?= (int) $product['id'] ?>"
                    name="quantity"
                    class="qty-input"
                    min="1"
                    max="99"
                    value="1"
                />
            </div>

            <button type="submit" class="btn btn-primary add-cart-btn">Add to Cart</button>
        </form>
    <?php elseif (!isLoggedIn()): ?>
        <form action="/lilian-online-store/auth/login.php" method="GET" class="cart-form">
            <div class="qty-group">
                <span class="qty-label">Qty</span>
                <input
                    type="number"
                    class="qty-input"
                    min="1"
                    max="99"
                    value="1"
                    disabled
                />
            </div>

            <button type="submit" class="btn btn-primary add-cart-btn">Login to Add</button>
        </form>
        <p class="shop-note">Guests can browse products, but login is required for cart and checkout.</p>
    <?php else: ?>
        <div class="shop-admin-note">Admins can manage the store from the admin dashboard.</div>
    <?php endif; ?>
</div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state shop-empty">
                        <h3>No products found</h3>
                        <p>Try changing your search, category, or sorting options.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . "/includes/footer.php"; ?>