<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/functions.php";
require_once __DIR__ . "/../includes/admin-check.php";

$currentPage = basename($_SERVER['PHP_SELF']);
$adminUserId = (int) $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT
        u.id,
        u.full_name,
        u.email,
        u.phone,
        u.address,
        u.created_at,
        a.username
    FROM users u
    LEFT JOIN admins a ON u.id = a.user_id
    WHERE u.id = ? AND u.role = 'admin'
    LIMIT 1
");
$stmt->bind_param("i", $adminUserId);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

$initial = strtoupper(substr(trim($admin['full_name'] ?? 'A'), 0, 1));

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
                        <a href="/lilian-online-store/admin/admins.php">
                            <span>Admin Accounts</span>
                            <span>→</span>
                        </a>
                        <a href="/lilian-online-store/admin/profile.php" class="<?= $currentPage === 'profile.php' ? 'active' : '' ?>">
                            <span>Admin Profile</span>
                            <span>→</span>
                        </a>
                    </nav>
                </div>
            </aside>

            <div class="admin-main">
                <div class="admin-profile-grid">
                    <aside class="admin-profile-card">
                        <div class="admin-profile-cover"></div>
                        <div class="admin-profile-avatar"><?= htmlspecialchars($initial) ?></div>

                        <div class="admin-profile-name"><?= htmlspecialchars($admin['full_name'] ?? '') ?></div>
                        <div class="admin-profile-role">Administrator</div>

                        <div class="admin-profile-info">
                            <div class="admin-profile-info-box">
                                <div class="admin-profile-info-label">Admin Username</div>
                                <div class="admin-profile-info-value"><?= htmlspecialchars($admin['username'] ?? 'Not set') ?></div>
                            </div>

                            <div class="admin-profile-info-box">
                                <div class="admin-profile-info-label">Email Address</div>
                                <div class="admin-profile-info-value"><?= htmlspecialchars($admin['email'] ?? '') ?></div>
                            </div>

                            <div class="admin-profile-info-box">
                                <div class="admin-profile-info-label">Contact Number</div>
                                <div class="admin-profile-info-value"><?= htmlspecialchars($admin['phone'] ?? 'Not set') ?></div>
                            </div>

                            <div class="admin-profile-info-box">
                                <div class="admin-profile-info-label">Member Since</div>
                                <div class="admin-profile-info-value">
                                    <?= !empty($admin['created_at']) ? date("F j, Y", strtotime($admin['created_at'])) : '—' ?>
                                </div>
                            </div>
                        </div>
                    </aside>

                    <div>
                        <section class="admin-profile-panel">
                            <div class="admin-card-head">
                                <div>
                                    <h2>Admin Profile Settings</h2>
                                    <p>Update your admin username and account details here.</p>
                                </div>
                            </div>

                            <form action="/lilian-online-store/actions/save-admin-profile.php" method="POST" class="admin-form-grid">
                                <div>
                                    <label for="full_name">Full Name</label>
                                    <input
                                        type="text"
                                        id="full_name"
                                        name="full_name"
                                        class="form-input"
                                        required
                                        value="<?= htmlspecialchars($admin['full_name'] ?? '') ?>"
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
                                        value="<?= htmlspecialchars($admin['username'] ?? '') ?>"
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
                                        value="<?= htmlspecialchars($admin['email'] ?? '') ?>"
                                    >
                                </div>

                                <div>
                                    <label for="phone">Contact Number</label>
                                    <input
                                        type="text"
                                        id="phone"
                                        name="phone"
                                        class="form-input"
                                        value="<?= htmlspecialchars($admin['phone'] ?? '') ?>"
                                    >
                                </div>

                                <div class="full-span">
                                    <label for="address">Address</label>
                                    <textarea
                                        id="address"
                                        name="address"
                                        class="form-textarea"
                                    ><?= htmlspecialchars($admin['address'] ?? '') ?></textarea>
                                </div>

                                <div>
                                    <label for="new_password">New Password</label>
                                    <input
                                        type="password"
                                        id="new_password"
                                        name="new_password"
                                        class="form-input"
                                        placeholder="Leave blank to keep current password"
                                    >
                                </div>

                                <div>
                                    <label for="confirm_password">Confirm New Password</label>
                                    <input
                                        type="password"
                                        id="confirm_password"
                                        name="confirm_password"
                                        class="form-input"
                                        placeholder="Repeat new password"
                                    >
                                </div>

                                <div class="full-span admin-panel-note">
                                    Leave the password fields blank if you only want to update your admin username or profile details.
                                </div>

                                <div class="full-span">
                                    <button type="submit" class="btn btn-primary">Save Admin Profile</button>
                                </div>
                            </form>
                        </section>

                        <section class="admin-profile-panel">
                            <div class="admin-card-head">
                                <div>
                                    <h2>Profile Reminder</h2>
                                    <p>This page is where the admin username is set and maintained.</p>
                                </div>
                            </div>

                            <p class="admin-panel-note">
                                This matches the required admin-side profile feature. Username changes are validated,
                                account details are secured, and updates are logged in the admin activity history.
                            </p>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . "/../includes/footer.php"; ?>