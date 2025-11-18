<?php
require_once 'includes/db_connection.php';
require_once 'includes/header.php';

// Initialize variables
$order = null;
$error = null;
$today = date('Y-m-d');

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['queue_number'])) {
    $queue_number = trim($_POST['queue_number']);
    
    // Validate queue number format (basic validation)
    if (empty($queue_number)) {
        $error = "Please enter a queue number.";
    } else {
        // Query the database for the order
        $sql = "SELECT o.*, os.name as status_name, t.table_number 
                FROM orders o 
                LEFT JOIN tables t ON o.table_id = t.table_id 
                JOIN order_statuses os ON o.status_id = os.status_id 
                WHERE o.queue_number = ? AND DATE(o.created_at) = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $queue_number, $today);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $order = $result->fetch_assoc();
            
            // Store the tracked order in session
            if (!isset($_SESSION)) {
                session_start();
            }
            $_SESSION['tracked_orders'][$queue_number] = [
                'queue_number' => $queue_number,
                'status_id' => $order['status_id'],
                'status_name' => $order['status_name'],
                'created_at' => $order['created_at']
            ];
            
            // Also store in localStorage via JavaScript
            echo "<script>
                try {
                    let trackedOrders = JSON.parse(localStorage.getItem('trackedOrders')) || {};
                    trackedOrders['{$queue_number}'] = {
                        queue_number: '{$queue_number}',
                        status_id: {$order['status_id']},
                        status_name: '{$order['status_name']}',
                        created_at: '{$order['created_at']}'
                    };
                    localStorage.setItem('trackedOrders', JSON.stringify(trackedOrders));
                } catch (e) {
                    console.error('Error storing order in localStorage:', e);
                }
            </script>";
        } else {
            $error = "Order not found. Please check your queue number or make sure the order was placed today.";
        }
        
        $stmt->close();
    }
}

// Get previously tracked orders from session
$tracked_orders = [];
if (!isset($_SESSION)) {
    session_start();
}

if (isset($_SESSION['tracked_orders'])) {
    $tracked_orders = $_SESSION['tracked_orders'];
}
?>

