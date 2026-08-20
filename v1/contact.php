<?php
/*
 * Contact page for Version 1 (FR3). Same form as v2 minus the flight/hotel
 * enquiry extras, though build_enquiry_message still handles those keys
 * if someone pastes a v2-style URL.
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

start_app_session();

$pdo = get_pdo();
$branchStmt = $pdo->prepare(
    'SELECT id, name, city, phone, email FROM branches
     ORDER BY CASE WHEN name LIKE :hq THEN 0 ELSE 1 END, city ASC'
);
$branchStmt->execute(['hq' => '%Headquarters%']);
$branches = $branchStmt->fetchAll();

$branchIds = [];
foreach ($branches as $row) {
    $branchIds[] = (string) $row['id'];
}

$name     = '';
$email    = '';
$message  = '';
$branchId = '';
$about    = '';
$errors   = [];
$success  = false;
$sentTo   = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $errors['form'] = 'Your session expired. Please try again.';
    }

    $name     = trim((string) ($_POST['name'] ?? ''));
    $email    = trim((string) ($_POST['email'] ?? ''));
    $message  = trim((string) ($_POST['message'] ?? ''));
    $branchId = trim((string) ($_POST['branch_id'] ?? ''));
    $about    = strtolower(trim((string) ($_POST['about'] ?? '')));

    if (!in_array($about, ['offer', 'flight', 'hotel'], true)) {
        $about = '';
    }

    if (!is_required($name)) {
        $errors['name'] = 'Please enter your name.';
    }
    if (!is_required($email)) {
        $errors['email'] = 'Please enter your email address.';
    } elseif (!is_valid_email($email)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    if ($branchId === '' || !in_array($branchId, $branchIds, true)) {
        $errors['branch_id'] = 'Please choose which branch should receive this message.';
        $branchId = '';
    }
    if (!is_required($message)) {
        $errors['message'] = 'Please enter a message.';
    } elseif (strlen($message) < 10) {
        $errors['message'] = 'Please write a little more so we can help (10 characters or more).';
    }

    // Confirmation only; sending actual email is a later phase.
    if (!$errors) {
        foreach ($branches as $row) {
            if ((string) $row['id'] === $branchId) {
                $sentTo = $row;
                break;
            }
        }
        $success  = true;
        $name     = '';
        $email    = '';
        $message  = '';
        $branchId = '';
        $about    = '';
    }
} else {
    $about    = strtolower(trim((string) ($_GET['about'] ?? '')));
    if (!in_array($about, ['offer', 'flight', 'hotel'], true)) {
        $about = '';
    }
    $message  = build_enquiry_message($_GET);
    $branchId = trim((string) ($_GET['branch_id'] ?? ''));
    if (!in_array($branchId, $branchIds, true)) {
        $branchId = '';
    }

    if (function_exists('is_user_logged_in') && is_user_logged_in()) {
        $user = current_user();
        if ($user !== null) {
            $name  = (string) $user['name'];
            $email = (string) $user['email'];
        }
    }
}

$page_title = 'Contact';
require __DIR__ . '/includes/header.php';
?>

<div class="page-intro">
    <h1>Contact us</h1>
    <p class="lede">Choose a branch, send a message, or telephone the team that covers your area. Online booking is not available in this phase — a staff member will call you back.</p>
</div>

<div class="split">
    <section class="form-panel">
        <h2><?php echo $about === 'flight' ? 'Hold a flight' : ($about === 'hotel' ? 'Check hotel availability' : ($about === 'offer' ? 'Book a package' : 'Send a message')); ?></h2>

        <?php if ($success && $sentTo): ?>
            <p class="notice notice-success" role="status">
                Thank you. Your message has been received by
                <?php echo escape($sentTo['name']); ?>
                (<?php echo escape($sentTo['city']); ?>).
                A member of the team will be in touch.
                You can also call them on
                <a href="tel:<?php echo escape(preg_replace('/\s+/', '', (string) $sentTo['phone'])); ?>"><?php echo escape($sentTo['phone']); ?></a>.
            </p>
        <?php elseif ($success): ?>
            <p class="notice notice-success" role="status">Thank you. Your message has been received. A member of the Book &amp; Board team will be in touch.</p>
        <?php endif; ?>

        <?php if (!empty($errors['form'])): ?>
            <p class="notice notice-error"><?php echo escape($errors['form']); ?></p>
        <?php endif; ?>

        <form method="post" action="" data-validate novalidate>
            <?php echo csrf_field(); ?>
            <?php if ($about !== ''): ?>
                <input type="hidden" name="about" value="<?php echo escape($about); ?>">
            <?php endif; ?>

            <div class="form-grid">
                <div>
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" required autocomplete="name" value="<?php echo escape($name); ?>">
                    <?php if (!empty($errors['name'])): ?>
                        <p class="field-error"><?php echo escape($errors['name']); ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required autocomplete="email" value="<?php echo escape($email); ?>">
                    <?php if (!empty($errors['email'])): ?>
                        <p class="field-error"><?php echo escape($errors['email']); ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="branch_id">Send to branch</label>
                    <select id="branch_id" name="branch_id" required>
                        <option value="">Choose a branch</option>
                        <?php foreach ($branches as $row): ?>
                            <option value="<?php echo (int) $row['id']; ?>"<?php echo selected_attr($branchId, (string) $row['id']); ?>>
                                <?php echo escape($row['name']); ?> — <?php echo escape($row['city']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['branch_id'])): ?>
                        <p class="field-error"><?php echo escape($errors['branch_id']); ?></p>
                    <?php endif; ?>
                    <p class="field-hint">The branch you pick will follow up. Nothing is booked until they confirm.</p>
                </div>

                <div>
                    <label for="message">Message</label>
                    <textarea id="message" name="message" required><?php echo escape($message); ?></textarea>
                    <?php if (!empty($errors['message'])): ?>
                        <p class="field-error"><?php echo escape($errors['message']); ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <button type="submit" class="btn">Send message</button>
                </div>
            </div>
        </form>
    </section>

    <aside>
        <h2>Branch contacts</h2>
        <div class="card-grid" style="grid-template-columns:1fr;">
            <?php foreach ($branches as $branch): ?>
                <article class="card">
                    <div class="card-body">
                        <h3><?php echo escape($branch['name']); ?></h3>
                        <p class="meta"><?php echo escape($branch['city']); ?></p>
                        <p><a href="tel:<?php echo escape(preg_replace('/\s+/', '', (string) $branch['phone'])); ?>"><?php echo escape($branch['phone']); ?></a></p>
                        <p><a href="mailto:<?php echo escape($branch['email']); ?>"><?php echo escape($branch['email']); ?></a></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </aside>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
