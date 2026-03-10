<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/functions.php";

if (isLoggedIn()) {
    if (isAdmin()) {
        redirect("/lilian-online-store/admin/dashboard.php");
    }
    redirect("/lilian-online-store/index.php");
}

$old = $_SESSION['form_old'] ?? [];

include __DIR__ . "/../includes/header.php";
?>

<link rel="stylesheet" href="/lilian-online-store/assets/css/auth.css" />

<section class="auth-page">
    <div class="container">
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($_SESSION['error_message']) ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_SESSION['success_message']) ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <div class="auth-wrap">
            <div class="auth-showcase">
                <span class="auth-badge">Welcome back</span>
                <h1>Login to continue your shopping.</h1>
                <p>
                    Sign in to manage your cart, place orders, track deliveries,
                    and access your customer or admin account in the same website.
                </p>

                <div class="auth-points">
                    <div class="auth-point">
                        <div class="auth-point-icon">A</div>
                        <div>
                            <strong>Customer access</strong>
                            <p>Use your account to shop, check order status, and manage profile details.</p>
                        </div>
                    </div>

                    <div class="auth-point">
                        <div class="auth-point-icon">B</div>
                        <div>
                            <strong>Admin access</strong>
                            <p>Admins are redirected to the dashboard after successful login.</p>
                        </div>
                    </div>

                    <div class="auth-point">
                        <div class="auth-point-icon">C</div>
                        <div>
                            <strong>Protected actions</strong>
                            <p>Features like add to cart, checkout, and placing orders require authentication.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="auth-card">
                <h2>Login</h2>
                <p>Enter your email and password.</p>

                <form action="/lilian-online-store/actions/login-user.php" method="POST" class="auth-form">
                    <div class="form-row">
                        <label for="email">Email Address</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-input"
                            required
                            value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                        />
                    </div>

                    <div class="form-row">
                        <label for="password">Password</label>
                        <div class="password-wrap">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-input"
                                required
                            />
                            <button type="button" class="password-toggle" data-toggle-password="password">Show</button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary auth-submit">Login</button>
                </form>

                <div class="auth-switch">
                    Don’t have an account yet?
                    <a href="/lilian-online-store/auth/register.php">Register here</a>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="/lilian-online-store/assets/js/auth.js"></script>

<?php
unset($_SESSION['form_old']);
include __DIR__ . "/../includes/footer.php";
?>