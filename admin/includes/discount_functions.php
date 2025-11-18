<?php
/**
 * BOSS LEVEL: Discount Calculation Functions
 * 
 * Handles Senior Citizen & PWD discount calculations
 * Integrates with payment processing and revenue tracking
 */

class DiscountManager {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Calculate discount for an order
     * 
     * @param float $original_amount Original order amount
     * @param string $discount_type 'senior_citizen' or 'pwd'
     * @return array Discount calculation result
     */
    public function calculateDiscount($original_amount, $discount_type) {
        // Get discount configuration
        $config_sql = "SELECT * FROM discount_config WHERE discount_type = ? AND is_active = 1";
        $stmt = $this->conn->prepare($config_sql);
        $stmt->bind_param("s", $discount_type);
        $stmt->execute();
        $config = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$config) {
            return [
                'success' => false,
                'error' => 'Discount configuration not found'
            ];
        }
        
        // Check minimum amount requirement
        if ($original_amount < $config['minimum_amount']) {
            return [
                'success' => false,
                'error' => 'Order amount below minimum for discount'
            ];
        }
        
        // Calculate discount
        $discount_percentage = $config['discount_percentage'];
        $discount_amount = ($original_amount * $discount_percentage) / 100;
        
        // Apply maximum discount limit if set
        if ($config['maximum_discount'] && $discount_amount > $config['maximum_discount']) {
            $discount_amount = $config['maximum_discount'];
        }
        
        $final_amount = $original_amount - $discount_amount;
        
