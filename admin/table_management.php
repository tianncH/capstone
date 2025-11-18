<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add_table':
                    $table_number = trim($_POST['table_number']);
                    
                    // Generate unique QR code for the table
                    $qr_code = 'QR_' . str_pad($table_number, 3, '0', STR_PAD_LEFT);
                    
                    // Generate QR code URL using secure QR system
                    $qr_code_url = "http://" . $_SERVER['HTTP_HOST'] . "/capstone/ordering/cianos_welcome.php?qr=" . urlencode($qr_code);
                    
                    $sql = "INSERT INTO tables (table_number, qr_code, qr_code_url, is_active) VALUES (?, ?, ?, 1)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('sss', $table_number, $qr_code, $qr_code_url);
                    
                    if ($stmt->execute()) {
                        $success_message = "Table added successfully!";
                    } else {
                        throw new Exception("Failed to add table: " . $stmt->error);
                    }
                    break;
                    
                case 'edit_table':
                    $table_id = (int)$_POST['table_id'];
                    $table_number = trim($_POST['table_number']);
                    $is_active = isset($_POST['is_active']) ? 1 : 0;
                    
                    // Get existing QR code or generate new one
                    $get_qr_sql = "SELECT qr_code FROM tables WHERE table_id = ?";
                    $get_qr_stmt = $conn->prepare($get_qr_sql);
                    $get_qr_stmt->bind_param('i', $table_id);
                    $get_qr_stmt->execute();
                    $qr_result = $get_qr_stmt->get_result()->fetch_assoc();
                    $get_qr_stmt->close();
                    
                    $qr_code = $qr_result['qr_code'] ?? 'QR_' . str_pad($table_number, 3, '0', STR_PAD_LEFT);
                    $qr_code_url = "http://" . $_SERVER['HTTP_HOST'] . "/capstone/ordering/cianos_welcome.php?qr=" . urlencode($qr_code);
                    
                    $sql = "UPDATE tables SET table_number=?, qr_code=?, qr_code_url=?, is_active=? WHERE table_id=?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('sssii', $table_number, $qr_code, $qr_code_url, $is_active, $table_id);
                    
                    if ($stmt->execute()) {
                        $success_message = "Table updated successfully!";
                    } else {
                        throw new Exception("Failed to update table: " . $stmt->error);
                    }
                    break;
                    
                case 'delete_table':
                    $table_id = (int)$_POST['table_id'];
                    
                    // Check if table has active orders
                    $check_sql = "SELECT COUNT(*) as count FROM orders o 
                                   JOIN order_statuses s ON o.status_id = s.status_id 
                                   WHERE o.table_id = ? AND s.name NOT IN ('completed', 'cancelled')";
                    $check_stmt = $conn->prepare($check_sql);
                    $check_stmt->bind_param('i', $table_id);
                    $check_stmt->execute();
                    $result = $check_stmt->get_result()->fetch_assoc();
                    
                    if ($result['count'] > 0) {
                        throw new Exception("Cannot delete table with active orders. Please complete or cancel all orders first.");
                    }
                    
                    $sql = "DELETE FROM tables WHERE table_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('i', $table_id);
                    
                    if ($stmt->execute()) {
                        $success_message = "Table deleted successfully!";
                    } else {
                        throw new Exception("Failed to delete table: " . $stmt->error);
                    }
                    break;
                    
                case 'generate_qr':
                    $table_id = (int)$_POST['table_id'];
                    
                    // Get table number and existing QR code
                    $table_sql = "SELECT table_number, qr_code FROM tables WHERE table_id = ?";
                    $table_stmt = $conn->prepare($table_sql);
                    $table_stmt->bind_param('i', $table_id);
                    $table_stmt->execute();
                    $table_result = $table_stmt->get_result()->fetch_assoc();
                    
                    if ($table_result) {
                        $qr_code = $table_result['qr_code'] ?? 'QR_' . str_pad($table_result['table_number'], 3, '0', STR_PAD_LEFT);
                        $qr_code_url = "http://" . $_SERVER['HTTP_HOST'] . "/capstone/ordering/cianos_welcome.php?qr=" . urlencode($qr_code);
                        
                        $update_sql = "UPDATE tables SET qr_code = ?, qr_code_url = ? WHERE table_id = ?";
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->bind_param('ssi', $qr_code, $qr_code_url, $table_id);
                        
                        if ($update_stmt->execute()) {
                            $success_message = "QR code generated successfully!";
                        } else {
                            throw new Exception("Failed to generate QR code: " . $update_stmt->error);
                        }
                    } else {
                        throw new Exception("Table not found.");
                    }
                    break;
            }
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get all tables
$tables_sql = "SELECT t.*, 
               COUNT(o.order_id) as active_orders,
               SUM(CASE WHEN s.name NOT IN ('completed', 'cancelled') THEN 1 ELSE 0 END) as pending_orders
               FROM tables t 
               LEFT JOIN orders o ON t.table_id = o.table_id 
               LEFT JOIN order_statuses s ON o.status_id = s.status_id
               GROUP BY t.table_id 
               ORDER BY t.table_number";
