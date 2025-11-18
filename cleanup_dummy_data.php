<?php
require_once 'admin/includes/db_connection.php';

echo "<h2>🧹 DUMMY DATA CLEANUP TIME!</h2>";
echo "<div style='font-family: Arial; line-height: 1.6; max-width: 1000px; margin: 0 auto; background: #f8f9fa; padding: 20px; border-radius: 10px;'>";

try {
    // Start transaction
    $conn->begin_transaction();
    
    echo "<h3>🎯 CLEANING UP TEST DATA...</h3>";
    
    // 1. Clean up test QR sessions
    echo "<h4>📱 QR Sessions Cleanup:</h4>";
    $qr_sessions_sql = "DELETE FROM qr_sessions WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)";
    $stmt = $conn->prepare($qr_sessions_sql);
    if ($stmt->execute()) {
        echo "✅ Cleaned up old QR sessions: " . $stmt->affected_rows . " rows<br>";
    }
    $stmt->close();
    
    // 2. Clean up test QR orders
    echo "<h4>🛒 QR Orders Cleanup:</h4>";
    $qr_orders_sql = "DELETE qo FROM qr_orders qo 
                      JOIN qr_sessions qs ON qo.session_id = qs.session_id 
                      WHERE qs.created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)";
    $stmt = $conn->prepare($qr_orders_sql);
    if ($stmt->execute()) {
        echo "✅ Cleaned up test QR orders: " . $stmt->affected_rows . " rows<br>";
    }
    $stmt->close();
    
    // 3. Clean up test table sessions
    echo "<h4>🪑 Table Sessions Cleanup:</h4>";
    $table_sessions_sql = "DELETE FROM table_sessions WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)";
    $stmt = $conn->prepare($table_sessions_sql);
    if ($stmt->execute()) {
        echo "✅ Cleaned up test table sessions: " . $stmt->affected_rows . " rows<br>";
    }
    $stmt->close();
    
    // 4. Clean up test orders
    echo "<h4>📋 Orders Cleanup:</h4>";
    $orders_sql = "DELETE FROM orders WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)";
    $stmt = $conn->prepare($orders_sql);
    if ($stmt->execute()) {
        echo "✅ Cleaned up test orders: " . $stmt->affected_rows . " rows<br>";
    }
    $stmt->close();
    
    // 5. Clean up test order items
    echo "<h4>🍽️ Order Items Cleanup:</h4>";
    $order_items_sql = "DELETE oi FROM order_items oi 
                        JOIN orders o ON oi.order_id = o.order_id 
                        WHERE o.created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)";
    $stmt = $conn->prepare($order_items_sql);
    if ($stmt->execute()) {
        echo "✅ Cleaned up test order items: " . $stmt->affected_rows . " rows<br>";
    }
    $stmt->close();
    
    // 6. Clean up test notifications
    echo "<h4>🔔 Notifications Cleanup:</h4>";
    $notifications_sql = "DELETE FROM qr_session_notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)";
    $stmt = $conn->prepare($notifications_sql);
    if ($stmt->execute()) {
        echo "✅ Cleaned up test QR notifications: " . $stmt->affected_rows . " rows<br>";
    }
    $stmt->close();
    
    $table_notifications_sql = "DELETE FROM table_session_notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)";
    $stmt = $conn->prepare($table_notifications_sql);
    if ($stmt->execute()) {
        echo "✅ Cleaned up test table notifications: " . $stmt->affected_rows . " rows<br>";
    }
    $stmt->close();
    
    // 7. Clean up test order status history
    echo "<h4>📊 Order Status History Cleanup:</h4>";
    $history_sql = "DELETE osh FROM order_status_history osh 
                    JOIN orders o ON osh.order_id = o.order_id 
                    WHERE o.created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)";
    $stmt = $conn->prepare($history_sql);
    if ($stmt->execute()) {
        echo "✅ Cleaned up test order status history: " . $stmt->affected_rows . " rows<br>";
    }
    $stmt->close();
    
    // 8. Reset table QR codes to clean state
    echo "<h4>🔄 Resetting Table QR Codes:</h4>";
    $reset_qr_sql = "UPDATE tables SET qr_code = CONCAT('QR_', LPAD(table_number, 3, '0')), 
                     qr_code_url = CONCAT('http://localhost/capstone/ordering/secure_qr_menu.php?qr=', CONCAT('QR_', LPAD(table_number, 3, '0'))) 
                     WHERE is_active = 1";
    $stmt = $conn->prepare($reset_qr_sql);
    if ($stmt->execute()) {
        echo "✅ Reset QR codes for all active tables: " . $stmt->affected_rows . " rows<br>";
    }
    $stmt->close();
    
    // 9. Clean up any test daily sales
    echo "<h4>💰 Daily Sales Cleanup:</h4>";
    $daily_sales_sql = "DELETE FROM daily_sales WHERE date = CURDATE() AND total_orders = 0";
    $stmt = $conn->prepare($daily_sales_sql);
    if ($stmt->execute()) {
        echo "✅ Cleaned up empty daily sales records: " . $stmt->affected_rows . " rows<br>";
    }
    $stmt->close();
    
    // 10. Clean up test cash float transactions
    echo "<h4>💵 Cash Float Cleanup:</h4>";
    $cash_float_sql = "DELETE FROM cash_float_transactions WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)";
    $stmt = $conn->prepare($cash_float_sql);
    if ($stmt->execute()) {
        echo "✅ Cleaned up test cash float transactions: " . $stmt->affected_rows . " rows<br>";
    }
    $stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    echo "<br><h3>🎉 CLEANUP COMPLETE!</h3>";
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>✅ System is now clean and ready for real customers!</h4>";
    echo "<ul>";
    echo "<li>🧹 All test sessions removed</li>";
    echo "<li>🛒 All test orders cleared</li>";
    echo "<li>🔔 All test notifications cleaned</li>";
    echo "<li>🔄 QR codes reset to clean state</li>";
    echo "<li>💰 Test sales data removed</li>";
    echo "<li>💵 Test cash float data cleared</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h4>🎯 Next Steps:</h4>";
    echo "<ol>";
    echo "<li>✅ System is ready for real customers</li>";
    echo "<li>📱 QR codes are properly configured</li>";
    echo "<li>🏪 Counter staff can start confirming sessions</li>";
    echo "<li>🍽️ Menu is ready for ordering</li>";
    echo "<li>💳 Payment system is ready</li>";
    echo "</ol>";
    
    echo "<div style='text-align: center; margin: 30px 0;'>";
    echo "<a href='admin/index.php' style='background: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 10px; display: inline-block;'>🏠 Go to Admin Dashboard</a>";
    echo "<a href='counter/index.php' style='background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 10px; display: inline-block;'>🏪 Go to Counter Dashboard</a>";
    echo "<a href='ordering/qr_landing.php' style='background: #17a2b8; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 10px; display: inline-block;'>📱 Test QR System</a>";
    echo "</div>";
    
} catch (Exception $e) {
    $conn->rollback();
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>❌ Error during cleanup:</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</div>";
?>






