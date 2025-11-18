<?php
require_once 'admin/includes/db_connection.php';

echo "<h2>🧹 Cleaning Up All Test Data...</h2>";
echo "<div style='font-family: Arial; line-height: 1.6; max-width: 800px; margin: 0 auto;'>";

try {
    // Start transaction
    $conn->begin_transaction();

    echo "<h4>🗑️ Deleting all test data...</h4>";

    // 1. Delete from order_status_history
    $sql = "DELETE FROM order_status_history";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    echo "<p>✅ Deleted " . $stmt->affected_rows . " entries from order_status_history.</p>";
    $stmt->close();

    // 2. Delete from order_item_addons
    $sql = "DELETE FROM order_item_addons";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    echo "<p>✅ Deleted " . $stmt->affected_rows . " entries from order_item_addons.</p>";
    $stmt->close();

    // 3. Delete from order_items
    $sql = "DELETE FROM order_items";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    echo "<p>✅ Deleted " . $stmt->affected_rows . " entries from order_items.</p>";
    $stmt->close();

    // 4. Delete from orders
    $sql = "DELETE FROM orders";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    echo "<p>✅ Deleted " . $stmt->affected_rows . " entries from orders.</p>";
    $stmt->close();

    // 5. Delete from table_session_notifications
    $sql = "DELETE FROM table_session_notifications";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    echo "<p>✅ Deleted " . $stmt->affected_rows . " entries from table_session_notifications.</p>";
    $stmt->close();

    // 6. Delete from table_session_items
    $sql = "DELETE FROM table_session_items";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    echo "<p>✅ Deleted " . $stmt->affected_rows . " entries from table_session_items.</p>";
    $stmt->close();

    // 7. Delete from table_sessions
    $sql = "DELETE FROM table_sessions";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    echo "<p>✅ Deleted " . $stmt->affected_rows . " entries from table_sessions.</p>";
    $stmt->close();

    // 8. Delete from daily_sales
    $sql = "DELETE FROM daily_sales";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    echo "<p>✅ Deleted " . $stmt->affected_rows . " entries from daily_sales.</p>";
    $stmt->close();

    // 9. Delete from cash_float_transactions
    $sql = "DELETE FROM cash_float_transactions";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    echo "<p>✅ Deleted " . $stmt->affected_rows . " entries from cash_float_transactions.</p>";
    $stmt->close();

    // 10. Delete from cash_float_sessions
    $sql = "DELETE FROM cash_float_sessions";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    echo "<p>✅ Deleted " . $stmt->affected_rows . " entries from cash_float_sessions.</p>";
    $stmt->close();

    $conn->commit();
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745; margin: 20px 0;'>";
    echo "<h4>🎉 Cleanup Complete!</h4>";
    echo "<p><strong>All test data has been successfully removed!</strong></p>";
    echo "<p>You can now start fresh and test the proper flow:</p>";
    echo "<ul>";
    echo "<li>1. Set cash float (admin)</li>";
    echo "<li>2. Customer places order</li>";
    echo "<li>3. Counter validates → Kitchen</li>";
    echo "<li>4. Kitchen marks ready</li>";
    echo "<li>5. Customer requests bill</li>";
    echo "<li>6. Counter processes payment</li>";
    echo "<li>7. Order completed</li>";
    echo "</ul>";
    echo "</div>";

} catch (Exception $e) {
    $conn->rollback();
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px; border-left: 4px solid #dc3545; margin: 20px 0;'>";
    echo "<h4>❌ Error during cleanup:</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<p><a href='counter/index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Counter Dashboard</a></p>";
echo "<p><a href='admin/index.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Admin Dashboard</a></p>";
echo "</div>";
$conn->close();
?>
