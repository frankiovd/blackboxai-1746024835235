<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Ensure user is logged in and is a client
requireRole('client');

try {
    $db = getDBConnection();
    
    // Get client ID
    $stmt = $db->prepare("SELECT id FROM clients WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $client_id = $stmt->fetchColumn();

    // Get all progress entries
    $stmt = $db->prepare("
        SELECT * FROM progress_tracking 
        WHERE client_id = ? 
        ORDER BY date DESC
    ");
    $stmt->execute([$client_id]);
    $progress_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare data for charts
    $dates = [];
    $weights = [];
    $body_fats = [];
    $measurements = [
        'chest' => [],
        'waist' => [],
        'hips' => [],
        'biceps' => [],
        'thighs' => []
    ];

    foreach ($progress_entries as $entry) {
        $formatted_date = formatDate($entry['date']);
        $dates[] = $formatted_date;
        $weights[] = $entry['weight'];
        $body_fats[] = $entry['body_fat'];
        
        $entry_measurements = json_decode($entry['measurements'], true);
        foreach ($measurements as $key => &$measurement_array) {
            $measurement_array[] = $entry_measurements[$key] ?? null;
        }
    }

    // Reverse arrays for chronological display
    $dates = array_reverse($dates);
    $weights = array_reverse($weights);
    $body_fats = array_reverse($body_fats);
    foreach ($measurements as &$measurement_array) {
        $measurement_array = array_reverse($measurement_array);
    }

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progress History - Gym Management System</title>
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
        <!-- Header -->
        <div class="mb-6 flex justify-between items-center">
            <h2 class="text-2xl font-bold text-gray-900">Progress History</h2>
            <a href="add_progress.php" 
               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i> Add New Entry
            </a>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 gap-6 mb-6">
            <!-- Weight and Body Fat Chart -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Weight and Body Fat Progress</h3>
                <canvas id="weightChart"></canvas>
            </div>

            <!-- Measurements Chart -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Body Measurements Progress</h3>
                <canvas id="measurementsChart"></canvas>
            </div>
        </div>

        <!-- Progress History Table -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Detailed History</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Weight (kg)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Body Fat %</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Measurements</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Photo</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($progress_entries as $entry): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo formatDate($entry['date']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo number_format($entry['weight'], 1); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $entry['body_fat'] ? number_format($entry['body_fat'], 1) : '-'; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php 
                                        $measurements = json_decode($entry['measurements'], true);
                                        if ($measurements) {
                                            echo "<ul>";
                                            foreach ($measurements as $key => $value) {
                                                if ($value) {
                                                    echo "<li class='capitalize'>{$key}: {$value} cm</li>";
                                                }
                                            }
                                            echo "</ul>";
                                        }
                                        ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php if ($entry['photo_url']): ?>
                                            <a href="<?php echo htmlspecialchars($entry['photo_url']); ?>" 
                                               target="_blank"
                                               class="text-blue-600 hover:text-blue-900">
                                                View Photo
                                            </a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
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
        // Weight and Body Fat Chart
        const weightCtx = document.getElementById('weightChart').getContext('2d');
        new Chart(weightCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [{
                    label: 'Weight (kg)',
                    data: <?php echo json_encode($weights); ?>,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.1,
                    yAxisID: 'y'
                }, {
                    label: 'Body Fat %',
                    data: <?php echo json_encode($body_fats); ?>,
                    borderColor: 'rgb(239, 68, 68)',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    tension: 0.1,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Weight (kg)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Body Fat %'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });

        // Measurements Chart
        const measurementsCtx = document.getElementById('measurementsChart').getContext('2d');
        new Chart(measurementsCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [{
                    label: 'Chest',
                    data: <?php echo json_encode($measurements['chest']); ?>,
                    borderColor: 'rgb(59, 130, 246)',
                    tension: 0.1
                }, {
                    label: 'Waist',
                    data: <?php echo json_encode($measurements['waist']); ?>,
                    borderColor: 'rgb(239, 68, 68)',
                    tension: 0.1
                }, {
                    label: 'Hips',
                    data: <?php echo json_encode($measurements['hips']); ?>,
                    borderColor: 'rgb(34, 197, 94)',
                    tension: 0.1
                }, {
                    label: 'Biceps',
                    data: <?php echo json_encode($measurements['biceps']); ?>,
                    borderColor: 'rgb(168, 85, 247)',
                    tension: 0.1
                }, {
                    label: 'Thighs',
                    data: <?php echo json_encode($measurements['thighs']); ?>,
                    borderColor: 'rgb(234, 179, 8)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: false,
                        title: {
                            display: true,
                            text: 'Measurements (cm)'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
