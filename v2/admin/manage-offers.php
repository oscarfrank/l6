<?php
/*
 * Staff offer list (FR5). require_admin() is the session check.
 * Delete is POST + CSRF so a GET link or a crawler cannot wipe a row.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$pdo = get_pdo();
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $flash = 'error:Your session expired. The offer was not deleted.';
    } else {
        $deleteId = (int) $_POST['delete_id'];
        $stmt = $pdo->prepare('DELETE FROM offers WHERE id = ?');
        $stmt->execute([$deleteId]);
        $flash = 'success:Offer deleted.';
    }
}

$listStmt = $pdo->prepare(
    'SELECT id, title, destination, price, is_bestseller, start_date, end_date
     FROM offers
     ORDER BY end_date DESC, title ASC'
);
$listStmt->execute();
$offers = $listStmt->fetchAll();

$page_title = 'Manage offers';
$is_admin   = true;
require __DIR__ . '/../includes/header.php';
?>

<div class="page-intro">
    <h1>Manage offers</h1>
    <p class="lede">Add, edit or remove holiday packages. Changes appear on the public home and offers pages immediately.</p>
</div>

<div class="toolbar">
    <a class="btn" href="<?php echo escape(app_url('admin/edit-offer.php')); ?>">Add an offer</a>
    <a class="btn btn-ghost" href="<?php echo escape(app_url('admin/manage-bookings.php')); ?>">Package requests</a>
</div>

<?php if ($flash): ?>
    <?php
    [$level, $text] = explode(':', $flash, 2);
    $class = $level === 'success' ? 'notice-success' : 'notice-error';
    ?>
    <p class="notice <?php echo escape($class); ?>"><?php echo escape($text); ?></p>
<?php endif; ?>

<div class="table-wrap">
    <table class="data">
        <thead>
            <tr>
                <th>Title</th>
                <th>Destination</th>
                <th>Price</th>
                <th>Bestseller</th>
                <th>Dates</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$offers): ?>
                <tr>
                    <td colspan="6">No offers yet. Use “Add an offer” to create the first package.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($offers as $offer): ?>
                <tr>
                    <td><?php echo escape($offer['title']); ?></td>
                    <td><?php echo escape($offer['destination']); ?></td>
                    <td><?php echo escape(format_price($offer['price'])); ?></td>
                    <td><?php echo !empty($offer['is_bestseller']) ? 'Yes' : 'No'; ?></td>
                    <td>
                        <?php echo escape(format_date($offer['start_date'])); ?>
                        –
                        <?php echo escape(format_date($offer['end_date'])); ?>
                    </td>
                    <td>
                        <div class="row-actions">
                            <a class="btn btn-ghost" href="<?php echo escape(app_url('admin/edit-offer.php?id=' . (int) $offer['id'])); ?>">Edit</a>
                            <form method="post" action="" onsubmit="return confirm('Delete this offer?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="delete_id" value="<?php echo (int) $offer['id']; ?>">
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
