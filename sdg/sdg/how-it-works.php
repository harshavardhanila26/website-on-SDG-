<?php
session_start();
require_once 'config/db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>How It Works - EcoRewards</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Navigation Bar -->
    <nav class="bg-white shadow-lg fixed w-full z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="flex items-center">
                        <i class="fas fa-recycle text-green-500 text-2xl mr-2"></i>
                        <span class="text-xl font-bold text-gray-800">EcoRewards</span>
                    </a>
                </div>
                <div class="flex items-center">
                    <a href="dashboard.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="pt-16">
        <!-- Hero Section -->
        <div class="bg-green-500 text-white py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h1 class="text-4xl font-bold text-center mb-4">How EcoRewards Works</h1>
                <p class="text-xl text-center max-w-3xl mx-auto">Turn your recycling efforts into rewards while helping create a sustainable future.</p>
            </div>
        </div>

        <!-- Steps Section -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Step 1: Sign Up -->
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-4">
                            <span class="text-green-500 text-xl font-bold">1</span>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800">Sign Up</h2>
                    </div>
                    <p class="text-gray-600 mb-4">Create your EcoRewards account to start your recycling journey. It's free and only takes a minute!</p>
                    <ul class="space-y-2 text-gray-600">
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            Simple registration process
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            Secure account management
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            Access to all features
                        </li>
                    </ul>
                </div>

                <!-- Step 2: Collect & Sort -->
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-4">
                            <span class="text-green-500 text-xl font-bold">2</span>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800">Collect & Sort</h2>
                    </div>
                    <p class="text-gray-600 mb-4">Gather your recyclable plastics and sort them by type:</p>
                    <ul class="space-y-2 text-gray-600">
                        <li class="flex items-center">
                            <i class="fas fa-recycle text-blue-500 mr-2"></i>
                            PET (Type 1) - Water bottles, soft drink bottles
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-recycle text-blue-500 mr-2"></i>
                            HDPE (Type 2) - Milk jugs, shampoo bottles
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-recycle text-blue-500 mr-2"></i>
                            Other plastic types (3-7)
                        </li>
                    </ul>
                </div>

                <!-- Step 3: Visit Center -->
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-4">
                            <span class="text-green-500 text-xl font-bold">3</span>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800">Visit Recycling Center</h2>
                    </div>
                    <p class="text-gray-600 mb-4">Drop off your sorted plastics at any of our participating centers:</p>
                    <ul class="space-y-2 text-gray-600">
                        <li class="flex items-center">
                            <i class="fas fa-map-marker-alt text-red-500 mr-2"></i>
                            Mangalagiri Center
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-map-marker-alt text-red-500 mr-2"></i>
                            Vijayawada Center
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-map-marker-alt text-red-500 mr-2"></i>
                            Neerukonda Center
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-map-marker-alt text-red-500 mr-2"></i>
                            Guntur Center
                        </li>
                    </ul>
                </div>

                <!-- Step 4: Earn Rewards -->
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-4">
                            <span class="text-green-500 text-xl font-bold">4</span>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800">Earn Rewards</h2>
                    </div>
                    <p class="text-gray-600 mb-4">Get rewarded for your recycling efforts:</p>
                    <ul class="space-y-2 text-gray-600">
                        <li class="flex items-center">
                            <i class="fas fa-coins text-yellow-500 mr-2"></i>
                            ₹0.111 per unit of plastic
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-wallet text-purple-500 mr-2"></i>
                            Automatic wallet credits
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-chart-line text-blue-500 mr-2"></i>
                            Track your impact
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Additional Information -->
            <div class="mt-12 bg-white rounded-lg shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Additional Features</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Dashboard -->
                    <div class="space-y-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-chart-bar text-blue-500 text-xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800">Interactive Dashboard</h3>
                        <p class="text-gray-600">
                            Monitor your recycling activity, track earnings, and view environmental impact through our user-friendly dashboard.
                        </p>
                    </div>

                    <!-- Wallet -->
                    <div class="space-y-4">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-wallet text-purple-500 text-xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800">Digital Wallet</h3>
                        <p class="text-gray-600">
                            Easily manage your earnings with our integrated wallet system. Withdraw funds once you reach ₹2,000.
                        </p>
                    </div>

                    <!-- Analytics -->
                    <div class="space-y-4">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-chart-line text-green-500 text-xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800">Detailed Analytics</h3>
                        <p class="text-gray-600">
                            Get insights into your recycling patterns, view monthly trends, and understand your environmental impact.
                        </p>
                    </div>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="mt-12 bg-white rounded-lg shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Frequently Asked Questions</h2>
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">What types of plastic do you accept?</h3>
                        <p class="text-gray-600">We accept all types of plastic (Types 1-7). Each type should be properly sorted before depositing.</p>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">How are rewards calculated?</h3>
                        <p class="text-gray-600">You earn ₹0.111 per unit of plastic. The quantity is measured at our recycling centers.</p>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">When can I withdraw my earnings?</h3>
                        <p class="text-gray-600">You can withdraw your earnings once your wallet balance reaches ₹2,000. Withdrawals are processed within 24-48 hours.</p>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Are there any fees?</h3>
                        <p class="text-gray-600">No, there are no hidden fees. You receive the full value of your recycling efforts.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Ready to Start?</h2>
                <p class="text-gray-600 mb-6">Join EcoRewards today and start earning while saving the environment.</p>
                <a href="dashboard.php" class="inline-block bg-green-500 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-600 transition-colors duration-200">
                    Return to Dashboard
                </a>
            </div>
        </div>
    </footer>

    <script>
        // Add smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html> 