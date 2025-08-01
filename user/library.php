<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session and includes
session_start();
require __DIR__ . '/../vendor/autoload.php';
include 'includes/db_connect.php';

// Restrict access to logged in users only
if (!isset($_SESSION['users'])) {
    header("Location: ../login.php");
    exit();
}

// Get user's education information with defaults
$user_id = $_SESSION['users']['id'];
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

// Set defaults if fields don't exist
$user_level = $user['education_level'] ?? 1; // Default to primary level
$user_class = $user['class_id'] ?? null;
$user_semester = $user['semester_id'] ?? null;

// Fetch levels, classes, semesters, and subjects
$levels = $conn->query("SELECT * FROM levels ORDER BY id")->fetch_all(MYSQLI_ASSOC);
$classes = $conn->query("SELECT c.*, l.name as level_name FROM classes c JOIN levels l ON c.level_id = l.id ORDER BY l.id, c.numeric_value")->fetch_all(MYSQLI_ASSOC);
$semesters = $conn->query("SELECT s.*, l.name as level_name FROM semesters s JOIN levels l ON s.level_id = l.id ORDER BY l.id, s.numeric_value")->fetch_all(MYSQLI_ASSOC);
$subjects = $conn->query("SELECT * FROM subjects ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Fetch resources based on user's level and class/semester
$where = ["r.is_active = 1"];
$params = [];
$types = '';

if ($user_level) {
    $where[] = "r.level_id = ?";
    $params[] = $user_level;
    $types .= 'i';
    
    // For primary/secondary students, filter by class
    if (($user_level == 1 || $user_level == 2) && $user_class) {
        $where[] = "r.class_id = ?";
        $params[] = $user_class;
        $types .= 'i';
    }
    // For tertiary students, filter by semester
    elseif ($user_level == 3 && $user_semester) {
        $where[] = "r.semester_id = ?";
        $params[] = $user_semester;
        $types .= 'i';
    }
}

// Apply subject filter if selected and not "All Subjects"
if (isset($_GET['subject']) && $_GET['subject'] !== '') {
    $where[] = "r.subject_id = ?";
    $params[] = (int)$_GET['subject'];
    $types .= 'i';
}

$whereClause = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

$query = "SELECT r.*, l.name as level_name, 
          c.name as class_name, s.name as subject_name, 
          sm.name as semester_name, u.username as uploaded_by_name
          FROM resources r
          JOIN levels l ON r.level_id = l.id
          LEFT JOIN classes c ON r.class_id = c.id
          LEFT JOIN semesters sm ON r.semester_id = sm.id
          LEFT JOIN subjects s ON r.subject_id = s.id
          JOIN users u ON r.uploaded_by = u.id
          $whereClause
          ORDER BY r.upload_date DESC";

$stmt = $conn->prepare($query);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$resources = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Educational Resources | Student Portal</title>
    <link rel="stylesheet" href="/smart-printing-system/assets/css/style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
    main {
      min-height: 80vh;
      padding: 40px 20px;
      background: #f8f9fa;
    }
    
    .resources-header {
      text-align: center;
      margin-bottom: 30px;
    }
    
    .resources-header h2 {
      color: #0a3d62;
      margin-bottom: 10px;
    }
    
    .resources-header p {
      color: #666;
    }
    
    .filter-card {
      background: white;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      margin-bottom: 30px;
    }
    
    .form-group {
      margin-bottom: 15px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: #0a3d62;
    }
    
    .form-control {
      width: 100%;
      padding: 10px 15px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 1rem;
      transition: all 0.3s ease;
    }
    
    .form-control:focus {
      border-color: #0a3d62;
      outline: none;
      box-shadow: 0 0 0 3px rgba(10, 61, 98, 0.2);
    }
    
    .resource-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 20px;
    }
    
    .resource-card {
      background: white;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      overflow: hidden;
      transition: all 0.3s ease;
    }
    
    .resource-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    }
    
    .resource-card img {
      width: 100%;
      height: 180px;
      object-fit: cover;
    }
    
    .resource-card-body {
      padding: 20px;
    }
    
    .resource-card-title {
      font-size: 1.2rem;
      margin-bottom: 10px;
      color: #0a3d62;
    }
    
    .resource-card-meta {
      font-size: 0.9rem;
      color: #666;
      margin-bottom: 15px;
    }
    
    .resource-card-meta p {
      margin-bottom: 8px;
    }
    
    .resource-card-meta strong {
      color: #333;
    }
    
    .resource-card-actions {
      display: flex;
      gap: 10px;
      margin-top: 15px;
    }
    
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 16px;
      border-radius: 6px;
      font-size: 0.9rem;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s ease;
    }
    
    .btn-view {
      background-color: #0a3d62;
      color: white;
    }
    
    .btn-download {
      background-color: #28a745;
      color: white;
    }
    
    .btn:hover {
      opacity: 0.9;
      transform: translateY(-2px);
    }
    
    .empty-state {
      text-align: center;
      padding: 40px 20px;
      background: white;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    
    .empty-state i {
      font-size: 3rem;
      color: #0a3d62;
      margin-bottom: 15px;
    }
    
    .empty-state h3 {
      margin-bottom: 10px;
      color: #0a3d62;
    }
    
    .empty-state p {
      color: #666;
      margin-bottom: 20px;
    }
    
    .btn-primary {
      background-color: #0a3d62;
      color: white;
      padding: 10px 20px;
    }
    
    @media (max-width: 768px) {
      .resource-grid {
        grid-template-columns: 1fr;
      }
      
      main {
        padding: 20px 15px;
      }
    }
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<main>
    <div class="resources-header">
        <h2><i class="fas fa-book"></i> Educational Resources Library</h2>
        <p>Access learning materials for your level</p>
    </div>

    <div class="filter-card">
        <form method="GET">
            <div class="form-group">
                <label for="filter_subject">Filter by Subject</label>
                <select id="filter_subject" name="subject" class="form-control" onchange="this.form.submit()">
                    <option value="">All Subjects</option>
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?= $subject['id'] ?>" <?= isset($_GET['subject']) && $_GET['subject'] == $subject['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($subject['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
    
    <?php if (empty($resources)): ?>
        <div class="empty-state">
            <i class="fas fa-book-open"></i>
            <h3>No Resources Available</h3>
            <p>There are currently no resources available for your level.</p>
            <?php if (isset($_GET['subject']) || $whereClause): ?>
                <a href="library.php" class="btn btn-primary">
                    <i class="fas fa-undo"></i> Clear Filters
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="resource-grid">
            <?php foreach ($resources as $resource): ?>
                <div class="resource-card">
                    <?php if (in_array($resource['file_type'], ['jpg', 'jpeg', 'png', 'gif'])): ?>
                        <img src="<?= htmlspecialchars($resource['file_path']) ?>" alt="<?= htmlspecialchars($resource['title']) ?>">
                    <?php else: ?>
                        <div style="background: #f0f0f0; height: 180px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-file-alt fa-5x" style="color: #0a3d62;"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="resource-card-body">
                        <h3 class="resource-card-title"><?= htmlspecialchars($resource['title']) ?></h3>
                        
                        <div class="resource-card-meta">
                            <p><strong>Level:</strong> <?= htmlspecialchars($resource['level_name']) ?></p>
                            
                            <?php if ($resource['class_name']): ?>
                                <p><strong>Class:</strong> <?= htmlspecialchars($resource['class_name']) ?></p>
                            <?php endif; ?>
                            
                            <?php if ($resource['semester_name']): ?>
                                <p><strong>Semester:</strong> <?= htmlspecialchars($resource['semester_name']) ?></p>
                            <?php endif; ?>
                            
                            <?php if ($resource['subject_name']): ?>
                                <p><strong>Subject:</strong> <?= htmlspecialchars($resource['subject_name']) ?></p>
                            <?php endif; ?>
                            
                            <p><strong>Uploaded by:</strong> <?= htmlspecialchars($resource['uploaded_by_name']) ?></p>
                            <p><strong>Date:</strong> <?= date('M j, Y', strtotime($resource['upload_date'])) ?></p>
                        </div>
                        
                      <div class="resource-card-actions">
    <?php if (!empty($resource['file_path'])): ?>
        <!-- View Button (for viewable file types) -->
        <?php if (in_array(strtolower(pathinfo($resource['file_path'], PATHINFO_EXTENSION)), ['pdf', 'jpg', 'jpeg', 'png', 'gif'])): ?>
            <a href="/smart-printing-system/uploads/resources/<?= htmlspecialchars($resource['file_path']) ?>" 
               class="btn btn-view" 
               target="_blank">
                <i class="fas fa-eye"></i> View
            </a>
        <?php endif; ?>
        
        <!-- Download Button (for all file types) -->
        <a href="/smart-printing-system/uploads/resources/<?= htmlspecialchars($resource['file_path']) ?>" 
           class="btn btn-download" 
           download="<?= htmlspecialchars($resource['title'] . '.' . pathinfo($resource['file_path'], PATHINFO_EXTENSION)) ?>">
            <i class="fas fa-download"></i> Download
        </a>
    <?php endif; ?>
</div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>

</body>
</html>