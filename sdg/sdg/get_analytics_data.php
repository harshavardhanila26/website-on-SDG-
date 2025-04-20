<?php
require_once 'config/db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$response = [];

try {
    // Get total deposits count
    $stmt = $conn->prepare("SELECT COUNT(*) as total_deposits FROM deposits WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $response['total_deposits'] = $result->fetch_assoc()['total_deposits'];

    // Get total items recycled
    $stmt = $conn->prepare("SELECT SUM(quantity) as total_items FROM deposits WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $response['total_items'] = $result->fetch_assoc()['total_items'] ?? 0;

    // Get total money earned
    $stmt = $conn->prepare("SELECT SUM(estimated_money) as total_earned FROM deposits WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $response['total_earned'] = $result->fetch_assoc()['total_earned'] ?? 0;

    // Get 24-hour activity data
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as hour,
            COUNT(*) as count,
            SUM(quantity) as total_quantity,
            SUM(estimated_money) as total_money
        FROM deposits 
        WHERE user_id = ? 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        GROUP BY hour
        ORDER BY hour ASC
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $labels = [];
    $values = [];
    while ($row = $result->fetch_assoc()) {
        $labels[] = date('h A', strtotime($row['hour']));
        $values[] = $row['count'];
    }
    
    $response['labels'] = $labels;
    $response['values'] = $values;

    // Get deposit distribution by plastic type
    $stmt = $conn->prepare("
        SELECT 
            plastic_type,
            COUNT(*) as count,
            SUM(quantity) as total_quantity,
            SUM(estimated_money) as total_money
        FROM deposits 
        WHERE user_id = ?
        GROUP BY plastic_type
        ORDER BY count DESC
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $plastic_types = [];
    $plastic_quantities = [];
    $plastic_earnings = [];
    while ($row = $result->fetch_assoc()) {
        $plastic_types[] = $row['plastic_type'];
        $plastic_quantities[] = $row['total_quantity'];
        $plastic_earnings[] = $row['total_money'];
    }
    
    $response['plastic_types'] = $plastic_types;
    $response['plastic_quantities'] = $plastic_quantities;
    $response['plastic_earnings'] = $plastic_earnings;

    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch analytics data']);
}
?> 