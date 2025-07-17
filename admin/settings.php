<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

// Restrict access to admin only
if (!isset($_SESSION['users']) || strtolower($_SESSION['users']['role']) !== 'admin') {
    header("Location: login.php");
    exit();
}

include 'includes/functions.php';
include __DIR__ . '/../user/includes/db_connect.php';

if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}

// Load PayChangu API key
$configFile = __DIR__ . '/../config.php';
$paychanguApiKey = '';
if (file_exists($configFile)) {
    include $configFile;
    $paychanguApiKey = defined('PAYCHANGU_API_KEY') ? PAYCHANGU_API_KEY : '';
}

$statusMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_settings') {
    // Raw inputs
    $siteName = trim($_POST['site_name'] ?? '');
    $contactEmailRaw = $_POST['contact_email'] ?? '';
    $contactEmail = trim($contactEmailRaw);
    $address = trim($_POST['address'] ?? '');
    $whatsappNumber = trim($_POST['whatsapp_number'] ?? '');
    $siteTimezone = trim($_POST['site_timezone'] ?? '');
    $footerText = trim($_POST['footer_text'] ?? '');
    $maintenanceMode = (isset($_POST['maintenance_mode']) && $_POST['maintenance_mode'] === '1') ? 1 : 0;
    $paychanguApiKeyNew = trim($_POST['paychangu_api_key'] ?? '');

    // Validate input
    if (empty($siteName)) {
        $statusMsg = "Site name is required.";
    } elseif (!filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
        $statusMsg = "Invalid contact email format.";
    }

    // Handle logo upload
    $logoPath = '';
    if (empty($statusMsg) && isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $allowedTypes = ['image/jpeg', 'image/png'];
        $fileType = $_FILES['logo']['type'];
        $fileSize = $_FILES['logo']['size'];
        $uploadDir = __DIR__ . '/../assets/images/';
        $fileTmpName = $_FILES['logo']['tmp_name'];

        if (!in_array($fileType, $allowedTypes)) {
            $statusMsg = "Invalid logo file format. Only JPEG and PNG allowed.";
        } elseif ($fileSize > 2 * 1024 * 1024) {
            $statusMsg = "Logo file size exceeds 2MB limit.";
        } elseif (!is_writable($uploadDir)) {
            $statusMsg = "Upload directory is not writable.";
        } else {
            $safeFileName = 'logo_' . time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', basename($_FILES['logo']['name']));
            if (!move_uploaded_file($fileTmpName, $uploadDir . $safeFileName)) {
                $statusMsg = "Failed to upload logo file.";
            } else {
                $logoPath = "/smart-printing-system/assets/images/$safeFileName";
            }
        }
    }

    if (empty($statusMsg)) {
        // If no new logo, get current one
        if (empty($logoPath)) {
            $resLogo = $conn->query("SELECT logo_path FROM settings WHERE id = 1 LIMIT 1");
            $logoPath = ($resLogo && $resLogo->num_rows > 0) ? $resLogo->fetch_assoc()['logo_path'] : '';
        }

        // Save settings
        $existsRes = $conn->query("SELECT id FROM settings WHERE id = 1");
        if ($existsRes && $existsRes->num_rows > 0) {
           $stmt = $conn->prepare("UPDATE settings SET business_name = ?, logo_path = ?, contact_email = ?, address = ?, phone = ?, site_timezone = ?, footer_text = ?, maintenance_mode = ?, updated_at = NOW() WHERE id = 1");
           $stmt->bind_param('sssssssi', $siteName, $logoPath, $contactEmail, $address, $whatsappNumber, $siteTimezone, $footerText, $maintenanceMode);

        } else {
            $stmt = $conn->prepare("INSERT INTO settings (id, business_name, logo_path, contact_email, address, phone, site_timezone, footer_text, maintenance_mode, updated_at) VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param('sssssssi', $siteName, $logoPath, $contactEmail, $address, $whatsappNumber, $siteTimezone, $footerText, $maintenanceMode);
        }

        if (!$stmt->execute()) {
            $statusMsg = "Failed to save settings: " . $stmt->error;
        } else {
            $statusMsg = "Settings updated successfully!";
        }
        $stmt->close();
    }

    // Update PayChangu API key file
    if (empty($statusMsg) && !empty($paychanguApiKeyNew)) {
        $apiKeyContent = "<?php\ndefine('PAYCHANGU_API_KEY', '" . addslashes($paychanguApiKeyNew) . "');\n?>";
        if (!file_put_contents($configFile, $apiKeyContent)) {
            $statusMsg = "Failed to write PayChangu API key to config file.";
        }
    }
}

// Load settings
$settings = [
    'business_name' => 'Smart Printing',
    'logo_path' => '/smart-printing-system/assets/images/logo.png',
    'contact_email' => '',
    'address' => '',
    'whatsapp_number' => '',
    'site_timezone' => 'Africa/Blantyre',
    'footer_text' => '© ' . date('Y') . ' Smart Printing System',
    'maintenance_mode' => 0,
];
$resSettings = $conn->query("SELECT business_name, logo_path, contact_email, address, phone, site_timezone, footer_text, maintenance_mode FROM settings WHERE id = 1 LIMIT 1");
if ($resSettings && $resSettings->num_rows > 0) {
   $row = $resSettings->fetch_assoc();
$settings = [
    'business_name' => $row['business_name'],
    'logo_path' => $row['logo_path'],
    'contact_email' => $row['contact_email'],
    'address' => $row['address'],
    'whatsapp_number' => $row['phone'], // map correctly here
    'site_timezone' => $row['site_timezone'],
    'footer_text' => $row['footer_text'],
    'maintenance_mode' => $row['maintenance_mode']
];

}
?>

