<?php
require_once 'includes/auth_check.php';
require_once 'includes/qr_generator.php';

echo "<h2>🧪 QR Code Test</h2>";
echo "<div style='font-family: Arial; line-height: 1.6; max-width: 1000px; margin: 0 auto;'>";

// Test QR code generation
$qr_generator = new QRGenerator();

echo "<h3>Testing QR Code Generation...</h3>";

// Test ordering QR
echo "<h4>Ordering QR Code Test:</h4>";
$ordering_qr = $qr_generator->generateOrderingQR(1);
if ($ordering_qr['success']) {
    echo "<div class='alert alert-success'>✅ Ordering QR generated successfully!</div>";
    echo "<p><strong>URL:</strong> " . htmlspecialchars($ordering_qr['data']) . "</p>";
    echo "<p><strong>Image URL:</strong> " . htmlspecialchars($ordering_qr['url']) . "</p>";
    echo "<img src='" . htmlspecialchars($ordering_qr['url']) . "' alt='Ordering QR Code' style='border: 1px solid #ccc; margin: 10px;'>";
} else {
    echo "<div class='alert alert-danger'>❌ Failed to generate ordering QR: " . htmlspecialchars($ordering_qr['error']) . "</div>";
}

echo "<hr>";

// Test feedback QR
echo "<h4>Feedback QR Code Test:</h4>";
$feedback_qr = $qr_generator->generateFeedbackQR(1);
if ($feedback_qr['success']) {
    echo "<div class='alert alert-success'>✅ Feedback QR generated successfully!</div>";
    echo "<p><strong>URL:</strong> " . htmlspecialchars($feedback_qr['data']) . "</p>";
    echo "<p><strong>Image URL:</strong> " . htmlspecialchars($feedback_qr['url']) . "</p>";
    echo "<img src='" . htmlspecialchars($feedback_qr['url']) . "' alt='Feedback QR Code' style='border: 1px solid #ccc; margin: 10px;'>";
} else {
    echo "<div class='alert alert-danger'>❌ Failed to generate feedback QR: " . htmlspecialchars($feedback_qr['error']) . "</div>";
}

echo "<hr>";

// Test direct API calls
echo "<h4>Direct API Test:</h4>";
$test_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode("http://localhost/capstone/ordering/index.php?table=1");
echo "<p><strong>Direct API URL:</strong> " . htmlspecialchars($test_url) . "</p>";
echo "<img src='" . htmlspecialchars($test_url) . "' alt='Direct API QR Code' style='border: 1px solid #ccc; margin: 10px;'>";

echo "<br><br><a href='qr_management.php' class='btn btn-primary'>Back to QR Management</a>";
echo "</div>";
?>









