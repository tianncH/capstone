<?php
echo "=== TESTING REGEX FIX ===\n\n";

$message = "Table 1 requesting bill - Total: ₱13.30";

echo "Message: {$message}\n";

// OLD regex (broken)
preg_match('/Total: ([\d,]+\.?\d*)/', $message, $matches_old);
$total_old = isset($matches_old[1]) ? str_replace(',', '', $matches_old[1]) : '0.00';
echo "OLD regex result: {$total_old}\n";

// NEW regex (fixed)
preg_match('/Total: ₱([\d,]+\.?\d*)/', $message, $matches_new);
$total_new = isset($matches_new[1]) ? str_replace(',', '', $matches_new[1]) : '0.00';
echo "NEW regex result: {$total_new}\n";

if ($total_new == '13.30') {
    echo "✅ Regex fix successful!\n";
} else {
    echo "❌ Regex fix failed!\n";
}
?>





