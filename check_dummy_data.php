<?php
require_once 'admin/includes/db_connection.php';

echo "<h2>🔍 DUMMY DATA INSPECTION</h2>";
echo "<div style='font-family: Arial; line-height: 1.6; max-width: 1000px; margin: 0 auto; background: #f8f9fa; padding: 20px; border-radius: 10px;'>";

echo "<h3>📊 Current System Status:</h3>";

// Check QR Sessions
$qr_sessions_sql = "SELECT COUNT(*) as count FROM qr_sessions";
$result = $conn->query($qr_sessions_sql);
$qr_sessions_count = $result->fetch_assoc()['count'];
echo "📱 QR Sessions: <strong>{$qr_sessions_count}</strong><br>";

// Check QR Orders
$qr_orders_sql = "SELECT COUNT(*) as count FROM qr_orders";
$result = $conn->query($qr_orders_sql);
$qr_orders_count = $result->fetch_assoc()['count'];
echo "🛒 QR Orders: <strong>{$qr_orders_count}</strong><br>";

// Check Table Sessions
$table_sessions_sql = "SELECT COUNT(*) as count FROM table_sessions";
$result = $conn->query($table_sessions_sql);
$table_sessions_count = $result->fetch_assoc()['count'];
echo "🪑 Table Sessions: <strong>{$table_sessions_count}</strong><br>";

// Check Orders
$orders_sql = "SELECT COUNT(*) as count FROM orders";
$result = $conn->query($orders_sql);
$orders_count = $result->fetch_assoc()['count'];
echo "📋 Orders: <strong>{$orders_count}</strong><br>";

// Check Order Items
$order_items_sql = "SELECT COUNT(*) as count FROM order_items";
$result = $conn->query($order_items_sql);
$order_items_count = $result->fetch_assoc()['count'];
echo "🍽️ Order Items: <strong>{$order_items_count}</strong><br>";

// Check Notifications
$qr_notifications_sql = "SELECT COUNT(*) as count FROM qr_session_notifications";
$result = $conn->query($qr_notifications_sql);
$qr_notifications_count = $result->fetch_assoc()['count'];
echo "🔔 QR Notifications: <strong>{$qr_notifications_count}</strong><br>";

$table_notifications_sql = "SELECT COUNT(*) as count FROM table_session_notifications";
$result = $conn->query($table_notifications_sql);
$table_notifications_count = $result->fetch_assoc()['count'];
echo "🔔 Table Notifications: <strong>{$table_notifications_count}</strong><br>";

// Check Tables
$tables_sql = "SELECT COUNT(*) as count FROM tables WHERE is_active = 1";
$result = $conn->query($tables_sql);
$tables_count = $result->fetch_assoc()['count'];
echo "🪑 Active Tables: <strong>{$tables_count}</strong><br>";

// Check Daily Sales
$daily_sales_sql = "SELECT COUNT(*) as count FROM daily_sales WHERE date = CURDATE()";
$result = $conn->query($daily_sales_sql);
$daily_sales_count = $result->fetch_assoc()['count'];
echo "💰 Today's Sales Records: <strong>{$daily_sales_count}</strong><br>";

echo "<br><h3>🎯 Recent Activity (Last Hour):</h3>";

// Recent QR Sessions
$recent_qr_sql = "SELECT COUNT(*) as count FROM qr_sessions WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
$result = $conn->query($recent_qr_sql);
$recent_qr_count = $result->fetch_assoc()['count'];
echo "📱 Recent QR Sessions: <strong>{$recent_qr_count}</strong><br>";

// Recent Orders
$recent_orders_sql = "SELECT COUNT(*) as count FROM orders WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
$result = $conn->query($recent_orders_sql);
$recent_orders_count = $result->fetch_assoc()['count'];
echo "📋 Recent Orders: <strong>{$recent_orders_count}</strong><br>";

// Recent Notifications
$recent_notifications_sql = "SELECT COUNT(*) as count FROM qr_session_notifications WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
$result = $conn->query($recent_notifications_sql);
$recent_notifications_count = $result->fetch_assoc()['count'];
echo "🔔 Recent Notifications: <strong>{$recent_notifications_count}</strong><br>";

echo "<br><h3>📋 Table Status:</h3>";
$tables_status_sql = "SELECT table_number, qr_code, qr_code_url FROM tables WHERE is_active = 1 ORDER BY table_number";
$result = $conn->query($tables_status_sql);

echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
echo "<tr style='background: #e9ecef;'>";
echo "<th style='border: 1px solid #dee2e6; padding: 10px;'>Table</th>";
echo "<th style='border: 1px solid #dee2e6; padding: 10px;'>QR Code</th>";
echo "<th style='border: 1px solid #dee2e6; padding: 10px;'>Status</th>";
echo "</tr>";

while ($table = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td style='border: 1px solid #dee2e6; padding: 10px;'>Table {$table['table_number']}</td>";
    echo "<td style='border: 1px solid #dee2e6; padding: 10px;'><code>{$table['qr_code']}</code></td>";
    echo "<td style='border: 1px solid #dee2e6; padding: 10px;'>";
    if (!empty($table['qr_code_url'])) {
        echo "<span style='color: green;'>✅ Ready</span>";
    } else {
        echo "<span style='color: red;'>❌ No QR</span>";
    }
    echo "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<br><div style='text-align: center; margin: 30px 0;'>";
echo "<a href='cleanup_dummy_data.php' style='background: #dc3545; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 10px; display: inline-block;'>🧹 CLEAN UP DUMMY DATA</a>";
echo "<a href='admin/index.php' style='background: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 10px; display: inline-block;'>🏠 Admin Dashboard</a>";
echo "</div>";

echo "</div>";
?>






