<?php
// Cash Float Management Functions

/**
 * Set cash float for a specific date
 */
function setCashFloat($conn, $date, $amount, $admin_id, $notes = '') {
    try {
        // Check if there's already an active float for this date
        $check_sql = "SELECT float_id FROM cash_float WHERE date = ? AND status = 'active'";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $date);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            return ['success' => false, 'message' => 'Cash float already set for this date'];
        }
        
        // Insert new cash float record
        $sql = "INSERT INTO cash_float (date, opening_amount, set_by_admin_id, notes) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sdis", $date, $amount, $admin_id, $notes);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Cash float set successfully', 'float_id' => $conn->insert_id];
        } else {
            return ['success' => false, 'message' => 'Failed to set cash float'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Get cash float for a specific date
 */
function getCashFloat($conn, $date) {
    try {
        $sql = "SELECT cf.*, au.full_name as set_by_name, au2.full_name as closed_by_name 
                FROM cash_float cf 
                LEFT JOIN admin_users au ON cf.set_by_admin_id = au.admin_id 
                LEFT JOIN admin_users au2 ON cf.closed_by_admin_id = au2.admin_id 
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
 * Update cash float amount
 */
function updateCashFloat($conn, $float_id, $amount, $admin_id, $notes = '') {
    try {
        $sql = "UPDATE cash_float SET opening_amount = ?, notes = ?, updated_at = CURRENT_TIMESTAMP WHERE float_id = ? AND status = 'active'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("dsi", $amount, $notes, $float_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            return ['success' => true, 'message' => 'Cash float updated successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to update cash float or float not found'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Close cash float for the day
 */
function closeCashFloat($conn, $float_id, $closing_amount, $admin_id, $notes = '') {
    try {
        $sql = "UPDATE cash_float SET closing_amount = ?, closed_by_admin_id = ?, status = 'closed', notes = CONCAT(IFNULL(notes, ''), ' | Closing: ', ?), updated_at = CURRENT_TIMESTAMP WHERE float_id = ? AND status = 'active'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("disi", $closing_amount, $admin_id, $notes, $float_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            return ['success' => true, 'message' => 'Cash float closed successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to close cash float or float not found'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Get cash float history
 */
function getCashFloatHistory($conn, $start_date = null, $end_date = null, $limit = 30) {
    try {
        $where_clause = "";
        $params = [];
        $types = "";
        
        if ($start_date && $end_date) {
            $where_clause = "WHERE cf.date BETWEEN ? AND ?";
            $params = [$start_date, $end_date];
            $types = "ss";
        }
        
        $sql = "SELECT cf.*, au.full_name as set_by_name, au2.full_name as closed_by_name 
                FROM cash_float cf 
                LEFT JOIN admin_users au ON cf.set_by_admin_id = au.admin_id 
                LEFT JOIN admin_users au2 ON cf.closed_by_admin_id = au2.admin_id 
                $where_clause
                ORDER BY cf.date DESC 
                LIMIT ?";
        
        $params[] = $limit;
        $types .= "i";
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $history = [];
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        
        return ['success' => true, 'data' => $history];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Calculate cash variance (difference between expected and actual)
 */
function calculateCashVariance($conn, $date) {
    try {
        // Get cash float for the date
        $float_result = getCashFloat($conn, $date);
        if (!$float_result['success']) {
            return ['success' => false, 'message' => 'No cash float found for this date'];
        }
        
        $cash_float = $float_result['data'];
        
        // Get total sales for the date
        $sales_sql = "SELECT COALESCE(SUM(total_amount), 0) as total_sales FROM orders WHERE DATE(created_at) = ? AND status_id = 2";
        $sales_stmt = $conn->prepare($sales_sql);
        $sales_stmt->bind_param("s", $date);
        $sales_stmt->execute();
        $sales_result = $sales_stmt->get_result();
        $total_sales = $sales_result->fetch_assoc()['total_sales'];
        
        // Calculate expected closing amount
        $expected_closing = $cash_float['opening_amount'] + $total_sales;
        
        // Calculate variance if closing amount is set
        $variance = null;
        if ($cash_float['closing_amount'] !== null) {
            $variance = $cash_float['closing_amount'] - $expected_closing;
        }
        
        return [
            'success' => true,
            'data' => [
                'opening_amount' => $cash_float['opening_amount'],
                'total_sales' => $total_sales,
                'expected_closing' => $expected_closing,
                'actual_closing' => $cash_float['closing_amount'],
                'variance' => $variance,
                'status' => $cash_float['status']
            ]
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}
?>