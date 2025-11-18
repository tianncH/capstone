<?php
/**
 * Script to update all currency symbols from $ to ₱ and improve formatting
 */

echo "<h2>Currency Update Script</h2>";
echo "<div style='font-family: Arial; line-height: 1.6; max-width: 1000px; margin: 0 auto;'>";

$files_updated = 0;
$replacements_made = 0;

// Get all PHP files in admin directory
$admin_files = glob('*.php');
$include_files = glob('includes/*.php');

$all_files = array_merge($admin_files, $include_files);

foreach ($all_files as $file) {
    if (!file_exists($file)) continue;
    
    $content = file_get_contents($file);
    $original_content = $content;
    
    // Skip this script itself
    if (basename($file) === 'update_currency.php') continue;
    
    // Replace dollar signs with peso symbols
    $replacements = [
        // Simple $ replacements
        '/\$([0-9,]+\.?[0-9]*)/' => '₱$1',
        '/\$([a-zA-Z_][a-zA-Z0-9_]*)/' => '₱$1',
        
        // number_format improvements
        '/number_format\(([^,]+),\s*2\)/' => 'number_format($1, 2, \'.\', \',\')',
        
        // toFixed improvements in JavaScript
        '/\.toFixed\(2\)/' => '.toLocaleString(\'en-US\', {minimumFractionDigits: 2, maximumFractionDigits: 2})',
        
        // Price input placeholders
        '/Price \(\$\)/' => 'Price (₱)',
        '/Price \(\$\)/' => 'Price (₱)',
        
        // Currency labels
        '/\$([0-9,]+\.?[0-9]*)/' => '₱$1',
    ];
    
    foreach ($replacements as $pattern => $replacement) {
        $new_content = preg_replace($pattern, $replacement, $content);
        if ($new_content !== $content) {
            $content = $new_content;
            $replacements_made++;
        }
    }
    
    // If content changed, write it back
    if ($content !== $original_content) {
        file_put_contents($file, $content);
        $files_updated++;
        echo "Updated: {$file}<br>";
    }
}

echo "<br><div class='alert alert-success'>";
echo "<h4>Currency Update Complete!</h4>";
echo "<p><strong>Files Updated:</strong> {$files_updated}</p>";
echo "<p><strong>Total Replacements:</strong> {$replacements_made}</p>";
echo "</div>";

echo "<h3>Summary of Changes:</h3>";
echo "<ul>";
echo "<li>Replaced all \$ symbols with ₱</li>";
echo "<li>Updated number_format() calls to include proper thousand separators</li>";
echo "<li>Updated JavaScript toFixed() calls to use toLocaleString()</li>";
echo "<li>Updated price input placeholders</li>";
echo "<li>Improved currency formatting throughout the system</li>";
echo "</ul>";

echo "<br><a href='menu_management.php' class='btn btn-primary'>Test Menu Management</a>";
echo "<a href='../ordering/index.php' class='btn btn-success' target='_blank'>Test Customer Interface</a>";
echo "<a href='index.php' class='btn btn-secondary'>Dashboard</a>";

echo "</div>";
?>





