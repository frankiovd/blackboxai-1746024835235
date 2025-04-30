<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Ensure user is logged in and is a trainer
requireRole('trainer');

$errors = [];
$success = false;

try {
    $db = getDBConnection();
    
    // Get trainer ID
    $stmt = $db->prepare("SELECT id FROM trainers WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $trainer_id = $stmt->fetchColumn();

    // Get list of trainer's clients
    $stmt = $db->prepare("
        SELECT c.id, u.username 
        FROM clients c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.trainer_id = ?
        ORDER BY u.username
    ");
    $stmt->execute([$trainer_id]);
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate CSRF token
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $errors[] = "Invalid request";
        } else {
            // Validate input
            $client_id = filter_input(INPUT_POST, 'client_id', FILTER_VALIDATE_INT);
            $name = sanitizeInput($_POST['name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            $start_date = $_POST['start_date'] ?? '';
            $end_date = $_POST['end_date'] ?? '';
            $exercises = $_POST['exercises'] ?? [];

            if (!$client_id) {
                $errors[] = "Please select a client";
            }
            if (empty($name)) {
                $errors[] = "Plan name is required";
            }
            if (empty($start_date) || empty($end_date)) {
                $errors[] = "Start and end dates are required";
            }
            if (strtotime($end_date) < strtotime($start_date)) {
                $errors[] = "End date must be after start date";
            }
            if (empty($exercises)) {
                $errors[] = "At least one exercise is required";
            }

            if (empty($errors)) {
                try {
                    $db->beginTransaction();

                    // Insert workout plan
                    $stmt = $db->prepare("
                        INSERT INTO workout_plans (trainer_id, client_id, name, description, start_date, end_date) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$trainer_id, $client_id, $name, $description, $start_date, $end_date]);
                    $plan_id = $db->lastInsertId();

                    // Insert exercises
                    $stmt = $db->prepare("
                        INSERT INTO workout_exercises (plan_id, exercise_name, sets, reps, day_of_week, notes) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    foreach ($exercises as $exercise) {
                        $stmt->execute([
                            $plan_id,
                            $exercise['name'],
                            $exercise['sets'],
                            $exercise['reps'],
                            $exercise['day'],
                            $exercise['notes'] ?? ''
                        ]);
                    }

                    $db->commit();
                    $success = true;
                    setFlashMessage('success', 'Workout plan created successfully!');
                    header("Location: dashboard.php");
                    exit();
                } catch (PDOException $e) {
                    $db->rollBack();
                    $errors[] = "Failed to create workout plan. Please try again.";
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
    <title>Create Workout Plan - Gym Management System</title>
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
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Create New Workout Plan</h2>

                <?php if (!empty($errors)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                        <ul class="list-disc list-inside">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="workoutForm" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                    <!-- Basic Information -->
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <label for="client_id" class="block text-sm font-medium text-gray-700">Client</label>
                            <select id="client_id" name="client_id" required
                                    class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Client</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?php echo $client['id']; ?>"
                                            <?php echo (isset($_POST['client_id']) && $_POST['client_id'] == $client['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($client['username']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Plan Name</label>
                            <input type="text" id="name" name="name" required
                                   class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                        </div>

                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                            <input type="date" id="start_date" name="start_date" required
                                   class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                   value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : ''; ?>">
                        </div>

                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                            <input type="date" id="end_date" name="end_date" required
                                   class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                   value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : ''; ?>">
                        </div>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea id="description" name="description" rows="3"
                                  class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>

                    <!-- Exercises Section -->
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Exercises</h3>
                        <div id="exercises-container" class="space-y-4">
                            <!-- Exercise template will be added here -->
                        </div>
                        <button type="button" id="add-exercise"
                                class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                            <i class="fas fa-plus mr-2"></i> Add Exercise
                        </button>
                    </div>

                    <div class="border-t border-gray-200 pt-6">
                        <button type="submit"
                                class="w-full md:w-auto inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Create Workout Plan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const exercisesContainer = document.getElementById('exercises-container');
            const addExerciseButton = document.getElementById('add-exercise');
            let exerciseCount = 0;

            function createExerciseElement() {
                const exerciseDiv = document.createElement('div');
                exerciseDiv.className = 'exercise-item bg-gray-50 p-4 rounded-lg';
                exerciseDiv.innerHTML = `
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Exercise Name</label>
                            <input type="text" name="exercises[${exerciseCount}][name]" required
                                   class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Sets</label>
                            <input type="number" name="exercises[${exerciseCount}][sets]" required min="1"
                                   class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Reps</label>
                            <input type="number" name="exercises[${exerciseCount}][reps]" required min="1"
                                   class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Day</label>
                            <select name="exercises[${exerciseCount}][day]" required
                                    class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                                <option value="Sunday">Sunday</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea name="exercises[${exerciseCount}][notes]" rows="2"
                                  class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                    <button type="button" class="remove-exercise mt-2 text-red-600 hover:text-red-800">
                        <i class="fas fa-trash"></i> Remove Exercise
                    </button>
                `;

                exercisesContainer.appendChild(exerciseDiv);
                exerciseCount++;

                // Add remove functionality
                exerciseDiv.querySelector('.remove-exercise').addEventListener('click', function() {
                    exerciseDiv.remove();
                });
            }

            addExerciseButton.addEventListener('click', createExerciseElement);

            // Add initial exercise
            createExerciseElement();

            // Form validation
            document.getElementById('workoutForm').addEventListener('submit', function(e) {
                if (exercisesContainer.children.length === 0) {
                    e.preventDefault();
                    alert('Please add at least one exercise to the workout plan.');
                }
            });
        });
    </script>
</body>
</html>
