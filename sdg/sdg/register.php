<?php
require_once 'config/db.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    // Validate input
    if (empty($name) || empty($email) || empty($password)) {
        $error = "All fields are required";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Email already exists";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $email, $hashed_password);
            
            if ($stmt->execute()) {
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['name'] = $name;
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Registration failed. Please try again.";
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
    <title>EcoRewards - Register</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body {
            background-image: url('image2.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
        }
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            z-index: 0;
        }
        .register-container {
            position: relative;
            z-index: 1;
        }
        .form-card {
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        .form-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        .input-field {
            background-color: rgba(255, 255, 255, 0.9);
            transition: all 0.3s ease;
        }
        .input-field:focus {
            background-color: white;
            transform: translateY(-2px);
        }
        .register-btn {
            transition: all 0.3s ease;
        }
        .register-btn:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="register-container max-w-md w-full space-y-8">
        <div class="form-card p-8 rounded-2xl shadow-xl">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-extrabold text-green-600">Welcome to EcoRewards</h2>
                <p class="mt-2 text-sm text-gray-600">Join us in making a difference</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="space-y-6">
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="name">
                        Full Name
                    </label>
                    <input class="input-field w-full px-4 py-3 rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" 
                           id="name" name="name" type="text" required>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="email">
                        Email Address
                    </label>
                    <input class="input-field w-full px-4 py-3 rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" 
                           id="email" name="email" type="email" required>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="password">
                        Password
                    </label>
                    <input class="input-field w-full px-4 py-3 rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" 
                           id="password" name="password" type="password" required>
                </div>
                
                <div>
                    <button type="submit" 
                            class="register-btn w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Create Account
                    </button>
                </div>
            </form>
            
            <div class="mt-6">
                <p class="text-center text-sm text-gray-600">
                    Already have an account? 
                    <a href="login.php" class="font-medium text-green-600 hover:text-green-500 transition-colors duration-200">
                        Sign in
                    </a>
                </p>
            </div>
        </div>
    </div>
</body>
</html> 