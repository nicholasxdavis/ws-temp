<?php
/**
 * Asset Cards Debug Script
 * Debug styling and rendering issues with asset cards
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Asset Cards Debug</title>
    <style>
        body { 
            font-family: monospace; 
            margin: 20px; 
            background: #1a1a1a; 
            color: #fff; 
        }
        .debug-section { 
            margin: 20px 0; 
            padding: 15px; 
            border: 1px solid #333; 
            border-radius: 5px; 
        }
        .success { color: #4CAF50; }
        .error { color: #f44336; }
        .warning { color: #ff9800; }
        .info { color: #2196F3; }
        
        /* Copy the exact styles from dashboard */
        .asset-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        .asset-card:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }
        .aspect-square {
            aspect-ratio: 1 / 1;
        }
        .bg-black\/20 {
            background-color: rgba(0, 0, 0, 0.2);
        }
        .rounded-md {
            border-radius: 0.375rem;
        }
        .flex {
            display: flex;
        }
        .items-center {
            align-items: center;
        }
        .justify-center {
            justify-content: center;
        }
        .mb-2 {
            margin-bottom: 0.5rem;
        }
        .overflow-hidden {
            overflow: hidden;
        }
        .relative {
            position: relative;
        }
        .absolute {
            position: absolute;
        }
        .inset-0 {
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
        }
        .w-full {
            width: 100%;
        }
        .h-full {
            height: 100%;
        }
        .object-cover {
            object-fit: cover;
        }
        .opacity-0 {
            opacity: 0;
        }
        .transition-opacity {
            transition-property: opacity;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        }
        .duration-300 {
            transition-duration: 300ms;
        }
        .loading-spinner {
            width: 1.5rem;
            height: 1.5rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .text-sm {
            font-size: 0.875rem;
            line-height: 1.25rem;
        }
        .font-medium {
            font-weight: 500;
        }
        .truncate {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .text-xs {
            font-size: 0.75rem;
            line-height: 1rem;
        }
        .text-4xl {
            font-size: 2.25rem;
            line-height: 2.5rem;
        }
        .fas {
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
        }
        .fa-image:before {
            content: "\f03e";
        }
        .fa-file-alt:before {
            content: "\f15c";
        }
    </style>
</head>
<body>
    <h1>🔍 Asset Cards Debug</h1>

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
    echo '<div class="debug-section"><span class="success">✓</span> Database connection successful</div>';
} catch(PDOException $e) {
    echo '<div class="debug-section"><span class="error">✗</span> Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit;
}

// Get sample assets
try {
    $stmt = $pdo->query("SELECT id, name, type, share_token, file_size FROM assets LIMIT 5");
    $assets = $stmt->fetchAll();
    
    if (empty($assets)) {
        echo '<div class="debug-section"><span class="warning">⚠</span> No assets found in database</div>';
    } else {
        echo '<div class="debug-section">';
        echo '<h2>Testing Asset Card Rendering</h2>';
        echo '<p>Rendering ' . count($assets) . ' sample assets with the exact same code as the dashboard:</p>';
        
        foreach ($assets as $asset) {
            $isImage = strpos($asset['type'], 'image/') === 0;
            
            // Use the exact same logic as dashboard
            $previewUrl = $isImage ? (
                (isset($asset['preview_url']) ? $asset['preview_url'] : null) || 
                ($asset['share_token'] ? '/api/image_proxy.php?t=' . $asset['share_token'] . '&size=300' : '/api/image_proxy.php?id=' . $asset['id'] . '&size=300')
            ) : null;
            
            echo '<div style="margin: 20px 0; padding: 15px; border: 1px solid #555; border-radius: 5px;">';
            echo '<h3>Asset: ' . htmlspecialchars($asset['name']) . '</h3>';
            echo '<p><strong>Type:</strong> ' . htmlspecialchars($asset['type']) . ' ' . ($isImage ? '<span class="success">(Image)</span>' : '<span class="warning">(Not Image)</span>') . '</p>';
            echo '<p><strong>Preview URL:</strong> ' . ($previewUrl ? htmlspecialchars($previewUrl) : 'None') . '</p>';
            
            // Render the exact same card HTML as dashboard
            echo '<div class="asset-card glass-card p-3 rounded-lg flex flex-col" style="width: 200px; margin: 10px 0;">';
            echo '<div class="aspect-square bg-black/20 rounded-md flex items-center justify-center mb-2 overflow-hidden relative">';
            
            if ($isImage && $previewUrl) {
                echo '<div class="absolute inset-0 flex items-center justify-center">';
                echo '<div class="loading-spinner"></div>';
                echo '</div>';
                echo '<img src="' . htmlspecialchars($previewUrl) . '" class="w-full h-full object-cover opacity-0 transition-opacity duration-300" crossorigin="anonymous" onload="this.style.opacity=\'1\'; this.previousElementSibling.style.display=\'none\'; console.log(\'Image loaded:\', this.src)" onerror="this.style.display=\'none\'; this.previousElementSibling.style.display=\'none\'; this.parentElement.innerHTML=\'<i class=\'fas fa-image text-4xl\' style=\'color: #666;\' title=\'Preview unavailable\'></i>\'; console.error(\'Image failed:\', this.src)" alt="Loading...">';
            } else {
                echo '<i class="fas fa-file-alt text-4xl" style="color: #666;"></i>';
            }
            
            echo '</div>';
            echo '<p class="text-sm font-medium truncate" title="' . htmlspecialchars($asset['name']) . '">' . htmlspecialchars($asset['name']) . '</p>';
            echo '<div class="flex justify-between items-center mt-1">';
            echo '<p class="text-xs" style="color: #999;">' . number_format($asset['file_size'] / 1024, 2) . ' KB</p>';
            echo '</div>';
            echo '</div>';
            
            echo '</div>';
        }
        
        echo '</div>';
    }
    
} catch (PDOException $e) {
    echo '<div class="debug-section"><span class="error">✗</span> Error fetching assets: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

?>

<div class="debug-section">
    <h2>🎨 CSS Debug Information</h2>
    <p><strong>Asset Card Classes:</strong> asset-card glass-card p-3 rounded-lg flex flex-col</p>
    <p><strong>Image Container Classes:</strong> aspect-square bg-black/20 rounded-md flex items-center justify-center mb-2 overflow-hidden relative</p>
    <p><strong>Image Classes:</strong> w-full h-full object-cover opacity-0 transition-opacity duration-300</p>
    <p><strong>Loading Spinner Classes:</strong> loading-spinner</p>
</div>

<div class="debug-section">
    <h2>🔧 JavaScript Debug</h2>
    <p>Open browser console to see image loading logs.</p>
    <p>Look for:</p>
    <ul>
        <li>"Image loaded:" - successful image loads</li>
        <li>"Image failed:" - failed image loads</li>
        <li>Any CSS or JavaScript errors</li>
    </ul>
</div>

<script>
console.log('Asset Cards Debug Page Loaded');

// Test if CSS classes are working
document.addEventListener('DOMContentLoaded', function() {
    const assetCards = document.querySelectorAll('.asset-card');
    console.log('Found', assetCards.length, 'asset cards');
    
    assetCards.forEach((card, index) => {
        console.log(`Card ${index + 1}:`, {
            classes: card.className,
            computedStyle: window.getComputedStyle(card)
        });
    });
    
    // Test image loading
    const images = document.querySelectorAll('img');
    console.log('Found', images.length, 'images');
    
    images.forEach((img, index) => {
        img.addEventListener('load', function() {
            console.log(`✓ Image ${index + 1} loaded successfully:`, this.src);
        });
        
        img.addEventListener('error', function() {
            console.error(`✗ Image ${index + 1} failed to load:`, this.src);
        });
    });
});
</script>

</body>
</html>
