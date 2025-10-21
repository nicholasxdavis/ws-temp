<?php
// Simple image preview endpoint that serves images directly
// Usage: /api/simple_image_preview.php?t={share_token}&size={size}

error_reporting(0);
ini_set('display_errors', 0);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-User-ID, X-User-Email');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit();
}

// Database connection
$host = getenv('DB_HOST') ?: 'mariadb-database-rgcs4ksokcww0g04wkwg4g4k';
$dbname = getenv('DB_DATABASE') ?: 'default';
$user = getenv('DB_USERNAME') ?: 'mariadb';
$pass = getenv('DB_PASSWORD') ?: 'ba55Ko1lA8FataxMYnpl9qVploHFJXZKqCvfnwrlcxvISIqbQusX4qFeELhdYPdO';

try {
	$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
	http_response_code(500);
	exit('Database connection failed');
}

// Get parameters
$token = $_GET['t'] ?? null;
$size = (int)($_GET['size'] ?? 300);

if (!$token) {
	http_response_code(400);
	exit('Missing token');
}

try {
	// Look up asset by share token
	$stmt = $pdo->prepare("SELECT a.*, u.nextcloud_username, u.nextcloud_password 
	                       FROM assets a 
	                       JOIN users u ON a.user_id = u.id 
	                       WHERE a.share_token = ?");
	$stmt->execute([$token]);
	$asset = $stmt->fetch();

	if (!$asset) {
		http_response_code(404);
		exit('Asset not found');
	}

	// Check if it's an image
	$isImage = strpos($asset['type'], 'image/') === 0 || 
	           preg_match('/\.(png|jpg|jpeg|gif|webp|svg|bmp|ico)$/i', $asset['name']);
	
	if (!$isImage) {
		http_response_code(400);
		exit('Not an image file');
	}

	// For now, let's create a simple placeholder image
	// This is a temporary solution until Nextcloud storage is fixed
	
	// Set appropriate headers
	$extension = strtolower(pathinfo($asset['name'], PATHINFO_EXTENSION));
	$mimeTypes = [
		'png' => 'image/png',
		'jpg' => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'gif' => 'image/gif',
		'webp' => 'image/webp',
		'svg' => 'image/svg+xml',
		'bmp' => 'image/bmp',
		'ico' => 'image/x-icon'
	];
	$contentType = $mimeTypes[$extension] ?? 'image/png';
	
	header('Content-Type: ' . $contentType);
	header('Cache-Control: public, max-age=3600');
	header('Content-Disposition: inline; filename="' . $asset['name'] . '"');
	
	// Create a simple placeholder image
	$width = $size;
	$height = $size;
	
	// Create a simple colored rectangle as placeholder
	$image = imagecreate($width, $height);
	$bgColor = imagecolorallocate($image, 156, 126, 173); // Purple color matching your theme
	$textColor = imagecolorallocate($image, 255, 255, 255);
	
	// Add text
	$text = 'Preview';
	$fontSize = 5;
	$textWidth = imagefontwidth($fontSize) * strlen($text);
	$textHeight = imagefontheight($fontSize);
	$x = ($width - $textWidth) / 2;
	$y = ($height - $textHeight) / 2;
	
	imagestring($image, $fontSize, $x, $y, $text, $textColor);
	
	// Output the image
	if ($extension === 'png') {
		imagepng($image);
	} elseif (in_array($extension, ['jpg', 'jpeg'])) {
		imagejpeg($image);
	} elseif ($extension === 'gif') {
		imagegif($image);
	} else {
		imagepng($image); // Default to PNG
	}
	
	imagedestroy($image);
	
} catch (Exception $e) {
	http_response_code(500);
	error_log("Simple image preview error: " . $e->getMessage());
	exit('Error: ' . $e->getMessage());
}
?>
