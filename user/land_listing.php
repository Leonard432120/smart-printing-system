<?php
include 'includes/db_connect.php';
include 'includes/header.php';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 9;
$offset = ($page - 1) * $per_page;

// Filters
$where = ["status = 'available'"];
$params = [];
$types = '';

if (isset($_GET['location']) && !empty($_GET['location'])) {
    $where[] = "location LIKE ?";
    $params[] = '%' . $_GET['location'] . '%';
    $types .= 's';
}

if (isset($_GET['min_price']) && is_numeric($_GET['min_price'])) {
    $where[] = "price >= ?";
    $params[] = (float)$_GET['min_price'];
    $types .= 'd';
}

if (isset($_GET['max_price']) && is_numeric($_GET['max_price'])) {
    $where[] = "price <= ?";
    $params[] = (float)$_GET['max_price'];
    $types .= 'd';
}

if (isset($_GET['min_size']) && is_numeric($_GET['min_size'])) {
    $where[] = "size >= ?";
    $params[] = (float)$_GET['min_size'];
    $types .= 'd';
}

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM lands $whereClause";
$count_stmt = $conn->prepare($count_query);
if ($params) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_assoc()['total'];
$pages = ceil($total / $per_page);

// Get lands data
$query = "SELECT * FROM lands $whereClause ORDER BY featured DESC, created_at DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $per_page;
$types .= 'ii';

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$lands = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="container">
    <link rel="stylesheet" href="/smart-printing-system/assets/css/land.css">
    <h1 class="my-4">Available Lands</h1>
    
    <!-- Search and Filter Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="location" class="form-label">Location</label>
                    <input type="text" class="form-control" id="location" name="location" 
                           value="<?= htmlspecialchars($_GET['location'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label for="min_price" class="form-label">Min Price</label>
                    <input type="number" class="form-control" id="min_price" name="min_price" 
                           value="<?= htmlspecialchars($_GET['min_price'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label for="max_price" class="form-label">Max Price</label>
                    <input type="number" class="form-control" id="max_price" name="max_price" 
                           value="<?= htmlspecialchars($_GET['max_price'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label for="min_size" class="form-label">Min Size (acres)</label>
                    <input type="number" step="0.01" class="form-control" id="min_size" name="min_size" 
                           value="<?= htmlspecialchars($_GET['min_size'] ?? '') ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Filter</button>
                    <a href="land_listing.php" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Lands Grid -->
    <div class="row">
        <?php if (empty($lands)): ?>
            <div class="col-12">
                <div class="alert alert-info">No lands found matching your criteria.</div>
            </div>
        <?php else: ?>
            <?php foreach ($lands as $land): ?>
                <?php
                // Get primary image
                $img_stmt = $conn->prepare("SELECT image_path FROM land_images WHERE land_id = ? AND is_primary = TRUE LIMIT 1");
                $img_stmt->bind_param("i", $land['id']);
                $img_stmt->execute();
                $primary_image = $img_stmt->get_result()->fetch_assoc();
                ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <img src="/smart-printing-system/uploads/lands/<?= htmlspecialchars($primary_image['image_path'] ?? 'default.jpg') ?>" 
                             class="card-img-top" alt="<?= htmlspecialchars($land['title']) ?>" style="height: 200px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($land['title']) ?></h5>
                            <p class="card-text text-muted">
                                <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($land['location']) ?>
                            </p>
                            <p class="card-text">
                                <strong>Size:</strong> <?= number_format($land['size'], 2) ?> acres<br>
                                <strong>Price:</strong> KSh <?= number_format($land['price'], 2) ?>
                            </p>
                        </div>
                        <div class="card-footer bg-white">
                            <a href="land_details.php?id=<?= $land['id'] ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                            <?php if (isset($_SESSION['users'])): ?>
                                <button class="btn btn-sm btn-success float-end" 
                                        onclick="showInterestModal(<?= $land['id'] ?>)">
                                    <i class="fas fa-handshake"></i> Interested
                                </button>
                            <?php else: ?>
                                <a href="../login.php" class="btn btn-sm btn-outline-secondary float-end">
                                    Login to Express Interest
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<!-- Interest Modal -->
<div class="modal fade" id="interestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Express Interest in Land</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="interestForm" method="POST" action="process_interest.php">
                <input type="hidden" name="land_id" id="modalLandId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="message" class="form-label">Message (Optional)</label>
                        <textarea class="form-control" id="message" name="message" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Submit Interest</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showInterestModal(landId) {
    document.getElementById('modalLandId').value = landId;
    var modal = new bootstrap.Modal(document.getElementById('interestModal'));
    modal.show();
}
</script>

<?php include 'includes/footer.php'; ?>