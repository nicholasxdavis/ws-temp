<?php
// Dedicated image preview endpoint
// Usage: /api/image_preview.php?t={share_token}&size={size}

error_reporting(0);
ini_set('display_errors', 0);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-User-ID, X-User-Email');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit();
}

// Load services
require_once dirname(__DIR__) . '/app/Services/NextcloudStorage.php';
require_once __DIR__ . '/auth_helper.php';
require_once __DIR__ . '/team_permissions.php';
use App\Services\NextcloudStorage;

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

	// Get user credentials
	$userCreds = [
		'nextcloud_username' => $asset['nextcloud_username'],
		'nextcloud_password' => $asset['nextcloud_password']
	];

	if (empty($userCreds['nextcloud_username']) || empty($userCreds['nextcloud_password'])) {
		http_response_code(500);
		exit('Nextcloud credentials not available');
	}

	// Build WebDAV URL
	$configPath = dirname(__DIR__) . '/config/nextcloud.php';
	$config = require $configPath;
	$baseUrl = rtrim($config['url'], '/');
	$webdavPath = trim($config['webdav_path'], '/');
	$encodedUsername = rawurlencode($userCreds['nextcloud_username']);
	
	// Encode path
	$pathParts = explode('/', $asset['file_path']);
	$encodedParts = array_map('rawurlencode', $pathParts);
	$encodedPath = '/' . ltrim(implode('/', $encodedParts), '/');
	
	$fileUrl = $baseUrl . '/' . $webdavPath . '/' . $encodedUsername . $encodedPath;
	
	// Debug logging
	error_log("Image preview attempt: " . $asset['name'] . " -> " . $fileUrl);
	
	// Set appropriate headers BEFORE streaming
	$contentType = $asset['type'] ?? 'application/octet-stream';
	
	// If the type is not a proper MIME type, try to detect it from the filename
	if (strpos($contentType, '/') === false || $contentType === 'asset') {
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
		$contentType = $mimeTypes[$extension] ?? 'application/octet-stream';
	}
	
	header('Content-Type: ' . $contentType);
	header('Cache-Control: public, max-age=3600');
	header('Content-Disposition: inline; filename="' . $asset['name'] . '"');
	
	// Stream the file
	$ch = curl_init($fileUrl);
	curl_setopt_array($ch, [
		CURLOPT_USERPWD => $userCreds['nextcloud_username'] . ':' . $userCreds['nextcloud_password'],
		CURLOPT_RETURNTRANSFER => false,
		CURLOPT_HEADER => false,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_SSL_VERIFYPEER => true,
		CURLOPT_SSL_VERIFYHOST => 2,
		CURLOPT_TIMEOUT => 30,
		// Write to output
		CURLOPT_WRITEFUNCTION => function($ch, $data) {
			echo $data;
			return strlen($data);
		}
	]);
	
	$result = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$curlError = curl_error($ch);
	$contentTypeReceived = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
	curl_close($ch);
	
	// Debug logging
	error_log("Image preview result: HTTP $httpCode, Content-Type: $contentTypeReceived, Error: $curlError");
	
	if ($httpCode < 200 || $httpCode >= 300) {
		http_response_code($httpCode);
		error_log("Image preview failed: HTTP $httpCode, Error: $curlError, URL: $fileUrl");
		exit('Failed to retrieve file');
	}
	
	// Check if we actually got image data
	if (empty($result)) {
		http_response_code(500);
		error_log("Image preview returned empty content");
		exit('No content received');
	}
	
} catch (Exception $e) {
	http_response_code(500);
	error_log("Image preview error: " . $e->getMessage());
	exit('Error: ' . $e->getMessage());
}
?>
