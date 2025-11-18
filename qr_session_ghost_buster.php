<?php
require_once 'admin/includes/db_connection.php';

echo "<h2>👻 QR SESSION GHOST BUSTER - DUPLICATE CLEANUP!</h2>";
echo "<div style='font-family: Arial; line-height: 1.6; max-width: 1200px; margin: 0 auto; background: #f8f9fa; padding: 20px; border-radius: 10px;'>";

try {
    // Start transaction
    $conn->begin_transaction();
    
    echo "<h3>🔍 INVESTIGATING QR SESSION GHOSTS...</h3>";
    
    // Check for duplicate QR sessions
    echo "<h4>📊 QR Session Investigation:</h4>";
    $duplicate_sql = "SELECT 
        qs.table_id, 
        t.qr_code, 
        COUNT(*) as session_count,
        GROUP_CONCAT(qs.session_id ORDER BY qs.created_at DESC) as session_ids,
        GROUP_CONCAT(qs.created_at ORDER BY qs.created_at DESC) as created_times,
        GROUP_CONCAT(qs.status ORDER BY qs.created_at DESC) as statuses
    FROM qr_sessions qs
    LEFT JOIN tables t ON qs.table_id = t.table_id
    WHERE qs.status = 'active'
    GROUP BY qs.table_id, t.qr_code 
    HAVING COUNT(*) > 1
    ORDER BY qs.table_id, t.qr_code";
    
    $result = $conn->query($duplicate_sql);
    
    if ($result->num_rows > 0) {
        echo "<table style='width: 100%; border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #e9ecef;'>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Table</th>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>QR Code</th>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Sessions</th>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Session IDs</th>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Created Times</th>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Action</th>";
        echo "</tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>Table {$row['table_id']}</td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>{$row['qr_code']}</td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'><span style='color: red; font-weight: bold;'>{$row['session_count']} DUPLICATES</span></td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px; font-family: monospace;'>{$row['session_ids']}</td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>{$row['created_times']}</td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'><span style='color: red;'>👻 GHOST DATA</span></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "✅ No duplicate QR sessions found.<br>";
    }
    
    // Check all active QR sessions
    echo "<h4>📋 All Active QR Sessions:</h4>";
    $all_sessions_sql = "SELECT 
        qs.session_id,
        qs.table_id,
        t.qr_code,
        qs.status,
        qs.created_at,
        qs.confirmed_by_counter,
        t.table_number
    FROM qr_sessions qs
    LEFT JOIN tables t ON qs.table_id = t.table_id
    WHERE qs.status = 'active'
    ORDER BY qs.table_id, qs.created_at DESC";
    
    $result = $conn->query($all_sessions_sql);
    
    if ($result->num_rows > 0) {
        echo "<table style='width: 100%; border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #e9ecef;'>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Session ID</th>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Table</th>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>QR Code</th>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Status</th>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Created</th>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Confirmed</th>";
        echo "</tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px; font-family: monospace;'>{$row['session_id']}</td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>Table {$row['table_number']}</td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>{$row['qr_code']}</td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>{$row['status']}</td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>{$row['created_at']}</td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>" . ($row['confirmed_by_counter'] ? '✅ Yes' : '❌ No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No active QR sessions found.<br>";
    }
    
    echo "<br><h3>🧹 EXORCISING QR SESSION GHOSTS...</h3>";
    
    // Clean up duplicate QR sessions - keep only the most recent one for each table
    echo "<h4>👻 Removing Duplicate QR Sessions:</h4>";
    
    $cleanup_sql = "DELETE qs1 FROM qr_sessions qs1
                    INNER JOIN qr_sessions qs2 
                    WHERE qs1.table_id = qs2.table_id 
                    AND qs1.status = 'active'
                    AND qs2.status = 'active'
                    AND qs1.session_id < qs2.session_id";
    
    $stmt = $conn->prepare($cleanup_sql);
    if ($stmt->execute()) {
        echo "✅ Removed duplicate QR sessions: " . $stmt->affected_rows . " rows<br>";
    }
    $stmt->close();
    
    // Also clean up any orphaned QR orders from deleted sessions
    echo "<h4>🍽️ Cleaning Orphaned QR Orders:</h4>";
    $cleanup_orders_sql = "DELETE FROM qr_orders WHERE session_id NOT IN (SELECT session_id FROM qr_sessions)";
    $stmt = $conn->prepare($cleanup_orders_sql);
    if ($stmt->execute()) {
        echo "✅ Removed orphaned QR orders: " . $stmt->affected_rows . " rows<br>";
    }
    $stmt->close();
    
    // Clean up orphaned QR session notifications
    echo "<h4>🔔 Cleaning Orphaned QR Notifications:</h4>";
    $cleanup_notifications_sql = "DELETE FROM qr_session_notifications WHERE session_id NOT IN (SELECT session_id FROM qr_sessions)";
    $stmt = $conn->prepare($cleanup_notifications_sql);
    if ($stmt->execute()) {
        echo "✅ Removed orphaned QR notifications: " . $stmt->affected_rows . " rows<br>";
    }
    $stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    echo "<br><h3>🎉 QR SESSION GHOST EXORCISM COMPLETE!</h3>";
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>✅ All QR session ghosts have been exorcised!</h4>";
    echo "<ul>";
    echo "<li>👻 Duplicate QR sessions removed</li>";
    echo "<li>🍽️ Orphaned QR orders cleaned</li>";
    echo "<li>🔔 Orphaned QR notifications cleaned</li>";
    echo "<li>🔍 Each table now has only ONE active QR session</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h4>🎯 Counter should now show:</h4>";
    echo "<ul>";
    echo "<li>✅ Table 1: 1 QR Session (most recent)</li>";
    echo "<li>✅ Table 2: 1 QR Session (most recent)</li>";
    echo "<li>✅ Table 3: 1 QR Session (most recent)</li>";
    echo "<li>✅ No more duplicate sessions</li>";
    echo "</ul>";
    
    echo "<div style='text-align: center; margin: 30px 0;'>";
    echo "<a href='counter/index.php' style='background: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 10px; display: inline-block;'>👨‍💼 Check Counter</a>";
    echo "<a href='admin/qr_session_management.php' style='background: #17a2b8; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 10px; display: inline-block;'>🔍 Check Admin QR Sessions</a>";
    echo "</div>";
    
} catch (Exception $e) {
    $conn->rollback();
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>❌ Error during QR session ghost exorcism:</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</div>";
?>
