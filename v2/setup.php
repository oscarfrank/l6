<?php
// First-run installer. After a successful save it writes setup.lock so it cannot be reused.
require_once __DIR__ . '/includes/installer.php';
installer_run(__DIR__);
