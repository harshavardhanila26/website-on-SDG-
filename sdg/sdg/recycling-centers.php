<?php
session_start();
require_once 'config/db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recycling Centers - EcoRewards</title>
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
                <h1 class="text-4xl font-bold text-center mb-4">Our Recycling Centers</h1>
                <p class="text-xl text-center max-w-3xl mx-auto">Find the nearest recycling center and start contributing to a greener future today.</p>
            </div>
        </div>

        <!-- Map and Centers Section -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Centers List -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6">Available Centers</h2>
                        <div class="space-y-6" id="centersList">
                            <!-- Mangalagiri Center -->
                            <div class="center-item p-4 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors duration-200" 
                                 data-lat="16.4307" data-lng="80.5487" onclick="centerClick(this)">
                                <h3 class="text-lg font-semibold text-gray-800">Mangalagiri Center</h3>
                                <p class="text-gray-600 mt-2">
                                    <i class="fas fa-map-marker-alt text-red-500 mr-2"></i>
                                    123 Main Road, Mangalagiri
                                </p>
                                <p class="text-gray-600 mt-1">
                                    <i class="fas fa-clock text-blue-500 mr-2"></i>
                                    9:00 AM - 6:00 PM
                                </p>
                                <p class="text-gray-600 mt-1">
                                    <i class="fas fa-phone text-green-500 mr-2"></i>
                                    +91 98765 43210
                                </p>
                            </div>

                            <!-- Vijayawada Center -->
                            <div class="center-item p-4 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors duration-200"
                                 data-lat="16.5062" data-lng="80.6480" onclick="centerClick(this)">
                                <h3 class="text-lg font-semibold text-gray-800">Vijayawada Center</h3>
                                <p class="text-gray-600 mt-2">
                                    <i class="fas fa-map-marker-alt text-red-500 mr-2"></i>
                                    456 MG Road, Vijayawada
                                </p>
                                <p class="text-gray-600 mt-1">
                                    <i class="fas fa-clock text-blue-500 mr-2"></i>
                                    9:00 AM - 6:00 PM
                                </p>
                                <p class="text-gray-600 mt-1">
                                    <i class="fas fa-phone text-green-500 mr-2"></i>
                                    +91 98765 43211
                                </p>
                            </div>

                            <!-- Neerukonda Center -->
                            <div class="center-item p-4 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors duration-200"
                                 data-lat="16.4867" data-lng="80.5196" onclick="centerClick(this)">
                                <h3 class="text-lg font-semibold text-gray-800">Neerukonda Center</h3>
                                <p class="text-gray-600 mt-2">
                                    <i class="fas fa-map-marker-alt text-red-500 mr-2"></i>
                                    789 University Road, Neerukonda
                                </p>
                                <p class="text-gray-600 mt-1">
                                    <i class="fas fa-clock text-blue-500 mr-2"></i>
                                    9:00 AM - 6:00 PM
                                </p>
                                <p class="text-gray-600 mt-1">
                                    <i class="fas fa-phone text-green-500 mr-2"></i>
                                    +91 98765 43212
                                </p>
                            </div>

                            <!-- Guntur Center -->
                            <div class="center-item p-4 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors duration-200"
                                 data-lat="16.3067" data-lng="80.4365" onclick="centerClick(this)">
                                <h3 class="text-lg font-semibold text-gray-800">Guntur Center</h3>
                                <p class="text-gray-600 mt-2">
                                    <i class="fas fa-map-marker-alt text-red-500 mr-2"></i>
                                    321 Ring Road, Guntur
                                </p>
                                <p class="text-gray-600 mt-1">
                                    <i class="fas fa-clock text-blue-500 mr-2"></i>
                                    9:00 AM - 6:00 PM
                                </p>
                                <p class="text-gray-600 mt-1">
                                    <i class="fas fa-phone text-green-500 mr-2"></i>
                                    +91 98765 43213
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Map -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <div id="map" class="w-full h-[600px] rounded-lg"></div>
                    </div>
                </div>
            </div>

            <!-- Additional Information -->
            <div class="mt-12 bg-white rounded-lg shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Center Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="space-y-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-clock text-blue-500 text-xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800">Operating Hours</h3>
                        <p class="text-gray-600">
                            All centers operate from Monday to Friday, 9:00 AM to 6:00 PM. Please ensure to visit during these hours.
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-recycle text-green-500 text-xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800">Accepted Materials</h3>
                        <p class="text-gray-600">
                            We accept all types of plastic (Types 1-7). Please ensure materials are clean and sorted by type.
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-hand-holding-usd text-purple-500 text-xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800">Instant Rewards</h3>
                        <p class="text-gray-600">
                            Receive immediate credits to your wallet upon successful deposit of recyclable materials.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let map;
        let markers = [];
        let activeInfoWindow = null;

        function loadGoogleMaps() {
            const script = document.createElement('script');
            script.src = 'https://maps.googleapis.com/maps/api/js?key=AIzaSyB41DRUbKWJHPxaFjMAwdrzWzbVKartNGg&callback=initMap';
            script.async = true;
            script.defer = true;
            document.head.appendChild(script);
        }

        function initMap() {
            // Center the map on Vijayawada
            const vijayawada = { lat: 16.5062, lng: 80.6480 };
            
            map = new google.maps.Map(document.getElementById('map'), {
                center: vijayawada,
                zoom: 11,
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
            });

            // Add markers for all centers
            const centers = document.querySelectorAll('.center-item');
            centers.forEach(center => {
                const lat = parseFloat(center.dataset.lat);
                const lng = parseFloat(center.dataset.lng);
                const title = center.querySelector('h3').textContent;
                const address = center.querySelector('p').textContent;
                
                addMarker({ lat, lng }, title, address);
            });
        }

        function addMarker(position, title, address) {
            const marker = new google.maps.Marker({
                position: position,
                map: map,
                title: title,
                animation: google.maps.Animation.DROP,
                icon: {
                    url: 'https://maps.google.com/mapfiles/ms/icons/green-dot.png'
                }
            });

            const infoWindow = new google.maps.InfoWindow({
                content: `
                    <div style="padding: 10px; max-width: 200px;">
                        <h3 style="font-weight: bold; margin-bottom: 5px;">${title}</h3>
                        <p style="margin: 5px 0;">${address}</p>
                        <p style="margin: 5px 0;">Open: Mon-Fri 9:00 AM - 6:00 PM</p>
                    </div>
                `
            });

            marker.addListener('click', () => {
                if (activeInfoWindow) {
                    activeInfoWindow.close();
                }
                infoWindow.open(map, marker);
                activeInfoWindow = infoWindow;
            });

            markers.push(marker);
        }

        function centerClick(element) {
            const lat = parseFloat(element.dataset.lat);
            const lng = parseFloat(element.dataset.lng);
            const position = { lat, lng };

            map.panTo(position);
            map.setZoom(15);

            // Find and click the corresponding marker
            const marker = markers.find(m => 
                m.getPosition().lat() === lat && 
                m.getPosition().lng() === lng
            );
            if (marker) {
                google.maps.event.trigger(marker, 'click');
            }

            // Highlight the clicked center
            document.querySelectorAll('.center-item').forEach(item => {
                item.classList.remove('bg-gray-50');
            });
            element.classList.add('bg-gray-50');
        }

        // Load Google Maps
        loadGoogleMaps();
    </script>
</body>
</html> 