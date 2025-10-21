<?php
/**
 * Simple Image Test
 * Test if we can create a simple image and display it
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Image Test</title>
    <style>
        body { 
            font-family: monospace; 
            margin: 20px; 
            background: #1a1a1a; 
            color: #fff; 
        }
        .test-section { 
            margin: 20px 0; 
            padding: 15px; 
            border: 1px solid #333; 
            border-radius: 5px; 
        }
        .test-image { 
            max-width: 200px; 
            max-height: 200px; 
            border: 2px solid #333; 
            margin: 10px; 
        }
        .success { color: #4CAF50; }
        .error { color: #f44336; }
        .info { color: #2196F3; }
        
        /* Test the exact asset card styles */
        .asset-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            padding: 12px;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            width: 200px;
            margin: 10px;
        }
        .aspect-square {
            aspect-ratio: 1 / 1;
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
            overflow: hidden;
            position: relative;
        }
        .test-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .test-img.loaded {
            opacity: 1;
        }
        .loading-spinner {
            width: 24px;
            height: 24px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <h1>🖼️ Simple Image Test</h1>

    <div class="test-section">
        <h2>Test 1: Basic Image Display</h2>
        <p>Testing if basic image display works:</p>
        
        <!-- Test with a simple placeholder image -->
        <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjNDQ0Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iI2ZmZiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPlRlc3QgSW1hZ2U8L3RleHQ+PC9zdmc+" 
             class="test-image" 
             onload="console.log('✓ Basic image loaded')" 
             onerror="console.error('✗ Basic image failed')" 
             alt="Test Image">
    </div>

    <div class="test-section">
        <h2>Test 2: Asset Card Style Test</h2>
        <p>Testing the exact asset card structure:</p>
        
        <div class="asset-card">
            <div class="aspect-square">
                <div class="loading-spinner"></div>
                <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjNDQ0Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iI2ZmZiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkFzc2V0IENhcmQ8L3RleHQ+PC9zdmc+" 
                     class="test-img" 
                     onload="this.classList.add('loaded'); this.previousElementSibling.style.display='none'; console.log('✓ Asset card image loaded')" 
                     onerror="this.style.display='none'; this.previousElementSibling.style.display='none'; console.error('✗ Asset card image failed')" 
                     alt="Asset Card Test">
            </div>
            <p style="font-size: 0.875rem; font-weight: 500; margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">Test Asset</p>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 4px;">
                <p style="font-size: 0.75rem; color: #999; margin: 0;">1.5 KB</p>
            </div>
        </div>
    </div>

    <div class="test-section">
        <h2>Test 3: Image Proxy Test</h2>
        <p>Testing if image proxy endpoint exists:</p>
        
        <?php
        // Test if image proxy file exists
        if (file_exists('api/image_proxy.php')) {
            echo '<p class="success">✓ image_proxy.php file exists</p>';
        } else {
            echo '<p class="error">✗ image_proxy.php file not found</p>';
        }
        
        // Test if we can access it
        $proxyUrl = '/api/image_proxy.php?id=1&size=300';
        echo '<p><strong>Proxy URL:</strong> ' . htmlspecialchars($proxyUrl) . '</p>';
        ?>
        
        <div class="asset-card">
            <div class="aspect-square">
                <div class="loading-spinner"></div>
                <img src="<?php echo htmlspecialchars($proxyUrl); ?>" 
                     class="test-img" 
                     onload="this.classList.add('loaded'); this.previousElementSibling.style.display='none'; console.log('✓ Image proxy loaded')" 
                     onerror="this.style.display='none'; this.previousElementSibling.style.display='none'; console.error('✗ Image proxy failed:', this.src)" 
                     alt="Image Proxy Test">
            </div>
            <p style="font-size: 0.875rem; font-weight: 500; margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">Image Proxy Test</p>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 4px;">
                <p style="font-size: 0.75rem; color: #999; margin: 0;">Unknown KB</p>
            </div>
        </div>
    </div>

    <div class="test-section">
        <h2>🔍 Debug Information</h2>
        <p><strong>Current URL:</strong> <?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?></p>
        <p><strong>Base URL:</strong> <?php echo htmlspecialchars((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']); ?></p>
        <p><strong>Image Proxy Path:</strong> /api/image_proxy.php</p>
        <p><strong>Dashboard Path:</strong> /dashboard/index.php</p>
    </div>

    <script>
    console.log('Simple Image Test Page Loaded');
    
    // Test CSS classes
    document.addEventListener('DOMContentLoaded', function() {
        const assetCards = document.querySelectorAll('.asset-card');
        console.log('Found', assetCards.length, 'asset cards');
        
        assetCards.forEach((card, index) => {
            const computedStyle = window.getComputedStyle(card);
            console.log(`Card ${index + 1} styles:`, {
                display: computedStyle.display,
                flexDirection: computedStyle.flexDirection,
                width: computedStyle.width,
                height: computedStyle.height,
                backgroundColor: computedStyle.backgroundColor
            });
        });
        
        // Test aspect-ratio support
        const aspectSquares = document.querySelectorAll('.aspect-square');
        aspectSquares.forEach((square, index) => {
            const computedStyle = window.getComputedStyle(square);
            console.log(`Aspect square ${index + 1}:`, {
                aspectRatio: computedStyle.aspectRatio,
                width: computedStyle.width,
                height: computedStyle.height
            });
        });
    });
    </script>

</body>
</html>
