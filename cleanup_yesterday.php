<?php
require_once 'admin/includes/db_connection.php';

// Get yesterday's date
$yesterday = date('Y-m-d', strtotime('-1 day'));
echo "<h2>🧹 Cleaning up data from: $yesterday</h2>";

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Clean up order status history from yesterday
    $sql = "DELETE osh FROM order_status_history osh 
            JOIN orders o ON osh.order_id = o.order_id 
            WHERE DATE(o.created_at) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $yesterday);
    if ($stmt->execute()) {
        echo "<p>✅ Deleted yesterday's order status history: " . $stmt->affected_rows . " rows</p>";
    }
    $stmt->close();
    
    // Clean up order items from yesterday
    $sql = "DELETE oi FROM order_items oi 
            JOIN orders o ON oi.order_id = o.order_id 
            WHERE DATE(o.created_at) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $yesterday);
    if ($stmt->execute()) {
        echo "<p>✅ Deleted yesterday's order items: " . $stmt->affected_rows . " rows</p>";
    }
    $stmt->close();
    
    // Clean up order item addons from yesterday
    $sql = "DELETE oia FROM order_item_addons oia 
            JOIN order_items oi ON oia.order_item_id = oi.order_item_id 
            JOIN orders o ON oi.order_id = o.order_id 
            WHERE DATE(o.created_at) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $yesterday);
    if ($stmt->execute()) {
        echo "<p>✅ Deleted yesterday's order item addons: " . $stmt->affected_rows . " rows</p>";
    }
    $stmt->close();
    
    // Clean up orders from yesterday
    $sql = "DELETE FROM orders WHERE DATE(created_at) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $yesterday);
    if ($stmt->execute()) {
        echo "<p>✅ Deleted yesterday's orders: " . $stmt->affected_rows . " rows</p>";
    }
    $stmt->close();
    
    // Clean up table session notifications from yesterday
    $sql = "DELETE tsn FROM table_session_notifications tsn 
            JOIN table_sessions ts ON tsn.session_id = ts.session_id 
            WHERE DATE(ts.created_at) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $yesterday);
    if ($stmt->execute()) {
        echo "<p>✅ Deleted yesterday's table session notifications: " . $stmt->affected_rows . " rows</p>";
    }
    $stmt->close();
    
    // Clean up table session items from yesterday
    $sql = "DELETE tsi FROM table_session_items tsi 
            JOIN table_sessions ts ON tsi.session_id = ts.session_id 
            WHERE DATE(ts.created_at) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $yesterday);
    if ($stmt->execute()) {
        echo "<p>✅ Deleted yesterday's table session items: " . $stmt->affected_rows . " rows</p>";
    }
    $stmt->close();
    
    // Clean up table sessions from yesterday
    $sql = "DELETE FROM table_sessions WHERE DATE(created_at) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $yesterday);
    if ($stmt->execute()) {
        echo "<p>✅ Deleted yesterday's table sessions: " . $stmt->affected_rows . " rows</p>";
    }
    $stmt->close();
    
    // Clean up daily sales from yesterday
    $sql = "DELETE FROM daily_sales WHERE date = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $yesterday);
    if ($stmt->execute()) {
        echo "<p>✅ Deleted yesterday's daily sales: " . $stmt->affected_rows . " rows</p>";
    }
    $stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    echo "<h3>🎉 Cleanup completed successfully!</h3>";
    echo "<p><strong>All yesterday's data has been removed.</strong></p>";
    echo "<p><a href='counter/index.php'>Go to Counter System</a></p>";
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    echo "<h3>❌ Error during cleanup:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}

$conn->close();
?>








