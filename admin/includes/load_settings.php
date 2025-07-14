<?php
// includes/load_settings.php
if (!isset($conn)) {
    include '../../user/includes/db_connect.php'; // or adjust path if needed
}

$settings = [
    'business_name' => 'Smart Printing System',
    'logo_path' => '/smart-printing-system/assets/images/logo.png',
    'contact' => '',
    'address' => '',
    'whatsapp_number' => '',
    'footer_text' => '© ' . date('Y') . ' Smart Printing System — All rights reserved.',
    'site_timezone' => 'Africa/Blantyre',
    'maintenance_mode' => 0
];

$result = $conn->query("SELECT * FROM settings WHERE id = 1 LIMIT 1");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    foreach ($row as $key => $value) {
        if (!empty($value)) {
            $settings[$key] = $value;
        }
    }
}
?>