<!-- HTML Below (Unchanged CSS) -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Settings | Smart Printing</title>
  <link rel="stylesheet" href="/smart-printing-system/assets/css/admin_style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body>
    
<style>
    .settings-form {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        max-width: 600px;
        margin: 20px auto;
    }
    .settings-form label {
        display: block;
        margin: 10px 0 5px;
        font-weight: bold;
    }
    .settings-form input, .settings-form select, .settings-form textarea {
        width: 100%;
        padding: 10px;
        margin-bottom: 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 1rem;
        resize: vertical;
    }
    .settings-form button {
        padding: 10px 20px;
        background: #3498db;
        color: #fff;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }
    .settings-form button:hover {
        background: #2980b9;
    }
    .status-msg {
        padding: 10px;
        margin-bottom: 20px;
        border-radius: 5px;
        text-align: center;
    }
    .status-msg.success {
        background: #2ecc71;
        color: #fff;
    }
    .status-msg.error {
        background: #e74c3c;
        color: #fff;
    }
    .current-logo {
        max-width: 100px;
        margin-bottom: 10px;
        display: block;
    }
    .sidebar {
        /* Your sidebar styles here if needed */
    }
</style>
<div class="admin-wrapper">
  <div class="sidebar">
    <div class="logo">
      <img src="<?php echo htmlspecialchars($settings['logo_path']); ?>" alt="Logo" class="logo-img" />
      <span><?php echo htmlspecialchars($settings['business_name']); ?></span>
    </div>
    <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="manage_lessons.php"><i class="fas fa-book"></i> Lessons</a>
    <a href="manage_prices.php"><i class="fas fa-tag"></i> Prices</a>
    <a href="manage_services.php"><i class="fas fa-cogs"></i> Services</a>
    <a href="manage_students.php"><i class="fas fa-user-graduate"></i> Students</a>
    <a href="manage_transactions.php"><i class="fas fa-credit-card"></i> Transactions</a>
    <a href="manage_users.php"><i class="fas fa-users"></i> Users</a>
    <a href="report.php"><i class="fas fa-file-alt"></i> Report</a>
    <a href="settings.php" class="active"><i class="fas fa-cog"></i> Settings</a>
    <a href="../logout.php" style="color: red;" onclick="return confirm('Are you sure you want to logout?');"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>

  <div class="main-content">
    <header class="dashboard-header">
      <div class="header-content">
        <h1><i class="fas fa-cog"></i> Settings</h1>
        <p>Manage system configurations.</p>
      </div>
      <div class="user-profile">
        <i class="fas fa-user-circle"></i>
        <span><?php echo htmlspecialchars($_SESSION['users']['username']); ?></span>
      </div>
    </header>

    <?php if (!empty($statusMsg)): ?>
      <div class="status-msg <?php echo (stripos($statusMsg, 'success') !== false) ? 'success' : 'error'; ?>">
        <?php echo htmlspecialchars($statusMsg); ?>
      </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="settings-form">
      <input type="hidden" name="action" value="update_settings" />

      <h2>System Settings</h2>

      <label for="site_name">Site Name</label>
      <input type="text" id="site_name" name="site_name" required value="<?php echo htmlspecialchars($settings['business_name']); ?>" />

      <label for="logo">Site Logo</label>
      <?php if (!empty($settings['logo_path'])): ?>
        <img src="<?php echo htmlspecialchars($settings['logo_path']); ?>" alt="Logo" class="current-logo" />
      <?php endif; ?>
      <input type="file" id="logo" name="logo" accept="image/jpeg,image/png" />

      <label for="contact_email">Contact Email</label>
      <input type="email" id="contact_email" name="contact_email"  value="<?php echo htmlspecialchars($settings['contact_email']); ?>" />

      <label for="address">Address</label>
      <textarea id="address" name="address"><?php echo htmlspecialchars($settings['address']); ?></textarea>

      <label for="whatsapp_number">WhatsApp Number</label>
      <input type="text" id="whatsapp_number" name="whatsapp_number" value="<?php echo htmlspecialchars($settings['whatsapp_number']); ?>" />

      <label for="site_timezone">Site Timezone</label>
      <input type="text" id="site_timezone" name="site_timezone" value="<?php echo htmlspecialchars($settings['site_timezone']); ?>" />

      <label for="footer_text">Footer Text</label>
      <input type="text" id="footer_text" name="footer_text" value="<?php echo htmlspecialchars($settings['footer_text']); ?>" />

      <label for="maintenance_mode">Maintenance Mode</label>
      <select id="maintenance_mode" name="maintenance_mode">
        <option value="0" <?php echo ($settings['maintenance_mode'] == 0) ? 'selected' : ''; ?>>Off</option>
        <option value="1" <?php echo ($settings['maintenance_mode'] == 1) ? 'selected' : ''; ?>>On</option>
      </select>

      <label for="paychangu_api_key">PayChangu API Key</label>
      <input type="password" id="paychangu_api_key" name="paychangu_api_key" placeholder="Enter new API key or leave blank" />

      <button type="submit">Update Settings</button>
    </form>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>





