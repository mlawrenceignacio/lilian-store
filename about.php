<?php
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/functions.php";

include __DIR__ . "/includes/header.php";
?>

<link rel="stylesheet" href="/lilian-online-store/assets/css/about.css" />

<section class="about-section">
    <div class="container">
        <div class="about-hero">
            <div class="about-copy">
                <span class="eyebrow">About our store</span>
                <h1>About Lilian Sari-Sari Store</h1>
                <p>
                    Lilian Sari-Sari Store has been serving the Angono community with quality
                    products and friendly service. We provide daily essentials at affordable prices.
                </p>
            </div>

            <div class="about-visual-card">
                <img
                    src="/lilian-online-store/assets/images/about-hero.png"
                    alt="About Lilian Sari-Sari Store illustration"
                />
            </div>
        </div>

        <div class="about-block">
            <div class="about-card">
                <h2>Meet Our Team</h2>
                <p>Get to know the people behind the store.</p>

                <div class="team-grid">
                    <article class="team-card">
                        <div class="team-image-wrap">
                            <img
                                src="/lilian-online-store/assets/images/owner.png"
                                alt="Lilian Beleza"
                                class="team-image"
                            />
                        </div>
                        <div class="team-body">
                            <h3 class="team-name">Lilian Beleza</h3>
                            <span class="team-role">Store Owner</span>
                        </div>
                    </article>

                    <article class="team-card">
                        <div class="team-image-wrap">
                            <img
                                src="/lilian-online-store/assets/images/clerk.png"
                                alt="Lhycka Beleza"
                                class="team-image"
                            />
                        </div>
                        <div class="team-body">
                            <h3 class="team-name">Lhycka Beleza</h3>
                            <span class="team-role">Store Clerk</span>
                        </div>
                    </article>

                    <article class="team-card">
                        <div class="team-image-wrap">
                            <img
                                src="/lilian-online-store/assets/images/manager.png"
                                alt="Lhynn Beleza"
                                class="team-image"
                            />
                        </div>
                        <div class="team-body">
                            <h3 class="team-name">Lhynn Beleza</h3>
                            <span class="team-role">Store Manager</span>
                        </div>
                    </article>
                </div>
            </div>
        </div>

        <div class="goal-card">
            <h2>Our Goal</h2>
            <p>
                Our goal is to provide quality products and friendly service to the community.
                We strive to make shopping convenient and enjoyable for everyone.
            </p>
        </div>
    </div>
</section>

<?php include __DIR__ . "/includes/footer.php"; ?>