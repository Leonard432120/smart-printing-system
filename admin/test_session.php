<?php
ini_set('session.cookie_path', '/');
session_start();

if (!isset($_SESSION['test'])) {
    $_SESSION['test'] = 'Session is working!';
    echo "Session variable 'test' is now set. Refresh page.";
} else {
    echo "Session variable 'test' value: " . $_SESSION['test'];
}
?>
