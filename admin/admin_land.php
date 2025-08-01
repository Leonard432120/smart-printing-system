<?php

include '../user/includes/db_connect.php';
include 'includes/functions.php';
include 'includes/load_settings.php';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get total lands count
$total = $conn->query("SELECT COUNT(*) FROM lands")->fetch_row()[0];
$pages = ceil($total / $per_page);

// Get lands with request counts
$lands = $conn->query("SELECT l.*, COUNT(lr.id) as request_count 
                      FROM lands l 
                      LEFT JOIN land_requests lr ON l.id = lr.land_id 
                      GROUP BY l.id 
                      ORDER BY l.created_at DESC 
                      LIMIT $offset, $per_page")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Lands</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/smart-printing-system/assets/css/admin.css">
</head>
<body>
    
    
    <div class="admin-container">
    
        
        <main class="admin-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Manage Lands</h1>
                <a href="add_land.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Land
                </a>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Location</th>
                            <th>Size</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Requests</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lands as $land): ?>
                        <tr>
                            <td><?= $land['id'] ?></td>
                            <td><?= htmlspecialchars($land['title']) ?></td>
                            <td><?= htmlspecialchars($land['location']) ?></td>
                            <td><?= number_format($land['size'], 2) ?> acres</td>
                            <td>KSh <?= number_format($land['price'], 2) ?></td>
                            <td>
                                <span class="status-badge <?= $land['status'] ?>">
                                    <?= ucfirst($land['status']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="land_requests.php?land_id=<?= $land['id'] ?>" class="btn btn-sm btn-info">
                                    <?= $land['request_count'] ?> Requests
                                </a>
                            </td>
                            <td>
                                <a href="edit_land.php?id=<?= $land['id'] ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="manage_images.php?land_id=<?= $land['id'] ?>" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-images"></i>
                                </a>
                                <a href="delete_land.php?id=<?= $land['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($pages > 1): ?>
            <nav class="pagination-container">
                <ul class="pagination">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
                    </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </main>
    </div>
    
    
</body>
</html>