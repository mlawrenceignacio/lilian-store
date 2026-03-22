<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/functions.php";
require_once __DIR__ . "/../includes/admin-check.php";

$currentPage = basename($_SERVER['PHP_SELF']);
$search = trim($_GET['search'] ?? '');
$categoryFilter = trim($_GET['category'] ?? 'all');

$categories = [];
$categoryResult = $conn->query("SELECT DISTINCT category FROM products ORDER BY category ASC");
if ($categoryResult) {
    while ($row = $categoryResult->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}

$sql = "SELECT * FROM products WHERE 1=1";
$params = [];
$types = "";

if ($search !== '') {
    $sql .= " AND (name LIKE ? OR category LIKE ? OR description LIKE ? OR image LIKE ?)";
    $like = "%" . $search . "%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "ssss";
}

if ($categoryFilter !== 'all') {
    $sql .= " AND category = ?";
    $params[] = $categoryFilter;
    $types .= "s";
}

$sql .= " ORDER BY category ASC, name ASC";

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

$totalProducts = $conn->query("SELECT COUNT(*) AS total FROM products")->fetch_assoc()['total'] ?? 0;

include __DIR__ . "/../includes/header.php";
?>

<link rel="stylesheet" href="/lilian-online-store/assets/css/auth.css" />
<link rel="stylesheet" href="/lilian-online-store/assets/css/admin.css" />

<section class="admin-section">
    <div class="container">
        <?php
renderFlashMessages([
    'success_title' => 'Success',
    'success_heading' => 'Admin action completed successfully',
    'error_title' => 'Something went wrong',
    'error_heading' => 'We couldn’t complete the admin request'
]);
?>

        <div class="admin-layout">
            <aside class="admin-sidebar">
                <div class="admin-sidebar-card">
                    <div class="admin-sidebar-top">
                        <h2>Admin Panel</h2>
                        <p>Manage orders, products, notifications, and store activity.</p>
                    </div>

                    <nav class="admin-nav">
                        <a href="/lilian-online-store/admin/dashboard.php">
                            <span>Dashboard</span>
                            <span>→</span>
                        </a>
                        <a href="/lilian-online-store/admin/notifications.php">
                            <span>Notifications</span>
                            <span>→</span>
                        </a>
                        <a href="/lilian-online-store/admin/orders.php">
                            <span>Orders</span>
                            <span>→</span>
                        </a>
                        <a href="/lilian-online-store/admin/products.php" class="<?= $currentPage === 'products.php' ? 'active' : '' ?>">
                            <span>Products</span>
                            <span>→</span>
                        </a>
                        <a href="/lilian-online-store/admin/activity-log.php">
                            <span>Activity Log</span>
                            <span>→</span>
                        </a>
                        <a href="/lilian-online-store/admin/admins.php">
                            <span>Admin Accounts</span>
                            <span>→</span>
                        </a>
                        <a href="/lilian-online-store/admin/profile.php">
                            <span>Admin Profile</span>
                            <span>→</span>
                        </a>
                    </nav>
                </div>
            </aside>

            <div class="admin-main">
                <section class="admin-main-card">
                    <div class="admin-main-head">
                        <div>
                            <h1>Products Management</h1>
                            <p>Update existing products, add new ones, and remove products except user ratings.</p>
                        </div>
                        <div class="admin-date-chip"><?= (int)$totalProducts ?> products</div>
                    </div>
                </section>

                <section class="admin-main-card admin-toolbar">
                    <div class="admin-card-head">
                        <div>
                            <h2>Search and Filter Products</h2>
                            <p>Find products by name, category, description, or image path.</p>
                        </div>
                    </div>

                    <form action="/lilian-online-store/admin/products.php" method="GET" class="admin-toolbar-grid">
    <div class="admin-filter-field">
        <label for="search">Search</label>
        <input
            type="text"
            id="search"
            name="search"
            class="form-input"
            placeholder="Search products"
            value="<?= htmlspecialchars($search) ?>"
        >
    </div>

    <div>
        <label for="category">Category</label>
        <select id="category" name="category" class="filter-select">
            <option value="all">All Categories</option>
            <?php foreach ($categories as $category): ?>
                <option
                    value="<?= htmlspecialchars($category) ?>"
                    <?= $categoryFilter === $category ? 'selected' : '' ?>
                >
                    <?= htmlspecialchars($category) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="admin-inline-row admin-filter-actions">
        <button type="submit" class="btn btn-primary">Apply</button>
        <a href="/lilian-online-store/admin/products.php" class="btn btn-secondary">Reset</a>
    </div>
</form>
                </section>

                <section class="admin-add-product-card">
                    <div class="admin-card-head">
                        <div>
                            <h2>Add New Product</h2>
                            <p>Add a new item to the product list. Rating stays system-generated, not admin-edited.</p>
                        </div>
                    </div>

                    <form action="/lilian-online-store/actions/admin-product-save.php" method="POST" class="admin-form-grid">
                        <div>
                            <label for="new_name">Product Name</label>
                            <input type="text" id="new_name" name="name" class="form-input" required>
                        </div>

                        <div>
                            <label for="new_category">Category</label>
                            <input type="text" id="new_category" name="category" class="form-input" required>
                        </div>

                        <div>
                            <label for="new_price">Price</label>
                            <input type="number" id="new_price" name="price" step="0.01" min="0" class="form-input" required>
                        </div>

                        <div>
                            <label for="new_image">Image Path</label>
                            <input
                                type="text"
                                id="new_image"
                                name="image"
                                class="form-input"
                                placeholder="assets/images/example.jpg"
                                required
                            >
                        </div>

                        <div class="full-span">
                            <label for="new_description">Description</label>
                            <textarea id="new_description" name="description" class="form-textarea" required></textarea>
                        </div>

                        <div class="full-span admin-inline-row">
                            <label style="font-weight:700;">
                                <input type="checkbox" name="is_featured" value="1">
                                Mark as featured product
                            </label>
                        </div>

                        <div class="full-span">
                            <button type="submit" class="btn btn-primary">Add Product</button>
                        </div>
                    </form>
                </section>

                <section class="admin-main-card">
                    <div class="admin-card-head">
                        <div>
                            <h2>Existing Products</h2>
                            <p>Update price, name, image path, description, and featured state. Rating is view-only.</p>
                        </div>
                    </div>

                    <?php if (!empty($products)): ?>
                        <div class="admin-products-grid">
                            <?php foreach ($products as $product): ?>
                                <article class="admin-product-manage-card">
                                    <div class="admin-product-manage-head">
                                        <img
                                            src="/lilian-online-store/<?= htmlspecialchars($product['image']) ?>"
                                            alt="<?= htmlspecialchars($product['name']) ?>"
                                        >

                                        <div>
                                            <div class="admin-product-manage-title"><?= htmlspecialchars($product['name']) ?></div>
                                            <div class="admin-product-manage-meta">
                                                ID: <?= (int)$product['id'] ?> •
                                                Category: <?= htmlspecialchars($product['category']) ?> •
                                                Featured: <?= (int)$product['is_featured'] === 1 ? 'Yes' : 'No' ?>
                                            </div>
                                        </div>

                                        <div class="admin-rating-readonly">
                                            Rating<br>
                                            ★ <?= number_format((float)$product['avg_rating'], 1) ?>
                                        </div>
                                    </div>

                                    <div class="admin-product-manage-body">
                                        <form action="/lilian-online-store/actions/admin-product-save.php" method="POST" class="admin-form-grid">
                                            <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">

                                            <div>
                                                <label for="name-<?= (int)$product['id'] ?>">Product Name</label>
                                                <input
                                                    type="text"
                                                    id="name-<?= (int)$product['id'] ?>"
                                                    name="name"
                                                    class="form-input"
                                                    required
                                                    value="<?= htmlspecialchars($product['name']) ?>"
                                                >
                                            </div>

                                            <div>
                                                <label for="category-<?= (int)$product['id'] ?>">Category</label>
                                                <input
                                                    type="text"
                                                    id="category-<?= (int)$product['id'] ?>"
                                                    name="category"
                                                    class="form-input"
                                                    required
                                                    value="<?= htmlspecialchars($product['category']) ?>"
                                                >
                                            </div>

                                            <div>
                                                <label for="price-<?= (int)$product['id'] ?>">Price</label>
                                                <input
                                                    type="number"
                                                    id="price-<?= (int)$product['id'] ?>"
                                                    name="price"
                                                    step="0.01"
                                                    min="0"
                                                    class="form-input"
                                                    required
                                                    value="<?= htmlspecialchars(number_format((float)$product['price'], 2, '.', '')) ?>"
                                                >
                                            </div>

                                            <div>
                                                <label for="image-<?= (int)$product['id'] ?>">Image Path</label>
                                                <input
                                                    type="text"
                                                    id="image-<?= (int)$product['id'] ?>"
                                                    name="image"
                                                    class="form-input"
                                                    required
                                                    value="<?= htmlspecialchars($product['image']) ?>"
                                                >
                                            </div>

                                            <div class="full-span">
                                                <label for="description-<?= (int)$product['id'] ?>">Description</label>
                                                <textarea
                                                    id="description-<?= (int)$product['id'] ?>"
                                                    name="description"
                                                    class="form-textarea"
                                                    required
                                                ><?= htmlspecialchars($product['description']) ?></textarea>
                                            </div>

                                            <div class="full-span admin-inline-row">
                                                <label style="font-weight:700;">
                                                    <input
                                                        type="checkbox"
                                                        name="is_featured"
                                                        value="1"
                                                        <?= (int)$product['is_featured'] === 1 ? 'checked' : '' ?>
                                                    >
                                                    Featured Product
                                                </label>
                                            </div>

                                            <div class="full-span admin-product-form-actions">
                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                        </form>

                                                <form action="/lilian-online-store/actions/admin-product-delete.php" method="POST" onsubmit="return confirm('Remove this product?');">
                                                    <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                                                    <button type="submit" class="btn btn-danger-outline">Remove Product</button>
                                                </form>
                                            </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="admin-empty">
                            <h3>No products found</h3>
                            <p>No products match the current search or filter. Add a new product or reset the filters.</p>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . "/../includes/footer.php"; ?>