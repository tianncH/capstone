<?php
require_once 'admin/includes/db_connection.php';

echo "=== FIXING DUPLICATE TABLE 1 SESSIONS ===\n\n";

// Find duplicate Table 1 sessions
$result = $conn->query("SELECT session_id, table_id, status, confirmed_by_counter, created_at FROM qr_sessions WHERE table_id = 1 AND status = 'active' ORDER BY created_at DESC");

if ($result && $result->num_rows > 1) {
    echo "Found multiple active sessions for Table 1:\n";
    $sessions = [];
    while ($row = $result->fetch_assoc()) {
        $sessions[] = $row;
        echo "Session {$row['session_id']}: Created {$row['created_at']}, Confirmed: " . ($row['confirmed_by_counter'] ? 'YES' : 'NO') . "\n";
    }
    
    // Keep the most recent confirmed session, close the others
    $keep_session = null;
    $close_sessions = [];
    
    foreach ($sessions as $session) {
        if ($session['confirmed_by_counter']) {
            if (!$keep_session || strtotime($session['created_at']) > strtotime($keep_session['created_at'])) {
                $keep_session = $session;
            }
        }
    }
    
    if ($keep_session) {
        echo "\nKeeping Session {$keep_session['session_id']} (most recent confirmed)\n";
        
        foreach ($sessions as $session) {
            if ($session['session_id'] != $keep_session['session_id']) {
                $close_sessions[] = $session;
            }
        }
        
        // Close duplicate sessions
        foreach ($close_sessions as $session) {
            $close_sql = "UPDATE qr_sessions SET status = 'closed', closed_at = NOW() WHERE session_id = ?";
            $stmt = $conn->prepare($close_sql);
            $stmt->bind_param('i', $session['session_id']);
            
            if ($stmt->execute()) {
                echo "✅ Closed duplicate Session {$session['session_id']}\n";
            } else {
                echo "❌ Error closing Session {$session['session_id']}: " . $stmt->error . "\n";
            }
            $stmt->close();
        }
    } else {
        echo "❌ No confirmed sessions found to keep\n";
    }
} else {
    echo "✅ No duplicate sessions found for Table 1\n";
}

echo "\n";

// Check device fingerprints
echo "=== DEVICE FINGERPRINT ANALYSIS ===\n";
$result = $conn->query("SELECT device_fingerprint, COUNT(*) as count FROM qr_sessions GROUP BY device_fingerprint ORDER BY count DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "Device {$row['device_fingerprint']}: {$row['count']} sessions\n";
    }
}

$conn->close();
?>






