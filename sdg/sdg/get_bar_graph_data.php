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
    // Get monthly data for the last 6 months
    $query = "SELECT 
                DATE_FORMAT(date, '%Y-%m') as month,
                COUNT(*) as deposit_count,
                SUM(quantity) as total_quantity,
                SUM(estimated_money) as total_money
              FROM deposits 
              WHERE user_id = ? 
              AND date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
              GROUP BY DATE_FORMAT(date, '%Y-%m')
              ORDER BY month ASC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    $labels = [];
    $deposits = [];
    $quantities = [];
    $money = [];

    while ($row = $result->fetch_assoc()) {
        $labels[] = date('M Y', strtotime($row['month']));
        $deposits[] = $row['deposit_count'];
        $quantities[] = $row['total_quantity'];
        $money[] = $row['total_money'];
    }

    // If no data found, return empty arrays
    if (empty($labels)) {
        $labels = array_fill(0, 6, '');
        $deposits = array_fill(0, 6, 0);
        $quantities = array_fill(0, 6, 0);
        $money = array_fill(0, 6, 0);
    }

    echo json_encode([
        'labels' => $labels,
        'deposits' => $deposits,
        'quantities' => $quantities,
        'money' => $money
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 