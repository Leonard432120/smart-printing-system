<?php
// includes/load_settings.php
if (!isset($conn)) {
    include '../../user/includes/db_connect.php'; // or adjust path if needed
}

$settings = [
    'business_name' => 'Smart Printing System',
    'logo_path' => '/smart-printing-system/assets/images/logo.png',
    'contact_email' => '', // changed from 'contact' to 'contact_email'
    'address' => '',
    'whatsapp_number' => '',
    'footer_text' => '© ' . date('Y') . ' Smart Printing System — All rights reserved.',
    'site_timezone' => 'Africa/Blantyre',
    'maintenance_mode' => 0
];

$result = $conn->query("SELECT business_name, logo_path, contact_email, address, phone, site_timezone, footer_text, maintenance_mode FROM settings WHERE id = 1 LIMIT 1");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    foreach ($row as $key => $value) {
        if (!empty($value)) {
            $settings[$key] = $value;
        }
    }
}
?>
