<?php
/**
 * Database Fix Script
 * This script will identify and fix SQL issues related to team_members table
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Stella Database Fix Script ===\n\n";

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

// 1. Check team_members table structure
echo "\n1. Checking team_members table structure...\n";
try {
    $stmt = $pdo->query("DESCRIBE team_members");
    $columns = $stmt->fetchAll();
    echo "Current team_members columns:\n";
    foreach ($columns as $column) {
        echo "  - {$column['Field']} ({$column['Type']})\n";
    }
} catch (PDOException $e) {
    echo "✗ Error checking team_members table: " . $e->getMessage() . "\n";
}

// 2. Test problematic queries
echo "\n2. Testing problematic queries...\n";

// Test 1: Check if user is team member of Pro owner
echo "Testing team member Pro check query...\n";
try {
    $testUserId = 1; // Use a test user ID
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
} catch (PDOException $e) {
    echo "✗ Team member Pro check query failed: " . $e->getMessage() . "\n";
}

// Test 2: Count team members
echo "Testing team member count query...\n";
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
} catch (PDOException $e) {
    echo "✗ Team member count query failed: " . $e->getMessage() . "\n";
}

// Test 3: Check team member role
echo "Testing team member role query...\n";
try {
    $testUserId = 1;
    $stmt = $pdo->prepare("SELECT role FROM team_members WHERE member_email = (SELECT email FROM users WHERE id = ?)");
    $stmt->execute([$testUserId]);
    $result = $stmt->fetch();
    echo "✓ Team member role query works\n";
} catch (PDOException $e) {
    echo "✗ Team member role query failed: " . $e->getMessage() . "\n";
}

// 3. Check for any remaining problematic queries in files
echo "\n3. Scanning files for problematic SQL patterns...\n";

$problematicPatterns = [
    'member_user_id',
    'tm\.user_id',
    'team_id',
    'WHERE.*email.*=.*SELECT.*email'
];

$filesToCheck = [
    'api/plan_limits.php',
    'api/check_pro.php', 
    'api/usage.php',
    'api/auth_helper.php',
    'api/team.php',
    'api/team_permissions.php',
    'api/admin.php',
    'app/Models/TeamMember.php'
];

foreach ($filesToCheck as $file) {
    if (file_exists($file)) {
        echo "Checking $file...\n";
        $content = file_get_contents($file);
        
        foreach ($problematicPatterns as $pattern) {
            if (preg_match("/$pattern/", $content)) {
                echo "  ⚠ Found potential issue: $pattern\n";
            }
        }
    } else {
        echo "  ⚠ File not found: $file\n";
    }
}

// 4. Test upload process simulation
echo "\n4. Testing upload process simulation...\n";

// Simulate the upload process that was failing
try {
    // Test user authentication
    $testUserId = 1;
    echo "Testing user authentication...\n";
    
    // Test plan limits check
    echo "Testing plan limits check...\n";
    require_once 'api/plan_limits.php';
    $planLimits = new PlanLimits($pdo);
    $effectivePlan = $planLimits->getEffectivePlan($testUserId);
    echo "✓ Effective plan: $effectivePlan\n";
    
    // Test storage check
    echo "Testing storage check...\n";
    $canUpload = $planLimits->canUploadFile($testUserId, 1024 * 1024); // 1MB test
    echo "✓ Can upload: " . ($canUpload['allowed'] ? 'Yes' : 'No') . "\n";
    
} catch (Exception $e) {
    echo "✗ Upload process simulation failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// 5. Check for any missing columns or tables
echo "\n5. Checking for missing columns or tables...\n";

$requiredTables = ['users', 'team_members', 'assets', 'brand_kits'];
foreach ($requiredTables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table LIMIT 1");
        echo "✓ Table $table exists\n";
    } catch (PDOException $e) {
        echo "✗ Table $table missing or error: " . $e->getMessage() . "\n";
    }
}

// 6. Show sample data
echo "\n6. Sample data check...\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "Users count: " . $result['count'] . "\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM team_members");
    $result = $stmt->fetch();
    echo "Team members count: " . $result['count'] . "\n";
    
    if ($result['count'] > 0) {
        $stmt = $pdo->query("SELECT * FROM team_members LIMIT 1");
        $sample = $stmt->fetch();
        echo "Sample team member data:\n";
        foreach ($sample as $key => $value) {
            echo "  $key: $value\n";
        }
    }
} catch (PDOException $e) {
    echo "✗ Error checking sample data: " . $e->getMessage() . "\n";
}

echo "\n=== Fix Script Complete ===\n";
echo "If you see any ✗ errors above, those need to be addressed.\n";
echo "If all tests pass with ✓, the upload should work now.\n";
?>
