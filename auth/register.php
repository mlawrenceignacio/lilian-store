<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/functions.php";

if (isLoggedIn()) {
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
                <span class="auth-badge">Create your customer account</span>
                <h1>Shop daily essentials with ease.</h1>
                <p>
                    Register to add products to cart, place orders, track deliveries,
                    use vouchers, and rate completed orders later in your profile.
                </p>

                <div class="auth-points">
                    <div class="auth-point">
                        <div class="auth-point-icon">1</div>
                        <div>
                            <strong>Browse and add to cart</strong>
                            <p>Guests can explore the store, but an account is needed for protected actions.</p>
                        </div>
                    </div>

                    <div class="auth-point">
                        <div class="auth-point-icon">2</div>
                        <div>
                            <strong>Track your orders</strong>
                            <p>Monitor statuses like to pay, to ship, to receive, and delivered.</p>
                        </div>
                    </div>

                    <div class="auth-point">
                        <div class="auth-point-icon">3</div>
                        <div>
                            <strong>Use your account features</strong>
                            <p>Access vouchers, order history, and profile tools in one place.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="auth-card">
                <h2>Register</h2>
                <p>Fill in your details to create a customer account.</p>

                <form action="/lilian-online-store/actions/register-user.php" method="POST" class="auth-form">
                    <div class="form-row">
                        <label for="full_name">Full Name</label>
                        <input
                            type="text"
                            id="full_name"
                            name="full_name"
                            class="form-input"
                            required
                            value="<?= htmlspecialchars($old['full_name'] ?? '') ?>"
                        />
                    </div>

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
                        <label for="phone">Contact Number</label>
                        <input
                            type="text"
                            id="phone"
                            name="phone"
                            class="form-input"
                            required
                            value="<?= htmlspecialchars($old['phone'] ?? '') ?>"
                        />
                    </div>

                    <div class="form-row">
                        <label for="address">Delivery Address</label>
                        <textarea
                            id="address"
                            name="address"
                            class="form-textarea"
                            required
                        ><?= htmlspecialchars($old['address'] ?? '') ?></textarea>
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
                        <div class="form-help">Use at least 8 characters.</div>
                    </div>

                    <div class="form-row">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="password-wrap">
                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                class="form-input"
                                required
                            />
                            <button type="button" class="password-toggle" data-toggle-password="confirm_password">Show</button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary auth-submit">Create Account</button>
                </form>

                <div class="auth-switch">
                    Already have an account?
                    <a href="/lilian-online-store/auth/login.php">Login here</a>
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