<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';
require_once 'includes/currency_functions.php';

$success_message = '';
$error_message = '';

// Get today's date
$today = date('Y-m-d');

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'assign_float':
                    $counter_id = intval($_POST['counter_id']);
                    $assigned_amount = floatval(str_replace(',', '', $_POST['assigned_amount']));
                    $notes = trim($_POST['assignment_notes']);
                    
                    // Check if float already assigned to this counter today
                    $check_sql = "SELECT session_id FROM cash_float_sessions 
                                  WHERE shift_date = ? AND assigned_to = ? AND status = 'active'";
                    $check_stmt = $conn->prepare($check_sql);
                    $check_stmt->bind_param('si', $today, $counter_id);
                    $check_stmt->execute();
                    $existing = $check_stmt->get_result()->fetch_assoc();
                    $check_stmt->close();
                    
                    if ($existing) {
                        throw new Exception('Cash float has already been assigned to this counter today.');
                    }
                    
                    // Start transaction
                    $conn->begin_transaction();
                    
                    // Create session
                    $session_sql = "INSERT INTO cash_float_sessions 
                                   (shift_date, opening_amount, assigned_to, assigned_by, notes, status) 
                                   VALUES (?, ?, ?, ?, ?, 'active')";
                    $session_stmt = $conn->prepare($session_sql);
                    $session_stmt->bind_param('sidis', $today, $assigned_amount, $counter_id, $_SESSION['admin_id'], $notes);
                    
                    if (!$session_stmt->execute()) {
                        throw new Exception('Failed to assign cash float: ' . $session_stmt->error);
                    }
                    $session_id = $conn->insert_id;
                    $session_stmt->close();
                    
                    // Create opening transaction
                    $transaction_sql = "INSERT INTO cash_float_transactions 
                                       (session_id, transaction_type, amount, cash_on_hand, notes, created_by, shift_date) 
                                       VALUES (?, 'opening', ?, ?, 'Cash float assigned by admin', ?, ?)";
                    $transaction_stmt = $conn->prepare($transaction_sql);
                    $transaction_stmt->bind_param('iddis', $session_id, $assigned_amount, $assigned_amount, $_SESSION['admin_id'], $today);
                    
                    if (!$transaction_stmt->execute()) {
                        throw new Exception('Failed to create opening transaction: ' . $transaction_stmt->error);
                    }
                    $transaction_stmt->close();
                    
                    $conn->commit();
                    $success_message = "Cash float of ₱" . number_format($assigned_amount, 2, '.', ',') . " assigned successfully to Main Counter";
                    break;
                    
                case 'close_float':
                    $session_id = intval($_POST['session_id']);
                    $closing_amount = floatval(str_replace(',', '', $_POST['closing_amount']));
                    $closing_notes = trim($_POST['closing_notes']);
                    
                    // Get session details
                    $session_sql = "SELECT * FROM cash_float_sessions WHERE session_id = ?";
                    $session_stmt = $conn->prepare($session_sql);
                    $session_stmt->bind_param('i', $session_id);
                    $session_stmt->execute();
                    $session = $session_stmt->get_result()->fetch_assoc();
                    $session_stmt->close();
                    
                    if (!$session) {
                        throw new Exception('Session not found.');
                    }
                    
                    // Start transaction
                    $conn->begin_transaction();
                    
                    // Update session
                    $close_sql = "UPDATE cash_float_sessions 
                                  SET closing_amount = ?, status = 'closed', closed_by = ?, closed_at = NOW(),
                                      notes = CONCAT(COALESCE(notes, ''), ' | Closed: ', ?)
                                  WHERE session_id = ?";
                    $close_stmt = $conn->prepare($close_sql);
                    $close_stmt->bind_param('diss', $closing_amount, $_SESSION['admin_id'], $closing_notes, $session_id);
                    
                    if (!$close_stmt->execute()) {
                        throw new Exception('Failed to close session: ' . $close_stmt->error);
                    }
                    $close_stmt->close();
                    
                    // Create closing transaction
                    $transaction_sql = "INSERT INTO cash_float_transactions 
                                       (session_id, transaction_type, amount, cash_on_hand, notes, created_by, shift_date) 
                                       VALUES (?, 'closing', ?, ?, ?, ?, ?)";
                    $transaction_stmt = $conn->prepare($transaction_sql);
                    $transaction_stmt->bind_param('iddiss', $session_id, $closing_amount, $closing_amount, $closing_notes, $_SESSION['admin_id'], $today);
                    
                    if (!$transaction_stmt->execute()) {
                        throw new Exception('Failed to create closing transaction: ' . $transaction_stmt->error);
                    }
                    $transaction_stmt->close();
                    
                    $conn->commit();
                    $success_message = "Cash float session closed successfully for Main Counter";
                    break;
                    
                case 'adjust_float':
                    $session_id = intval($_POST['session_id']);
                    $adjustment_amount = floatval(str_replace(',', '', $_POST['adjustment_amount']));
                    $adjustment_notes = trim($_POST['adjustment_notes']);
                    
                    // Get current cash on hand
                    $cash_sql = "SELECT cash_on_hand FROM cash_float_transactions 
                                 WHERE session_id = ? 
                                 ORDER BY created_at DESC 
                                 LIMIT 1";
                    $cash_stmt = $conn->prepare($cash_sql);
                    $cash_stmt->bind_param('i', $session_id);
                    $cash_stmt->execute();
                    $cash_result = $cash_stmt->get_result();
                    $current_cash = $cash_result->fetch_assoc()['cash_on_hand'] ?? 0;
                    $cash_stmt->close();
                    
                    $new_cash = $current_cash + $adjustment_amount;
                    
                    // Create adjustment transaction
                    $transaction_sql = "INSERT INTO cash_float_transactions 
                                       (session_id, transaction_type, amount, cash_on_hand, notes, created_by, shift_date) 
                                       VALUES (?, 'adjustment', ?, ?, ?, ?, ?)";
                    $transaction_stmt = $conn->prepare($transaction_sql);
                    $transaction_stmt->bind_param('iddiss', $session_id, $adjustment_amount, $new_cash, $adjustment_notes, $_SESSION['admin_id'], $today);
                    
                    if (!$transaction_stmt->execute()) {
                        throw new Exception('Failed to add adjustment: ' . $transaction_stmt->error);
                    }
                    $transaction_stmt->close();
                    
                    // Update session adjustments
                    $adjust_sql = "UPDATE cash_float_sessions SET adjustments = adjustments + ? WHERE session_id = ?";
                    $adjust_stmt = $conn->prepare($adjust_sql);
                    $adjust_stmt->bind_param('di', $adjustment_amount, $session_id);
                    $adjust_stmt->execute();
                    $adjust_stmt->close();
                    
                    $success_message = "Adjustment added: " . ($adjustment_amount >= 0 ? '+' : '') . "₱" . number_format($adjustment_amount, 2, '.', ',');
                    break;
            }
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = $e->getMessage();
    }
}

