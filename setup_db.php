<?php
/**
 * VitalRed - Automated Database Initializer for Cloud / Render / Local
 * Runs schema and seed data against the connected MySQL instance.
 */

require_once __DIR__ . '/config/db.php';

$message = '';
$status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'initialize') {
    try {
        // Read schema and seed files
        $schema_file = __DIR__ . '/database/schema.sql';
        $seed_file = __DIR__ . '/database/seed.sql';

        if (!file_exists($schema_file) || !file_exists($seed_file)) {
            throw new Exception("Schema or Seed SQL files not found in database directory.");
        }

        $schema_sql = file_get_contents($schema_file);
        $seed_sql = file_get_contents($seed_file);

        // Remove CREATE DATABASE / DROP DATABASE / USE statements for managed cloud DB compatibility
        $schema_sql = preg_replace('/DROP\s+DATABASE\s+IF\s+EXISTS\s+[^;]+;/i', '', $schema_sql);
        $schema_sql = preg_replace('/CREATE\s+DATABASE\s+[^;]+;/i', '', $schema_sql);
        $schema_sql = preg_replace('/USE\s+[^;]+;/i', '', $schema_sql);

        $seed_sql = preg_replace('/USE\s+[^;]+;/i', '', $seed_sql);

        // Disable foreign key checks during import
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

        // Execute Schema
        $pdo->exec($schema_sql);

        // Execute Seed Data
        $pdo->exec($seed_sql);

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

        $status = 'success';
        $message = 'VitalRed database schema and seed data imported successfully! You can now log in.';
    } catch (Exception $e) {
        $status = 'danger';
        $message = 'Initialization failed: ' . $e->getMessage();
    }
}

// Check existing tables count
$table_count = 0;
try {
    $stmt = $pdo->query("SHOW TABLES");
    $table_count = count($stmt->fetchAll());
} catch (Exception $e) {
    // Database might be completely new
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - VitalRed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; font-family: system-ui, -apple-system, sans-serif; }
        .setup-card { max-width: 600px; margin: 60px auto; border-radius: 16px; border: 1px solid #e2e8f0; }
        .brand-icon { width: 50px; height: 50px; background: #dc2626; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: #fff; font-size: 1.4rem; }
    </style>
</head>
<body>
<div class="container">
    <div class="card setup-card shadow-sm p-4 p-md-5 bg-white">
        <div class="text-center mb-4">
            <div class="brand-icon mb-3">
                <i class="fa-solid fa-droplet"></i>
            </div>
            <h3 class="fw-bold text-dark">VitalRed Database Setup</h3>
            <p class="text-muted small">Initialize or restore 3NF relational tables and test seed data for Cloud / Render deployment.</p>
        </div>

        <div class="alert alert-light border mb-4">
            <div class="d-flex justify-content-between mb-1">
                <span class="text-muted small">Connected DB Host:</span>
                <span class="font-monospace fw-bold small"><?= htmlspecialchars(DB_HOST) ?>:<?= htmlspecialchars(DB_PORT) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-1">
                <span class="text-muted small">Database Name:</span>
                <span class="font-monospace fw-bold small text-danger"><?= htmlspecialchars(DB_NAME) ?></span>
            </div>
            <div class="d-flex justify-content-between">
                <span class="text-muted small">Current Tables in DB:</span>
                <span class="badge bg-secondary"><?= $table_count ?> table(s)</span>
            </div>
        </div>

        <?php if ($status === 'success'): ?>
            <div class="alert alert-success d-flex align-items-center mb-4">
                <i class="fa-solid fa-circle-check fs-4 me-3 text-success"></i>
                <div>
                    <strong>Success!</strong> <?= htmlspecialchars($message) ?>
                </div>
            </div>
            <div class="text-center">
                <a href="login.php" class="btn btn-danger w-100 py-2 fw-semibold">
                    <i class="fa-solid fa-right-to-bracket me-2"></i> Go to VitalRed Login
                </a>
            </div>
        <?php else: ?>
            <?php if ($status === 'danger'): ?>
                <div class="alert alert-danger mb-4">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="action" value="initialize">
                <button type="submit" class="btn btn-danger w-100 py-2 fw-semibold mb-3">
                    <i class="fa-solid fa-database me-2"></i> Initialize Tables &amp; Seed Data
                </button>
            </form>
            <div class="text-center">
                <a href="index.php" class="text-muted small text-decoration-none">&larr; Back to Home</a>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
