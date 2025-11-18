<?php
// Counter Cash Float Functions - Backend Only

/**
 * Get today's cash float set by admin
 */
function getAdminCashFloat($conn, $date = null) {
    if ($date === null) {
        $date = date('Y-m-d');
    }
    
    try {
        $sql = "SELECT cf.*, au.full_name as set_by_name 
                FROM cash_float cf 
                LEFT JOIN admin_users au ON cf.set_by_admin_id = au.admin_id 
                WHERE cf.date = ? AND cf.status = 'active'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return ['success' => true, 'data' => $result->fetch_assoc()];
        } else {
            return ['success' => false, 'message' => 'No active cash float found for this date'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Create cash transactions table if it doesn't exist
 */
function createCashTransactionsTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS `cash_transactions` (
        `transaction_id` int(11) NOT NULL AUTO_INCREMENT,
        `float_id` int(11) NOT NULL,
        `order_id` int(11) DEFAULT NULL,
        `transaction_type` enum('payment','change','adjustment','opening','closing') NOT NULL,
        `amount` decimal(10,2) NOT NULL,
        `customer_payment` decimal(10,2) DEFAULT NULL,
        `change_given` decimal(10,2) DEFAULT NULL,
        `notes` text DEFAULT NULL,
        `counter_user_id` int(11) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`transaction_id`),
        KEY `float_id` (`float_id`),
        KEY `order_id` (`order_id`),
        KEY `counter_user_id` (`counter_user_id`),
        KEY `transaction_type` (`transaction_type`),
        CONSTRAINT `cash_transactions_ibfk_1` FOREIGN KEY (`float_id`) REFERENCES `cash_float` (`float_id`),
        CONSTRAINT `cash_transactions_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
        CONSTRAINT `cash_transactions_ibfk_3` FOREIGN KEY (`counter_user_id`) REFERENCES `counter_users` (`counter_user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    return $conn->query($sql);
}

/**
 * Record cash transaction (payment with change)
 */
function recordCashTransaction($conn, $float_id, $order_id, $customer_payment, $order_total, $counter_user_id, $notes = '') {
    try {
        $change_given = $customer_payment - $order_total;
        
        if ($change_given < 0) {
            return ['success' => false, 'message' => 'Customer payment is insufficient'];
        }
        
        // Record the transaction
        $sql = "INSERT INTO cash_transactions 
                (float_id, order_id, transaction_type, amount, customer_payment, change_given, notes, counter_user_id) 
                VALUES (?, ?, 'payment', ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iidddsi", $float_id, $order_id, $order_total, $customer_payment, $change_given, $notes, $counter_user_id);
        
        if ($stmt->execute()) {
            return [
                'success' => true, 
                'message' => 'Cash transaction recorded successfully',
                'transaction_id' => $conn->insert_id,
                'change_given' => $change_given
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to record cash transaction'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Get current cash position
 */
function getCurrentCashPosition($conn, $float_id) {
    try {
        // Get opening amount
        $sql_opening = "SELECT opening_amount FROM cash_float WHERE float_id = ?";
        $stmt_opening = $conn->prepare($sql_opening);
        $stmt_opening->bind_param("i", $float_id);
        $stmt_opening->execute();
        $result_opening = $stmt_opening->get_result();
        $opening_amount = $result_opening->fetch_assoc()['opening_amount'];
        
        // Get total payments received
        $sql_payments = "SELECT COALESCE(SUM(customer_payment), 0) as total_payments 
                        FROM cash_transactions 
                        WHERE float_id = ? AND transaction_type = 'payment'";
        $stmt_payments = $conn->prepare($sql_payments);
        $stmt_payments->bind_param("i", $float_id);
        $stmt_payments->execute();
        $result_payments = $stmt_payments->get_result();
        $total_payments = $result_payments->fetch_assoc()['total_payments'];
        
        // Get total change given
        $sql_change = "SELECT COALESCE(SUM(change_given), 0) as total_change 
                      FROM cash_transactions 
                      WHERE float_id = ? AND transaction_type = 'payment'";
        $stmt_change = $conn->prepare($sql_change);
        $stmt_change->bind_param("i", $float_id);
        $stmt_change->execute();
        $result_change = $stmt_change->get_result();
        $total_change = $result_change->fetch_assoc()['total_change'];
        
        // Calculate current cash position
        $current_cash = $opening_amount + $total_payments - $total_change;
        
        return [
            'success' => true,
            'data' => [
                'opening_amount' => $opening_amount,
                'total_payments' => $total_payments,
                'total_change' => $total_change,
                'current_cash' => $current_cash
            ]
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Get cash transactions for a float
 */
function getCashTransactions($conn, $float_id, $limit = 50) {
    try {
        $sql = "SELECT ct.*, o.queue_number, cu.full_name as counter_name
                FROM cash_transactions ct
                LEFT JOIN orders o ON ct.order_id = o.order_id
                LEFT JOIN counter_users cu ON ct.counter_user_id = cu.counter_user_id
                WHERE ct.float_id = ?
                ORDER BY ct.created_at DESC
                LIMIT ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $float_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $transactions = [];
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
        
        return ['success' => true, 'data' => $transactions];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Record manual cash adjustment
 */
function recordCashAdjustment($conn, $float_id, $amount, $reason, $counter_user_id) {
    try {
        $sql = "INSERT INTO cash_transactions 
                (float_id, transaction_type, amount, notes, counter_user_id) 
                VALUES (?, 'adjustment', ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("idsi", $float_id, $amount, $reason, $counter_user_id);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Cash adjustment recorded successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to record cash adjustment'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}
?>