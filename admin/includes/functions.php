<?php
// ✅ Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Include DB connection if not already set
if (!isset($conn)) {
    include __DIR__ . '/../../user/includes/db_connect.php'; // Adjust path to db_connect.php
}

/**
 * ✅ Check if the logged-in user is an admin
 */
function isAdminLoggedIn()
{
    return isset($_SESSION['users']) && strtolower($_SESSION['users']['role']) === 'admin';
}

/**
 * ✅ Redirect to login if user is not admin
 */
function ensureAdminLoggedIn()
{
    if (!isAdminLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

/**
 * ✅ Sanitize user input
 */
function sanitizeInput($input)
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * ✅ Format number to MWK currency
 */
function formatCurrency($amount)
{
    return "MWK " . number_format($amount, 2);
}

/**
 * ✅ Set or display a flash message
 */
function flashMessage($name, $message = '', $type = 'success')
{
    if (!empty($message)) {
        $_SESSION['flash'][$name] = ['message' => $message, 'type' => $type];
    } elseif (isset($_SESSION['flash'][$name])) {
        $flash = $_SESSION['flash'][$name];
        unset($_SESSION['flash'][$name]);

        echo "<div class='alert alert-{$flash['type']}'>" . $flash['message'] . "</div>";
    }
}
