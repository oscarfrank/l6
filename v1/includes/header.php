<?php
/*
 * Layout header for Version 1 (Home, Offers, Branches, Contact + staff pages).
 * $page_title is required. Set $is_admin on pages under /admin.
 * Version 2 adds extra nav items; this file stays as the prototype menu.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

start_app_session();

$page_title = $page_title ?? 'Book & Board';
$is_admin   = !empty($is_admin);
$body_class = $body_class ?? '';

$current_script = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo escape($page_title); ?> — Book &amp; Board</title>
    <link rel="icon" href="<?php echo escape(app_url('assets/images/logo.png')); ?>" type="image/png">
    <link rel="stylesheet" href="<?php echo escape(app_url('assets/css/styles.css')); ?>">
</head>
<body class="<?php echo escape($body_class); ?>">
    <a class="skip-link" href="#main">Skip to content</a>

    <header class="site-header">
        <div class="header-inner">
            <a class="brand" href="<?php echo escape(app_url('index.php')); ?>">
                <img src="<?php echo escape(app_url('assets/images/logo.png')); ?>" alt="" width="40" height="40">
                <span class="brand-text">
                    <span class="brand-name">Book &amp; Board</span>
                    <span class="brand-tag">Travel since 1975</span>
                </span>
            </a>

            <button
                type="button"
                class="nav-toggle"
                id="nav-toggle"
                aria-controls="site-nav"
                aria-expanded="false"
            >
                <span class="nav-toggle-bar"></span>
                <span class="nav-toggle-bar"></span>
                <span class="nav-toggle-bar"></span>
                <span class="visually-hidden">Open menu</span>
            </button>

            <nav id="site-nav" class="site-nav" aria-label="Primary">
                <ul>
                    <?php if ($is_admin): ?>
                        <li>
                            <a href="<?php echo escape(app_url('admin/manage-offers.php')); ?>"
                               <?php echo $current_script === 'manage-offers.php' || $current_script === 'edit-offer.php' ? 'aria-current="page"' : ''; ?>>
                                Manage offers
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo escape(app_url('index.php')); ?>">View site</a>
                        </li>
                        <li>
                            <a href="<?php echo escape(app_url('admin/admin-logout.php')); ?>">Log out</a>
                        </li>
                    <?php else: ?>
                        <li>
                            <a href="<?php echo escape(app_url('index.php')); ?>"
                               <?php echo $current_script === 'index.php' ? 'aria-current="page"' : ''; ?>>Home</a>
                        </li>
                        <li>
                            <a href="<?php echo escape(app_url('offers.php')); ?>"
                               <?php echo $current_script === 'offers.php' ? 'aria-current="page"' : ''; ?>>Offers</a>
                        </li>
                        <li>
                            <a href="<?php echo escape(app_url('branches.php')); ?>"
                               <?php echo $current_script === 'branches.php' ? 'aria-current="page"' : ''; ?>>Branches</a>
                        </li>
                        <li>
                            <a href="<?php echo escape(app_url('contact.php')); ?>"
                               <?php echo $current_script === 'contact.php' ? 'aria-current="page"' : ''; ?>>Contact</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main id="main">
