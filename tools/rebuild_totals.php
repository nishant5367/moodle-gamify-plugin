<?php
// Developer-only tool to rebuild XP totals.
// Must be run as admin.

require_once(__DIR__ . '/../../../config.php');
require_login();

if (!is_siteadmin()) {
    die('Only admin users can run this tool.');
}

echo "<pre>Rebuilding XP totals...\n";

$xpman = new \local_gamify\manager\xp_manager();
$count = $xpman->recalc_all_totals();

echo "Recalculated totals for {$count} user-course combinations.\n";
echo "Done.\n</pre>";