// Get active cash float sessions
$active_sessions_sql = "SELECT s.*, 
                               (SELECT cash_on_hand FROM cash_float_transactions 
                                WHERE session_id = s.session_id 
                                ORDER BY created_at DESC LIMIT 1) as current_cash,
                               (SELECT COUNT(*) FROM cash_float_transactions 
                                WHERE session_id = s.session_id AND transaction_type = 'sale') as sales_count,
                               (SELECT SUM(amount) FROM cash_float_transactions 
                                WHERE session_id = s.session_id AND transaction_type = 'sale') as total_sales
                        FROM cash_float_sessions s 
                        WHERE s.shift_date = ? AND s.status = 'active'
                        ORDER BY s.assigned_to";
$active_sessions_stmt = $conn->prepare($active_sessions_sql);
$active_sessions_stmt->bind_param('s', $today);
$active_sessions_stmt->execute();
$active_sessions = $active_sessions_stmt->get_result();
$active_sessions_stmt->close();

// Get closed sessions for today
$closed_sessions_sql = "SELECT s.*, 
                               (SELECT COUNT(*) FROM cash_float_transactions 
                                WHERE session_id = s.session_id AND transaction_type = 'sale') as sales_count,
                               (SELECT SUM(amount) FROM cash_float_transactions 
                                WHERE session_id = s.session_id AND transaction_type = 'sale') as total_sales
                        FROM cash_float_sessions s 
                        WHERE s.shift_date = ? AND s.status = 'closed'
                        ORDER BY s.closed_at DESC";
