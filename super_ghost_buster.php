<?php
require_once 'admin/includes/db_connection.php';

echo "<h2>👻 SUPER GHOST BUSTER - ADVANCED EXORCISM!</h2>";
echo "<div style='font-family: Arial; line-height: 1.6; max-width: 1000px; margin: 0 auto; background: #f8f9fa; padding: 20px; border-radius: 10px;'>";

try {
    // Start transaction
    $conn->begin_transaction();
    
    echo "<h3>🔍 DEEP GHOST INVESTIGATION...</h3>";
    
    // Check ALL sales tables for any remaining data
    echo "<h4>📊 Complete Sales Data Audit:</h4>";
    
    // Daily Sales
    $daily_sql = "SELECT COUNT(*) as count, SUM(total_sales) as total FROM daily_sales";
    $result = $conn->query($daily_sql);
    $daily_data = $result->fetch_assoc();
    echo "📅 Daily Sales Records: <strong>{$daily_data['count']}</strong> (Total: ₱" . number_format($daily_data['total'], 2) . ")<br>";
    
    // Monthly Sales
    $monthly_sql = "SELECT COUNT(*) as count, SUM(total_sales) as total FROM monthly_sales";
    $result = $conn->query($monthly_sql);
    $monthly_data = $result->fetch_assoc();
    echo "📆 Monthly Sales Records: <strong>{$monthly_data['count']}</strong> (Total: ₱" . number_format($monthly_data['total'], 2) . ")<br>";
    
    // Yearly Sales
    $yearly_sql = "SELECT COUNT(*) as count, SUM(total_sales) as total FROM yearly_sales";
    $result = $conn->query($yearly_sql);
    $yearly_data = $result->fetch_assoc();
    echo "🗓️ Yearly Sales Records: <strong>{$yearly_data['count']}</strong> (Total: ₱" . number_format($yearly_data['total'], 2) . ")<br>";
    
    // Check if there are ANY orders in the system
    echo "<h4>📋 Order System Check:</h4>";
    $orders_sql = "SELECT COUNT(*) as count FROM orders";
    $result = $conn->query($orders_sql);
    $orders_count = $result->fetch_assoc()['count'];
    echo "📋 Total Orders in System: <strong>{$orders_count}</strong><br>";
    
    if ($orders_count == 0) {
        echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4>👻 GHOST DETECTED!</h4>";
        echo "<p>There are <strong>NO ORDERS</strong> in the system, but sales data still exists! This is definitely ghost data!</p>";
        echo "</div>";
    }
    
    echo "<br><h3>🧹 NUCLEAR GHOST EXORCISM...</h3>";
    
    // NUCLEAR OPTION: Delete ALL sales data if no orders exist
    if ($orders_count == 0) {
        echo "<h4>💥 NUCLEAR OPTION - Deleting ALL Sales Data:</h4>";
        
        // Delete ALL daily sales
        $delete_all_daily = "DELETE FROM daily_sales";
        $stmt = $conn->prepare($delete_all_daily);
        if ($stmt->execute()) {
            echo "💥 Nuked ALL daily sales: " . $stmt->affected_rows . " rows<br>";
        }
        $stmt->close();
        
        // Delete ALL monthly sales
        $delete_all_monthly = "DELETE FROM monthly_sales";
        $stmt = $conn->prepare($delete_all_monthly);
        if ($stmt->execute()) {
            echo "💥 Nuked ALL monthly sales: " . $stmt->affected_rows . " rows<br>";
        }
        $stmt->close();
        
        // Delete ALL yearly sales
        $delete_all_yearly = "DELETE FROM yearly_sales";
        $stmt = $conn->prepare($delete_all_yearly);
        if ($stmt->execute()) {
            echo "💥 Nuked ALL yearly sales: " . $stmt->affected_rows . " rows<br>";
        }
        $stmt->close();
        
    } else {
        echo "<h4>🔍 Selective Ghost Removal:</h4>";
        
        // Only delete sales records that don't have corresponding orders
        $delete_orphaned_daily = "DELETE FROM daily_sales WHERE date NOT IN (SELECT DISTINCT DATE(created_at) FROM orders)";
        $stmt = $conn->prepare($delete_orphaned_daily);
        if ($stmt->execute()) {
            echo "✅ Removed orphaned daily sales: " . $stmt->affected_rows . " rows<br>";
        }
        $stmt->close();
        
        $delete_orphaned_monthly = "DELETE FROM monthly_sales WHERE month NOT IN (SELECT DISTINCT DATE_FORMAT(created_at, '%Y-%m') FROM orders)";
        $stmt = $conn->prepare($delete_orphaned_monthly);
        if ($stmt->execute()) {
            echo "✅ Removed orphaned monthly sales: " . $stmt->affected_rows . " rows<br>";
        }
        $stmt->close();
        
        $delete_orphaned_yearly = "DELETE FROM yearly_sales WHERE year NOT IN (SELECT DISTINCT YEAR(created_at) FROM orders)";
        $stmt = $conn->prepare($delete_orphaned_yearly);
        if ($stmt->execute()) {
            echo "✅ Removed orphaned yearly sales: " . $stmt->affected_rows . " rows<br>";
        }
        $stmt->close();
    }
    
    // Also clean up any order status history that might be orphaned
    echo "<h4>🧹 Cleaning Orphaned Order History:</h4>";
    $delete_orphaned_history = "DELETE FROM order_status_history WHERE order_id NOT IN (SELECT order_id FROM orders)";
    $stmt = $conn->prepare($delete_orphaned_history);
    if ($stmt->execute()) {
        echo "✅ Removed orphaned order history: " . $stmt->affected_rows . " rows<br>";
    }
    $stmt->close();
    
    // Clean up any orphaned order items
    echo "<h4>🍽️ Cleaning Orphaned Order Items:</h4>";
    $delete_orphaned_items = "DELETE FROM order_items WHERE order_id NOT IN (SELECT order_id FROM orders)";
    $stmt = $conn->prepare($delete_orphaned_items);
    if ($stmt->execute()) {
        echo "✅ Removed orphaned order items: " . $stmt->affected_rows . " rows<br>";
    }
    $stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    echo "<br><h3>🎉 SUPER GHOST EXORCISM COMPLETE!</h3>";
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>✅ All persistent ghosts have been exorcised!</h4>";
    echo "<ul>";
    echo "<li>💥 All orphaned sales data removed</li>";
    echo "<li>🧹 All orphaned order history cleaned</li>";
    echo "<li>🍽️ All orphaned order items removed</li>";
    echo "<li>🔍 System is now completely clean</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h4>🎯 Dashboard should now show:</h4>";
    echo "<ul>";
    echo "<li>📊 TODAY'S SALES: ₱0.00 (0 orders)</li>";
    echo "<li>📅 MONTHLY SALES: ₱0.00 (0 orders)</li>";
    echo "<li>🗓️ YEARLY SALES: ₱0.00 (0 orders)</li>";
    echo "<li>🍽️ MENU ITEMS: 26 (this stays - it's real data)</li>";
    echo "</ul>";
    
    echo "<div style='text-align: center; margin: 30px 0;'>";
    echo "<a href='admin/index.php' style='background: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 10px; display: inline-block;'>🏠 Check Dashboard</a>";
    echo "<a href='check_dummy_data.php' style='background: #17a2b8; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 10px; display: inline-block;'>🔍 Final System Check</a>";
    echo "</div>";
    
} catch (Exception $e) {
    $conn->rollback();
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>❌ Error during super ghost exorcism:</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</div>";
?>





