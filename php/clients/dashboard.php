<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Ensure user is logged in and is a client
requireRole('client');

try {
    $db = getDBConnection();
    
    // Get client details
    $stmt = $db->prepare("
        SELECT c.*, u.username, u.email, 
               t.id as trainer_id, tu.username as trainer_name
        FROM clients c 
        JOIN users u ON c.user_id = u.id 
        LEFT JOIN trainers t ON c.trainer_id = t.id
        LEFT JOIN users tu ON t.user_id = tu.id
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get active workout plan
    $stmt = $db->prepare("
        SELECT * FROM workout_plans 
        WHERE client_id = ? 
        AND end_date >= CURRENT_DATE 
        ORDER BY start_date DESC 
        LIMIT 1
    ");
    $stmt->execute([$client['id']]);
    $activeWorkoutPlan = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get active diet plan
    $stmt = $db->prepare("
        SELECT * FROM diet_plans 
        WHERE client_id = ? 
        AND end_date >= CURRENT_DATE 
        ORDER BY start_date DESC 
        LIMIT 1
    ");
    $stmt->execute([$client['id']]);
    $activeDietPlan = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get recent progress entries
    $stmt = $db->prepare("
        SELECT * FROM progress_tracking 
        WHERE client_id = ? 
        ORDER BY date DESC 
        LIMIT 5
    ");
    $stmt->execute([$client['id']]);
    $recentProgress = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - Gym Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <span class="text-gray-700 mr-4">
                        Welcome, <?php echo htmlspecialchars($client['username']); ?>
                    </span>
                    <a href="../auth/logout.php" class="text-gray-700 hover:text-gray-900">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Trainer Info -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Your Trainer</h3>
                <?php if ($client['trainer_id']): ?>
                    <p class="text-gray-600">
                        <i class="fas fa-user-tie mr-2"></i>
                        <?php echo htmlspecialchars($client['trainer_name']); ?>
                    </p>
                <?php else: ?>
                    <p class="text-gray-600">No trainer assigned yet</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Current Plans -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
            <!-- Workout Plan -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Current Workout Plan</h3>
                    <?php if ($activeWorkoutPlan): ?>
                        <div class="space-y-4">
                            <h4 class="font-medium text-gray-700">
                                <?php echo htmlspecialchars($activeWorkoutPlan['name']); ?>
                            </h4>
                            <p class="text-gray-600">
                                <?php echo htmlspecialchars($activeWorkoutPlan['description']); ?>
                            </p>
                            <div class="text-sm text-gray-500">
                                <p>Start: <?php echo formatDate($activeWorkoutPlan['start_date']); ?></p>
                                <p>End: <?php echo formatDate($activeWorkoutPlan['end_date']); ?></p>
                            </div>
                            <a href="view_workout.php?id=<?php echo $activeWorkoutPlan['id']; ?>" 
                               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                View Details
                            </a>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-600">No active workout plan</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Diet Plan -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Current Diet Plan</h3>
                    <?php if ($activeDietPlan): ?>
                        <div class="space-y-4">
                            <h4 class="font-medium text-gray-700">
                                <?php echo htmlspecialchars($activeDietPlan['name']); ?>
                            </h4>
                            <p class="text-gray-600">
                                <?php echo htmlspecialchars($activeDietPlan['description']); ?>
                            </p>
                            <div class="text-sm text-gray-500">
                                <p>Daily Calories: <?php echo htmlspecialchars($activeDietPlan['total_calories']); ?></p>
                                <p>Start: <?php echo formatDate($activeDietPlan['start_date']); ?></p>
                                <p>End: <?php echo formatDate($activeDietPlan['end_date']); ?></p>
                            </div>
                            <a href="view_diet.php?id=<?php echo $activeDietPlan['id']; ?>" 
                               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                                View Details
                            </a>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-600">No active diet plan</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Progress Tracking -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Progress Tracking</h3>
                    <a href="add_progress.php" 
                       class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i> Add Progress
                    </a>
                </div>

                <?php if (!empty($recentProgress)): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Weight</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Body Fat %</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($recentProgress as $progress): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo formatDate($progress['date']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $progress['weight']; ?> kg
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $progress['body_fat']; ?>%
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <a href="view_progress.php?id=<?php echo $progress['id']; ?>" 
                                           class="text-blue-600 hover:text-blue-900">
                                            View Details
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-6">
                        <canvas id="progressChart"></canvas>
                    </div>
                <?php else: ?>
                    <p class="text-gray-600">No progress entries yet</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="view_workouts.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <i class="fas fa-dumbbell mr-2"></i> All Workouts
                    </a>
                    <a href="view_diets.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                        <i class="fas fa-utensils mr-2"></i> All Diet Plans
                    </a>
                    <a href="profile.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gray-600 hover:bg-gray-700">
                        <i class="fas fa-user mr-2"></i> Edit Profile
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
    <?php if (!empty($recentProgress)): ?>
    // Progress Chart
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('progressChart').getContext('2d');
        const dates = <?php echo json_encode(array_map(function($p) { 
            return formatDate($p['date']); 
        }, array_reverse($recentProgress))); ?>;
        const weights = <?php echo json_encode(array_map(function($p) { 
            return $p['weight']; 
        }, array_reverse($recentProgress))); ?>;
        const bodyFats = <?php echo json_encode(array_map(function($p) { 
            return $p['body_fat']; 
        }, array_reverse($recentProgress))); ?>;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [{
                    label: 'Weight (kg)',
                    data: weights,
                    borderColor: 'rgb(59, 130, 246)',
                    tension: 0.1
                }, {
                    label: 'Body Fat %',
                    data: bodyFats,
                    borderColor: 'rgb(239, 68, 68)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: false
                    }
                }
            }
        });
    });
    <?php endif; ?>
    </script>
</body>
</html>