<div class="container">
    <?php require_once 'includes/navbar.php'; ?>
    
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Track Your Order</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="queue_number" class="form-label">Enter Your Queue Number:</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="queue_number" name="queue_number" placeholder="e.g., 20250514-123" required>
                                <button type="submit" class="btn btn-primary">Track</button>
                            </div>
                            <div class="form-text">Enter the queue number you received when placing your order.</div>
                        </div>
                    </form>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger mt-3">
                            <?= $error ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($order): ?>
                        <div class="alert alert-success mt-3">
                            <h5>Order Found!</h5>
                        </div>
                        
                        <div class="card mt-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Order #<?= htmlspecialchars($order['queue_number']) ?></h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Ordered at:</strong> <?= date('h:i A', strtotime($order['created_at'])) ?></p>
                                        <?php if ($order['table_number']): ?>
                                            <p><strong>Table:</strong> <?= htmlspecialchars($order['table_number']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Status:</strong> 
                                            <span class="badge <?= getStatusBadgeClass($order['status_id']) ?>">
                                                <?= htmlspecialchars($order['status_name']) ?>
                                            </span>
                                        </p>
                                        <p><strong>Total:</strong> ₱<?= number_format($order['total_amount'], 2) ?></p>
                                    </div>
                                </div>
                                
                                <?php
                                // Get order items
                                $sql_items = "SELECT oi.*, mi.name as item_name, iv.name as variation_name 
                                              FROM order_items oi 
                                              JOIN menu_items mi ON oi.item_id = mi.item_id 
                                              LEFT JOIN item_variations iv ON oi.variation_id = iv.variation_id 
                                              WHERE oi.order_id = " . $order['order_id'];
                                
                                $result_items = $conn->query($sql_items);
                                
                                if ($result_items && $result_items->num_rows > 0):
                                ?>
                                    <h6 class="mt-3">Order Items:</h6>
                                    <ul class="list-group">
                                        <?php while ($item = $result_items->fetch_assoc()): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <?= $item['quantity'] ?>x <?= htmlspecialchars($item['item_name']) ?>
                                                    <?php if ($item['variation_name']): ?>
                                                        <small class="text-muted">(<?= htmlspecialchars($item['variation_name']) ?>)</small>
                                                    <?php endif; ?>
                                                </div>
                                                <span>₱<?= number_format($item['subtotal'], 2) ?></span>
                                            </li>
                                        <?php endwhile; ?>
                                    </ul>
                                <?php endif; ?>
                                
                                <?php if (!empty($order['notes'])): ?>
                                    <div class="mt-3">
                                        <h6>Special Instructions:</h6>
                                        <p class="mb-0"><?= nl2br(htmlspecialchars($order['notes'])) ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="mt-3">
                                    <h6>Order Status Explanation:</h6>
                                    <ul class="list-unstyled small">
                                        <li><span class="badge bg-warning">Pending</span> - Order placed but not paid</li>
                                        <li><span class="badge bg-primary">Paid</span> - Order paid and waiting to be prepared</li>
                                        <li><span class="badge bg-info">Preparing</span> - Order is being prepared in the kitchen</li>
                                        <li><span class="badge bg-success">Ready</span> - Order is ready for pickup</li>
                                        <li><span class="badge bg-secondary">Completed</span> - Order has been picked up and completed</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Previously Tracked Orders -->
            <div id="trackedOrdersContainer">
                <!-- This will be populated by JavaScript -->
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Function to display tracked orders
    function displayTrackedOrders() {
        const container = document.getElementById('trackedOrdersContainer');
        let trackedOrders = {};
        
        // Try to get tracked orders from localStorage
        try {
            trackedOrders = JSON.parse(localStorage.getItem('trackedOrders')) || {};
        } catch (e) {
            console.error('Error retrieving tracked orders from localStorage:', e);
        }
        
        // Filter out orders that are not from today
        const today = new Date().toISOString().split('T')[0]; // YYYY-MM-DD format
        Object.keys(trackedOrders).forEach(key => {
            const orderDate = new Date(trackedOrders[key].created_at).toISOString().split('T')[0];
            if (orderDate !== today) {
                delete trackedOrders[key];
            }
        });
        
        // Save filtered orders back to localStorage
        localStorage.setItem('trackedOrders', JSON.stringify(trackedOrders));
        
        // If no tracked orders, hide the container
        if (Object.keys(trackedOrders).length === 0) {
            container.innerHTML = '';
            return;
        }
        
        // Build HTML for tracked orders
        let html = `
            <div class="card">
                <div class="card-header">
                    <h5>Your Recent Orders</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
        `;
        
        Object.values(trackedOrders).forEach(order => {
            const statusClass = getStatusBadgeClass(order.status_id);
            const orderTime = new Date(order.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            
            html += `
                <a href="track_order.php?queue_number=${order.queue_number}" class="list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">Order #${order.queue_number}</h6>
                        <small>${orderTime}</small>
                    </div>
                    <p class="mb-1">Status: <span class="badge ${statusClass}">${order.status_name}</span></p>
                </a>
            `;
        });
        
        html += `
                    </div>
                    <div class="mt-3">
                        <button id="refreshOrdersBtn" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-arrow-clockwise"></i> Refresh Status
                        </button>
                        <button id="clearOrdersBtn" class="btn btn-outline-danger btn-sm float-end">
                            Clear All
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        container.innerHTML = html;
        
        // Add event listeners
        document.getElementById('refreshOrdersBtn').addEventListener('click', refreshOrderStatuses);
        document.getElementById('clearOrdersBtn').addEventListener('click', clearTrackedOrders);
    }
    
    // Function to refresh order statuses
    function refreshOrderStatuses() {
        let trackedOrders = {};
        
        try {
            trackedOrders = JSON.parse(localStorage.getItem('trackedOrders')) || {};
        } catch (e) {
            console.error('Error retrieving tracked orders from localStorage:', e);
            return;
        }
        
        const queueNumbers = Object.keys(trackedOrders);
        
        if (queueNumbers.length === 0) {
            return;
        }
        
        // Show loading indicator
        const refreshBtn = document.getElementById('refreshOrdersBtn');
        const originalText = refreshBtn.innerHTML;
        refreshBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Refreshing...';
        refreshBtn.disabled = true;
        
        // Fetch updated statuses
        fetch('get_order_statuses.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ queue_numbers: queueNumbers })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update localStorage with new statuses
                Object.keys(data.orders).forEach(queueNumber => {
                    if (trackedOrders[queueNumber]) {
                        trackedOrders[queueNumber].status_id = data.orders[queueNumber].status_id;
                        trackedOrders[queueNumber].status_name = data.orders[queueNumber].status_name;
                    }
                });
                
                localStorage.setItem('trackedOrders', JSON.stringify(trackedOrders));
                
                // Refresh the display
                displayTrackedOrders();
                
                // Show success message
                alert('Order statuses refreshed successfully!');
            } else {
                alert('Error refreshing order statuses: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while refreshing order statuses.');
        })
        .finally(() => {
            // Reset button
            refreshBtn.innerHTML = originalText;
            refreshBtn.disabled = false;
        });
    }
    
    // Function to clear tracked orders
    function clearTrackedOrders() {
        if (confirm('Are you sure you want to clear all tracked orders?')) {
            localStorage.removeItem('trackedOrders');
            displayTrackedOrders();
        }
    }
    
    // Initialize
    displayTrackedOrders();
    
    // Auto-fill queue number from URL parameter if present
    const urlParams = new URLSearchParams(window.location.search);
    const queueNumber = urlParams.get('queue_number');
    if (queueNumber) {
        document.getElementById('queue_number').value = queueNumber;
        // Auto-submit the form
        document.querySelector('form').submit();
    }
});

// Helper function to get status badge class
function getStatusBadgeClass(statusId) {
    switch (parseInt(statusId)) {
        case 1: return 'bg-warning';
        case 2: return 'bg-primary';
        case 3: return 'bg-info';
        case 4: return 'bg-success';
        case 5: return 'bg-secondary';
        case 6: return 'bg-danger';
        default: return 'bg-secondary';
    }
}
</script>

<?php
// Helper function to get status badge class
function getStatusBadgeClass($statusId) {
    switch (intval($statusId)) {
        case 1: return 'bg-warning';
        case 2: return 'bg-primary';
        case 3: return 'bg-info';
        case 4: return 'bg-success';
        case 5: return 'bg-secondary';
        case 6: return 'bg-danger';
        default: return 'bg-secondary';
    }
}

require_once 'includes/footer.php';
$conn->close();
?>