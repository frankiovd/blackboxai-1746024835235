<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Ensure user is logged in and is a client
requireRole('client');

$errors = [];
$success = false;

try {
    $db = getDBConnection();
    
    // Get client ID
    $stmt = $db->prepare("SELECT id FROM clients WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $client_id = $stmt->fetchColumn();

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate CSRF token
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $errors[] = "Invalid request";
        } else {
            // Validate and sanitize input
            $date = $_POST['date'] ?? '';
            $weight = filter_input(INPUT_POST, 'weight', FILTER_VALIDATE_FLOAT);
            $body_fat = filter_input(INPUT_POST, 'body_fat', FILTER_VALIDATE_FLOAT);
            $measurements = [
                'chest' => filter_input(INPUT_POST, 'chest', FILTER_VALIDATE_FLOAT),
                'waist' => filter_input(INPUT_POST, 'waist', FILTER_VALIDATE_FLOAT),
                'hips' => filter_input(INPUT_POST, 'hips', FILTER_VALIDATE_FLOAT),
                'biceps' => filter_input(INPUT_POST, 'biceps', FILTER_VALIDATE_FLOAT),
                'thighs' => filter_input(INPUT_POST, 'thighs', FILTER_VALIDATE_FLOAT)
            ];

            // Validation
            if (empty($date)) {
                $errors[] = "Date is required";
            }
            if (!$weight || $weight <= 0) {
                $errors[] = "Valid weight is required";
            }
            if ($body_fat !== false && ($body_fat < 0 || $body_fat > 100)) {
                $errors[] = "Body fat percentage must be between 0 and 100";
            }

            // Handle photo upload
            $photo_url = null;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png'];
                $upload_dir = '../../uploads/progress/';
                
                if (!in_array($_FILES['photo']['type'], $allowed_types)) {
                    $errors[] = "Invalid file type. Only JPG and PNG are allowed.";
                } else {
                    $photo_url = uploadFile($_FILES['photo'], ['jpg', 'jpeg', 'png'], $upload_dir);
                    if (!$photo_url) {
                        $errors[] = "Failed to upload photo";
                    }
                }
            }

            if (empty($errors)) {
                try {
                    // Insert progress entry
                    $stmt = $db->prepare("
                        INSERT INTO progress_tracking (client_id, date, weight, body_fat, measurements, photo_url) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $client_id,
                        $date,
                        $weight,
                        $body_fat,
                        json_encode($measurements),
                        $photo_url
                    ]);

                    setFlashMessage('success', 'Progress entry added successfully!');
                    header("Location: dashboard.php");
                    exit();
                } catch (PDOException $e) {
                    $errors[] = "Failed to save progress entry. Please try again.";
                }
            }
        }
    }
} catch (PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Progress - Gym Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <i class="fas fa-dumbbell text-2xl text-blue-600"></i>
                        <span class="ml-2 text-xl font-bold">GymMS</span>
                    </div>
                </div>
                <div class="flex items-center">
                    <a href="dashboard.php" class="text-gray-700 hover:text-gray-900 mr-4">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                    <a href="../auth/logout.php" class="text-gray-700 hover:text-gray-900">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Add Progress Entry</h2>

                <?php if (!empty($errors)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                        <ul class="list-disc list-inside">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <label for="date" class="block text-sm font-medium text-gray-700">Date</label>
                            <input type="date" id="date" name="date" required
                                   class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                   value="<?php echo isset($_POST['date']) ? htmlspecialchars($_POST['date']) : date('Y-m-d'); ?>">
                        </div>

                        <div>
                            <label for="weight" class="block text-sm font-medium text-gray-700">Weight (kg)</label>
                            <input type="number" id="weight" name="weight" required step="0.1" min="0"
                                   class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                   value="<?php echo isset($_POST['weight']) ? htmlspecialchars($_POST['weight']) : ''; ?>">
                        </div>

                        <div>
                            <label for="body_fat" class="block text-sm font-medium text-gray-700">Body Fat %</label>
                            <input type="number" id="body_fat" name="body_fat" step="0.1" min="0" max="100"
                                   class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                   value="<?php echo isset($_POST['body_fat']) ? htmlspecialchars($_POST['body_fat']) : ''; ?>">
                        </div>

                        <div>
                            <label for="photo" class="block text-sm font-medium text-gray-700">Progress Photo</label>
                            <input type="file" id="photo" name="photo" accept="image/jpeg,image/png"
                                   class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <p class="mt-1 text-sm text-gray-500">JPG or PNG only</p>
                        </div>
                    </div>

                    <!-- Measurements -->
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Body Measurements (cm)</h3>
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                            <div>
                                <label for="chest" class="block text-sm font-medium text-gray-700">Chest</label>
                                <input type="number" id="chest" name="chest" step="0.1" min="0"
                                       class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                       value="<?php echo isset($_POST['chest']) ? htmlspecialchars($_POST['chest']) : ''; ?>">
                            </div>

                            <div>
                                <label for="waist" class="block text-sm font-medium text-gray-700">Waist</label>
                                <input type="number" id="waist" name="waist" step="0.1" min="0"
                                       class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                       value="<?php echo isset($_POST['waist']) ? htmlspecialchars($_POST['waist']) : ''; ?>">
                            </div>

                            <div>
                                <label for="hips" class="block text-sm font-medium text-gray-700">Hips</label>
                                <input type="number" id="hips" name="hips" step="0.1" min="0"
                                       class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                       value="<?php echo isset($_POST['hips']) ? htmlspecialchars($_POST['hips']) : ''; ?>">
                            </div>

                            <div>
                                <label for="biceps" class="block text-sm font-medium text-gray-700">Biceps</label>
                                <input type="number" id="biceps" name="biceps" step="0.1" min="0"
                                       class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                       value="<?php echo isset($_POST['biceps']) ? htmlspecialchars($_POST['biceps']) : ''; ?>">
                            </div>

                            <div>
                                <label for="thighs" class="block text-sm font-medium text-gray-700">Thighs</label>
                                <input type="number" id="thighs" name="thighs" step="0.1" min="0"
                                       class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                       value="<?php echo isset($_POST['thighs']) ? htmlspecialchars($_POST['thighs']) : ''; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 pt-6">
                        <button type="submit"
                                class="w-full md:w-auto inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Save Progress
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Client-side validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const weight = document.getElementById('weight').value;
            const bodyFat = document.getElementById('body_fat').value;
            let isValid = true;

            if (!weight || weight <= 0) {
                isValid = false;
                alert('Please enter a valid weight');
            }

            if (bodyFat && (bodyFat < 0 || bodyFat > 100)) {
                isValid = false;
                alert('Body fat percentage must be between 0 and 100');
            }

            if (!isValid) {
                e.preventDefault();
            }
        });

        // Preview image before upload
        document.getElementById('photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (!['image/jpeg', 'image/png'].includes(file.type)) {
                    alert('Please select a valid image file (JPG or PNG)');
                    e.target.value = '';
                } else if (file.size > 5 * 1024 * 1024) { // 5MB limit
                    alert('File size must be less than 5MB');
                    e.target.value = '';
                }
            }
        });
    </script>
</body>
</html>
