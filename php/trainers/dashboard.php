<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Ensure user is logged in and is a trainer
requireRole('trainer');

try {
    $db = getDBConnection();
    
    // Get trainer details
    $stmt = $db->prepare("
        SELECT t.*, u.username, u.email 
        FROM trainers t 
        JOIN users u ON t.user_id = u.id 
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $trainer = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get total number of clients
    $stmt = $db->prepare("SELECT COUNT(*) FROM clients WHERE trainer_id = ?");
    $stmt->execute([$trainer['id']]);
    $totalClients = $stmt->fetchColumn();

    // Get total number of active workout plans
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM workout_plans 
        WHERE trainer_id = ? 
        AND end_date >= CURRENT_DATE
    ");
    $stmt->execute([$trainer['id']]);
    $activeWorkoutPlans = $stmt->fetchColumn();

    // Get total number of active diet plans
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM diet_plans 
        WHERE trainer_id = ? 
        AND end_date >= CURRENT_DATE
    ");
    $stmt->execute([$trainer['id']]);
    $activeDietPlans = $stmt->fetchColumn();

    // Get recent clients
    $stmt = $db->prepare("
        SELECT c.*, u.username, u.email 
        FROM clients c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.trainer_id = ? 
        ORDER BY c.id DESC 
        LIMIT 5
    ");
    $stmt->execute([$trainer['id']]);
    $recentClients = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Dashboard - Gym Management System</title>
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
                        Welcome, <?php echo htmlspecialchars($trainer['username']); ?>
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
        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Total Clients -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                            <i class="fas fa-users text-white text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Clients</dt>
                                <dd class="text-3xl font-semibold text-gray-900"><?php echo $totalClients; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Workout Plans -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                            <i class="fas fa-dumbbell text-white text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Active Workout Plans</dt>
                                <dd class="text-3xl font-semibold text-gray-900"><?php echo $activeWorkoutPlans; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Diet Plans -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                            <i class="fas fa-utensils text-white text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Active Diet Plans</dt>
                                <dd class="text-3xl font-semibold text-gray-900"><?php echo $activeDietPlans; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <a href="create_workout.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i> New Workout Plan
                    </a>
                    <a href="create_diet.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                        <i class="fas fa-plus mr-2"></i> New Diet Plan
                    </a>
                    <a href="view_clients.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700">
                        <i class="fas fa-users mr-2"></i> View All Clients
                    </a>
                    <a href="profile.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gray-600 hover:bg-gray-700">
                        <i class="fas fa-user mr-2"></i> Edit Profile
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Clients -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Clients</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($recentClients as $client): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($client['username']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($client['email']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <a href="view_client.php?id=<?php echo $client['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                        View Details
                                    </a>
                                    <a href="create_workout.php?client_id=<?php echo $client['id']; ?>" class="text-green-600 hover:text-green-900 mr-3">
                                        Create Workout
                                    </a>
                                    <a href="create_diet.php?client_id=<?php echo $client['id']; ?>" class="text-purple-600 hover:text-purple-900">
                                        Create Diet
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add any JavaScript functionality here
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize any components or add event listeners
        });
    </script>
</body>
</html>
