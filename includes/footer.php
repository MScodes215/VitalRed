<?php
/**
 * VitalRed - Common Footer
 */
?>
</main>

<footer>
    <div class="container">
        <div class="row gy-4 align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <div class="d-flex align-items-center gap-2 justify-content-center justify-content-md-start mb-2">
                    <span class="brand-icon-drop" style="width:26px;height:26px;"><i class="fa-solid fa-droplet" style="font-size:0.75rem;"></i></span>
                    <strong class="text-dark fs-6">VitalRed</strong>
                    <span class="badge bg-success-subtle text-success small">Certified Transfusion Network</span>
                </div>
                <p class="small text-muted mb-0">
                    National Blood Transfusion Network &amp; Inventory Management System.<br>
                    Connecting voluntary blood donors, accredited hospital networks, and certified transfusion centers 24/7.
                </p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <div class="d-flex gap-3 justify-content-center justify-content-md-end mb-2">
                    <a href="<?= BASE_URL ?>index.php" class="text-decoration-none text-muted small">Home</a>
                    <a href="<?= BASE_URL ?>index.php#stock-section" class="text-decoration-none text-muted small">Blood Stock</a>
                    <a href="<?= BASE_URL ?>index.php#compatibility" class="text-decoration-none text-muted small">Compatibility Guide</a>
                    <a href="<?= BASE_URL ?>login.php" class="text-decoration-none text-muted small">Sign In</a>
                    <a href="<?= BASE_URL ?>register.php" class="text-decoration-none text-muted small">Register</a>
                </div>
                <p class="small text-muted mb-0">
                    &copy; <?= date('Y') ?> VitalRed Healthcare Network. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5.3 Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- VitalRed Application Scripts -->
<script src="<?= BASE_URL ?>assets/js/main.js"></script>
<script src="<?= BASE_URL ?>assets/js/google-auth.js"></script>

<!-- Google Identity Services (GSI) Client SDK -->
<script src="https://accounts.google.com/gsi/client" async defer></script>

</body>
</html>