$closed_sessions_stmt = $conn->prepare($closed_sessions_sql);
$closed_sessions_stmt->bind_param('s', $today);
$closed_sessions_stmt->execute();
$closed_sessions = $closed_sessions_stmt->get_result();
$closed_sessions_stmt->close();

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Cash Float Assignment</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <span class="badge bg-info me-2">Single Counter Setup</span>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#assignFloatModal">
                <i class="bi bi-plus-circle"></i> Assign Cash Float
            </button>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Active Sessions -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Active Cash Float Sessions - <?= date('M j, Y') ?></h5>
        </div>
        <div class="card-body">
            <?php if ($active_sessions->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Counter</th>
                                <th>Assigned Amount</th>
                                <th>Current Cash</th>
                                <th>Total Sales</th>
                                <th>Sales Count</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($session = $active_sessions->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>Main Counter</strong></td>
                                    <td><strong><?= formatPeso($session['opening_amount']) ?></strong></td>
                                    <td>
                                        <span class="text-primary"><strong><?= formatPeso($session['current_cash'] ?? 0) ?></strong></span>
                                    </td>
                                    <td><?= formatPeso($session['total_sales'] ?? 0) ?></td>
                                    <td>
                                        <span class="badge bg-info"><?= $session['sales_count'] ?? 0 ?> sales</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">Active</span>
                                        <br><small class="text-muted">Opened: <?= date('g:i A', strtotime($session['opened_at'])) ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary" 
                                                    onclick="adjustFloat(<?= $session['session_id'] ?>, '<?= formatPeso($session['current_cash'] ?? 0) ?>')">
                                                <i class="bi bi-plus-minus"></i> Adjust
                                            </button>
                                            <button type="button" class="btn btn-outline-warning" 
                                                    onclick="closeFloat(<?= $session['session_id'] ?>, '<?= formatPeso($session['current_cash'] ?? 0) ?>')">
                                                <i class="bi bi-stop-circle"></i> Close
                                            </button>
                                            <button type="button" class="btn btn-outline-info" 
                                                    onclick="viewSessionDetails(<?= $session['session_id'] ?>)">
                                                <i class="bi bi-eye"></i> Details
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="bi bi-cash-coin text-muted" style="font-size: 3rem;"></i>
                    <h4 class="mt-3 text-muted">No Active Cash Float Sessions</h4>
                    <p class="text-muted">Assign cash float to counters to begin operations.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Closed Sessions -->
    <?php if ($closed_sessions->num_rows > 0): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Closed Sessions Today</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Counter</th>
                                <th>Opening Amount</th>
                                <th>Closing Amount</th>
                                <th>Difference</th>
                                <th>Total Sales</th>
                                <th>Closed At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($session = $closed_sessions->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>Main Counter</strong></td>
                                    <td><?= formatPeso($session['opening_amount']) ?></td>
                                    <td><?= formatPeso($session['closing_amount']) ?></td>
                                    <td>
                                        <?php $difference = $session['closing_amount'] - $session['opening_amount']; ?>
                                        <span class="text-<?= $difference >= 0 ? 'success' : 'danger' ?>">
                                            <strong><?= $difference >= 0 ? '+' : '' ?><?= formatPeso($difference) ?></strong>
                                        </span>
                                    </td>
                                    <td><?= formatPeso($session['total_sales'] ?? 0) ?></td>
                                    <td><?= date('g:i A', strtotime($session['closed_at'])) ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-info" 
                                                onclick="viewSessionDetails(<?= $session['session_id'] ?>)">
                                            <i class="bi bi-eye"></i> Details
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Assign Float Modal -->
<div class="modal fade" id="assignFloatModal" tabindex="-1" aria-labelledby="assignFloatModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignFloatModalLabel">Assign Cash Float</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="assign_float">
                    <div class="mb-3">
                        <label for="counter_id" class="form-label">Counter Assignment *</label>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> <strong>Single Counter Setup</strong><br>
                            <small>Cash float will be assigned to the main counter.</small>
                        </div>
                        <input type="hidden" id="counter_id" name="counter_id" value="1">
                        <div class="form-control-plaintext">Counter #1 (Main Counter)</div>
                    </div>
                    <div class="mb-3">
                        <label for="assigned_amount" class="form-label">Cash Float Amount (₱) *</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="text" class="form-control" id="assigned_amount" name="assigned_amount" 
                                   placeholder="5,000.00" required>
                        </div>
                        <div class="form-text">
                            <strong>Recommended amounts:</strong><br>
                            • Small counter: ₱3,000 - ₱5,000<br>
                            • Medium counter: ₱5,000 - ₱10,000<br>
                            • Large counter: ₱10,000 - ₱20,000<br>
                            <small class="text-muted">Include various denominations for proper change making</small>
                        </div>
                        
                        <!-- Quick Amount Buttons -->
                        <div class="mt-2">
                            <label class="form-label">Quick Amounts:</label>
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-primary" onclick="setAmount(3000)">₱3,000</button>
                                <button type="button" class="btn btn-outline-primary" onclick="setAmount(5000)">₱5,000</button>
                                <button type="button" class="btn btn-outline-primary" onclick="setAmount(10000)">₱10,000</button>
                                <button type="button" class="btn btn-outline-primary" onclick="setAmount(15000)">₱15,000</button>
                                <button type="button" class="btn btn-outline-primary" onclick="setAmount(20000)">₱20,000</button>
                            </div>
                        </div>
                        
                        <!-- Cash Breakdown -->
                        <div class="mt-3">
                            <button type="button" class="btn btn-sm btn-outline-info" onclick="showCashBreakdown()">
                                <i class="bi bi-calculator"></i> Show Recommended Breakdown
                            </button>
                            <div id="cashBreakdown" class="mt-2" style="display: none;">
                                <div class="alert alert-info">
                                    <h6>Recommended Cash Breakdown for ₱5,000:</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <small>
                                                • 2 × ₱1,000 bills = ₱2,000<br>
                                                • 4 × ₱500 bills = ₱2,000<br>
                                                • 10 × ₱100 bills = ₱1,000<br>
                                            </small>
                                        </div>
                                        <div class="col-md-6">
                                            <small>
                                                • 20 × ₱50 bills = ₱1,000<br>
                                                • 20 × ₱20 bills = ₱400<br>
                                                • 20 × ₱10 bills = ₱200<br>
                                                • 20 × ₱5 bills = ₱100<br>
                                            </small>
                                        </div>
                                    </div>
                                    <small class="text-muted">Adjust quantities based on your restaurant's needs</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="assignment_notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="assignment_notes" name="assignment_notes" 
                                  rows="3" placeholder="Optional notes for this assignment..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Assign Cash Float</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Close Float Modal -->
<div class="modal fade" id="closeFloatModal" tabindex="-1" aria-labelledby="closeFloatModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="closeFloatModalLabel">Close Cash Float Session</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="close_float">
                    <input type="hidden" id="close_session_id" name="session_id">
                    <div class="mb-3">
                        <label for="closing_amount" class="form-label">Final Cash Count (₱) *</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="text" class="form-control" id="closing_amount" name="closing_amount" 
                                   placeholder="0.00" required>
                        </div>
                        <div class="form-text">Enter the actual cash count at closing.</div>
                    </div>
                    <div class="mb-3">
                        <label for="closing_notes" class="form-label">Closing Notes</label>
                        <textarea class="form-control" id="closing_notes" name="closing_notes" 
                                  rows="3" placeholder="Notes about the closing..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Close Session</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Adjust Float Modal -->
<div class="modal fade" id="adjustFloatModal" tabindex="-1" aria-labelledby="adjustFloatModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="adjustFloatModalLabel">Adjust Cash Float</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="adjust_float">
                    <input type="hidden" id="adjust_session_id" name="session_id">
                    <div class="mb-3">
                        <label for="adjustment_amount" class="form-label">Adjustment Amount (₱) *</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="text" class="form-control" id="adjustment_amount" name="adjustment_amount" 
                                   placeholder="0.00" required>
                        </div>
                        <div class="form-text">Use positive values to add cash, negative values to remove cash.</div>
                    </div>
                    <div class="mb-3">
                        <label for="adjustment_notes" class="form-label">Reason for Adjustment</label>
                        <textarea class="form-control" id="adjustment_notes" name="adjustment_notes" 
                                  rows="3" placeholder="Explain the reason for this adjustment..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Adjustment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Currency formatting for input fields
document.addEventListener('DOMContentLoaded', function() {
    const currencyInputs = ['assigned_amount', 'closing_amount', 'adjustment_amount'];
    
    currencyInputs.forEach(inputId => {
        const input = document.getElementById(inputId);
        if (input) {
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/[^\d.]/g, '');
                let parts = value.split('.');
                if (parts.length > 2) {
                    value = parts[0] + '.' + parts.slice(1).join('');
                }
                
                if (value && !isNaN(parseFloat(value))) {
                    let formatted = parseFloat(value).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                    e.target.value = formatted;
                }
            });
            
            input.addEventListener('blur', function(e) {
                if (e.target.value) {
                    let value = parseFloat(e.target.value.replace(/,/g, ''));
                    e.target.value = value.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            });
        }
    });
});

function closeFloat(sessionId, currentCash) {
    document.getElementById('close_session_id').value = sessionId;
    document.getElementById('closing_amount').placeholder = currentCash;
    new bootstrap.Modal(document.getElementById('closeFloatModal')).show();
}

function adjustFloat(sessionId, currentCash) {
    document.getElementById('adjust_session_id').value = sessionId;
    new bootstrap.Modal(document.getElementById('adjustFloatModal')).show();
}

function viewSessionDetails(sessionId) {
    // This would open a modal or redirect to detailed view
    window.open(`cash_float_session_details.php?session_id=${sessionId}`, '_blank');
}

function setAmount(amount) {
    const input = document.getElementById('assigned_amount');
    if (input) {
        input.value = amount.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
}

function showCashBreakdown() {
    const breakdown = document.getElementById('cashBreakdown');
    if (breakdown.style.display === 'none') {
        breakdown.style.display = 'block';
    } else {
        breakdown.style.display = 'none';
    }
}
</script>

<?php include 'includes/footer.php'; ?>
