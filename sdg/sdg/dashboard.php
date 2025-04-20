<?php
require_once 'config/db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user's wallet balance
$stmt = $conn->prepare("SELECT wallet_balance FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$wallet_balance = $user['wallet_balance'];

// Get total deposits count
$stmt = $conn->prepare("SELECT COUNT(*) as total_deposits FROM deposits");
$stmt->execute();
$result = $stmt->get_result();
$total_deposits = $result->fetch_assoc()['total_deposits'];

// Handle withdrawal submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['withdraw'])) {
    $amount = floatval($_POST['withdrawal_amount']);
    $min_amount = 2000;
    
    if ($amount < $min_amount) {
        $error = "Minimum withdrawal amount is ₹" . number_format($min_amount, 2);
    } elseif ($amount > $wallet_balance) {
        $error = "Insufficient balance for withdrawal";
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Get current balance from database
            $stmt = $conn->prepare("SELECT wallet_balance FROM users WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $current_balance = $result->fetch_assoc()['wallet_balance'];
            
            // Calculate new balance
            $new_balance = $current_balance - $amount;
            
            // Insert withdrawal record
            $stmt = $conn->prepare("INSERT INTO withdrawals (user_id, amount, status) VALUES (?, ?, 'pending')");
            $stmt->bind_param("id", $_SESSION['user_id'], $amount);
            $stmt->execute();
            
            // Update wallet balance
            $stmt = $conn->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
            $stmt->bind_param("di", $new_balance, $_SESSION['user_id']);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            // Update session and local variables
            $wallet_balance = $new_balance;
            $_SESSION['wallet_balance'] = $wallet_balance;
            
            $success = "Withdrawal request submitted successfully! Remaining balance: ₹" . number_format($new_balance, 2);
            
            // Redirect to refresh the page
            header("Location: dashboard.php");
            exit();
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error = "Failed to process withdrawal. Please try again.";
        }
    }
}

