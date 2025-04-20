<?php
require_once 'config/db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$range = isset($_GET['range']) ? $_GET['range'] : 'week';
$user_id = $_SESSION['user_id'];

try {
    // Get activity data
    $stmt = $conn->prepare("
        SELECT 
            DATE(date) as date,
            COUNT(*) as deposit_count,
            SUM(quantity) as total_quantity,
            SUM(estimated_money) as total_money
        FROM deposits 
        WHERE user_id = ? 
        AND date >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
        GROUP BY DATE(date)
        ORDER BY date ASC
    ");
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $labels = [];
    $deposits = [];
    $quantities = [];
    $money = [];

    while ($row = $result->fetch_assoc()) {
        $labels[] = date('d M', strtotime($row['date']));
        $deposits[] = intval($row['deposit_count']);
        $quantities[] = intval($row['total_quantity']);
        $money[] = floatval($row['total_money']);
    }

    // Prepare response
    $response = [
        'labels' => $labels,
        'deposits' => $deposits,
        'quantities' => $quantities,
        'money' => $money
    ];

    header('Content-Type: application/json');
    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 