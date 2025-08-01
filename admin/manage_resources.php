<?php
// Module 1: Session and Dependencies
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require __DIR__ . '/../vendor/autoload.php';
include '../user/includes/db_connect.php';
include 'includes/functions.php';
include 'includes/load_settings.php';

// Restrict access to admin only
if (!isset($_SESSION['users']) || strtolower($_SESSION['users']['role']) !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Module 2: Initialize Variables
$statusMsg = '';
$levels = $classes = $semesters = $subjects = [];

// Module 3: Data Fetching
$levels = $conn->query("SELECT * FROM levels ORDER BY id")->fetch_all(MYSQLI_ASSOC);
$classes = $conn->query("SELECT c.*, l.name as level_name FROM classes c JOIN levels l ON c.level_id = l.id ORDER BY l.id, c.numeric_value")->fetch_all(MYSQLI_ASSOC);
$semesters = $conn->query("SELECT s.*, l.name as level_name FROM semesters s JOIN levels l ON s.level_id = l.id ORDER BY l.id, s.numeric_value")->fetch_all(MYSQLI_ASSOC);
$subjects = $conn->query("SELECT * FROM subjects ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Module 4: Resource Upload Handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'upload_resource') {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $level_id = intval($_POST['level_id']);
        $class_id = !empty($_POST['class_id']) ? intval($_POST['class_id']) : NULL;
        $semester_id = !empty($_POST['semester_id']) ? intval($_POST['semester_id']) : NULL;
        $subject_id = !empty($_POST['subject_id']) ? intval($_POST['subject_id']) : NULL;
        
        // Validate inputs
        if (empty($title) || empty($_FILES['resource_file']['name'])) {
            $statusMsg = "Title and file are required!";
        } else {
            // Handle file upload
            $uploadDir = '../uploads/library/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $originalFilename = basename($_FILES['resource_file']['name']);
            $fileExtension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
            $newFilename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9-_\.]/', '', $originalFilename);
            $targetPath = $uploadDir . $newFilename;
            
            // Allowed file types
            $allowedTypes = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'txt'];
            
            if (!in_array($fileExtension, $allowedTypes)) {
                $statusMsg = "File type not allowed!";
            } elseif (move_uploaded_file($_FILES['resource_file']['tmp_name'], $targetPath)) {
                // Insert into database
                $fileSize = filesize($targetPath);
                $stmt = $conn->prepare("INSERT INTO resources (title, description, file_path, original_filename, file_size, file_type, level_id, class_id, semester_id, subject_id, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssisiiiii", $title, $description, $targetPath, $originalFilename, $fileSize, $fileExtension, $level_id, $class_id, $semester_id, $subject_id, $_SESSION['users']['id']);
                
                if ($stmt->execute()) {
                    $statusMsg = "Resource uploaded successfully!";
                } else {
                    $statusMsg = "Failed to save resource details: " . $conn->error;
                    // Delete the uploaded file if DB insert failed
                    unlink($targetPath);
                }
            } else {
                $statusMsg = "Failed to upload file!";
            }
        }
    }
    // Handle resource deletion
    elseif ($_POST['action'] === 'delete_resource') {
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        // Get file path before deleting
        $stmt = $conn->prepare("SELECT file_path FROM resources WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $resource = $result->fetch_assoc();
        
        if ($resource) {
            // Delete from database
            $stmt = $conn->prepare("DELETE FROM resources WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                // Delete the actual file
                if (file_exists($resource['file_path'])) {
                    unlink($resource['file_path']);
                }
                $statusMsg = "Resource deleted successfully!";
            } else {
                $statusMsg = "Error deleting resource: " . $conn->error;
            }
        } else {
            $statusMsg = "Resource not found!";
        }
    }
}

// Module 5: Filter Resources
$where = [];
$params = [];
$types = '';

if (!empty($_GET['level'])) {
    $where[] = "r.level_id = ?";
    $params[] = intval($_GET['level']);
    $types .= 'i';
}

if (!empty($_GET['class'])) {
    $where[] = "r.class_id = ?";
    $params[] = intval($_GET['class']);
    $types .= 'i';
}

if (!empty($_GET['semester'])) {
    $where[] = "r.semester_id = ?";
    $params[] = intval($_GET['semester']);
    $types .= 'i';
}

