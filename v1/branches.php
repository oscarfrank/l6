<?php
// FR2 branch list. Prepared statement even with no user input, so every
// query still goes through PDO the same way.

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = get_pdo();

$stmt = $pdo->prepare(
    'SELECT id, name, city, address, phone, email, opening_hours
     FROM branches
     ORDER BY CASE WHEN name LIKE :hq THEN 0 ELSE 1 END, city ASC'
);
$stmt->execute(['hq' => '%Headquarters%']);
$branches = $stmt->fetchAll();

$page_title = 'Our branches';
require __DIR__ . '/includes/header.php';
?>

<div class="page-intro">
    <h1>Our branches</h1>
    <p class="lede">Four high-street shops and a London headquarters. Call in, or use the details below to reach the team that covers your region.</p>
</div>

<div class="card-grid two-col">
    <?php foreach ($branches as $branch): ?>
        <article class="card branch-card">
            <div class="card-body">
                <h2><?php echo escape($branch['name']); ?></h2>
                <p class="meta"><?php echo escape($branch['city']); ?></p>
                <p><?php echo escape($branch['address']); ?></p>
                <p>
                    <a href="tel:<?php echo escape(preg_replace('/\s+/', '', (string) $branch['phone'])); ?>">
                        <?php echo escape($branch['phone']); ?>
                    </a>
                </p>
                <p>
                    <a href="mailto:<?php echo escape($branch['email']); ?>">
                        <?php echo escape($branch['email']); ?>
                    </a>
                </p>
                <p class="meta"><?php echo escape($branch['opening_hours']); ?></p>
            </div>
        </article>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
