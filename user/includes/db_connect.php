<?php
$host = 'localhost';
$db = 'smart_printing_system';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db, 3306, '/opt/lampp/var/mysql/mysql.sock');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
?>