$tables_result = $conn->query($tables_sql);

// Get table for editing
$edit_table = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_sql = "SELECT * FROM tables WHERE table_id = ?";
    $edit_stmt = $conn->prepare($edit_sql);
    $edit_stmt->bind_param('i', $edit_id);
    $edit_stmt->execute();
    $edit_table = $edit_stmt->get_result()->fetch_assoc();
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Table Management</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addTableModal">
                    <i class="bi bi-plus-circle"></i> Add Table
                </button>
                <a href="qr_codes.php" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-qr-code"></i> QR Codes
                </a>
            </div>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $success_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $error_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Tables Grid -->
    <div class="row">
        <?php while ($table = $tables_result->fetch_assoc()): ?>
            <div class="col-md-4 col-lg-3 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-table"></i> Table <?= htmlspecialchars($table['table_number']) ?>
                        </h6>
                        <div class="btn-group btn-group-sm">
                            <a href="?edit=<?= $table['table_id'] ?>" class="btn btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button type="button" class="btn btn-outline-success" onclick="generateQR(<?= $table['table_id'] ?>)">
                                <i class="bi bi-qr-code"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger" onclick="deleteTable(<?= $table['table_id'] ?>, '<?= htmlspecialchars($table['table_number']) ?>')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Table Number</small>
                                <p class="mb-1"><strong><?= htmlspecialchars($table['table_number']) ?></strong></p>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Status</small>
                                <p class="mb-1">
                                    <?php if ($table['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Active Orders</small>
                                <p class="mb-1">
                                    <span class="badge bg-<?= $table['pending_orders'] > 0 ? 'warning' : 'success' ?>">
                                        <?= $table['pending_orders'] ?>
                                    </span>
                                </p>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Status</small>
                                <p class="mb-1">
                                    <?php if ($table['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        
                        <?php if ($table['qr_code_url']): ?>
                            <div class="mt-3">
                                <a href="<?= $table['qr_code_url'] ?>" target="_blank" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="bi bi-box-arrow-up-right"></i> View Order Page
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- Add Table Modal -->
<div class="modal fade" id="addTableModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add_table">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Table</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="table_number" class="form-label">Table Number *</label>
                        <input type="text" class="form-control" id="table_number" name="table_number" required>
                    </div>
                    
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Table</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Table Modal -->
<?php if ($edit_table): ?>
<div class="modal fade show" id="editTableModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="edit_table">
                <input type="hidden" name="table_id" value="<?= $edit_table['table_id'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Table</h5>
                    <a href="table_management.php" class="btn-close"></a>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_table_number" class="form-label">Table Number *</label>
                        <input type="text" class="form-control" id="edit_table_number" name="table_number" 
                               value="<?= htmlspecialchars($edit_table['table_number']) ?>" required>
                    </div>
                    
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active" 
                                   <?= $edit_table['is_active'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="edit_is_active">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="table_management.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Table</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Delete Table Form -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete_table">
    <input type="hidden" name="table_id" id="delete_table_id">
</form>

<!-- Generate QR Form -->
<form id="generateQRForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="generate_qr">
    <input type="hidden" name="table_id" id="generate_qr_table_id">
</form>

<script>
function deleteTable(tableId, tableName) {
    if (confirm(`Are you sure you want to delete "${tableName}"? This action cannot be undone.`)) {
        document.getElementById('delete_table_id').value = tableId;
        document.getElementById('deleteForm').submit();
    }
}

function generateQR(tableId) {
    if (confirm('Generate new QR code for this table?')) {
        document.getElementById('generate_qr_table_id').value = tableId;
        document.getElementById('generateQRForm').submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
