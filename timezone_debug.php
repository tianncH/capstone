<?php
require_once 'admin/includes/db_connection.php';

echo "=== TIMEZONE DEBUG ===\n\n";

echo "PHP Default Timezone: " . date_default_timezone_get() . "\n";
echo "PHP Current Time: " . date('Y-m-d H:i:s') . "\n";
echo "PHP Current Timestamp: " . time() . "\n";
echo "PHP +2 Hours: " . date('Y-m-d H:i:s', strtotime('+2 hours')) . "\n\n";

echo "MySQL Current Time: ";
$result = $conn->query("SELECT NOW() AS current_time");
$row = $result->fetch_assoc();
echo $row['current_time'] . "\n";

echo "MySQL +2 Hours: ";
$result = $conn->query("SELECT DATE_ADD(NOW(), INTERVAL 2 HOUR) AS plus_2_hours");
$row = $result->fetch_assoc();
echo $row['plus_2_hours'] . "\n\n";

echo "Table 1 Session Details:\n";
$result = $conn->query("SELECT created_at, expires_at, TIMESTAMPDIFF(MINUTE, created_at, expires_at) AS duration_minutes FROM qr_sessions WHERE table_id = 1 ORDER BY created_at DESC LIMIT 1");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "Created: " . $row['created_at'] . "\n";
    echo "Expires: " . $row['expires_at'] . "\n";
    echo "Duration: " . $row['duration_minutes'] . " minutes\n";
    
    $now = new DateTime();
    $expires = new DateTime($row['expires_at']);
    $is_expired = $now > $expires;
    echo "Is Expired: " . ($is_expired ? 'YES' : 'NO') . "\n";
}

$conn->close();
?>
