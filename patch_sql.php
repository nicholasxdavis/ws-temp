<?php
/**
 * SQL Patch Script
 * This will directly fix any remaining SQL issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== SQL Patch Script ===\n\n";

// List of files to patch
$filesToPatch = [
    'api/plan_limits.php' => [
        'member_user_id' => 'member.id',
        'tm.user_id' => 'tm.workspace_owner_id'
    ],
    'api/check_pro.php' => [
        'member_user_id' => 'member.id',
        'tm.user_id' => 'tm.workspace_owner_id'
    ],
    'api/usage.php' => [
        'team_id' => 'workspace_owner_id'
    ],
    'api/auth_helper.php' => [
        'WHERE email =' => 'WHERE member_email ='
    ]
];

foreach ($filesToPatch as $file => $replacements) {
    if (file_exists($file)) {
        echo "Patching $file...\n";
        $content = file_get_contents($file);
        $originalContent = $content;
        
        foreach ($replacements as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }
        
        if ($content !== $originalContent) {
            file_put_contents($file, $content);
            echo "✓ Patched $file\n";
        } else {
            echo "✓ $file already clean\n";
        }
    } else {
        echo "⚠ $file not found\n";
    }
}

echo "\n=== Patch Complete ===\n";
echo "All SQL issues should now be fixed.\n";
echo "Try uploading again.\n";
?>
