<?php
session_start();
include 'includes/db_connect.php';

// Ensure user is logged in
if (!isset($_SESSION['users'])) {
    header("Location: ../admin/login.php");
    exit();
}

$user_id = $_SESSION['users']['id'];
$user_email = $_SESSION['users']['email'];

$entries = [];

// === Fetch document uploads (transactions) ===
$stmt1 = $conn->prepare("SELECT reference_code AS title, status, created_at FROM transactions WHERE user_id = ?");
$stmt1->bind_param("i", $user_id);
$stmt1->execute();
$result1 = $stmt1->get_result();

while ($row = $result1->fetch_assoc()) {
    $entries[] = [
        'type' => 'Upload',
        'title' => $row['title'],
        'status' => $row['status'],
        'created_at' => $row['created_at']
    ];
}

// === Fetch service orders ===
$stmt2 = $conn->prepare("SELECT notes AS title, status, created_at FROM orders WHERE email = ?");
$stmt2->bind_param("s", $user_email);
$stmt2->execute();
$result2 = $stmt2->get_result();

while ($row = $result2->fetch_assoc()) {
    $entries[] = [
        'type' => 'Order',
        'title' => $row['title'] ?: 'No notes',
        'status' => $row['status'],
        'created_at' => $row['created_at']
    ];
}

// Sort all entries by date DESC
usort($entries, function ($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Track Your Status</title>
  <link rel="stylesheet" href="/smart-printing-system/assets/css/style.css">
  <style>
    body { background: #f0f2f5; font-family: Arial, sans-serif; }
    main { max-width: 900px; margin: auto; padding: 40px 20px; }
    h2 { color: #0a3d62; text-align: center; }
    table { width: 100%; border-collapse: collapse; margin-top: 30px; background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    th, td { padding: 12px; border-bottom: 1px solid #ccc; text-align: left; }
    th { background-color: #0a3d62; color: white; }
    .status { font-weight: bold; text-transform: capitalize; }
    .pending { color: orange; }
    .processing { color: dodgerblue; }
    .completed { color: green; }
    .cancelled, .failed { color: red; }
  </style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<main>
  <h2>All Activity Status</h2>

  <?php if (count($entries) > 0): ?>
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Type</th>
          <th>Title / Reference</th>
          <th>Status</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php $i = 1; foreach ($entries as $entry): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($entry['type']) ?></td>
            <td><?= htmlspecialchars($entry['title']) ?></td>
            <td class="status <?= strtolower($entry['status']) ?>"><?= htmlspecialchars($entry['status']) ?></td>
            <td><?= date('M d, Y H:i', strtotime($entry['created_at'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p style="text-align:center; color:#777;">No uploads or orders found.</p>
  <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>
</body>
</html>