        return [
            'success' => true,
            'original_amount' => $original_amount,
            'discount_percentage' => $discount_percentage,
            'discount_amount' => $discount_amount,
            'final_amount' => $final_amount,
            'discount_type' => $discount_type
        ];
    }
    
    /**
     * Apply discount to an order
     * 
     * @param int $order_id Order ID
     * @param string $discount_type Discount type
     * @param int $applied_by User ID who applied the discount
     * @return array Result of discount application
     */
    public function applyDiscountToOrder($order_id, $discount_type, $applied_by = null) {
        try {
            $this->conn->begin_transaction();
            
            // Get current order amount
            $order_sql = "SELECT total_amount FROM orders WHERE order_id = ?";
            $stmt = $this->conn->prepare($order_sql);
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if (!$order) {
                throw new Exception('Order not found');
            }
            
            $original_amount = $order['total_amount'];
            
            // Calculate discount
            $discount_result = $this->calculateDiscount($original_amount, $discount_type);
            
            if (!$discount_result['success']) {
                throw new Exception($discount_result['error']);
            }
            
            // Update order with discount
            $update_sql = "UPDATE orders SET 
                          discount_type = ?, 
                          discount_percentage = ?, 
                          discount_amount = ?, 
                          original_amount = ?, 
                          total_amount = ?,
                          discount_notes = ?
                          WHERE order_id = ?";
            
            $stmt = $this->conn->prepare($update_sql);
            $notes = ucfirst(str_replace('_', ' ', $discount_type)) . " discount applied";
            $stmt->bind_param("sdddsi", 
                $discount_type,
                $discount_result['discount_percentage'],
                $discount_result['discount_amount'],
                $original_amount,
                $discount_result['final_amount'],
                $notes,
                $order_id
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to update order with discount');
            }
            $stmt->close();
            
            // Record in discount analytics
            $analytics_sql = "INSERT INTO discount_analytics 
                            (order_id, discount_type, original_amount, discount_amount, final_amount, discount_percentage, applied_by, notes) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($analytics_sql);
            $stmt->bind_param("isddddss", 
                $order_id,
                $discount_type,
                $discount_result['original_amount'],
                $discount_result['discount_amount'],
                $discount_result['final_amount'],
                $discount_result['discount_percentage'],
                $applied_by,
                $notes
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to record discount analytics');
            }
            $stmt->close();
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'message' => 'Discount applied successfully',
                'discount_result' => $discount_result
            ];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Apply discount to QR order
     * 
     * @param int $qr_order_id QR Order ID
     * @param string $discount_type Discount type
     * @param int $applied_by User ID who applied the discount
     * @return array Result of discount application
     */
    public function applyDiscountToQROrder($qr_order_id, $discount_type, $applied_by = null) {
        try {
            $this->conn->begin_transaction();
            
            // Get current QR order amount
            $order_sql = "SELECT subtotal FROM qr_orders WHERE order_id = ?";
            $stmt = $this->conn->prepare($order_sql);
            $stmt->bind_param("i", $qr_order_id);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if (!$order) {
                throw new Exception('QR Order not found');
            }
            
            $original_amount = $order['subtotal'];
            
            // Calculate discount
            $discount_result = $this->calculateDiscount($original_amount, $discount_type);
            
            if (!$discount_result['success']) {
                throw new Exception($discount_result['error']);
            }
            
            // Update QR order with discount
            $update_sql = "UPDATE qr_orders SET 
                          discount_type = ?, 
                          discount_percentage = ?, 
                          discount_amount = ?, 
                          original_subtotal = ?,
                          subtotal = ?
                          WHERE order_id = ?";
            
            $stmt = $this->conn->prepare($update_sql);
            $stmt->bind_param("sdddi", 
                $discount_type,
                $discount_result['discount_percentage'],
                $discount_result['discount_amount'],
                $original_amount,
                $discount_result['final_amount'],
                $qr_order_id
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to update QR order with discount');
            }
            $stmt->close();
            
            // Record in discount analytics
            $analytics_sql = "INSERT INTO discount_analytics 
                            (qr_order_id, discount_type, original_amount, discount_amount, final_amount, discount_percentage, applied_by, notes) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($analytics_sql);
            $notes = ucfirst(str_replace('_', ' ', $discount_type)) . " discount applied to QR order";
            $stmt->bind_param("isddddss", 
                $qr_order_id,
                $discount_type,
                $discount_result['original_amount'],
                $discount_result['discount_amount'],
                $discount_result['final_amount'],
                $discount_result['discount_percentage'],
                $applied_by,
                $notes
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to record QR discount analytics');
            }
            $stmt->close();
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'message' => 'QR Order discount applied successfully',
                'discount_result' => $discount_result
            ];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get discount analytics for reporting
     * 
     * @param string $date_from Start date (Y-m-d)
     * @param string $date_to End date (Y-m-d)
     * @return array Discount analytics data
     */
    public function getDiscountAnalytics($date_from, $date_to) {
        $sql = "SELECT 
                discount_type,
                COUNT(*) as total_discounts,
                SUM(original_amount) as total_original_amount,
                SUM(discount_amount) as total_discount_amount,
                SUM(final_amount) as total_final_amount,
                AVG(discount_percentage) as avg_discount_percentage
                FROM discount_analytics 
                WHERE DATE(applied_at) BETWEEN ? AND ?
                GROUP BY discount_type";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $date_from, $date_to);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $analytics = [];
        while ($row = $result->fetch_assoc()) {
            $analytics[] = $row;
        }
        $stmt->close();
        
        return $analytics;
    }
    
    /**
     * Get available discount types
     * 
     * @return array Available discount configurations
     */
    public function getAvailableDiscounts() {
        $sql = "SELECT * FROM discount_config WHERE is_active = 1 ORDER BY discount_type";
        $result = $this->conn->query($sql);
        
        $discounts = [];
        while ($row = $result->fetch_assoc()) {
            $discounts[] = $row;
        }
        
        return $discounts;
    }
}

/**
 * Helper function to format discount display
 */
function formatDiscountDisplay($discount_type) {
    switch ($discount_type) {
        case 'senior_citizen':
            return 'Senior Citizen';
        case 'pwd':
            return 'PWD';
        default:
            return 'None';
    }
}

/**
 * Helper function to get discount badge class
 */
function getDiscountBadgeClass($discount_type) {
    switch ($discount_type) {
        case 'senior_citizen':
            return 'bg-info';
        case 'pwd':
            return 'bg-success';
        default:
            return 'bg-secondary';
    }
}
?>




