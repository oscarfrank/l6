<?php
/*
 * Add or edit one offer (FR5). ?id= means edit; no id means insert.
 * All fields go through prepared statements. Bestsellers show on the home page.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$pdo = get_pdo();

$offerId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit  = $offerId > 0;

$fields = [
    'title'         => '',
    'description'   => '',
    'destination'   => '',
    'price'         => '',
    'image_url'     => '',
    'is_bestseller' => '0',
    'start_date'    => '',
    'end_date'      => '',
];
$errors = [];

if ($isEdit && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $load = $pdo->prepare('SELECT * FROM offers WHERE id = ? LIMIT 1');
    $load->execute([$offerId]);
    $row = $load->fetch();
    if (!$row) {
        redirect(app_url('admin/manage-offers.php'));
    }
    foreach ($fields as $key => $unused) {
        $fields[$key] = (string) ($row[$key] ?? '');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $errors['form'] = 'Your session expired. Please submit the form again.';
    }

    foreach ($fields as $key => $unused) {
        if ($key === 'is_bestseller') {
            $fields[$key] = isset($_POST['is_bestseller']) ? '1' : '0';
        } else {
            $fields[$key] = trim((string) ($_POST[$key] ?? ''));
        }
    }

    if (!is_required($fields['title'])) {
        $errors['title'] = 'Title is required.';
    }
    if (!is_required($fields['destination'])) {
        $errors['destination'] = 'Destination is required.';
    }
    if (!is_required($fields['price']) || !is_non_negative_number($fields['price'])) {
        $errors['price'] = 'Enter a valid price (0 or greater).';
    }
    if ($fields['start_date'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fields['start_date'])) {
        $errors['start_date'] = 'Use a valid start date.';
    }
    if ($fields['end_date'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fields['end_date'])) {
        $errors['end_date'] = 'Use a valid end date.';
    }

    if (!$errors) {
        $params = [
            $fields['title'],
            $fields['description'] !== '' ? $fields['description'] : null,
            $fields['destination'],
            $fields['price'],
            $fields['image_url'] !== '' ? $fields['image_url'] : null,
            (int) $fields['is_bestseller'],
            $fields['start_date'] !== '' ? $fields['start_date'] : null,
            $fields['end_date'] !== '' ? $fields['end_date'] : null,
        ];

        if ($isEdit) {
            $sql = 'UPDATE offers
                    SET title = ?, description = ?, destination = ?, price = ?,
                        image_url = ?, is_bestseller = ?, start_date = ?, end_date = ?
                    WHERE id = ?';
            $params[] = $offerId;
        } else {
            $sql = 'INSERT INTO offers
                    (title, description, destination, price, image_url, is_bestseller, start_date, end_date)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        redirect(app_url('admin/manage-offers.php'));
    }
}

$page_title = $isEdit ? 'Edit offer' : 'Add offer';
$is_admin   = true;
require __DIR__ . '/../includes/header.php';
?>

<div class="page-intro">
    <h1><?php echo $isEdit ? 'Edit offer' : 'Add an offer'; ?></h1>
    <p class="lede">All fields are stored with a prepared statement. Mark bestsellers to feature them on the home page.</p>
</div>

<section class="form-panel">
    <?php if (!empty($errors['form'])): ?>
        <p class="notice notice-error"><?php echo escape($errors['form']); ?></p>
    <?php endif; ?>

    <form method="post" action="" data-validate novalidate>
        <?php echo csrf_field(); ?>
        <div class="form-grid two">
            <div>
                <label for="title">Title</label>
                <input type="text" id="title" name="title" required value="<?php echo escape($fields['title']); ?>">
                <?php if (!empty($errors['title'])): ?><p class="field-error"><?php echo escape($errors['title']); ?></p><?php endif; ?>
            </div>
            <div>
                <label for="destination">Destination</label>
                <input type="text" id="destination" name="destination" required value="<?php echo escape($fields['destination']); ?>">
                <?php if (!empty($errors['destination'])): ?><p class="field-error"><?php echo escape($errors['destination']); ?></p><?php endif; ?>
            </div>
            <div>
                <label for="price">Price (GBP, per person)</label>
                <input type="number" id="price" name="price" min="0" step="0.01" required value="<?php echo escape($fields['price']); ?>">
                <?php if (!empty($errors['price'])): ?><p class="field-error"><?php echo escape($errors['price']); ?></p><?php endif; ?>
            </div>
            <div>
                <label for="image_url">Image URL</label>
                <input type="text" id="image_url" name="image_url" value="<?php echo escape($fields['image_url']); ?>">
                <p class="field-hint">Example: assets/images/paris.png</p>
            </div>
            <div>
                <label for="start_date">Start date</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo escape($fields['start_date']); ?>">
                <?php if (!empty($errors['start_date'])): ?><p class="field-error"><?php echo escape($errors['start_date']); ?></p><?php endif; ?>
            </div>
            <div>
                <label for="end_date">End date</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo escape($fields['end_date']); ?>">
                <?php if (!empty($errors['end_date'])): ?><p class="field-error"><?php echo escape($errors['end_date']); ?></p><?php endif; ?>
            </div>
            <div class="full" style="grid-column:1/-1;">
                <label for="description">Description</label>
                <textarea id="description" name="description"><?php echo escape($fields['description']); ?></textarea>
            </div>
            <div class="checkbox-row">
                <input type="checkbox" id="is_bestseller" name="is_bestseller" value="1" <?php echo $fields['is_bestseller'] === '1' ? 'checked' : ''; ?>>
                <label for="is_bestseller">Mark as bestseller (shown with a badge on public pages)</label>
            </div>
        </div>
        <p style="margin-top:1.25rem;">
            <button type="submit" class="btn"><?php echo $isEdit ? 'Save changes' : 'Create offer'; ?></button>
            <a class="btn btn-ghost" href="<?php echo escape(app_url('admin/manage-offers.php')); ?>">Cancel</a>
        </p>
    </form>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
