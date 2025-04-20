<?php
require_once 'config/db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    // Get total deposits count
    $stmt = $conn->prepare("SELECT COUNT(*) as total_deposits FROM deposits WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_deposits = $result->fetch_assoc()['total_deposits'];

    // Get total items and money earned
    $stmt = $conn->prepare("SELECT SUM(quantity) as total_items, SUM(estimated_money) as total_earned FROM deposits WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $totals = $result->fetch_assoc();
    $total_items = $totals['total_items'] ?? 0;
    $total_earned = $totals['total_earned'] ?? 0;

    // Get 24-hour activity data
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(date, '%H:00') as hour,
            COUNT(*) as deposit_count,
            SUM(quantity) as total_quantity,
            SUM(estimated_money) as total_money
        FROM deposits 
        WHERE user_id = ? AND date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        GROUP BY hour
        ORDER BY hour
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $activity_data = [];
    while ($row = $result->fetch_assoc()) {
        $activity_data[] = $row;
    }

    // Get plastic type distribution
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
    while ($row = $result->fetch_assoc()) {
        $plastic_types[] = $row;
    }

    // Calculate percentages for plastic types
    $total_count = array_sum(array_column($plastic_types, 'count'));
    foreach ($plastic_types as &$type) {
        $type['percentage'] = round(($type['count'] / $total_count) * 100, 1);
    }

    // Prepare response
    $response = [
        'total_deposits' => $total_deposits,
        'total_items' => $total_items,
        'total_earned' => $total_earned,
        'activity_data' => $activity_data,
        'plastic_types' => $plastic_types
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 