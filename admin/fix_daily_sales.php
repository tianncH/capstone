<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';

echo "<h2>🔧 Fix Daily Sales Data</h2>";
echo "<div style='font-family: Arial; line-height: 1.6; max-width: 1000px; margin: 0 auto;'>";

try {
    // Get today's date
    $today = date('Y-m-d');
    
    echo "<div class='alert alert-info'>";
    echo "<h4>📊 Daily Sales Analysis - " . date('F j, Y') . "</h4>";
    echo "</div>";
    
    // Get current daily sales record
    $daily_sql = "SELECT * FROM daily_sales WHERE date = ?";
    $daily_stmt = $conn->prepare($daily_sql);
    $daily_stmt->bind_param('s', $today);
    $daily_stmt->execute();
    $daily_result = $daily_stmt->get_result();
    $daily_sales = $daily_result->fetch_assoc();
    $daily_stmt->close();
    
    // Calculate actual sales from orders
    $actual_sql = "SELECT 
                    COUNT(*) as actual_orders,
                    COALESCE(SUM(total_amount), 0) as actual_sales
                   FROM orders 
                   WHERE DATE(created_at) = ? 
                   AND status_id IN (2, 4, 5)"; // Only paid, ready, and completed orders
    
    $actual_stmt = $conn->prepare($actual_sql);
    $actual_stmt->bind_param('s', $today);
    $actual_stmt->execute();
    $actual_result = $actual_stmt->get_result();
    $actual_data = $actual_result->fetch_assoc();
    $actual_stmt->close();
    
    // Get cancelled orders that might have been counted
    $cancelled_sql = "SELECT 
                       COUNT(*) as cancelled_orders,
                       COALESCE(SUM(total_amount), 0) as cancelled_sales
                      FROM orders 
                      WHERE DATE(created_at) = ? 
                      AND status_id = 6"; // Cancelled orders
    
    $cancelled_stmt = $conn->prepare($cancelled_sql);
    $cancelled_stmt->bind_param('s', $today);
    $cancelled_stmt->execute();
    $cancelled_result = $cancelled_stmt->get_result();
    $cancelled_data = $cancelled_result->fetch_assoc();
    $cancelled_stmt->close();
    
    echo "<div class='row mb-4'>";
    echo "<div class='col-md-6'>";
    echo "<div class='card'>";
    echo "<div class='card-header'><h5>Current Daily Sales Record</h5></div>";
    echo "<div class='card-body'>";
    if ($daily_sales) {
        echo "<p><strong>Total Orders:</strong> " . $daily_sales['total_orders'] . "</p>";
        echo "<p><strong>Total Sales:</strong> ₱" . number_format($daily_sales['total_sales'], 2, '.', ',') . "</p>";
        echo "<p><strong>Last Updated:</strong> " . date('g:i A', strtotime($daily_sales['updated_at'])) . "</p>";
    } else {
        echo "<p class='text-muted'>No daily sales record found for today.</p>";
    }
    echo "</div></div></div>";
    
    echo "<div class='col-md-6'>";
    echo "<div class='card'>";
    echo "<div class='card-header'><h5>Actual Orders (Paid/Ready/Completed)</h5></div>";
    echo "<div class='card-body'>";
    echo "<p><strong>Total Orders:</strong> " . $actual_data['actual_orders'] . "</p>";
    echo "<p><strong>Total Sales:</strong> ₱" . number_format($actual_data['actual_sales'], 2, '.', ',') . "</p>";
    echo "</div></div></div>";
    echo "</div>";
    
    echo "<div class='row mb-4'>";
    echo "<div class='col-md-6'>";
    echo "<div class='card'>";
    echo "<div class='card-header'><h5>Cancelled Orders</h5></div>";
    echo "<div class='card-body'>";
    echo "<p><strong>Cancelled Orders:</strong> " . $cancelled_data['cancelled_orders'] . "</p>";
    echo "<p><strong>Cancelled Amount:</strong> ₱" . number_format($cancelled_data['cancelled_sales'], 2, '.', ',') . "</p>";
    echo "</div></div></div>";
    
    echo "<div class='col-md-6'>";
    echo "<div class='card'>";
    echo "<div class='card-header'><h5>Discrepancy Analysis</h5></div>";
    echo "<div class='card-body'>";
    
    if ($daily_sales) {
        $order_diff = $daily_sales['total_orders'] - $actual_data['actual_orders'];
        $sales_diff = $daily_sales['total_sales'] - $actual_data['actual_sales'];
        
        if ($order_diff == 0 && $sales_diff == 0) {
            echo "<div class='alert alert-success'>";
            echo "<strong>✅ No Discrepancy Found</strong><br>";
            echo "Daily sales data is accurate!";
            echo "</div>";
        } else {
            echo "<div class='alert alert-warning'>";
            echo "<strong>⚠️ Discrepancy Found</strong><br>";
            echo "Order difference: " . ($order_diff > 0 ? '+' : '') . $order_diff . "<br>";
            echo "Sales difference: " . ($sales_diff > 0 ? '+' : '') . "₱" . number_format($sales_diff, 2, '.', ',');
            echo "</div>";
        }
    } else {
        echo "<div class='alert alert-info'>";
        echo "<strong>ℹ️ No Daily Sales Record</strong><br>";
        echo "Will create new record with actual data.";
        echo "</div>";
    }
    echo "</div></div></div>";
    echo "</div>";
    
    // Fix button
    if (!$daily_sales || $order_diff != 0 || $sales_diff != 0) {
        echo "<div class='text-center mb-4'>";
        echo "<form method='POST' class='d-inline'>";
        echo "<input type='hidden' name='action' value='fix_daily_sales'>";
        echo "<button type='submit' class='btn btn-warning btn-lg' onclick='return confirm(\"Are you sure you want to fix the daily sales data? This will update the records to match actual orders.\")'>";
        echo "<i class='bi bi-tools'></i> Fix Daily Sales Data";
        echo "</button>";
        echo "</form>";
        echo "</div>";
    }
    
    // Process fix request
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'fix_daily_sales') {
        $conn->begin_transaction();
        
        try {
            if ($daily_sales) {
                // Update existing record
                $update_sql = "UPDATE daily_sales 
                              SET total_orders = ?, 
                                  total_sales = ?,
                                  updated_at = CURRENT_TIMESTAMP 
                              WHERE date = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param('ids', $actual_data['actual_orders'], $actual_data['actual_sales'], $today);
                
                if (!$update_stmt->execute()) {
                    throw new Exception('Error updating daily sales: ' . $conn->error);
                }
                $update_stmt->close();
            } else {
                // Create new record
                $insert_sql = "INSERT INTO daily_sales (date, total_orders, total_sales) VALUES (?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param('sid', $today, $actual_data['actual_orders'], $actual_data['actual_sales']);
                
                if (!$insert_stmt->execute()) {
                    throw new Exception('Error creating daily sales: ' . $conn->error);
                }
                $insert_stmt->close();
            }
            
            $conn->commit();
            
            echo "<div class='alert alert-success'>";
            echo "<h4>✅ Daily Sales Data Fixed!</h4>";
            echo "<p>Daily sales record has been updated to match actual order data.</p>";
            echo "</div>";
            
            // Refresh the page to show updated data
            echo "<script>setTimeout(function(){ location.reload(); }, 2000);</script>";
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }
    
    echo "<div class='d-grid gap-2 d-md-flex justify-content-md-center'>";
    echo "<a href='daily_sales.php' class='btn btn-primary btn-lg'>View Daily Sales</a>";
    echo "<a href='index.php' class='btn btn-secondary btn-lg'>Dashboard</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ Error:</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</div>";
?>









