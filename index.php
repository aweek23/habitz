<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

$pageTitle = 'Life Tracker';

ob_start();
?>
<?php
$content = ob_get_clean();

include __DIR__ . '/php/layout.php';
