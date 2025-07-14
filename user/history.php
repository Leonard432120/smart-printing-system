<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

// Redirect if not logged in
if (!isset($_SESSION['users'])) {
    header("Location: ../admin/login.php");
    exit();
}

include 'includes/db_connect.php';

$user_id = $_SESSION['users']['id'];

// Handle deletion of selected orders
if (isset($_POST['delete_selected']) && !empty($_POST['order_ids'])) {
    $ids = $_POST['order_ids'];  // array of selected order ids

    // Sanitize - cast to int
    $ids = array_map('intval', $ids);

    // Build placeholders e.g. "?,?,?,?"
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $sql = "DELETE FROM orders WHERE id IN ($placeholders)";
    $stmt = $conn->prepare($sql);

    // Create types string (all integers)
    $types = str_repeat('i', count($ids));

    // bind_param requires references, so do this trick:
    $stmt_params = [];
    $stmt_params[] = & $types;
    for ($i = 0; $i < count($ids); $i++) {
        $stmt_params[] = & $ids[$i];
    }

    // Call bind_param with dynamic params
    call_user_func_array([$stmt, 'bind_param'], $stmt_params);

    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "<script>alert('Selected orders deleted successfully.'); window.location.href = 'history.php';</script>";
        exit();
    } else {
        echo "<script>alert('Failed to delete selected orders.'); window.location.href = 'history.php';</script>";
        exit();
    }
}

// Fetch orders for this user
$query = "
    SELECT o.*, s.name AS service_name
    FROM orders o
    LEFT JOIN services s ON o.service_id = s.id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>My Order History</title>
  <link rel="stylesheet" href="/smart-printing-system/assets/css/style.css" />
  <style>
    main {
      min-height: 80vh;
      padding: 40px 20px;
      background: #f8f9fa;
    }
    .history-table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .history-table th, .history-table td {
      padding: 14px 16px;
      border-bottom: 1px solid #e0e0e0;
      text-align: left;
      font-size: 0.95rem;
    }
    .history-table th {
      background-color: #0a3d62;
      color: white;
    }
    .status {
      padding: 6px 10px;
      border-radius: 4px;
      font-weight: bold;
      text-transform: capitalize;
    }
    .status.pending { background: #f39c12; color: white; }
    .status.completed { background: #28a745; color: white; }
    .status.cancelled { background: #dc3545; color: white; }

    h2 {
      text-align: center;
      color: #0a3d62;
      margin-bottom: 20px;
    }

    .delete-button {
      display: block;
      margin: 10px auto 25px;
      padding: 10px 20px;
      background-color: #dc3545;
      color: white;
      border: none;
      border-radius: 6px;
      font-weight: bold;
      cursor: pointer;
      transition: background 0.3s;
    }

    .delete-button:hover {
      background-color: #b52a2a;
    }

    .checkbox {
      transform: scale(1.2);
    }
    #delete-selected-btn {
    display: none;
    background-color: #dc3545;
    color: white;
    padding: 10px 18px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    transition: background-color 0.3s ease;
  }
  #delete-selected-btn:hover {
    background-color: #b02a37;
  }
  </style>
</head>
<body>

<?php include 'includes/header.php'; ?>
<main>
  <h2 style="margin-bottom: 20px; color: #0a3d62; text-align: center;">My Order History</h2>

  <?php if ($result->num_rows > 0): ?>
    <form method="POST" onsubmit="return confirm('Are you sure you want to delete selected orders?');">
      <div style="display: flex; justify-content: flex-end; margin-bottom: 12px;">
        <button id="delete-selected-btn" type="submit" name="delete_selected" title="Delete Selected">
          🗑️ Delete Selected
        </button>
      </div>

      <div style="overflow-x:auto;">
        <table class="history-table">
          <thead>
            <tr>
              <th><input type="checkbox" id="select-all" /></th>
              <th>#</th>
              <th>Service</th>
              <th>Notes</th>
              <th>Status</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php $count = 1; while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td><input type="checkbox" class="order-checkbox" name="order_ids[]" value="<?php echo $row['id']; ?>"></td>
                <td><?php echo $count++; ?></td>
                <td><?php echo htmlspecialchars($row['service_name'] ?? 'Unknown'); ?></td>
                <td><?php echo htmlspecialchars($row['notes'] ?: '-'); ?></td>
                <td>
                  <span class="status <?php echo isset($row['status']) ? strtolower($row['status']) : 'pending'; ?>">
                    <?php echo htmlspecialchars($row['status'] ?? 'Pending'); ?>
                  </span>
                </td>
                <td><?php echo date("M d, Y H:i", strtotime($row['created_at'])); ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </form>

    <script>
      const deleteBtn = document.getElementById('delete-selected-btn');
      const checkboxes = document.querySelectorAll('.order-checkbox');
      const selectAllCheckbox = document.getElementById('select-all');

      function toggleDeleteButton() {
        const anyChecked = Array.from(checkboxes).some(chk => chk.checked);
        deleteBtn.style.display = anyChecked ? 'inline-block' : 'none';
      }

      checkboxes.forEach(chk => {
        chk.addEventListener('change', toggleDeleteButton);
      });

      selectAllCheckbox.addEventListener('change', function() {
        checkboxes.forEach(chk => chk.checked = this.checked);
        toggleDeleteButton();
      });
    </script>
  <?php else: ?>
    <p style="text-align:center; color: #666;">You have not made any orders yet.</p>
  <?php endif; ?>
</main>


<?php include 'includes/footer.php'; ?>

<script>
  function toggleAll(source) {
    const checkboxes = document.querySelectorAll('input[type="checkbox"].checkbox');
    checkboxes.forEach(cb => cb.checked = source.checked);
  }
</script>

</body>
</html>
