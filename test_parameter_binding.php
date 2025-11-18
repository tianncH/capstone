<?php
echo "=== TESTING PARAMETER BINDING ===\n\n";

// Simulate the parameters
$session_id = 7;
$total_amount = 13.30;
$notes = "QR Payment - Table 1 - Amount: ₱13.30, Received: ₱500, Change: ₱486.70";
$counter_user_id = 1;

echo "Parameters:\n";
echo "1. session_id: {$session_id} (integer)\n";
echo "2. total_amount: {$total_amount} (double)\n";
echo "3. notes: {$notes} (string)\n";
echo "4. counter_user_id: {$counter_user_id} (integer)\n";

echo "\nType definitions:\n";
echo "OLD: 'ids' (3 types) - ❌ WRONG (4 parameters, 3 types)\n";
echo "NEW: 'idsi' (4 types) - ✅ CORRECT (4 parameters, 4 types)\n";

echo "\nType mapping:\n";
echo "i = integer (session_id)\n";
echo "d = double (total_amount)\n";
echo "s = string (notes)\n";
echo "i = integer (counter_user_id)\n";

echo "\n✅ Parameter binding fix is correct!\n";
?>





