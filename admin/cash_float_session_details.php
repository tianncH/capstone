<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';
require_once 'includes/currency_functions.php';

$session_id = intval($_GET['session_id']);

// Get session details
$session_sql = "SELECT * FROM cash_float_sessions WHERE session_id = ?";
$session_stmt = $conn->prepare($session_sql);
$session_stmt->bind_param('i', $session_id);
$session_stmt->execute();
$session = $session_stmt->get_result()->fetch_assoc();
$session_stmt->close();

if (!$session) {
    echo '<div class="alert alert-danger">Session not found.</div>';
    exit;
}

// Get all transactions for this session
$transactions_sql = "SELECT * FROM cash_float_transactions 
                     WHERE shift_date = ? 
                     ORDER BY created_at ASC";
$transactions_stmt = $conn->prepare($transactions_sql);
$transactions_stmt->bind_param('s', $session['shift_date']);
$transactions_stmt->execute();
$transactions = $transactions_stmt->get_result();
$transactions_stmt->close();
?>

<div class="row">
    <div class="col-md-6">
        <h6 class="text-primary">Session Information</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>Date:</strong></td>
                <td><?= date('M j, Y', strtotime($session['shift_date'])) ?></td>
            </tr>
            <tr>
                <td><strong>Status:</strong></td>
                <td>
                    <span class="badge bg-<?= $session['status'] == 'active' ? 'success' : 'secondary' ?>">
                        <?= ucfirst($session['status']) ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td><strong>Opening Amount:</strong></td>
                <td><strong><?= formatPeso($session['opening_amount']) ?></strong></td>
            </tr>
            <tr>
                <td><strong>Closing Amount:</strong></td>
                <td>
                    <?php if ($session['closing_amount'] !== null): ?>
                        <strong><?= formatPeso($session['closing_amount']) ?></strong>
                    <?php else: ?>
                        <span class="text-muted">Not closed</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>Difference:</strong></td>
                <td>
                    <?php if ($session['closing_amount'] !== null): ?>
                        <?php $difference = $session['closing_amount'] - $session['opening_amount']; ?>
                        <span class="text-<?= $difference >= 0 ? 'success' : 'danger' ?>">
                            <strong><?= $difference >= 0 ? '+' : '' ?><?= formatPeso($difference) ?></strong>
                        </span>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>Adjustments:</strong></td>
                <td>
                    <?php if ($session['adjustments'] != 0): ?>
                        <span class="text-info"><?= formatPeso($session['adjustments']) ?></span>
                    <?php else: ?>
                        <span class="text-muted">₱0.00</span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>
    
    <div class="col-md-6">
        <h6 class="text-primary">Timing Information</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>Opened:</strong></td>
                <td><?= date('g:i A', strtotime($session['opened_at'])) ?></td>
            </tr>
            <tr>
                <td><strong>Closed:</strong></td>
                <td>
                    <?php if ($session['closed_at']): ?>
                        <?= date('g:i A', strtotime($session['closed_at'])) ?>
                    <?php else: ?>
                        <span class="text-muted">Not closed</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>Duration:</strong></td>
                <td>
                    <?php if ($session['closed_at']): ?>
                        <?php
                        $start = strtotime($session['opened_at']);
                        $end = strtotime($session['closed_at']);
                        $duration = $end - $start;
                        $hours = floor($duration / 3600);
                        $minutes = floor(($duration % 3600) / 60);
                        echo $hours . 'h ' . $minutes . 'm';
                        ?>
                    <?php else: ?>
                        <span class="text-muted">In progress</span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>
</div>

<?php if ($session['notes']): ?>
<div class="mt-3">
    <h6 class="text-primary">Notes</h6>
    <div class="alert alert-light">
        <?= nl2br(htmlspecialchars($session['notes'])) ?>
    </div>
</div>
<?php endif; ?>

<hr>

<h6 class="text-primary">Transaction History</h6>
<?php if ($transactions->num_rows > 0): ?>
    <div class="table-responsive">
        <table class="table table-sm table-striped">
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
                            <span class="badge bg-<?= $transaction['transaction_type'] == 'opening' ? 'success' : 
                                                   ($transaction['transaction_type'] == 'closing' ? 'warning' : 
                                                   ($transaction['transaction_type'] == 'adjustment' ? 'info' : 'secondary')) ?>">
                                <?= ucfirst($transaction['transaction_type']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="<?= $transaction['amount'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= $transaction['amount'] >= 0 ? '+' : '' ?><?= formatPeso($transaction['amount']) ?>
                            </span>
                        </td>
                        <td><strong><?= formatPeso($transaction['cash_on_hand']) ?></strong></td>
                        <td><?= htmlspecialchars($transaction['notes']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="text-center py-3">
        <i class="bi bi-receipt text-muted"></i>
        <p class="text-muted mb-0">No transactions found for this session.</p>
    </div>
<?php endif; ?>









