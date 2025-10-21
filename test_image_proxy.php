<?php
/**
 * Test Image Proxy
 * Simple test to verify image proxy is working
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Image Proxy Test</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #1a1a1a; color: #fff; }
        .test-image { max-width: 300px; max-height: 300px; border: 2px solid #333; margin: 10px; }
        .error { color: #f44336; }
        .success { color: #4CAF50; }
        .info { color: #2196F3; }
    </style>
</head>
<body>
    <h1>🖼️ Image Proxy Test</h1>

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
    echo '<p class="success">✓ Database connection successful</p>';
} catch(PDOException $e) {
    echo '<p class="error">✗ Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</p>';
    exit;
}

// Get some sample assets
try {
    $stmt = $pdo->query("SELECT id, name, type, share_token FROM assets WHERE type LIKE 'image/%' LIMIT 5");
    $assets = $stmt->fetchAll();
    
    if (empty($assets)) {
        echo '<p class="info">No image assets found in database</p>';
    } else {
        echo '<h2>Testing Image Assets:</h2>';
        
        foreach ($assets as $asset) {
            echo '<div style="margin: 20px 0; padding: 15px; border: 1px solid #333; border-radius: 5px;">';
            echo '<h3>' . htmlspecialchars($asset['name']) . '</h3>';
            echo '<p><strong>ID:</strong> ' . $asset['id'] . '</p>';
            echo '<p><strong>Type:</strong> ' . htmlspecialchars($asset['type']) . '</p>';
            echo '<p><strong>Share Token:</strong> ' . ($asset['share_token'] ? htmlspecialchars($asset['share_token']) : 'None') . '</p>';
            
            // Test different URL formats
            $urls = [];
            
            if ($asset['share_token']) {
                $urls[] = [
                    'name' => 'Share Token URL',
                    'url' => '/api/image_proxy.php?t=' . $asset['share_token'] . '&size=300'
                ];
            }
            
            $urls[] = [
                'name' => 'Asset ID URL',
                'url' => '/api/image_proxy.php?id=' . $asset['id'] . '&size=300'
            ];
            
            foreach ($urls as $urlTest) {
                echo '<div style="margin: 10px 0;">';
                echo '<p><strong>' . $urlTest['name'] . ':</strong></p>';
                echo '<p><code>' . htmlspecialchars($urlTest['url']) . '</code></p>';
                echo '<img src="' . htmlspecialchars($urlTest['url']) . '" class="test-image" onload="this.style.border=\'2px solid #4CAF50\'" onerror="this.style.border=\'2px solid #f44336\'; this.alt=\'Failed to load\'" alt="Loading...">';
                echo '</div>';
            }
            
            echo '</div>';
        }
    }
    
} catch (PDOException $e) {
    echo '<p class="error">✗ Error fetching assets: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

?>

<div style="margin: 20px 0; padding: 15px; border: 1px solid #333; border-radius: 5px;">
    <h2>🔍 Debug Information</h2>
    <p><strong>Current URL:</strong> <?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?></p>
    <p><strong>Base URL:</strong> <?php echo htmlspecialchars((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']); ?></p>
    <p><strong>Image Proxy Path:</strong> /api/image_proxy.php</p>
</div>

<script>
// Test image loading with JavaScript
console.log('Image Proxy Test Page Loaded');

// Add event listeners to all test images
document.querySelectorAll('.test-image').forEach((img, index) => {
    img.addEventListener('load', function() {
        console.log(`✓ Image ${index + 1} loaded successfully:`, this.src);
    });
    
    img.addEventListener('error', function() {
        console.error(`✗ Image ${index + 1} failed to load:`, this.src);
    });
});
</script>

</body>
</html>
