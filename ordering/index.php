<?php
require_once 'includes/db_connection.php';

// QR-CENTERED SYSTEM: Redirect to QR-based ordering
$table_number = isset($_GET['table']) ? intval($_GET['table']) : 1;

// Get table information to find QR code
$table_sql = "SELECT * FROM tables WHERE table_number = ? AND is_active = 1";
$table_stmt = $conn->prepare($table_sql);
$table_stmt->bind_param('i', $table_number);
$table_stmt->execute();
$table = $table_stmt->get_result()->fetch_assoc();
$table_stmt->close();

if ($table && !empty($table['qr_code'])) {
    // Redirect to QR-based ordering system
    header("Location: secure_qr_menu.php?qr=" . urlencode($table['qr_code']));
    exit;
} else {
    // Fallback: Show QR code message
    die('QR code not found for Table ' . $table_number . '. Please scan the QR code at your table or contact staff.');
}
?>