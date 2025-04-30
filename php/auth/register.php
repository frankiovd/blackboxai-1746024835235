<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Redirect if already logged in
if (isAuthenticated()) {
    header("Location: /index.php");
    exit();
}

$errors = [];

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid request";
    } else {
        // Sanitize and validate input
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $user_type = sanitizeInput($_POST['user_type'] ?? '');

        // Validation
        if (empty($username)) {
            $errors[] = "Username is required";
        }
        if (empty($email)) {
            $errors[] = "Email is required";
        } elseif (!isValidEmail($email)) {
            $errors[] = "Invalid email format";
        }
        if (empty($password)) {
            $errors[] = "Password is required";
        } elseif (!isValidPassword($password)) {
            $errors[] = "Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, and one number";
        }
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match";
        }
        if (!in_array($user_type, ['trainer', 'client'])) {
            $errors[] = "Invalid user type";
        }

        if (empty($errors)) {
            try {
                $db = getDBConnection();

                // Check if username exists
                $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $errors[] = "Username already exists";
                }

                // Check if email exists
                $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $errors[] = "Email already exists";
                }

                if (empty($errors)) {
                    // Begin transaction
                    $db->beginTransaction();

                    // Insert user
                    $stmt = $db->prepare("INSERT INTO users (username, email, password, user_type) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$username, $email, hashPassword($password), $user_type]);
                    $user_id = $db->lastInsertId();

                    // Create profile based on user type
                    if ($user_type === 'trainer') {
                        $stmt = $db->prepare("INSERT INTO trainers (user_id) VALUES (?)");
                        $stmt->execute([$user_id]);
                    } else {
                        $stmt = $db->prepare("INSERT INTO clients (user_id) VALUES (?)");
                        $stmt->execute([$user_id]);
                    }

                    $db->commit();

                    // Set success message and redirect to login
                    setFlashMessage('success', 'Registration successful! Please login.');
                    header("Location: login.php");
                    exit();
                }
            } catch (PDOException $e) {
                $db->rollBack();
                $errors[] = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Gym Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center py-12">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <div class="text-center mb-8">
            <i class="fas fa-dumbbell text-4xl text-blue-600 mb-4"></i>
            <h1 class="text-2xl font-bold text-gray-800">Create an Account</h1>
            <p class="text-gray-600">Join our gym management system</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                <input type="text" id="username" name="username" 
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" id="email" name="email" 
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" id="password" name="password" 
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <p class="mt-1 text-sm text-gray-500">
                    Must be at least 8 characters with 1 uppercase, 1 lowercase, and 1 number
                </p>
            </div>

            <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" 
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label for="user_type" class="block text-sm font-medium text-gray-700">I am a</label>
                <select id="user_type" name="user_type" 
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Select role</option>
                    <option value="trainer" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'trainer') ? 'selected' : ''; ?>>Trainer</option>
                    <option value="client" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'client') ? 'selected' : ''; ?>>Client</option>
                </select>
            </div>

            <div>
                <button type="submit" 
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Register
                </button>
            </div>
        </form>

        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                Already have an account? 
                <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500">
                    Sign in here
                </a>
            </p>
        </div>
    </div>

    <script>
        // Client-side validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const userType = document.getElementById('user_type').value;
            let isValid = true;

            if (!username) {
                isValid = false;
                alert('Username is required');
            }

            if (!email) {
                isValid = false;
                alert('Email is required');
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                isValid = false;
                alert('Invalid email format');
            }

            if (!password) {
                isValid = false;
                alert('Password is required');
            } else if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/.test(password)) {
                isValid = false;
                alert('Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, and one number');
            }

            if (password !== confirmPassword) {
                isValid = false;
                alert('Passwords do not match');
            }

            if (!userType) {
                isValid = false;
                alert('Please select a role');
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
