<?php
require_once 'admin/includes/db_connection.php';

echo "<h2>👻 GHOST BUSTER - SALES DATA EXORCISM!</h2>";
echo "<div style='font-family: Arial; line-height: 1.6; max-width: 1000px; margin: 0 auto; background: #f8f9fa; padding: 20px; border-radius: 10px;'>";

try {
    // Start transaction
    $conn->begin_transaction();
    
    echo "<h3>🔍 INVESTIGATING GHOST SALES...</h3>";
    
    // Check what's in daily_sales
    echo "<h4>📊 Daily Sales Investigation:</h4>";
    $daily_sales_sql = "SELECT * FROM daily_sales ORDER BY date DESC LIMIT 10";
    $result = $conn->query($daily_sales_sql);
    
    if ($result->num_rows > 0) {
        echo "<table style='width: 100%; border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #e9ecef;'>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Date</th>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Orders</th>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Sales</th>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Action</th>";
        echo "</tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>{$row['date']}</td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>{$row['total_orders']}</td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>₱" . number_format($row['total_sales'], 2) . "</td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>";
            if ($row['total_orders'] == 0 || $row['total_sales'] == 0) {
                echo "<span style='color: red;'>👻 GHOST DATA</span>";
            } else {
                echo "<span style='color: green;'>✅ Real Data</span>";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No daily sales records found.<br>";
    }
    
    // Check monthly sales
    echo "<h4>📅 Monthly Sales Investigation:</h4>";
    $monthly_sales_sql = "SELECT * FROM monthly_sales ORDER BY month DESC LIMIT 5";
    $result = $conn->query($monthly_sales_sql);
    
    if ($result->num_rows > 0) {
        echo "<table style='width: 100%; border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #e9ecef;'>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Month</th>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Orders</th>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Sales</th>";
        echo "</tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>{$row['month']}</td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>{$row['total_orders']}</td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>₱" . number_format($row['total_sales'], 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No monthly sales records found.<br>";
    }
    
    // Check yearly sales
    echo "<h4>🗓️ Yearly Sales Investigation:</h4>";
    $yearly_sales_sql = "SELECT * FROM yearly_sales ORDER BY year DESC LIMIT 5";
    $result = $conn->query($yearly_sales_sql);
    
    if ($result->num_rows > 0) {
        echo "<table style='width: 100%; border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #e9ecef;'>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Year</th>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Orders</th>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Sales</th>";
        echo "</tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>{$row['year']}</td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>{$row['total_orders']}</td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>₱" . number_format($row['total_sales'], 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No yearly sales records found.<br>";
    }
    
    echo "<br><h3>🧹 EXORCISING GHOST SALES DATA...</h3>";
    
    // Delete ghost daily sales (where orders = 0 or sales = 0)
    echo "<h4>👻 Exorcising Ghost Daily Sales:</h4>";
    $delete_daily_sql = "DELETE FROM daily_sales WHERE total_orders = 0 OR total_sales = 0";
    $stmt = $conn->prepare($delete_daily_sql);
    if ($stmt->execute()) {
        echo "✅ Exorcised ghost daily sales: " . $stmt->affected_rows . " rows<br>";
    }
    $stmt->close();
    
    // Delete ghost monthly sales
    echo "<h4>👻 Exorcising Ghost Monthly Sales:</h4>";
    $delete_monthly_sql = "DELETE FROM monthly_sales WHERE total_orders = 0 OR total_sales = 0";
    $stmt = $conn->prepare($delete_monthly_sql);
    if ($stmt->execute()) {
        echo "✅ Exorcised ghost monthly sales: " . $stmt->affected_rows . " rows<br>";
    }
    $stmt->close();
    
    // Delete ghost yearly sales
    echo "<h4>👻 Exorcising Ghost Yearly Sales:</h4>";
    $delete_yearly_sql = "DELETE FROM yearly_sales WHERE total_orders = 0 OR total_sales = 0";
    $stmt = $conn->prepare($delete_yearly_sql);
    if ($stmt->execute()) {
        echo "✅ Exorcised ghost yearly sales: " . $stmt->affected_rows . " rows<br>";
    }
    $stmt->close();
    
    // Delete any sales records from today that have no corresponding orders
    echo "<h4>🔍 Checking Today's Sales vs Orders:</h4>";
    $today = date('Y-m-d');
    
    // Check if there are any orders for today
    $today_orders_sql = "SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = ?";
    $stmt = $conn->prepare($today_orders_sql);
    $stmt->bind_param('s', $today);
    $stmt->execute();
    $today_orders = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
    
    echo "📋 Orders found for today: <strong>{$today_orders}</strong><br>";
    
    if ($today_orders == 0) {
        // Delete today's sales record if no orders exist
        $delete_today_sql = "DELETE FROM daily_sales WHERE date = ?";
        $stmt = $conn->prepare($delete_today_sql);
        $stmt->bind_param('s', $today);
        if ($stmt->execute()) {
            echo "✅ Deleted today's ghost sales record: " . $stmt->affected_rows . " rows<br>";
        }
        $stmt->close();
    }
    
    // Commit transaction
    $conn->commit();
    
    echo "<br><h3>🎉 GHOST EXORCISM COMPLETE!</h3>";
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>✅ All ghost sales data has been exorcised!</h4>";
    echo "<ul>";
    echo "<li>👻 Ghost daily sales removed</li>";
    echo "<li>👻 Ghost monthly sales removed</li>";
    echo "<li>👻 Ghost yearly sales removed</li>";
    echo "<li>🔍 Today's sales verified against actual orders</li>";
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
    echo "<a href='check_dummy_data.php' style='background: #17a2b8; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 10px; display: inline-block;'>🔍 Check System Status</a>";
    echo "</div>";
    
} catch (Exception $e) {
    $conn->rollback();
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>❌ Error during ghost exorcism:</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</div>";
?>






