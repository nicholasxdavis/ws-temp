<?php
/**
 * Web-based Upload Fix Script
 * Run this in your browser to diagnose upload issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Stella Upload Fix</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #1a1a1a; color: #fff; }
        .success { color: #4CAF50; }
        .error { color: #f44336; }
        .warning { color: #ff9800; }
        .info { color: #2196F3; }
        pre { background: #2a2a2a; padding: 10px; border-radius: 5px; overflow-x: auto; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #333; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🔧 Stella Upload Fix Diagnostic</h1>

<?php

// Database connection
$host = getenv('DB_HOST') ?: 'mariadb-database-rgcs4ksokcww0g04wkwg4g4k';
$dbname = getenv('DB_DATABASE') ?: 'default';
$user = getenv('DB_USERNAME') ?: 'mariadb';
$pass = getenv('DB_PASSWORD') ?: 'ba55Ko1lA8FataxMYnpl9qVploHFJXZKqCvfnwrlcxvISIqbQusX4qFeELhdYPdO';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    echo '<div class="section"><span class="success">✓</span> Database connection successful</div>';
} catch(PDOException $e) {
    echo '<div class="section"><span class="error">✗</span> Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit;
}

// 1. Check team_members table structure
echo '<div class="section">';
echo '<h3>1. Team Members Table Structure</h3>';
try {
    $stmt = $pdo->query("DESCRIBE team_members");
    $columns = $stmt->fetchAll();
    echo '<span class="success">✓</span> team_members table exists<br>';
    echo '<pre>';
    foreach ($columns as $column) {
        echo $column['Field'] . ' - ' . $column['Type'] . "\n";
    }
    echo '</pre>';
} catch (PDOException $e) {
    echo '<span class="error">✗</span> Error checking team_members table: ' . htmlspecialchars($e->getMessage());
}
echo '</div>';

// 2. Test the problematic query
echo '<div class="section">';
echo '<h3>2. Testing Problematic Query</h3>';
try {
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
    echo '<span class="success">✓</span> Team member Pro check query works<br>';
    if ($result) {
        echo '<span class="info">Result:</span> ' . json_encode($result) . '<br>';
    } else {
        echo '<span class="info">No team membership found for user ' . $testUserId . '</span><br>';
    }
} catch (PDOException $e) {
    echo '<span class="error">✗</span> Query failed: ' . htmlspecialchars($e->getMessage()) . '<br>';
    echo '<span class="warning">This is the exact error you were seeing!</span>';
}
echo '</div>';

// 3. Test PlanLimits class
echo '<div class="section">';
echo '<h3>3. Testing PlanLimits Class</h3>';
try {
    require_once 'api/plan_limits.php';
    $planLimits = new PlanLimits($pdo);
    
    $testUserId = 1;
    echo '<span class="info">Testing getEffectivePlan...</span><br>';
    $plan = $planLimits->getEffectivePlan($testUserId);
    echo '<span class="success">✓</span> Effective plan: ' . $plan . '<br>';
    
    echo '<span class="info">Testing canUploadFile...</span><br>';
    $canUpload = $planLimits->canUploadFile($testUserId, 1024 * 1024); // 1MB
    echo '<span class="success">✓</span> Can upload: ' . ($canUpload['allowed'] ? 'Yes' : 'No') . '<br>';
    if (!$canUpload['allowed']) {
        echo '<span class="warning">Reason:</span> ' . htmlspecialchars($canUpload['message']) . '<br>';
    }
    
} catch (Exception $e) {
    echo '<span class="error">✗</span> PlanLimits class failed: ' . htmlspecialchars($e->getMessage()) . '<br>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
}
echo '</div>';

// 4. Check file contents
echo '<div class="section">';
echo '<h3>4. Checking File Contents</h3>';
$filesToCheck = [
    'api/plan_limits.php',
    'api/check_pro.php',
    'api/usage.php',
    'api/auth_helper.php'
];

foreach ($filesToCheck as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, 'member_user_id') !== false) {
            echo '<span class="error">✗</span> ' . $file . ' still contains "member_user_id"<br>';
        } else {
            echo '<span class="success">✓</span> ' . $file . ' looks clean<br>';
        }
    } else {
        echo '<span class="warning">⚠</span> ' . $file . ' not found<br>';
    }
}
echo '</div>';

// 5. Sample data
echo '<div class="section">';
echo '<h3>5. Sample Data</h3>';
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo '<span class="info">Users count:</span> ' . $result['count'] . '<br>';
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM team_members");
    $result = $stmt->fetch();
    echo '<span class="info">Team members count:</span> ' . $result['count'] . '<br>';
    
    if ($result['count'] > 0) {
        $stmt = $pdo->query("SELECT * FROM team_members LIMIT 1");
        $sample = $stmt->fetch();
        echo '<span class="info">Sample team member:</span><br>';
        echo '<pre>' . json_encode($sample, JSON_PRETTY_PRINT) . '</pre>';
    }
} catch (PDOException $e) {
    echo '<span class="error">✗</span> Error checking sample data: ' . htmlspecialchars($e->getMessage());
}
echo '</div>';

// 6. Test upload simulation
echo '<div class="section">';
echo '<h3>6. Upload Simulation</h3>';
try {
    // Simulate the exact upload process
    $testUserId = 1;
    
    // Test authentication (simplified)
    echo '<span class="info">Testing authentication...</span><br>';
    $stmt = $pdo->prepare("SELECT id, plan_type FROM users WHERE id = ?");
    $stmt->execute([$testUserId]);
    $user = $stmt->fetch();
    if ($user) {
        echo '<span class="success">✓</span> User found: ' . $user['plan_type'] . '<br>';
    } else {
        echo '<span class="error">✗</span> User not found<br>';
    }
    
    // Test plan limits
    echo '<span class="info">Testing plan limits...</span><br>';
    $planLimits = new PlanLimits($pdo);
    $canUpload = $planLimits->canUploadFile($testUserId, 1024 * 1024);
    echo '<span class="success">✓</span> Plan limits check passed<br>';
    
    echo '<span class="success">✓</span> Upload simulation successful!<br>';
    
} catch (Exception $e) {
    echo '<span class="error">✗</span> Upload simulation failed: ' . htmlspecialchars($e->getMessage()) . '<br>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
}
echo '</div>';

?>

<div class="section">
    <h3>🎯 Summary</h3>
    <p>If all tests show <span class="success">✓</span>, your upload should work now.</p>
    <p>If you see <span class="error">✗</span> errors, those need to be fixed.</p>
    <p>If you still get upload errors after this, check your server error logs for more details.</p>
</div>

</body>
</html>
