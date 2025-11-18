<?php
require_once 'includes/db_connection.php';

// Simple cash float management - no blinking!
$today = date('Y-m-d');

// Get current session
$session_sql = "SELECT * FROM cash_float_sessions WHERE shift_date = ? AND assigned_to = 1 AND status = 'active'";
$stmt = $conn->prepare($session_sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$current_session = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get current cash and session start time
$current_cash = 0;
$session_start_time = null;
if ($current_session) {
    $cash_sql = "SELECT cash_on_hand FROM cash_float_transactions WHERE session_id = ? ORDER BY created_at DESC LIMIT 1";
    $stmt = $conn->prepare($cash_sql);
    $stmt->bind_param("i", $current_session['session_id']);
    $stmt->execute();
    $cash_result = $stmt->get_result();
    if ($cash_row = $cash_result->fetch_assoc()) {
        $current_cash = $cash_row['cash_on_hand'];
    }
    $stmt->close();
    
    // Get session start time from first transaction
    $start_sql = "SELECT created_at FROM cash_float_transactions WHERE session_id = ? ORDER BY created_at ASC LIMIT 1";
    $stmt = $conn->prepare($start_sql);
    $stmt->bind_param("i", $current_session['session_id']);
    $stmt->execute();
    $start_result = $stmt->get_result();
    if ($start_row = $start_result->fetch_assoc()) {
        $session_start_time = $start_row['created_at'];
    }
    $stmt->close();
}

// Get recent transactions
$transactions = null;
if ($current_session) {
    $trans_sql = "SELECT * FROM cash_float_transactions WHERE session_id = ? ORDER BY created_at DESC LIMIT 20";
    $stmt = $conn->prepare($trans_sql);
    $stmt->bind_param("i", $current_session['session_id']);
    $stmt->execute();
    $transactions = $stmt->get_result();
    $stmt->close();
}

// SECURITY: Counter staff cannot open/close cash float sessions
// Only admin can assign cash float sessions for security reasons
// This page is read-only for counter staff
?>

<?php
$page_title = "Cash Float Management";
require_once 'includes/header_clean.php';
?>
    <div class="container-fluid">
        <!-- Header -->
        <div class="header">
            <h1><i class="bi bi-cash-coin"></i> Cash Float Management - Clean Version</h1>
            <p class="mb-0">Simple cash float tracking</p>
        </div>
        
        <!-- Success Message -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i> <?= htmlspecialchars($_GET['success']) ?>
            </div>
        <?php endif; ?>
        
        <!-- Status Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="stats-card">
                    <h5><i class="bi bi-calendar"></i> Today's Date</h5>
                    <h3><?= date('M j, Y') ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <h5><i class="bi bi-wallet2"></i> Current Cash</h5>
                    <h3>₱<?= number_format($current_cash, 2) ?></h3>
                    <small><?= $current_session ? 'Active Session' : 'No Session' ?></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <h5><i class="bi bi-play-circle"></i> Session Status</h5>
                    <h3><?= $current_session ? 'Active' : 'Closed' ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <h5><i class="bi bi-clock"></i> Session Time</h5>
                    <h3><?= $session_start_time ? date('h:i A', strtotime($session_start_time)) : 'N/A' ?></h3>
                </div>
            </div>
        </div>
        
        <!-- Security Notice -->
        <div class="header">
            <h3><i class="bi bi-shield-check"></i> Security Information</h3>
            
            <?php if (!$current_session): ?>
                <div class="alert alert-warning">
                    <h5><i class="bi bi-exclamation-triangle"></i> No Active Cash Float Session</h5>
                    <p class="mb-2">No cash float has been assigned to the Main Counter for today.</p>
                    <p class="mb-0"><strong>Security Note:</strong> Only administrators can assign cash float sessions. Please contact your administrator to set up a cash float before processing payments.</p>
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    <h5><i class="bi bi-check-circle"></i> Active Cash Float Session</h5>
                    <p class="mb-0">Cash float session is active and managed by your administrator. You can process payments when orders are ready.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Transactions -->
        <?php if ($transactions && $transactions->num_rows > 0): ?>
            <div class="header">
                <h3><i class="bi bi-list-ul"></i> Recent Transactions</h3>
                
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Cash on Hand</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($transaction = $transactions->fetch_assoc()): ?>
                                <tr>
                                    <td><?= date('g:i A', strtotime($transaction['created_at'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $transaction['transaction_type'] == 'opening' ? 'success' : ($transaction['transaction_type'] == 'closing' ? 'warning' : 'primary') ?>">
                                            <?= ucfirst($transaction['transaction_type']) ?>
                                        </span>
                                    </td>
                                    <td class="<?= $transaction['amount'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= $transaction['amount'] >= 0 ? '+' : '' ?>₱<?= number_format($transaction['amount'], 2) ?>
                                    </td>
                                    <td><strong>₱<?= number_format($transaction['cash_on_hand'], 2) ?></strong></td>
                                    <td><?= htmlspecialchars($transaction['notes']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Navigation -->
        <div class="header text-center">
            <a href="index.php" class="btn btn-primary">
                <i class="bi bi-arrow-left"></i> Back to Orders
            </a>
            <a href="daily_sales.php" class="btn btn-primary">
                <i class="bi bi-graph-up"></i> Daily Sales
            </a>
        </div>
    </div>
    
<?php require_once 'includes/footer_clean.php'; ?>
