<?php
session_start();
include 'includes/db_connect.php';

// Redirect if already completed profile
if (isset($_SESSION['users']['education_level'])) {
    header("Location: library.php");
    exit();
}

// Fetch available levels with error handling
$levels = $conn->query("SELECT * FROM levels ORDER BY id")->fetch_all(MYSQLI_ASSOC);

// If no levels exist, show an error
if (empty($levels)) {
    die("<h2>System Configuration Error</h2>
        <p>No education levels are configured in the system.</p>
        <p>Please contact the administrator to set up education levels.</p>");
}

// Form processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $level = intval($_POST['education_level']);
    $class = isset($_POST['class_id']) ? intval($_POST['class_id']) : null;
    $semester = isset($_POST['semester_id']) ? intval($_POST['semester_id']) : null;
    
    // Validate inputs
    $validLevels = array_column($levels, 'id');
    if (!in_array($level, $validLevels)) {
        $error = "Please select a valid education level";
    } else {
        // Additional validation based on level
        if (($level == 1 || $level == 2) && empty($class)) {
            $error = "Please select your class";
        } elseif ($level == 3 && empty($semester)) {
            $error = "Please select your semester";
        } else {
            // Update database
            $stmt = $conn->prepare("UPDATE users SET education_level = ?, class_id = ?, semester_id = ? WHERE id = ?");
            $stmt->bind_param("iiii", $level, $class, $semester, $_SESSION['users']['id']);
            
            if ($stmt->execute()) {
                // Update session data
                $_SESSION['users']['education_level'] = $level;
                $_SESSION['users']['class_id'] = $class;
                $_SESSION['users']['semester_id'] = $semester;
                
                header("Location: profile_complete.php");
                exit();
            } else {
                $error = "Error updating profile: " . $conn->error;
            }
        }
    }
}

// Fetch classes and semesters
$classes = $conn->query("
    SELECT c.*, l.name as level_name 
    FROM classes c 
    JOIN levels l ON c.level_id = l.id 
    ORDER BY c.level_id, c.numeric_value
")->fetch_all(MYSQLI_ASSOC);

$semesters = $conn->query("
    SELECT s.*, l.name as level_name 
    FROM semesters s 
    JOIN levels l ON s.level_id = l.id 
    ORDER BY s.level_id, s.numeric_value
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Your Profile | Student Portal</title>
    <link rel="stylesheet" href="/smart-printing-system/assets/css/student_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: var(--dark);
            line-height: 1.6;
        }
        
        .profile-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .profile-header h1 {
            color: var(--primary);
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
        }
        
        .profile-header p {
            color: var(--gray);
            font-size: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark);
            font-size: 0.95rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #f9f9f9;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            background-color: white;
        }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .btn-block {
            display: block;
            width: 100%;
            padding: 0.85rem;
        }
        
        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .dynamic-field {
            display: none;
            animation: fadeIn 0.4s ease-out;
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 6px;
            font-size: 0.95rem;
        }
        
        .alert-danger {
            background-color: #fdecea;
            color: #d32f2f;
            border-left: 4px solid #d32f2f;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1em;
        }
        
        @media (max-width: 768px) {
            .profile-container {
                margin: 1rem;
                padding: 1.5rem;
            }
            
            .profile-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="profile-header">
            <h1><i class="fas fa-user-graduate"></i> Complete Your Profile</h1>
            <p>Please provide your educational information to access all features</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="education_level">Education Level *</label>
                <select id="education_level" name="education_level" class="form-control" required>
                    <option value="">-- Select Your Education Level --</option>
                    <?php foreach ($levels as $level): ?>
                        <option value="<?= $level['id'] ?>">
                            <?= htmlspecialchars($level['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div id="primary-classes" class="form-group dynamic-field">
                <label for="primary_class">Standard *</label>
                <select id="primary_class" name="class_id" class="form-control">
                    <option value="">-- Select Your Standard --</option>
                    <?php foreach ($classes as $class): ?>
                        <?php if ($class['level_id'] == 1): ?>
                            <option value="<?= $class['id'] ?>">
                                <?= htmlspecialchars($class['name']) ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div id="secondary-classes" class="form-group dynamic-field">
                <label for="secondary_class">Form *</label>
                <select id="secondary_class" name="class_id" class="form-control">
                    <option value="">-- Select Your Form --</option>
                    <?php foreach ($classes as $class): ?>
                        <?php if ($class['level_id'] == 2): ?>
                            <option value="<?= $class['id'] ?>">
                                <?= htmlspecialchars($class['name']) ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div id="tertiary-semesters" class="form-group dynamic-field">
                <label for="semester_id">Semester *</label>
                <select id="semester_id" name="semester_id" class="form-control">
                    <option value="">-- Select Your Semester --</option>
                    <?php foreach ($semesters as $semester): ?>
                        <?php if ($semester['level_id'] == 3): ?>
                            <option value="<?= $semester['id'] ?>">
                                <?= htmlspecialchars($semester['name']) ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-block">
                <i class="fas fa-save"></i> Save Profile
            </button>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const levelSelect = document.getElementById('education_level');
        const primaryField = document.getElementById('primary-classes');
        const secondaryField = document.getElementById('secondary-classes');
        const tertiaryField = document.getElementById('tertiary-semesters');
        
        // Hide all fields initially
        primaryField.style.display = 'none';
        secondaryField.style.display = 'none';
        tertiaryField.style.display = 'none';
        
        // Function to update visible fields
        function updateFields() {
            const level = levelSelect.value;
            
            // Hide all fields first
            primaryField.style.display = 'none';
            secondaryField.style.display = 'none';
            tertiaryField.style.display = 'none';
            
            // Clear any previous selections
            document.getElementById('primary_class').value = '';
            document.getElementById('secondary_class').value = '';
            document.getElementById('semester_id').value = '';
            
            // Show appropriate field based on level
            if (level === '1') {
                primaryField.style.display = 'block';
            } 
            else if (level === '2') {
                secondaryField.style.display = 'block';
            }
            else if (level === '3') {
                tertiaryField.style.display = 'block';
            }
        }
        
        // Initialize fields on page load
        updateFields();
        
        // Update fields when level changes
        levelSelect.addEventListener('change', updateFields);
    });
    </script>
</body>
</html>