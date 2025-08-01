<?php

include '../user/includes/db_connect.php';
include 'includes/functions.php';
include 'includes/load_settings.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form submission
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $location = $conn->real_escape_string($_POST['location']);
    $size = (float)$_POST['size'];
    $price = (float)$_POST['price'];
    $status = $conn->real_escape_string($_POST['status']);
    $featured = isset($_POST['featured']) ? 1 : 0;

    // Insert land data
    $stmt = $conn->prepare("INSERT INTO lands (title, description, location, size, price, status, featured) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssddsi", $title, $description, $location, $size, $price, $status, $featured);
    $stmt->execute();
    $land_id = $stmt->insert_id;

    // Process features
    if (!empty($_POST['features'])) {
        $feature_stmt = $conn->prepare("INSERT INTO land_features (land_id, feature) VALUES (?, ?)");
        foreach ($_POST['features'] as $feature) {
            if (!empty(trim($feature))) {
                $feature_stmt->bind_param("is", $land_id, $feature);
                $feature_stmt->execute();
            }
        }
    }

    // Process images
    if (!empty($_FILES['images']['name'][0])) {
        $upload_dir = '../uploads/lands/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $primary_set = false;
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            $filename = time() . '_' . basename($_FILES['images']['name'][$key]);
            $target = $upload_dir . $filename;
            
            if (move_uploaded_file($tmp_name, $target)) {
                $is_primary = (!$primary_set && $key == 0) ? 1 : 0;
                if ($is_primary) $primary_set = true;
                
                $img_stmt = $conn->prepare("INSERT INTO land_images (land_id, image_path, is_primary) 
                                          VALUES (?, ?, ?)");
                $img_stmt->bind_param("isi", $land_id, $filename, $is_primary);
                $img_stmt->execute();
            }
        }
    }

    $_SESSION['success'] = "Land added successfully!";
    header("Location: admin_land.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Land</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/smart-printing-system/assets/css/admin.css">
</head>
<body>
  
    
    <div class="admin-container">
       
        
        <main class="admin-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Add New Land</h1>
                <a href="lands.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Back to Lands
                </a>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="land-form">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="title">Land Title*</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="location">Location*</label>
                        <input type="text" class="form-control" id="location" name="location" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="size">Size (acres)*</label>
                        <input type="number" step="0.01" class="form-control" id="size" name="size" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="price">Price (KSh)*</label>
                        <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="status">Status*</label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="available">Available</option>
                            <option value="reserved">Reserved</option>
                            <option value="sold">Sold</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="featured" name="featured">
                        <label class="form-check-label" for="featured">Featured Property</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Features (Add multiple)</label>
                    <div id="features-container">
                        <div class="input-group mb-2">
                            <input type="text" class="form-control" name="features[]">
                            <button type="button" class="btn btn-outline-secondary add-feature">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="images">Upload Images*</label>
                    <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*" required>
                    <small class="text-muted">First image will be set as primary</small>
                </div>
                
                <button type="submit" class="btn btn-primary">Add Land</button>
            </form>
        </main>
    </div>

    
    <script>
    // Add more feature fields dynamically
    document.querySelector('.add-feature').addEventListener('click', function() {
        const container = document.getElementById('features-container');
        const div = document.createElement('div');
        div.className = 'input-group mb-2';
        div.innerHTML = `
            <input type="text" class="form-control" name="features[]">
            <button type="button" class="btn btn-outline-danger remove-feature">
                <i class="fas fa-minus"></i>
            </button>
        `;
        container.appendChild(div);
    });

    // Remove feature fields
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-feature')) {
            e.target.closest('.input-group').remove();
        }
    });
    </script>
</body>
</html>