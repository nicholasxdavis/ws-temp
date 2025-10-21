<?php
/**
 * Upload Fix Script
 * Specifically fixes the upload SQL error
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Upload Fix Script ===\n\n";

// Database connection
$host = getenv('DB_HOST') ?: 'mariadb-database-rgcs4ksokcww0g04wkwg4g4k';
$dbname = getenv('DB_DATABASE') ?: 'default';
$user = getenv('DB_USERNAME') ?: 'mariadb';
$pass = getenv('DB_PASSWORD') ?: 'ba55Ko1lA8FataxMYnpl9qVploHFJXZKqCvfnwrlcxvISIqbQusX4qFeELhdYPdO';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    echo "✓ Database connection successful\n";
} catch(PDOException $e) {
    die("✗ Database connection failed: " . $e->getMessage() . "\n");
}

// Test the exact query that's failing in plan_limits.php
echo "\nTesting the exact query that was failing...\n";

try {
    // This is the query from plan_limits.php that was causing the error
    $testUserId = 1;
    $stmt = $pdo->prepare("
        SELECT u.plan_type as owner_plan
        FROM team_members tm
        JOIN users u ON tm.workspace_owner_id = u.id
        JOIN users member ON member.email = tm.member_email
        WHERE member.id = ?
        AND tm.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$testUserId]);
    $result = $stmt->fetch();
    echo "✓ Team member Pro check query works\n";
    if ($result) {
        echo "  Result: " . json_encode($result) . "\n";
    } else {
        echo "  No team membership found for user $testUserId\n";
    }
} catch (PDOException $e) {
    echo "✗ Query failed: " . $e->getMessage() . "\n";
    echo "This is the exact error you were seeing!\n";
}

// Test the team member count query
echo "\nTesting team member count query...\n";
try {
    $testUserId = 1;
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM team_members 
        WHERE workspace_owner_id = ? 
        AND status IN ('active', 'pending')
    ");
    $stmt->execute([$testUserId]);
    $result = $stmt->fetch();
    echo "✓ Team member count query works\n";
    echo "  Count: " . $result['count'] . "\n";
} catch (PDOException $e) {
    echo "✗ Team member count query failed: " . $e->getMessage() . "\n";
}

// Test the storage check query
echo "\nTesting storage check query...\n";
try {
    $testUserId = 1;
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(file_size), 0) as total_bytes 
        FROM assets 
        WHERE user_id = ?
    ");
    $stmt->execute([$testUserId]);
    $result = $stmt->fetch();
    echo "✓ Storage check query works\n";
    echo "  Total bytes: " . $result['total_bytes'] . "\n";
} catch (PDOException $e) {
    echo "✗ Storage check query failed: " . $e->getMessage() . "\n";
}

// Test the complete PlanLimits class
echo "\nTesting PlanLimits class...\n";
try {
    require_once 'api/plan_limits.php';
    $planLimits = new PlanLimits($pdo);
    
    $testUserId = 1;
    echo "Testing getEffectivePlan...\n";
    $plan = $planLimits->getEffectivePlan($testUserId);
    echo "✓ Effective plan: $plan\n";
    
    echo "Testing canUploadFile...\n";
    $canUpload = $planLimits->canUploadFile($testUserId, 1024 * 1024); // 1MB
    echo "✓ Can upload: " . ($canUpload['allowed'] ? 'Yes' : 'No') . "\n";
    if (!$canUpload['allowed']) {
        echo "  Reason: " . $canUpload['message'] . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ PlanLimits class failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// Check if there are any cached files that might be causing issues
echo "\nChecking for cached files...\n";
$cacheFiles = [
    'api/plan_limits.php',
    'api/check_pro.php',
    'api/usage.php',
    'api/auth_helper.php'
];

foreach ($cacheFiles as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, 'member_user_id') !== false) {
            echo "✗ $file still contains 'member_user_id'\n";
        } else {
            echo "✓ $file looks clean\n";
        }
    } else {
        echo "⚠ $file not found\n";
    }
}

echo "\n=== Upload Fix Complete ===\n";
echo "If all tests pass with ✓, try uploading again.\n";
echo "If you still get errors, check the server logs for more details.\n";
?>