if (!empty($_GET['subject'])) {
    $where[] = "r.subject_id = ?";
    $params[] = intval($_GET['subject']);
    $types .= 'i';
}

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

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
    <title>Manage Educational Resources | Smart Printing</title>
    <link rel="stylesheet" href="/smart-printing-system/assets/css/admin_style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        :root {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --success: #2ecc71;
            --danger: #e74c3c;
            --warning: #f39c12;
            --light: #f8f9fa;
            --dark: #343a40;
            --gray: #6c757d;
        }
        
        .dashboard-header {
            color: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .form-container {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .form-container .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-container label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .form-container input,
        .form-container select,
        .form-container textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        
        .form-container input:focus,
        .form-container select:focus,
        .form-container textarea:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        .form-container button {
            padding: 10px 20px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.2s;
        }
        
        .form-container button:hover {
            background: var(--primary-dark);
        }
        
        .resources-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .resources-table th, 
        .resources-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .resources-table th {
            background: var(--primary);
            color: #fff;
        }
        
        .resource-card {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 10px;
            border-radius: 5px;
            background: #f9f9f9;
            margin: 5px 0;
        }
        
        .resource-icon {
            font-size: 24px;
            color: var(--primary);
        }
        
        .resource-info {
            flex: 1;
        }
        
        .resource-title {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--dark);
        }
        
        .resource-meta {
            font-size: 0.85rem;
            color: var(--gray);
        }
        
        .resource-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 5px 10px;
            border-radius: 4px;
            color: white;
            text-decoration: none;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-view {
            background: var(--primary);
        }
        
        .btn-download {
            background: var(--success);
        }
        
        .btn-delete {
            background: var(--danger);
        }
        
        .status-msg {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }
        
        .status-msg.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-msg.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .filter-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .filter-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .filter-group {
            flex: 1;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--gray);
        }
        
        .empty-state i {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 15px;
        }
        
        .dynamic-select {
            display: none;
        }
        
        .dynamic-select.active {
            display: block;
        }
        
        @media (max-width: 768px) {
            .filter-row {
                flex-direction: column;
                gap: 10px;
            }
            
            .resource-actions {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <img src="<?php echo htmlspecialchars($settings['logo_path']); ?>" alt="Logo" class="logo-img">
                <span><?php echo htmlspecialchars($settings['business_name']); ?></span>
            </div>
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="manage_lessons.php"><i class="fas fa-book"></i> Lessons</a>
            <a href="manage_prices.php"><i class="fas fa-tag"></i> Prices</a>
            <a href="manage_services.php"><i class="fas fa-cogs"></i> Services</a>
            <a href="manage_uploads.php" class="active"><i class="fas fa-print"></i> Printing</a>
            <a href="manage_students.php"><i class="fas fa-user-graduate"></i> Students</a>
            <a href="manage_transactions.php"><i class="fas fa-credit-card"></i> Transactions</a>
            <a href="manage_users.php"><i class="fas fa-users"></i> Users</a>
            <a href="reports.php"><i class="fas fa-chart-line"></i> Reports</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            <a href="../logout.php" style="color: red;" onclick="return confirm('Are you sure you want to logout?');">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <header class="dashboard-header">
                <div class="header-content">
                    <h1><i class="fas fa-book"></i> Educational Resources Library</h1>
                    <p>Manage and organize educational materials for all levels</p>
                </div>
                <div class="user-profile">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo htmlspecialchars($_SESSION['users']['username']); ?></span>
                </div>
            </header>

            <?php if (!empty($statusMsg)): ?>
                <div class="status-msg <?php echo strpos($statusMsg, 'success') !== false ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($statusMsg); ?>
                </div>
            <?php endif; ?>

            <!-- Upload Resource Form -->
            <div class="form-container">
                <h2><i class="fas fa-upload"></i> Upload New Resource</h2>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_resource" />
                    
                    <div class="form-group">
                        <label for="title">Resource Title</label>
                        <input type="text" id="title" name="title" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="level_id">Education Level</label>
                        <select id="level_id" name="level_id" class="form-control" required onchange="updateClassSemesterSelects()">
                            <option value="">-- Select Level --</option>
                            <?php foreach ($levels as $level): ?>
                                <option value="<?= $level['id'] ?>"><?= htmlspecialchars($level['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div id="class-select" class="form-group dynamic-select">
                        <label for="class_id">Class</label>
                        <select id="class_id" name="class_id" class="form-control">
                            <option value="">-- Select Class --</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= $class['id'] ?>" data-level="<?= $class['level_id'] ?>">
                                    <?= htmlspecialchars($class['level_name'] . ' - ' . $class['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div id="semester-select" class="form-group dynamic-select">
                        <label for="semester_id">Semester</label>
                        <select id="semester_id" name="semester_id" class="form-control">
                            <option value="">-- Select Semester --</option>
                            <?php foreach ($semesters as $semester): ?>
                                <option value="<?= $semester['id'] ?>" data-level="<?= $semester['level_id'] ?>">
                                    <?= htmlspecialchars($semester['level_name'] . ' - ' . $semester['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="subject_id">Subject (Optional)</label>
                        <select id="subject_id" name="subject_id" class="form-control">
                            <option value="">-- Select Subject --</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= $subject['id'] ?>"><?= htmlspecialchars($subject['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="resource_file">Resource File</label>
                        <input type="file" id="resource_file" name="resource_file" class="form-control" required>
                        <small class="text-muted">Allowed formats: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, JPG, JPEG, PNG, TXT</small>
                    </div>
                    
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-upload"></i> Upload Resource
                    </button>
                </form>
            </div>

            <!-- Resources Filter -->
            <div class="filter-card">
                <h3><i class="fas fa-filter"></i> Filter Resources</h3>
                <form method="GET">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="filter_level">Level</label>
                            <select id="filter_level" name="level" class="form-control" onchange="this.form.submit()">
                                <option value="">All Levels</option>
                                <?php foreach ($levels as $level): ?>
                                    <option value="<?= $level['id'] ?>" <?= isset($_GET['level']) && $_GET['level'] == $level['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($level['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="filter_class">Class</label>
                            <select id="filter_class" name="class" class="form-control" onchange="this.form.submit()">
                                <option value="">All Classes</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?= $class['id'] ?>" <?= isset($_GET['class']) && $_GET['class'] == $class['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($class['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="filter_semester">Semester</label>
                            <select id="filter_semester" name="semester" class="form-control" onchange="this.form.submit()">
                                <option value="">All Semesters</option>
                                <?php foreach ($semesters as $semester): ?>
                                    <option value="<?= $semester['id'] ?>" <?= isset($_GET['semester']) && $_GET['semester'] == $semester['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($semester['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="filter_subject">Subject</label>
                            <select id="filter_subject" name="subject" class="form-control" onchange="this.form.submit()">
                                <option value="">All Subjects</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?= $subject['id'] ?>" <?= isset($_GET['subject']) && $_GET['subject'] == $subject['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($subject['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Resources Table -->
            <h3><i class="fas fa-book-open"></i> Resource Library</h3>
            
            <?php if (empty($resources)): ?>
                <div class="empty-state">
                    <i class="fas fa-book fa-3x"></i>
                    <h3>No Resources Found</h3>
                    <p>There are no resources matching your criteria.</p>
                    <?php if ($whereClause): ?>
                        <a href="manage_resources.php" class="btn btn-primary" style="margin-top: 15px;">
                            <i class="fas fa-undo"></i> Clear Filters
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <table class="resources-table">
                    <thead>
                        <tr>
                            <th>Resource</th>
                            <th>Details</th>
                            <th>Uploaded</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resources as $resource): ?>
                            <tr>
                                <td>
                                    <div class="resource-card">
                                        <div class="resource-icon">
                                            <i class="fas fa-file-<?php 
                                                echo $resource['file_type'] === 'pdf' ? 'pdf' : 
                                                    (in_array($resource['file_type'], ['jpg','jpeg','png','gif']) ? 'image' : 'alt');
                                            ?>"></i>
                                        </div>
                                        <div class="resource-info">
                                            <div class="resource-title"><?= htmlspecialchars($resource['title']) ?></div>
                                            <div class="resource-meta">
                                                <?= htmlspecialchars($resource['original_filename']) ?>
                                                <br>
                                                <?= round($resource['file_size'] / 1024) ?> KB
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="resource-meta">
                                        <strong>Level:</strong> <?= htmlspecialchars($resource['level_name']) ?><br>
                                        <?php if ($resource['class_name']): ?>
                                            <strong>Class:</strong> <?= htmlspecialchars($resource['class_name']) ?><br>
                                        <?php endif; ?>
                                        <?php if ($resource['semester_name']): ?>
                                            <strong>Semester:</strong> <?= htmlspecialchars($resource['semester_name']) ?><br>
                                        <?php endif; ?>
                                        <?php if ($resource['subject_name']): ?>
                                            <strong>Subject:</strong> <?= htmlspecialchars($resource['subject_name']) ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="resource-meta">
                                        <?= date('M j, Y', strtotime($resource['upload_date'])) ?><br>
                                        <small>by <?= htmlspecialchars($resource['uploaded_by_name']) ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="resource-actions">
                                        <a href="view_document.php?id=<?= $resource['id'] ?>" class="btn btn-view" target="_blank">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="download_document.php?id=<?= $resource['id'] ?>" class="btn btn-download">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="delete_resource">
                                            <input type="hidden" name="id" value="<?= $resource['id'] ?>">
                                            <button type="submit" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this resource?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        function updateClassSemesterSelects() {
            const levelSelect = document.getElementById('level_id');
            const classSelect = document.getElementById('class-select');
            const semesterSelect = document.getElementById('semester-select');
            
            const selectedLevel = levelSelect.value;
            
            // Hide both initially
            classSelect.classList.remove('active');
            semesterSelect.classList.remove('active');
            
            // Show appropriate select based on level
            if (selectedLevel) {
                // Assuming level_id 1 is Primary, 2 is Secondary, 3 is Tertiary
                if (selectedLevel == 1 || selectedLevel == 2) {
                    classSelect.classList.add('active');
                    
                    // Filter class options
                    const classOptions = document.querySelectorAll('#class_id option[data-level]');
                    classOptions.forEach(option => {
                        option.style.display = option.getAttribute('data-level') == selectedLevel ? 'block' : 'none';
                    });
                    
                    // Reset selection
                    document.getElementById('class_id').value = '';
                } else if (selectedLevel == 3) {
                    semesterSelect.classList.add('active');
                    
                    // Filter semester options
                    const semesterOptions = document.querySelectorAll('#semester_id option[data-level]');
                    semesterOptions.forEach(option => {
                        option.style.display = option.getAttribute('data-level') == selectedLevel ? 'block' : 'none';
                    });
                    
                    // Reset selection
                    document.getElementById('semester_id').value = '';
                }
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateClassSemesterSelects();
        });
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>