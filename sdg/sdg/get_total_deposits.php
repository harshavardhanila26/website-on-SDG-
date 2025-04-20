<?php
require_once 'config/db.php';
session_start();

// Get total deposits count from all centers
$stmt = $conn->prepare("SELECT COUNT(*) as total_deposits FROM deposits");
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

// Return JSON response
header('Content-Type: application/json');
echo json_encode($data);
?> 