// Get updated wallet balance after withdrawal
$stmt = $conn->prepare("SELECT wallet_balance FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$wallet_balance = $user['wallet_balance'];

// Create withdrawals table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS withdrawals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'completed', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";
if (!$conn->query($create_table_sql)) {
    $error = "Failed to create withdrawals table: " . $conn->error;
}

// Handle deposit submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['log_deposit'])) {
    // Check if deposit_token exists in POST data
    $deposit_token = isset($_POST['deposit_token']) ? $_POST['deposit_token'] : '';
    
    // Check if this is a duplicate submission
    if (!isset($_SESSION['last_deposit']) || $_SESSION['last_deposit'] != $deposit_token) {
        $center = $_POST['recycling_center'];
        $plastic_type = $_POST['plastic_type'];
        $quantity = (int)$_POST['quantity'];
        
        // Calculate estimates
        $estimated_money = $quantity * 0.111; // ₹0.111 per unit
        $estimated_weight = $quantity * 0.06; // 0.06 kg per unit
        
        // Insert deposit record
        $stmt = $conn->prepare("INSERT INTO deposits (user_id, date, recycling_center, plastic_type, quantity, estimated_money, estimated_weight) 
                               VALUES (?, CURDATE(), ?, ?, ?, ?, ?)");
        $stmt->bind_param("issidd", $_SESSION['user_id'], $center, $plastic_type, $quantity, $estimated_money, $estimated_weight);
        
        if ($stmt->execute()) {
            // Update wallet balance
            $new_balance = $wallet_balance + $estimated_money;
            $stmt = $conn->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
            $stmt->bind_param("di", $new_balance, $_SESSION['user_id']);
            $stmt->execute();
            $wallet_balance = $new_balance;
            
            // Store the deposit token to prevent duplicate submissions
            $_SESSION['last_deposit'] = $deposit_token;
            
            $success = "Deposit logged successfully!";
        } else {
            $error = "Failed to log deposit. Please try again.";
        }
    } else {
        $error = "This deposit has already been processed.";
    }
}

// Generate a unique token for the form
$deposit_token = uniqid();

// Get deposit history for menu
$stmt = $conn->prepare("SELECT date, recycling_center, plastic_type, quantity, estimated_money, estimated_weight 
                       FROM deposits 
                       WHERE user_id = ? 
                       ORDER BY date DESC 
                       LIMIT 10");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$deposit_menu_history = [];
while ($row = $result->fetch_assoc()) {
    $deposit_menu_history[] = $row;
}

// Get withdrawal history for menu
$stmt = $conn->prepare("SELECT id, amount, status, created_at 
                       FROM withdrawals 
                       WHERE user_id = ? 
                       ORDER BY created_at DESC 
                       LIMIT 10");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$withdrawal_menu_history = [];
while ($row = $result->fetch_assoc()) {
    $withdrawal_menu_history[] = $row;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - EcoRewards</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        .slide-menu {
            transform: translateX(-100%);
            transition: transform 0.3s ease-out;
        }
        .slide-menu.active {
            transform: translateX(0);
        }
        .history-item {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.3s ease-out, transform 0.3s ease-out;
        }
        .history-item.show {
            opacity: 1;
            transform: translateY(0);
        }
        .cube-cluster {
            background-color: rgba(255, 255, 255, 0.9) !important;
            backdrop-filter: blur(5px);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .cube-cluster::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            opacity: 0;
            transition: opacity 0.6s ease;
        }
        .cube-cluster:hover::before {
            opacity: 1;
            animation: fadeOut 1.5s ease forwards;
        }
        @keyframes fadeOut {
            0% { opacity: 1; }
            100% { opacity: 0; }
        }
        /* Analysis cluster specific styles */
        .analysis-cluster {
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        .analysis-cluster:hover {
            border-color: rgba(59, 130, 246, 0.3);
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.2);
        }
        /* Withdrawal cluster specific styles */
        .withdrawal-cluster {
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        .withdrawal-cluster:hover {
            border-color: rgba(168, 85, 247, 0.3);
            box-shadow: 0 0 20px rgba(168, 85, 247, 0.2);
        }
        
        /* New styles for navbar and logo */
        .navbar {
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }
        .logo-container {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .recycle-icon {
            animation: spin 4s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .logout-btn {
            transition: all 0.3s ease;
        }
        .logout-btn:hover {
            transform: translateY(-2px);
        }
        .main-content {
            margin-top: 4rem; /* Add margin to account for fixed navbar */
        }
        #menuOverlay {
            transition: opacity 0.3s ease-out;
        }
        .border-r-2 {
            border-right-width: 2px;
        }
        #analysisOverlay {
            transition: opacity 0.3s ease-out;
        }

        /* New background styles */
        body {
            background-image: url('image1.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
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
            background: rgba(255, 255, 255, 0.95);
            z-index: -1;
        }
        .bg-white {
            background-color: rgba(255, 255, 255, 0.9) !important;
            backdrop-filter: blur(5px);
        }
        .cube-cluster {
            background-color: rgba(255, 255, 255, 0.9) !important;
            backdrop-filter: blur(5px);
        }

        /* New Interactive Styles */
        .interactive-card {
            transition: all 0.3s ease;
            transform: translateY(0);
        }
        .interactive-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .glow-on-hover {
            transition: all 0.3s ease;
        }
        .glow-on-hover:hover {
            box-shadow: 0 0 15px rgba(34, 197, 94, 0.5);
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .slide-in {
            animation: slideIn 0.5s ease-out;
        }
        @keyframes slideIn {
            from { transform: translateX(-20px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .bounce-on-hover {
            transition: all 0.3s ease;
        }
        .bounce-on-hover:hover {
            animation: bounce 0.5s;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .rotate-on-hover {
            transition: transform 0.3s ease;
        }
        .rotate-on-hover:hover {
            transform: rotate(5deg);
        }
        .scale-on-hover {
            transition: transform 0.3s ease;
        }
        .scale-on-hover:hover {
            transform: scale(1.05);
        }
        .stats-counter {
            transition: all 0.3s ease;
        }
        .stats-counter:hover {
            transform: scale(1.1);
            background-color: rgba(34, 197, 94, 0.1);
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Navigation Bar -->
    <nav class="navbar py-3 px-6">
        <div class="container mx-auto flex justify-between items-center">
            <div class="logo-container">
                <i class="fas fa-recycle recycle-icon text-green-500 text-2xl"></i>
                <span class="text-xl font-bold text-gray-800">EcoRewards</span>
            </div>
            <div class="flex items-center space-x-4">
                <div class="bg-green-500 text-white px-4 py-2 rounded-lg shadow">
                    <span class="font-medium">Wallet Balance:</span>
                    <span class="ml-2 font-bold" id="walletBalanceDisplay">₹<?php echo number_format($wallet_balance, 2); ?></span>
                </div>
                <button id="historyMenuBtn" class="bg-white p-2 rounded-lg shadow hover:shadow-md transition-shadow duration-200">
                    <i class="fas fa-history text-gray-600"></i>
                </button>
                <a href="logout.php" class="logout-btn bg-red-500 text-white px-4 py-2 rounded-lg shadow hover:bg-red-600">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content container mx-auto px-4 py-8">
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $success; ?></span>
            </div>
        <?php endif; ?>

        <!-- Header Section -->
        <div class="flex justify-between items-center mb-8">
            <div class="border-r-2 border-gray-200 pr-8">
                <h1 class="text-3xl font-bold text-gray-800">Welcome Back!</h1>
                <p class="text-gray-600 mt-1">Track your recycling journey</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow-lg">
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Total Deposits</h3>
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-recycle text-green-500"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800" id="totalDepositsCounter"><?php echo number_format($total_deposits); ?></p>
                        <p class="text-sm text-gray-500">Across all recycle centers</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Grid Layout -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- Analysis Card -->
            <div class="cube-cluster bg-white p-6 rounded-xl shadow-lg interactive-card scale-on-hover cursor-pointer analysis-cluster" onclick="showAnalysisModal()">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center rotate-on-hover">
                        <i class="fas fa-chart-line text-blue-500 text-xl"></i>
                    </div>
                    <span class="text-sm font-medium text-gray-400 slide-in">Analysis</span>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2 fade-in">View Analytics</h3>
                <p class="text-gray-600 fade-in">Track your recycling progress and impact over time</p>
            </div>

            <!-- New Deposit Card -->
            <div id="newDepositCluster" class="cube-cluster bg-white p-6 rounded-xl shadow-lg interactive-card glow-on-hover cursor-pointer">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center bounce-on-hover">
                        <i class="fas fa-plus text-green-500 text-xl"></i>
                    </div>
                    <span class="text-sm font-medium text-gray-400 slide-in">New</span>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2 fade-in">Deposit</h3>
                <p class="text-gray-600 fade-in">Record your latest recycling contribution</p>
            </div>

            <!-- Withdrawal Card -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-purple-100 p-3 rounded-xl">
                        <i class="fas fa-wallet text-purple-600 text-xl"></i>
                    </div>
                    <span class="text-gray-500">Balance</span>
                </div>
                <div class="space-y-1 mb-6">
                    <h3 class="text-3xl font-bold text-gray-800">₹<?php echo number_format($wallet_balance, 2); ?></h3>
                    <p class="text-gray-600">Current wallet balance</p>
                </div>
                <button id="withdrawButton" 
                        class="w-full bg-purple-500 hover:bg-purple-600 text-white font-semibold py-3 px-4 rounded-xl transition-all duration-200 transform hover:scale-[1.02] active:scale-[0.98]">
                    Withdraw Funds
                </button>
            </div>
        </div>

        <!-- Bar Graph Section -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8 interactive-card">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 slide-in">Monthly Overview</h2>
            <div class="w-full h-96">
                <canvas id="barChart"></canvas>
            </div>
        </div>

        <!-- Analysis Modal -->
        <div id="analysisModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 overflow-y-auto">
            <div class="relative mx-auto p-6 bg-white w-11/12 max-w-4xl rounded-xl shadow-lg my-8">
                <div class="flex justify-between items-center mb-6 sticky top-0 bg-white z-10">
                    <h2 class="text-2xl font-bold text-gray-800">Recycling Analysis</h2>
                    <button onclick="hideAnalysisModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h3 class="text-lg font-semibold text-blue-800 mb-2">Total Deposits</h3>
                        <p class="text-3xl font-bold text-blue-600" id="totalDeposits">0</p>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg">
                        <h3 class="text-lg font-semibold text-green-800 mb-2">Total Items</h3>
                        <p class="text-3xl font-bold text-green-600" id="totalItems">0</p>
                    </div>
                    <div class="bg-purple-50 p-4 rounded-lg">
                        <h3 class="text-lg font-semibold text-purple-800 mb-2">Total Earned</h3>
                        <p class="text-3xl font-bold text-purple-600" id="totalEarned">₹0.00</p>
                    </div>
                </div>

                <!-- Analysis Graphs -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <!-- 24-Hour Activity Graph -->
                    <div class="bg-white p-4 rounded-lg shadow">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">24-Hour Activity</h3>
                        <div class="h-64">
                            <canvas id="activityChart"></canvas>
                        </div>
                    </div>

                    <!-- Plastic Type Distribution -->
                    <div class="bg-white p-4 rounded-lg shadow">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Plastic Type Distribution</h3>
                        <div class="h-64">
                            <canvas id="plasticTypeChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Detailed Statistics -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Detailed Statistics</h3>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="text-left text-sm font-semibold text-gray-600">
                                        <th class="pb-2">Plastic Type</th>
                                        <th class="pb-2">Quantity</th>
                                        <th class="pb-2">Earnings</th>
                                        <th class="pb-2">Percentage</th>
                                    </tr>
                                </thead>
                                <tbody id="detailedStats">
                                    <!-- Stats will be populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Analysis Modal Overlay -->
        <div id="analysisOverlay" class="fixed inset-0 bg-black bg-opacity-50 hidden z-40" onclick="hideAnalysisModal()"></div>

        <!-- Deposit Form Popdown -->
        <div id="recyclingFormPopdown" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
            <div id="formContainer" class="relative -top-full transition-all duration-500 max-w-lg mx-auto bg-white rounded-xl shadow-lg mt-20">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-bold text-gray-800">Log New Deposit</h3>
                        <button id="closeRecyclingForm" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <form method="POST" action="" class="space-y-4">
                        <input type="hidden" name="deposit_token" value="<?php echo $deposit_token; ?>">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="recycling_center">
                                Recycling Center
                            </label>
                            <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                    id="recycling_center" name="recycling_center" required>
                                <option value="">Select Center</option>
                                <option value="Mangalagiri">Mangalagiri</option>
                                <option value="Vijayawada">Vijayawada</option>
                                <option value="Neerukonda">Neerukonda</option>
                                <option value="Guntur">Guntur</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="plastic_type">
                                Plastic Type
                            </label>
                            <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                    id="plastic_type" name="plastic_type" required>
                                <option value="">Select Type</option>
                                <option value="PET">PET (Type 1) - (Water bottles, Soda bottles, Food containers)</option>
                                <option value="HDPE">HDPE (Type 2) - (Milk jugs, Shampoo bottles, Detergent containers)</option>
                                <option value="PVC">PVC (Type 3) - (Pipes, Window frames, Shower curtains)</option>
                                <option value="LDPE">LDPE (Type 4) - (Plastic bags, Squeeze bottles, Food packaging)</option>
                                <option value="PP">PP (Type 5) - (Food containers, Bottle caps, Medicine bottles)</option>
                                <option value="PS">PS (Type 6) - (Disposable cutlery, Food trays, CD cases)</option>
                                <option value="Other">Other Plastics (Type 7) - (Mixed plastics, Acrylic, Nylon)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="quantity">
                                Quantity (Units)
                            </label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                   id="quantity" name="quantity" type="number" min="1" required>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">
                                    Estimated Money
                                </label>
                                <div class="text-green-600 font-bold" id="estimated_money">₹0.00</div>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">
                                    Estimated Weight
                                </label>
                                <div class="text-green-600 font-bold" id="estimated_weight">0.00 kg</div>
                            </div>
                        </div>

                        <button class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full transition-colors duration-200" 
                                type="submit" name="log_deposit">
                            Log Deposit
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Withdrawal Form Modal -->
        <div id="withdrawalModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
            <div class="relative top-20 mx-auto p-6 bg-white w-11/12 max-w-md rounded-2xl shadow-lg">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Withdraw Funds</h2>
                    <button onclick="hideWithdrawalForm()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST" action="" class="space-y-6" id="withdrawalForm">
                    <input type="hidden" name="withdraw" value="1">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="withdrawal_amount">
                            Amount (Minimum ₹2000)
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-gray-500">₹</span>
                            <input type="number" 
                                   id="withdrawal_amount" 
                                   name="withdrawal_amount" 
                                   class="shadow-sm border border-gray-200 rounded-xl w-full py-3 px-8 text-gray-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                   min="2000" 
                                   step="100" 
                                   required>
                        </div>
                    </div>
                    <div class="text-sm space-y-2">
                        <p class="text-gray-600">Available Balance: ₹<?php echo number_format($wallet_balance, 2); ?></p>
                        <p class="text-red-500 hidden" id="insufficient_balance">Insufficient balance for withdrawal</p>
                        <p class="text-yellow-500 hidden" id="minimum_amount">Minimum withdrawal amount is ₹2,000</p>
                    </div>
                    <div class="bg-purple-50 p-4 rounded-xl">
                        <h3 class="font-semibold text-purple-900 mb-2">Withdrawal Details</h3>
                        <div class="space-y-2 text-sm text-purple-700">
                            <p><i class="fas fa-clock mr-2"></i>Processing Time: 24-48 hours</p>
                            <p><i class="fas fa-history mr-2"></i>Status Updates: Available in History</p>
                            <p><i class="fas fa-info-circle mr-2"></i>Minimum Amount: ₹2,000</p>
                        </div>
                    </div>
                    <button type="submit" 
                            class="w-full bg-purple-500 hover:bg-purple-600 text-white font-semibold py-3 px-4 rounded-xl transition-all duration-200 transform hover:scale-[1.02] active:scale-[0.98]">
                        Process Withdrawal
                    </button>
                </form>
            </div>
        </div>

        <!-- Slide Menu -->
        <div id="historyMenu" class="slide-menu fixed top-0 left-0 w-80 h-full bg-white shadow-lg z-50 overflow-y-auto">
            <div class="p-4">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-800">History</h2>
                    <button id="closeMenuBtn" class="text-gray-500 hover:text-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <button id="depositHistoryBtn" class="w-full text-left p-3 bg-green-50 rounded-lg hover:bg-green-100 transition-colors duration-200">
                            <h3 class="font-semibold text-green-800 text-lg">Deposit History</h3>
                        </button>
                        <div id="depositHistoryList" class="mt-3 space-y-3 hidden">
                            <?php foreach ($deposit_menu_history as $index => $deposit): ?>
                                <div class="history-item p-3 bg-gray-50 rounded-lg border border-gray-100">
                                    <div class="text-sm text-gray-600"><?php echo $deposit['date']; ?></div>
                                    <div class="font-medium text-gray-800"><?php echo $deposit['plastic_type']; ?></div>
                                    <div class="text-sm">
                                        <span class="text-green-600 font-semibold"><?php echo $deposit['quantity']; ?> units</span>
                                        <span class="text-gray-500 ml-2">(₹<?php echo number_format($deposit['estimated_money'], 2); ?>)</span>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1"><?php echo $deposit['recycling_center']; ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Withdrawal History Section -->
                    <div>
                        <button id="withdrawalHistoryBtn" class="w-full text-left p-3 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors duration-200">
                            <h3 class="font-semibold text-purple-800 text-lg">Withdrawal History</h3>
                        </button>
                        <div id="withdrawalHistoryList" class="mt-3 space-y-3 hidden">
                            <?php if (empty($withdrawal_menu_history)): ?>
                                <div class="p-3 bg-gray-50 rounded-lg border border-gray-100 text-center text-gray-500">
                                    No withdrawal history available
                                </div>
                            <?php else: ?>
                                <?php foreach ($withdrawal_menu_history as $index => $withdrawal): ?>
                                    <div class="history-item p-3 bg-gray-50 rounded-lg border border-gray-100">
                                        <div class="flex justify-between items-start">
                                            <div class="text-sm text-gray-600">
                                                <?php echo date('M d, Y h:i A', strtotime($withdrawal['created_at'])); ?>
                                            </div>
                                            <span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">
                                                Completed
                                            </span>
                                        </div>
                                        <div class="mt-2">
                                            <span class="text-purple-600 font-semibold">₹<?php echo number_format($withdrawal['amount'], 2); ?></span>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">Transaction ID: <?php echo $withdrawal['id']; ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Menu Overlay -->
        <div id="menuOverlay" class="fixed inset-0 bg-black bg-opacity-50 hidden z-40" onclick="closeHistoryMenu()"></div>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-8">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div class="col-span-1">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-recycle text-green-500 text-2xl mr-2"></i>
                        <h3 class="text-lg font-bold text-gray-800">EcoRewards</h3>
                    </div>
                    <p class="text-gray-600 text-sm mb-4">
                        Making recycling rewarding for everyone. Join us in creating a sustainable future.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-gray-500">
                            <i class="fab fa-facebook text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-gray-500">
                            <i class="fab fa-twitter text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-gray-500">
                            <i class="fab fa-instagram text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-gray-500">
                            <i class="fab fa-linkedin text-xl"></i>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="col-span-1">
                    <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Quick Links</h3>
                    <ul class="space-y-3">
                        <li>
                            <a href="#" class="text-base text-gray-600 hover:text-gray-900">About Us</a>
                        </li>
                        <li>
                            <a href="how-it-works.php" class="text-base text-gray-600 hover:text-gray-900">How It Works</a>
                        </li>
                        <li>
                            <a href="#" class="text-base text-gray-600 hover:text-gray-900">Recycling Centers</a>
                        </li>
                        <li>
                            <a href="#" class="text-base text-gray-600 hover:text-gray-900">FAQs</a>
                        </li>
                    </ul>
                </div>

                <!-- Support -->
                <div class="col-span-1">
                    <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Support</h3>
                    <ul class="space-y-3">
                        <li>
                            <a href="#" class="text-base text-gray-600 hover:text-gray-900">Help Center</a>
                        </li>
                        <li>
                            <a href="#" class="text-base text-gray-600 hover:text-gray-900">Contact Us</a>
                        </li>
                        <li>
                            <a href="#" class="text-base text-gray-600 hover:text-gray-900">Privacy Policy</a>
                        </li>
                        <li>
                            <a href="#" class="text-base text-gray-600 hover:text-gray-900">Terms of Service</a>
                        </li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div class="col-span-1">
                    <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Contact Info</h3>
                    <ul class="space-y-3">
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-map-marker-alt w-5 text-gray-400"></i>
                            <span>123 Eco Street, Green City, 12345</span>
                        </li>
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-phone w-5 text-gray-400"></i>
                            <span>+1 (555) 123-4567</span>
                        </li>
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-envelope w-5 text-gray-400"></i>
                            <span>support@ecorewards.com</span>
                        </li>
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-clock w-5 text-gray-400"></i>
                            <span>Mon - Fri: 9:00 AM - 6:00 PM</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="border-t border-gray-200 mt-8 pt-8">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <p class="text-base text-gray-400">
                        © 2024 EcoRewards. All rights reserved.
                    </p>
                    <div class="flex space-x-6 mt-4 md:mt-0">
                        <a href="#" class="text-sm text-gray-500 hover:text-gray-900">Privacy</a>
                        <a href="#" class="text-sm text-gray-500 hover:text-gray-900">Terms</a>
                        <a href="#" onclick="showLocationMap(event)" class="text-sm text-gray-500 hover:text-gray-900">Sitemap</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Google Maps Modal -->
    <div id="mapModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="relative top-20 mx-auto p-6 bg-white w-11/12 max-w-4xl rounded-xl shadow-lg">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-gray-800">Our Location</h2>
                <button onclick="hideLocationMap()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="map" class="w-full h-[500px] rounded-lg border border-gray-200"></div>
            <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                <h3 class="font-semibold text-gray-700 mb-2">EcoRewards Recycling Center</h3>
                <p class="text-gray-600">123 Eco Street, Green City, 12345</p>
                <p class="text-gray-600 mt-1">
                    <i class="fas fa-phone mr-2"></i>+1 (555) 123-4567
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Show cube clusters with animation on page load
        document.addEventListener('DOMContentLoaded', function() {
            const clusters = document.querySelectorAll('.cube-cluster');
            clusters.forEach((cluster, index) => {
                setTimeout(() => {
                    cluster.classList.add('show');
                }, index * 200);
            });
        });

        // Menu Toggle
        const historyMenu = document.getElementById('historyMenu');
        const historyMenuBtn = document.getElementById('historyMenuBtn');
        const closeMenuBtn = document.getElementById('closeMenuBtn');
        const menuOverlay = document.getElementById('menuOverlay');
        const depositHistoryBtn = document.getElementById('depositHistoryBtn');
        const depositHistoryList = document.getElementById('depositHistoryList');

        function openHistoryMenu() {
            historyMenu.classList.add('active');
            menuOverlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeHistoryMenu() {
            historyMenu.classList.remove('active');
            menuOverlay.classList.add('hidden');
            document.body.style.overflow = 'auto';
            // Reset both lists when closing menu
            depositHistoryList.classList.add('hidden');
            // Remove show class from all items
            document.querySelectorAll('.history-item').forEach(item => {
                item.classList.remove('show');
            });
        }

        historyMenuBtn.addEventListener('click', openHistoryMenu);
        closeMenuBtn.addEventListener('click', closeHistoryMenu);
        menuOverlay.addEventListener('click', closeHistoryMenu);

        // Function to animate history items
        function animateHistoryItems(parentElement) {
            const items = parentElement.querySelectorAll('.history-item');
            items.forEach((item, index) => {
                item.classList.remove('show');
                setTimeout(() => {
                    item.classList.add('show');
                }, index * 100);
            });
        }

        // Toggle Deposit History
        depositHistoryBtn.addEventListener('click', () => {
            if (depositHistoryList.classList.contains('hidden')) {
                // Show deposit history
                depositHistoryList.classList.remove('hidden');
                animateHistoryItems(depositHistoryList);
            } else {
                // Hide deposit history
                depositHistoryList.classList.add('hidden');
                depositHistoryList.querySelectorAll('.history-item').forEach(item => {
                    item.classList.remove('show');
                });
            }
        });

        // Calculate estimates on quantity change
        document.getElementById('quantity').addEventListener('input', function() {
            const quantity = this.value;
            const estimatedMoney = (quantity * 0.111).toFixed(2);
            const estimatedWeight = (quantity * 0.06).toFixed(2);
            
            document.getElementById('estimated_money').textContent = `₹${estimatedMoney}`;
            document.getElementById('estimated_weight').textContent = `${estimatedWeight} kg`;
        });

        // Remove activity chart related code
        let activityChart = null;
        let plasticTypeChart = null;

        function initCharts() {
            // Plastic Type Chart
            const plasticCtx = document.getElementById('plasticTypeChart').getContext('2d');
            plasticTypeChart = new Chart(plasticCtx, {
                type: 'doughnut',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: [
                            'rgb(34, 197, 94)',
                            'rgb(59, 130, 246)',
                            'rgb(168, 85, 247)',
                            'rgb(234, 179, 8)',
                            'rgb(249, 115, 22)',
                            'rgb(239, 68, 68)',
                            'rgb(236, 72, 153)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Bar Graph
        let barChart = null;

        function initBarChart() {
            const ctx = document.getElementById('barChart').getContext('2d');
            barChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [
                        {
                            label: 'Deposits',
                            data: [],
                            backgroundColor: 'rgba(34, 197, 94, 0.7)',
                            borderColor: 'rgb(34, 197, 94)',
                            borderWidth: 1
                        },
                        {
                            label: 'Quantity',
                            data: [],
                            backgroundColor: 'rgba(59, 130, 246, 0.7)',
                            borderColor: 'rgb(59, 130, 246)',
                            borderWidth: 1
                        },
                        {
                            label: 'Money (₹)',
                            data: [],
                            backgroundColor: 'rgba(168, 85, 247, 0.7)',
                            borderColor: 'rgb(168, 85, 247)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                title: function(context) {
                                    return context[0].label;
                                },
                                label: function(context) {
                                    const datasetLabel = context.dataset.label;
                                    const value = context.parsed.y;
                                    
                                    switch(datasetLabel) {
                                        case 'Deposits':
                                            return `${datasetLabel}: ${value.toLocaleString()}`;
                                        case 'Quantity':
                                            return `${datasetLabel}: ${value.toLocaleString()}`;
                                        case 'Money (₹)':
                                            return `${datasetLabel.replace(' (₹)', '')}: ₹${value.toLocaleString('en-IN', {minimumFractionDigits: 2})}`;
                                        default:
                                            return `${datasetLabel}: ${value}`;
                                    }
                                }
                            },
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            displayColors: true,
                            bodySpacing: 8,
                            mode: 'index',
                            intersect: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    hover: {
                        mode: 'index',
                        intersect: false
                    }
                }
            });
        }

        function updateBarChart() {
            fetch('get_bar_graph_data.php')
                .then(response => response.json())
                .then(data => {
                    if (!barChart) {
                        initBarChart();
                    }
                    
                    barChart.data.labels = data.labels;
                    barChart.data.datasets[0].data = data.deposits;
                    barChart.data.datasets[1].data = data.quantities;
                    barChart.data.datasets[2].data = data.money;
                    barChart.update();
                })
                .catch(error => console.error('Error fetching bar graph data:', error));
        }

        // Initialize charts when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initCharts();
            initBarChart();
            updateAnalysisData();
            updateBarChart();
            
            // Update bar chart every 5 minutes
            setInterval(updateBarChart, 300000);
        });

        // Analysis Data Update
        function updateAnalysisData() {
            fetch('get_analysis_data.php')
                .then(response => response.json())
                .then(data => {
                    // Update total deposits
                    document.getElementById('totalDeposits').textContent = data.total_deposits.toLocaleString();

                    // Update total items
                    document.getElementById('totalItems').textContent = data.total_items.toLocaleString();

                    // Update total earned
                    document.getElementById('totalEarned').textContent = `₹${data.total_earned.toLocaleString('en-IN', {minimumFractionDigits: 2})}`;

                    // Update plastic type distribution
                    if (plasticTypeChart) {
                        const labels = data.plastic_types.map(type => type.plastic_type);
                        const counts = data.plastic_types.map(type => type.count);
                        const percentages = data.plastic_types.map(type => type.percentage);

                        plasticTypeChart.data.labels = labels;
                        plasticTypeChart.data.datasets[0].data = counts;
                        plasticTypeChart.update();
                    }

                    // Update detailed statistics table
                    const statsTable = document.getElementById('detailedStats');
                    statsTable.innerHTML = data.plastic_types.map(type => `
                        <tr class="text-sm">
                            <td class="py-2">${type.plastic_type}</td>
                            <td class="py-2">${type.total_quantity.toLocaleString()}</td>
                            <td class="py-2">₹${type.total_money.toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                            <td class="py-2">${type.percentage}%</td>
                        </tr>
                    `).join('');
                })
                .catch(error => console.error('Error fetching analysis data:', error));
        }

        // New Deposit Form Handling
        const newDepositCluster = document.getElementById('newDepositCluster');
        const recyclingFormPopdown = document.getElementById('recyclingFormPopdown');
        const formContainer = document.getElementById('formContainer');
        const closeRecyclingForm = document.getElementById('closeRecyclingForm');

        function showRecyclingForm() {
            recyclingFormPopdown.classList.remove('hidden');
            requestAnimationFrame(() => {
                formContainer.style.transition = 'top 0.5s ease-out';
                formContainer.style.top = '5%';
            });
        }

        function hideRecyclingForm() {
            formContainer.style.top = '-100%';
            setTimeout(() => {
                recyclingFormPopdown.classList.add('hidden');
            }, 500);
        }

        if (newDepositCluster) {
            newDepositCluster.addEventListener('click', showRecyclingForm);
        }

        if (closeRecyclingForm) {
            closeRecyclingForm.addEventListener('click', hideRecyclingForm);
        }

        // Close form when clicking outside
        recyclingFormPopdown.addEventListener('click', (e) => {
            if (e.target === recyclingFormPopdown) {
                hideRecyclingForm();
            }
        });

        // Analysis Modal Functions
        function showAnalysisModal() {
            document.getElementById('analysisModal').classList.remove('hidden');
            document.getElementById('analysisOverlay').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            updateAnalysisData();
        }

        function hideAnalysisModal() {
            document.getElementById('analysisModal').classList.add('hidden');
            document.getElementById('analysisOverlay').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('analysisModal');
            const overlay = document.getElementById('analysisOverlay');
            if (event.target === overlay) {
                hideAnalysisModal();
            }
        });

        // Enhanced Withdrawal Form Handling
        const withdrawButton = document.getElementById('withdrawButton');
        
        if (withdrawButton) {
            withdrawButton.addEventListener('click', function(e) {
                e.preventDefault();
                showWithdrawalForm();
            });
        }

        function showWithdrawalForm() {
            document.getElementById('withdrawalModal').classList.remove('hidden');
            document.getElementById('withdrawal_amount').focus();
            document.getElementById('withdrawalForm').reset();
            document.getElementById('insufficient_balance').classList.add('hidden');
            document.getElementById('minimum_amount').classList.add('hidden');
            document.body.style.overflow = 'hidden';
        }

        function hideWithdrawalForm() {
            document.getElementById('withdrawalModal').classList.add('hidden');
            document.getElementById('withdrawalForm').reset();
            document.getElementById('insufficient_balance').classList.add('hidden');
            document.getElementById('minimum_amount').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Real-time withdrawal amount validation
        document.getElementById('withdrawal_amount').addEventListener('input', function() {
            const amount = parseFloat(this.value) || 0;
            const balance = <?php echo $wallet_balance; ?>;
            const minAmount = 2000;
            const insufficientBalance = document.getElementById('insufficient_balance');
            const minimumAmount = document.getElementById('minimum_amount');
            
            if (amount < minAmount) {
                this.setCustomValidity(`Minimum withdrawal amount is ₹${minAmount}`);
                insufficientBalance.classList.add('hidden');
                minimumAmount.classList.remove('hidden');
            } else if (amount > balance) {
                this.setCustomValidity('Amount exceeds available balance');
                insufficientBalance.classList.remove('hidden');
                minimumAmount.classList.add('hidden');
            } else {
                this.setCustomValidity('');
                insufficientBalance.classList.add('hidden');
                minimumAmount.classList.add('hidden');
            }
        });

        // Handle withdrawal form submission with animation
        document.getElementById('withdrawalForm').addEventListener('submit', function(e) {
            const amount = parseFloat(document.getElementById('withdrawal_amount').value);
            const balance = <?php echo $wallet_balance; ?>;
            const minAmount = 2000;
            
            if (amount < minAmount) {
                e.preventDefault();
                document.getElementById('minimum_amount').classList.remove('hidden');
            } else if (amount > balance) {
                e.preventDefault();
                document.getElementById('insufficient_balance').classList.remove('hidden');
            } else {
                // Add loading state to button
                const submitButton = this.querySelector('button[type="submit"]');
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
            }
        });

        // Close modal when clicking outside
        document.getElementById('withdrawalModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideWithdrawalForm();
            }
        });

        // Enhanced Withdrawal History Toggle
        const withdrawalHistoryBtn = document.getElementById('withdrawalHistoryBtn');
        const withdrawalHistoryList = document.getElementById('withdrawalHistoryList');

        withdrawalHistoryBtn.addEventListener('click', () => {
            if (withdrawalHistoryList.classList.contains('hidden')) {
                // Hide deposit history if visible
                document.getElementById('depositHistoryList').classList.add('hidden');
                document.getElementById('depositHistoryList').querySelectorAll('.history-item').forEach(item => {
                    item.classList.remove('show');
                });
                
                // Show withdrawal history
                withdrawalHistoryList.classList.remove('hidden');
                animateHistoryItems(withdrawalHistoryList);
            } else {
                // Hide withdrawal history
                withdrawalHistoryList.classList.add('hidden');
                withdrawalHistoryList.querySelectorAll('.history-item').forEach(item => {
                    item.classList.remove('show');
                });
            }
        });

        // Simulated Deposits Counter
        let simulatedDeposits = <?php echo $total_deposits; ?>;
        let depositInterval;

        function startDepositSimulation() {
            // Clear any existing interval
            if (depositInterval) {
                clearInterval(depositInterval);
            }
            
            // Start new interval
            depositInterval = setInterval(() => {
                simulatedDeposits += 2; // Add 2 deposits every 2 seconds
                document.getElementById('totalDepositsCounter').textContent = simulatedDeposits.toLocaleString();
            }, 2000); // Update every 2 seconds
        }

        // Start simulation when page loads
        startDepositSimulation();

        // Stop simulation when leaving the page
        window.addEventListener('beforeunload', () => {
            if (depositInterval) {
                clearInterval(depositInterval);
            }
        });

        // Google Maps functionality
        function loadGoogleMaps() {
            const script = document.createElement('script');
            script.src = 'https://maps.googleapis.com/maps/api/js?key=AIzaSyB41DRUbKWJHPxaFjMAwdrzWzbVKartNGg&callback=initMap';
            script.async = true;
            script.defer = true;
            document.head.appendChild(script);
        }

        // Initialize map
        function initMap() {
            try {
                // Coordinates for Vijayawada
                const location = { lat: 16.5062, lng: 80.6480 };
                
                const mapOptions = {
                    center: location,
                    zoom: 15,
                    mapTypeId: google.maps.MapTypeId.ROADMAP,
                    mapTypeControl: true,
                    mapTypeControlOptions: {
                        style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR,
                        position: google.maps.ControlPosition.TOP_RIGHT
                    },
                    zoomControl: true,
                    zoomControlOptions: {
                        position: google.maps.ControlPosition.RIGHT_CENTER
                    },
                    scaleControl: true,
                    streetViewControl: true,
                    streetViewControlOptions: {
                        position: google.maps.ControlPosition.RIGHT_CENTER
                    },
                    fullscreenControl: true
                };

                // Create the map
                const map = new google.maps.Map(document.getElementById('map'), mapOptions);

                // Add marker
                const marker = new google.maps.Marker({
                    position: location,
                    map: map,
                    title: 'EcoRewards Recycling Center',
                    animation: google.maps.Animation.DROP,
                    icon: {
                        url: 'https://maps.google.com/mapfiles/ms/icons/green-dot.png'
                    }
                });

                // Add info window
                const infoWindow = new google.maps.InfoWindow({
                    content: `
                        <div class="info-window">
                            <h3 class="font-bold">EcoRewards Recycling Center</h3>
                            <p>Your local recycling partner</p>
                            <p>Open 24/7</p>
                        </div>
                    `
                });

                // Show info window on marker click
                marker.addListener('click', () => {
                    infoWindow.open(map, marker);
                });

            } catch (error) {
                console.error('Error initializing map:', error);
            }
        }

        function showLocationMap(event) {
            event.preventDefault();
            const mapModal = document.getElementById('mapModal');
            mapModal.classList.remove('hidden');
            
            // Load Google Maps if not already loaded
            if (typeof google === 'undefined') {
                loadGoogleMaps();
            } else {
                initMap();
            }
        }

        function hideLocationMap() {
            document.getElementById('mapModal').classList.add('hidden');
        }

        // Close map modal when clicking outside
        document.getElementById('mapModal').addEventListener('click', function(event) {
            if (event.target === this) {
                hideLocationMap();
            }
        });

        // Add new interactive features
        document.addEventListener('DOMContentLoaded', function() {
            // Add animation classes to elements
            const elements = document.querySelectorAll('.cube-cluster, .stats-counter');
            elements.forEach((element, index) => {
                setTimeout(() => {
                    element.classList.add('fade-in');
                }, index * 100);
            });

            // Add hover effects to cards
            const cards = document.querySelectorAll('.interactive-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', () => {
                    card.style.transform = 'translateY(-5px)';
                });
                card.addEventListener('mouseleave', () => {
                    card.style.transform = 'translateY(0)';
                });
            });

            // Add click animations
            const buttons = document.querySelectorAll('button');
            buttons.forEach(button => {
                button.addEventListener('click', () => {
                    button.classList.add('bounce-on-hover');
                    setTimeout(() => {
                        button.classList.remove('bounce-on-hover');
                    }, 500);
                });
            });

            // Add scroll animations
            window.addEventListener('scroll', () => {
                const elements = document.querySelectorAll('.slide-in');
                elements.forEach(element => {
                    const elementTop = element.getBoundingClientRect().top;
                    const elementBottom = element.getBoundingClientRect().bottom;
                    if (elementTop < window.innerHeight && elementBottom > 0) {
                        element.classList.add('slide-in');
                    }
                });
            });

            // Enhanced tooltip functionality
            const tooltips = document.querySelectorAll('[data-tooltip]');
            tooltips.forEach(tooltip => {
                tooltip.addEventListener('mouseenter', () => {
                    const tooltipText = tooltip.getAttribute('data-tooltip');
                    const tooltipElement = document.createElement('div');
                    tooltipElement.className = 'tooltip';
                    tooltipElement.textContent = tooltipText;
                    document.body.appendChild(tooltipElement);
                    
                    const rect = tooltip.getBoundingClientRect();
                    tooltipElement.style.top = `${rect.top - tooltipElement.offsetHeight - 10}px`;
                    tooltipElement.style.left = `${rect.left + (rect.width - tooltipElement.offsetWidth) / 2}px`;
                });

                tooltip.addEventListener('mouseleave', () => {
                    const tooltipElement = document.querySelector('.tooltip');
                    if (tooltipElement) {
                        tooltipElement.remove();
                    }
                });
            });
        });

        // Add smooth scrolling for navigation
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Enhanced form interactions
        const formInputs = document.querySelectorAll('input, select, textarea');
        formInputs.forEach(input => {
            input.addEventListener('focus', () => {
                input.parentElement.classList.add('glow-on-hover');
            });
            input.addEventListener('blur', () => {
                input.parentElement.classList.remove('glow-on-hover');
            });
        });
    </script>
</body>
</html> 