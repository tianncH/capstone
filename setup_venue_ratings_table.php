<?php
require_once 'admin/includes/db_connection.php';

echo "<h1>🏢 SETUP VENUE RATINGS TABLE</h1>";
echo "<style>body { font-family: Arial; margin: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; }</style>";

// Check if venue_ratings table exists
$check_table = "SHOW TABLES LIKE 'venue_ratings'";
$result = $conn->query($check_table);

if ($result->num_rows == 0) {
    echo "<div style='background: #fff3cd; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h2>🗄️ CREATING VENUE RATINGS TABLE</h2>";
    echo "<p>Creating database table for venue ratings...</p>";
    
    // Create venue_ratings table
    $create_table = "
    CREATE TABLE venue_ratings (
        rating_id INT AUTO_INCREMENT PRIMARY KEY,
        receipt_number VARCHAR(50) NOT NULL,
        customer_name VARCHAR(100),
        venue_rating INT NOT NULL CHECK (venue_rating >= 1 AND venue_rating <= 5),
        venue_comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (receipt_number) REFERENCES receipt_qr_codes(receipt_number)
    )";
    
    if ($conn->query($create_table)) {
        echo "<p class='success'>✅ Created venue_ratings table successfully!</p>";
    } else {
        echo "<p class='error'>❌ Failed to create venue_ratings table: " . $conn->error . "</p>";
    }
    
    echo "</div>";
} else {
    echo "<div style='background: #d1ecf1; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h2>✅ VENUE RATINGS TABLE EXISTS</h2>";
    echo "<p>The venue_ratings table is already set up!</p>";
    echo "</div>";
}

// Check if feedback table has the new columns
$check_columns = "SHOW COLUMNS FROM feedback LIKE 'food_quality'";
$result = $conn->query($check_columns);

if ($result->num_rows == 0) {
    echo "<div style='background: #fff3cd; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h2>🗄️ UPDATING FEEDBACK TABLE</h2>";
    echo "<p>Adding new columns to feedback table for enhanced ratings...</p>";
    
    // Add new columns to feedback table
    $alter_feedback = "
    ALTER TABLE feedback 
    ADD COLUMN food_quality INT DEFAULT NULL,
    ADD COLUMN service_quality INT DEFAULT NULL,
    ADD COLUMN place_quality INT DEFAULT NULL,
    ADD COLUMN suggestions TEXT DEFAULT NULL
    ";
    
    if ($conn->query($alter_feedback)) {
        echo "<p class='success'>✅ Updated feedback table with new columns!</p>";
    } else {
        echo "<p class='error'>❌ Failed to update feedback table: " . $conn->error . "</p>";
    }
    
    echo "</div>";
} else {
    echo "<div style='background: #d1ecf1; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h2>✅ FEEDBACK TABLE UPDATED</h2>";
    echo "<p>The feedback table already has the enhanced rating columns!</p>";
    echo "</div>";
}

echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h2>🎉 ENHANCED FEEDBACK SYSTEM READY!</h2>";
echo "<p><strong>Your enhanced feedback system now includes:</strong></p>";
echo "<ul>";
echo "<li>🍽️ <strong>Food Quality Rating</strong> - Rate the food quality</li>";
echo "<li>🛎️ <strong>Service Quality Rating</strong> - Rate the service quality</li>";
echo "<li>🏢 <strong>Place Quality Rating</strong> - Rate the place quality</li>";
echo "<li>💭 <strong>Suggestions Box</strong> - Optional suggestions</li>";
echo "<li>🏢 <strong>Venue Rating</strong> - Separate rating for venue experience</li>";
echo "<li>🔒 <strong>Anti-cheating</strong> - Only paying customers can rate</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #f0f8ff; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h2>🎯 SYSTEM FLOW</h2>";
echo "<ol>";
echo "<li>📱 <strong>Customer scans table QR</strong> → Mobile ordering menu</li>";
echo "<li>🍽️ <strong>Customer orders and pays</strong> → Gets receipt</li>";
echo "<li>🧾 <strong>Receipt has 2 QR codes:</strong></li>";
echo "<ul>";
echo "<li>💬 <strong>Feedback QR</strong> → Rate food, service, place + suggestions</li>";
echo "<li>🏢 <strong>Venue Rating QR</strong> → Rate venue only (after booking)</li>";
echo "</ul>";
echo "<li>🔒 <strong>Security:</strong> Only paying customers can rate</li>";
echo "<li>🎉 <strong>Authentic feedback!</strong></li>";
echo "</ol>";
echo "</div>";

$conn->close();
?>






