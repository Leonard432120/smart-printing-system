<?php
session_start();
if (!isset($_SESSION['users'])) {
    header("Location: ../admin/login.php");
    exit();
}

include 'includes/db_connect.php';

$user_contact = $_SESSION['users']['email']; // or phone

// Fetch notifications
$stmt = $conn->prepare("SELECT * FROM notifications WHERE recipient_contact = ? ORDER BY created_at DESC");
$stmt->bind_param("s", $user_contact);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Notifications - Smart Printing System</title>
  <link rel="stylesheet" href="/smart-printing-system/assets/css/style.css" />
  <style>
    main {
      min-height: 80vh;
      padding: 40px 20px;
      background: #f8f9fa;
    }
    .notifications-container {
      max-width: 700px;
      margin: auto;
      background: white;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 6px 15px rgba(0,0,0,0.1);
    }
    .notifications-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
    }
    .notifications-header h2 {
      margin: 0;
      color: #0a3d62;
    }
    .btn {
      background-color: #0a3d62;
      color: white;
      border: none;
      padding: 8px 14px;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      transition: background 0.3s ease;
      font-size: 0.9rem;
    }
    .btn:hover {
      background-color: #064173;
    }
    .notification {
      border-bottom: 1px solid #ddd;
      padding: 15px 0;
      cursor: pointer;
      position: relative;
    }
    .notification:last-child {
      border-bottom: none;
    }
    .notification.unread {
      background-color: #e7f3ff;
      font-weight: bold;
    }
    .notification p {
      margin: 0 0 6px 0;
      color: #444;
      white-space: pre-wrap;
    }
    .notification time {
      font-size: 0.85rem;
      color: #888;
    }
    .delete-btn {
      position: absolute;
      right: 10px;
      top: 18px;
      background: transparent;
      border: none;
      color: #dc3545;
      font-size: 1.1rem;
      cursor: pointer;
      opacity: 0.6;
      transition: opacity 0.2s ease;
    }
    .delete-btn:hover {
      opacity: 1;
    }
    .no-notifications {
      text-align: center;
      color: #666;
      padding: 30px 0;
      font-size: 1.1rem;
    }
  </style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<main>
  <div class="notifications-container">
    <div class="notifications-header">
      <h2>Your Notifications</h2>
      <div>
        <button id="markAllReadBtn" class="btn">Mark All as Read</button>
        <button id="deleteAllBtn" class="btn" style="background-color:#dc3545; margin-left: 8px;">Delete All</button>
      </div>
    </div>

    <div id="notificationsList">
      <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <div class="notification <?php echo $row['sent'] == 0 ? 'unread' : ''; ?>" data-id="<?php echo $row['id']; ?>">
            <p><?php echo htmlspecialchars($row['message']); ?></p>
            <time><?php echo date("M d, Y H:i", strtotime($row['created_at'])); ?></time>
            <button class="delete-btn" title="Delete Notification">&times;</button>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p class="no-notifications">You have no notifications at the moment.</p>
      <?php endif; ?>
    </div>
  </div>
</main>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const notificationsList = document.getElementById('notificationsList');

    // Mark individual as read
    notificationsList.addEventListener('click', function (e) {
      const notificationDiv = e.target.closest('.notification');
      if (!notificationDiv || e.target.classList.contains('delete-btn')) return;

      const notificationId = notificationDiv.dataset.id;
      if (notificationDiv.classList.contains('unread')) {
        fetch('/user/notifications_mark_read.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: notificationId })
        }).then(res => res.json()).then(data => {
          if (data.success) {
            notificationDiv.classList.remove('unread');
          }
        });
      }
    });

    // Delete single
    notificationsList.addEventListener('click', function (e) {
      if (e.target.classList.contains('delete-btn')) {
        const notificationDiv = e.target.closest('.notification');
        const notificationId = notificationDiv.dataset.id;

        if (confirm('Are you sure you want to delete this notification?')) {
          fetch('/user/notifications_delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: notificationId })
          }).then(res => res.json()).then(data => {
            if (data.success) {
              notificationDiv.remove();
              if (!document.querySelector('.notification')) {
                notificationsList.innerHTML = '<p class="no-notifications">You have no notifications at the moment.</p>';
              }
            } else {
              alert('Delete failed.');
            }
          });
        }
      }
    });

    // Mark all as read
    document.getElementById('markAllReadBtn').addEventListener('click', function () {
      fetch('/user/notifications_mark_all_read.php', {
        method: 'POST'
      }).then(res => res.json()).then(data => {
        if (data.success) {
          document.querySelectorAll('.notification.unread').forEach(el => el.classList.remove('unread'));
        }
      });
    });

    // Delete all
    document.getElementById('deleteAllBtn').addEventListener('click', function () {
      if (confirm('Are you sure you want to delete ALL notifications?')) {
        fetch('/user/notifications_delete_all.php', {
          method: 'POST'
        }).then(res => res.json()).then(data => {
          if (data.success) {
            notificationsList.innerHTML = '<p class="no-notifications">You have no notifications at the moment.</p>';
          }
        });
      }
    });
  });
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>
