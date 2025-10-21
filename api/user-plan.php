<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-User-ID, X-User-Email');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Suppress errors for cleaner JSON output
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config/env.php';

try {
    // Get user ID from headers
    $userId = $_SERVER['HTTP_X_USER_ID'] ?? null;
    $userEmail = $_SERVER['HTTP_X_USER_EMAIL'] ?? null;
    
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['error' => 'User authentication required']);
        exit;
    }
    
    $userId = (int)$userId;
    
    // Get database connection
    $config = require __DIR__ . '/../config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Get user's plan
    $plan = getUserPlan($pdo, $userId);
    
    echo json_encode(['plan' => $plan]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function getUserPlan($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("SELECT plan_type FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        // Default to 'free' if no plan is set
        return $user ? $user['plan_type'] : 'free';
    } catch (Exception $e) {
        return 'free'; // Default to free plan on error
    }
}
?>
