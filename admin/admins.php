<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/functions.php";
require_once __DIR__ . "/../includes/admin-check.php";

$currentPage = basename($_SERVER['PHP_SELF']);
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT
        u.id,
        u.full_name,
        u.email,
        u.phone,
        u.address,
        u.created_at,
        a.username
    FROM users u
    INNER JOIN admins a ON u.id = a.user_id
    WHERE u.role = 'admin'
";

$params = [];
$types = "";

if ($search !== '') {
    $sql .= " AND (
        u.full_name LIKE ?
        OR u.email LIKE ?
        OR a.username LIKE ?
        OR u.phone LIKE ?
    )";
    $like = "%" . $search . "%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "ssss";
}

$sql .= " ORDER BY u.created_at DESC, u.id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$admins = [];
while ($row = $result->fetch_assoc()) {
    $admins[] = $row;
}

include __DIR__ . "/../includes/header.php";
?>

<link rel="stylesheet" href="/lilian-online-store/assets/css/auth.css" />
<link rel="stylesheet" href="/lilian-online-store/assets/css/admin.css" />

<section class="admin-section">
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
                        <a href="/lilian-online-store/admin/products.php">
                            <span>Products</span>
                            <span>→</span>
                        </a>
                        <a href="/lilian-online-store/admin/activity-log.php">
                            <span>Activity Log</span>
                            <span>→</span>
                        </a>
                        <a href="/lilian-online-store/admin/admins.php" class="<?= $currentPage === 'admins.php' ? 'active' : '' ?>">
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
                            <h1>Admin Accounts</h1>
                            <p>View existing administrators and register new admin accounts securely from inside the system.</p>
                        </div>
                        <div class="admin-date-chip"><?= count($admins) ?> admin<?= count($admins) !== 1 ? 's' : '' ?></div>
                    </div>
                </section>

                <section class="admin-main-card admin-toolbar">
                    <div class="admin-card-head">
                        <div>
                            <h2>Search Admin Accounts</h2>
                            <p>Search by full name, email, username, or contact number.</p>
                        </div>
                    </div>

                    <form action="/lilian-online-store/admin/admins.php" method="GET" class="admin-toolbar-grid" style="grid-template-columns: 1fr auto;">
                        <div>
                            <label for="search">Search</label>
                            <input
                                type="text"
                                id="search"
                                name="search"
                                class="form-input"
                                placeholder="Search admin accounts"
                                value="<?= htmlspecialchars($search) ?>"
                            >
                        </div>

                        <div class="admin-inline-row" style="align-self:end;">
                            <button type="submit" class="btn btn-primary">Apply</button>
                            <a href="/lilian-online-store/admin/admins.php" class="btn btn-secondary">Reset</a>
                        </div>
                    </form>
                </section>

                <section class="admin-main-card">
                    <div class="admin-card-head">
                        <div>
                            <h2>Register New Admin</h2>
                            <p>Create a new administrator account from inside the admin panel.</p>
                        </div>
                    </div>

                    <form action="/lilian-online-store/actions/register-admin.php" method="POST" class="admin-form-grid">
                        <div>
                            <label for="full_name">Full Name</label>
                            <input
                                type="text"
                                id="full_name"
                                name="full_name"
                                class="form-input"
                                required
                                value="<?= htmlspecialchars($_SESSION['form_old']['full_name'] ?? '') ?>"
                            >
                        </div>

                        <div>
                            <label for="username">Admin Username</label>
                            <input
                                type="text"
                                id="username"
                                name="username"
                                class="form-input"
                                required
                                value="<?= htmlspecialchars($_SESSION['form_old']['username'] ?? '') ?>"
                            >
                        </div>

                        <div>
                            <label for="email">Email Address</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-input"
                                required
                                value="<?= htmlspecialchars($_SESSION['form_old']['email'] ?? '') ?>"
                            >
                        </div>

                        <div>
                            <label for="phone">Contact Number</label>
                            <input
                                type="text"
                                id="phone"
                                name="phone"
                                class="form-input"
                                value="<?= htmlspecialchars($_SESSION['form_old']['phone'] ?? '') ?>"
                            >
                        </div>

                        <div class="full-span">
                            <label for="address">Address</label>
                            <textarea
                                id="address"
                                name="address"
                                class="form-textarea"
                            ><?= htmlspecialchars($_SESSION['form_old']['address'] ?? '') ?></textarea>
                        </div>

                        <div>
                            <label for="password">Password</label>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-input"
                                required
                                minlength="8"
                                placeholder="Enter temporary password"
                            >
                        </div>

                        <div>
                            <label for="confirm_password">Confirm Password</label>
                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                class="form-input"
                                required
                                minlength="8"
                                placeholder="Repeat password"
                            >
                        </div>

                        <div class="full-span admin-panel-note">
                            New admins are created only from the admin side. Make sure the email and username are unique.
                        </div>

                        <div class="full-span">
                            <button type="submit" class="btn btn-primary">Register Admin</button>
                        </div>
                    </form>
                    <?php unset($_SESSION['form_old']); ?>
                </section>

                <section class="admin-main-card">
                    <div class="admin-card-head">
                        <div>
                            <h2>Accounts List</h2>
                            <p>Use this page to review existing administrators and create new admin accounts when needed.</p>
                        </div>
                    </div>

                    <?php if (!empty($admins)): ?>
                        <div class="admin-accounts-grid">
                            <?php foreach ($admins as $admin): ?>
                                <?php $initial = strtoupper(substr(trim($admin['full_name'] ?: 'A'), 0, 1)); ?>
                                <article class="admin-account-card">
                                    <div class="admin-account-avatar"><?= htmlspecialchars($initial) ?></div>

                                    <div>
                                        <div class="admin-account-title"><?= htmlspecialchars($admin['full_name']) ?></div>
                                        <div class="admin-account-meta">Username: <?= htmlspecialchars($admin['username'] ?: 'Not set') ?></div>
                                        <div class="admin-account-meta">Email: <?= htmlspecialchars($admin['email']) ?></div>
                                        <div class="admin-account-meta">Contact: <?= htmlspecialchars($admin['phone'] ?: 'Not set') ?></div>
                                        <div class="admin-account-meta">Address: <?= htmlspecialchars($admin['address'] ?: 'Not set') ?></div>
                                        <div class="admin-account-role">Administrator</div>
                                    </div>

                                    <div class="admin-account-side">
                                        <div class="admin-account-date">
                                            Added on <?= !empty($admin['created_at']) ? date("F j, Y", strtotime($admin['created_at'])) : '—' ?>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="admin-empty">
                            <h3>No admin accounts match the current search. You can still register a new admin using the form above.</h3>
                            <p>
                                No admin accounts match the current search. This page is for viewing
                                all admin accounts only.
                            </p>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . "/../includes/footer.php"; ?>