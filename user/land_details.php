<?php
include 'includes/db_connect.php';
include 'includes/header.php';

$land_id = intval($_GET['id'] ?? 0);

// Get land details
$land_stmt = $conn->prepare("SELECT * FROM lands WHERE id = ?");
$land_stmt->bind_param("i", $land_id);
$land_stmt->execute();
$land = $land_stmt->get_result()->fetch_assoc();

if (!$land) {
    header("Location: land_listing.php");
    exit();
}

// Get land images
$images_stmt = $conn->prepare("SELECT * FROM land_images WHERE land_id = ?");
$images_stmt->bind_param("i", $land_id);
$images_stmt->execute();
$images = $images_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get land features
$features_stmt = $conn->prepare("SELECT feature FROM land_features WHERE land_id = ?");
$features_stmt->bind_param("i", $land_id);
$features_stmt->execute();
$features = $features_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="container my-4">
    <link rel="stylesheet" href="/smart-printing-system/assets/css/land.css">
    <div class="row">
        <div class="col-md-8">
            <!-- Main Carousel -->
            <div id="landCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <?php foreach ($images as $index => $image): ?>
                        <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                            <img src="/smart-printing-system/uploads/lands/<?= htmlspecialchars($image['image_path']) ?>" 
                                 class="d-block w-100" style="height: 500px; object-fit: cover;" 
                                 alt="Land image <?= $index + 1 ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#landCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#landCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
            
            <!-- Thumbnails -->
            <div class="row mt-3">
                <?php foreach ($images as $index => $image): ?>
                    <div class="col-3">
                        <img src="/smart-printing-system/uploads/lands/<?= htmlspecialchars($image['image_path']) ?>" 
                             class="img-thumbnail" style="height: 100px; cursor: pointer;" 
                             onclick="document.getElementById('landCarousel').carousel(<?= $index ?>)" 
                             alt="Thumbnail <?= $index + 1 ?>">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h2 class="card-title"><?= htmlspecialchars($land['title']) ?></h2>
                    <p class="text-muted">
                        <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($land['location']) ?>
                    </p>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <div>
                            <h4 class="text-primary">KSh <?= number_format($land['price'], 2) ?></h4>
                            <small class="text-muted">Total Price</small>
                        </div>
                        <div>
                            <h4><?= number_format($land['size'], 2) ?> acres</h4>
                            <small class="text-muted">Land Size</small>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h5>Features</h5>
                    <ul>
                        <?php foreach ($features as $feature): ?>
                            <li><?= htmlspecialchars($feature['feature']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <hr>
                    
                    <h5>Description</h5>
                    <p><?= nl2br(htmlspecialchars($land['description'])) ?></p>
                    
                    <hr>
                    
                    <div class="d-grid gap-2">
                        <?php if (isset($_SESSION['users'])): ?>
                            <button class="btn btn-success btn-lg" 
                                    onclick="showInterestModal(<?= $land['id'] ?>)">
                                <i class="fas fa-handshake"></i> Express Interest
                            </button>
                        <?php else: ?>
                            <a href="../login.php" class="btn btn-outline-primary btn-lg">
                                Login to Express Interest
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include the same modal from land_listing.php -->
<?php include 'includes/footer.php'; ?>