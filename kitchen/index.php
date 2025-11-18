<?php
require_once __DIR__ . '/includes/db_connection.php';

// Process AJAX requests for real-time updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_orders') {
    header('Content-Type: application/json');
    
    $today = date('Y-m-d');
    
    // Get orders with session information
    $sql_orders = "SELECT o.*, os.name as status_name, t.table_number, ts.session_id
                   FROM orders o 
                   LEFT JOIN tables t ON o.table_id = t.table_id 
                   JOIN order_statuses os ON o.status_id = os.status_id 
                   LEFT JOIN table_sessions ts ON o.session_id = ts.session_id
                   WHERE DATE(o.created_at) = ? 
                   AND (o.status_id = 3 OR o.status_id = 4 OR o.status_id = 5) 
                   ORDER BY o.created_at ASC";
    $stmt_orders = $conn->prepare($sql_orders);
    $stmt_orders->bind_param("s", $today);
    $stmt_orders->execute();
    $result_orders = $stmt_orders->get_result();
    $stmt_orders->close();
    
    $orders = [];
    $paid_count = 0;
    $preparing_count = 0;
    
    while ($order = $result_orders->fetch_assoc()) {
        // Get order items (try regular order_items first, then qr_orders)
        $sql_items = "SELECT oi.*, mi.name as item_name, iv.name as variation_name 
                      FROM order_items oi 
                      JOIN menu_items mi ON oi.item_id = mi.item_id 
                      LEFT JOIN item_variations iv ON oi.variation_id = iv.variation_id 
                      WHERE oi.order_id = ?";
        
        $stmt_items = $conn->prepare($sql_items);
        $stmt_items->bind_param("i", $order['order_id']);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();
        $stmt_items->close();
        
        $items = [];
        
        // If no regular order items found, check for QR orders
        if ($result_items->num_rows == 0) {
            // Try to find QR orders for this order
            $sql_qr_items = "SELECT qo.*, mi.name as item_name, 'QR Order' as variation_name
                             FROM qr_orders qo 
                             JOIN menu_items mi ON qo.menu_item_id = mi.item_id 
                             WHERE qo.session_id = (SELECT session_id FROM qr_sessions WHERE table_id = ? AND status = 'active' LIMIT 1)";
            
            $stmt_qr_items = $conn->prepare($sql_qr_items);
            $stmt_qr_items->bind_param("i", $order['table_id']);
            $stmt_qr_items->execute();
            $result_items = $stmt_qr_items->get_result();
            $stmt_qr_items->close();
        }
        
        while ($item = $result_items->fetch_assoc()) {
            // Get add-ons
            $sql_addons = "SELECT oia.*, ia.name as addon_name 
                          FROM order_item_addons oia 
                          JOIN item_addons ia ON oia.addon_id = ia.addon_id 
                          WHERE oia.order_item_id = ?";
            
            $stmt_addons = $conn->prepare($sql_addons);
            $stmt_addons->bind_param("i", $item['order_item_id']);
            $stmt_addons->execute();
            $result_addons = $stmt_addons->get_result();
            $stmt_addons->close();
            
            $addons = [];
            while ($addon = $result_addons->fetch_assoc()) {
                $addons[] = $addon['addon_name'];
            }
            
            $items[] = [
                'name' => $item['item_name'],
                'quantity' => $item['quantity'],
                'variation' => $item['variation_name'],
                'addons' => $addons
            ];
        }
        
        $order['items'] = $items;
        // Calculate time elapsed in minutes
        // For preparing/ready orders, use updated_at (when status changed)
        // For new orders, use created_at (when order was created)
        $timestamp_to_use = ($order['status_id'] >= 3) ? $order['updated_at'] : $order['created_at'];
        
        // Use DateTime with Manila timezone to ensure correct calculation
        $timestamp_dt = new DateTime($timestamp_to_use, new DateTimeZone('Asia/Manila'));
        $current_dt = new DateTime('now', new DateTimeZone('Asia/Manila'));
        $time_elapsed = round(($current_dt->getTimestamp() - $timestamp_dt->getTimestamp()) / 60);
        
        // Ensure positive value (handle timezone issues)
        $order['time_elapsed'] = max(0, $time_elapsed);
        
        // Debug logging for timer
        error_log("Order {$order['queue_number']}: Created={$order['created_at']}, Elapsed={$order['time_elapsed']}m");
        
        if ($order['status_id'] == 3) $preparing_count++;
        if ($order['status_id'] == 4) $paid_count++; // Ready orders
        if ($order['status_id'] == 5) $paid_count++; // Served orders (count as completed)
        
        $orders[] = $order;
    }
    
    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'stats' => [
            'preparing' => $preparing_count,
            'ready' => $paid_count,
            'total' => $paid_count + $preparing_count
        ]
    ]);
    exit;
}

// Include header for non-AJAX requests
require_once __DIR__ . '/includes/header.php';

// Process order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);
    $action = $_POST['action'];
    
    // Start transaction to ensure data consistency
    $conn->begin_transaction();
    
    try {
        if ($action === 'start_preparing') {
            // Update order status to preparing (status_id = 3)
            $sql = "UPDATE orders SET status_id = 3, updated_at = CURRENT_TIMESTAMP WHERE order_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $order_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Error updating order status: " . $conn->error);
            }
            $stmt->close();
            
            // Add to order status history
            $sql_history = "INSERT INTO order_status_history (order_id, status_id, notes) 
                            VALUES (?, 3, 'Order preparation started')";
            $stmt_history = $conn->prepare($sql_history);
            $stmt_history->bind_param("i", $order_id);
            
            if (!$stmt_history->execute()) {
                throw new Exception("Error adding status history: " . $conn->error);
            }
            $stmt_history->close();
            
            // Commit transaction
            $conn->commit();
            
            // Redirect to prevent form resubmission
            header("Location: index.php?success=1&message=Order+preparation+started");
            exit;
            
        } elseif ($action === 'mark_ready') {
            // Update order status to ready (status_id = 4)
            $sql = "UPDATE orders SET status_id = 4, updated_at = CURRENT_TIMESTAMP WHERE order_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $order_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Error updating order status: " . $conn->error);
            }
            $stmt->close();
            
            // Commit transaction
            $conn->commit();
            
            // Redirect to prevent form resubmission and refresh the page
            header("Location: index.php?success=1&message=Order+marked+as+ready");
            exit;
            
        } elseif ($action === 'mark_served') {
            // Update order status to served (status_id = 5)
            $sql = "UPDATE orders SET status_id = 5, updated_at = CURRENT_TIMESTAMP WHERE order_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $order_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Error updating order status: " . $conn->error);
            }
            $stmt->close();
            
            // Commit transaction
            $conn->commit();
            
            // Redirect to prevent form resubmission and refresh the page
            header("Location: index.php?success=1&message=Order+marked+as+served");
            exit;
            
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        // Redirect with error message
        header("Location: index.php?error=1&message=" . urlencode($e->getMessage()));
        exit;
    }
}

// Get today's date
$today = date('Y-m-d');

// Get paid and preparing orders for today
$sql_orders = "SELECT o.*, os.name as status_name, t.table_number 
               FROM orders o 
               LEFT JOIN tables t ON o.table_id = t.table_id 
               JOIN order_statuses os ON o.status_id = os.status_id 
               WHERE DATE(o.created_at) = ? 
               AND (o.status_id = 1 OR o.status_id = 3 OR o.status_id = 4)
               AND o.status_id != 2
               AND o.status_id != 6 
               ORDER BY o.created_at ASC";
$stmt_orders = $conn->prepare($sql_orders);
$stmt_orders->bind_param("s", $today);
$stmt_orders->execute();
$result_orders = $stmt_orders->get_result();
$stmt_orders->close();

// Count orders by status
$paid_count = 0;
$preparing_count = 0;

if ($result_orders->num_rows > 0) {
    $result_orders->data_seek(0);
    while ($order = $result_orders->fetch_assoc()) {
        if ($order['status_id'] == 1) {
            $preparing_count++; // Awaiting validation
        } elseif ($order['status_id'] == 3) {
            $preparing_count++; // Ready to prepare
        } elseif ($order['status_id'] == 4) {
            $paid_count++; // Ready for pickup
        }
    }
}

// Reset result pointer
if ($result_orders->num_rows > 0) {
    $result_orders->data_seek(0);
}
?>

<!-- Real-time Kitchen Dashboard -->
<div class="kitchen-dashboard">
    <!-- Header with Live Stats -->
    <div class="dashboard-header">
        <div class="header-content">
            <div class="header-left">
                <h1 class="dashboard-title">
                    <i class="bi bi-egg-fried"></i> Kitchen Command Center
                </h1>
                <p class="dashboard-subtitle">Real-time order management</p>
            </div>
            <div class="header-right">
                <div class="live-stats">
                    <div class="stat-card new-orders">
                        <div class="stat-number" id="newOrdersCount">0</div>
                        <div class="stat-label">Preparing</div>
                    </div>
                    <div class="stat-card preparing-orders">
                        <div class="stat-number" id="preparingCount">0</div>
                        <div class="stat-label">Ready</div>
                    </div>
                    <div class="stat-card total-orders">
                        <div class="stat-number" id="totalOrdersCount">0</div>
                        <div class="stat-label">Total Active</div>
                    </div>
                </div>
                <div class="connection-status">
                    <div class="status-indicator text-success" id="connectionStatus">
                        <i class="bi bi-wifi"></i> Live Updates
                    </div>
                    <div class="last-update" id="lastUpdate">Auto-refresh every 5s</div>
            </div>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    <div id="alertContainer"></div>

    <!-- Filter Buttons -->
    <div class="filter-section">
        <div class="filter-buttons">
            <button class="filter-btn active" data-filter="all">
                <i class="bi bi-list-ul"></i> All Orders
            </button>
            <button class="filter-btn" data-filter="new">
                <i class="bi bi-plus-circle"></i> New Orders
            </button>
            <button class="filter-btn" data-filter="preparing">
                <i class="bi bi-check-circle"></i> Ready
            </button>
            <button class="filter-btn" data-filter="served">
                <i class="bi bi-check-circle-fill"></i> Served
            </button>
        </div>
        <div class="view-controls">
            <button class="control-btn" id="soundToggle" title="Toggle Sound">
                <i class="bi bi-volume-up"></i>
                </button>
            <button class="control-btn" id="refreshBtn" title="Refresh Now">
                <i class="bi bi-arrow-clockwise"></i>
                </button>
        </div>
    </div>

    <!-- Orders Grid -->
    <div class="orders-grid" id="ordersGrid">
        <!-- Orders will be loaded here via JavaScript -->
    </div>

    <!-- Empty State -->
    <div class="empty-state" id="emptyState" style="display: none;">
        <div class="empty-icon">
            <i class="bi bi-egg-fried"></i>
        </div>
        <h3>No Active Orders</h3>
        <p>All caught up! New orders will appear here automatically.</p>
    </div>
</div>

<!-- JavaScript for Real-time Kitchen System -->
<script>
class KitchenSystem {
    constructor() {
        this.orders = [];
        this.currentFilter = 'all';
        this.soundEnabled = true;
        this.refreshInterval = null;
        this.lastOrderCount = 0;
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.loadOrders();
        this.startAutoRefresh();
    }
    
    bindEvents() {
        // Filter buttons
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                this.currentFilter = e.target.dataset.filter;
                this.renderOrders();
            });
        });
        
        // Control buttons
        document.getElementById('soundToggle').addEventListener('click', () => {
            this.soundEnabled = !this.soundEnabled;
            const icon = document.querySelector('#soundToggle i');
            icon.className = this.soundEnabled ? 'bi bi-volume-up' : 'bi bi-volume-mute';
        });
        
        document.getElementById('refreshBtn').addEventListener('click', () => {
            this.loadOrders();
        });
    }
    
        async loadOrders() {
            try {
                console.log('🔄 Loading orders...');
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=get_orders'
                });
                
                console.log('📡 Response status:', response.status);
                const data = await response.json();
                console.log('📊 Response data:', data);
            
            if (data.success) {
                this.orders = data.orders;
                this.updateStats(data.stats);
                this.renderOrders();
                this.checkForNewOrders(data.stats.total);
                this.updateLastUpdate();
            } else {
                console.error('❌ Server error:', data);
                this.showAlert('Server error: ' + (data.message || 'Unknown error'), 'danger');
            }
        } catch (error) {
            console.error('❌ Error loading orders:', error);
            console.error('❌ Error details:', error.message);
            this.showAlert('Unable to load orders. Check your connection.', 'warning');
        }
    }
    
    updateStats(stats) {
        document.getElementById('newOrdersCount').textContent = stats.preparing;
        document.getElementById('preparingCount').textContent = stats.ready;
        document.getElementById('totalOrdersCount').textContent = stats.total;
    }
    
    renderOrders() {
        const grid = document.getElementById('ordersGrid');
        const emptyState = document.getElementById('emptyState');
        
        let filteredOrders = this.orders;
        
        if (this.currentFilter === 'new') {
            filteredOrders = this.orders.filter(order => order.status_id === 1 || order.status_id === 3);
        } else if (this.currentFilter === 'preparing') {
            filteredOrders = this.orders.filter(order => order.status_id === 4);
        } else if (this.currentFilter === 'served') {
            filteredOrders = this.orders.filter(order => order.status_id === 5);
        }
        
        if (filteredOrders.length === 0) {
            grid.innerHTML = '';
            emptyState.style.display = 'block';
            return;
        }
        
        emptyState.style.display = 'none';
        
        grid.innerHTML = filteredOrders.map(order => this.createOrderCard(order)).join('');
        
        // Bind action buttons
        this.bindOrderActions();
    }
    
    createOrderCard(order) {
        const timeClass = order.time_elapsed >= 15 ? 'timer-danger' : 
                         order.time_elapsed >= 10 ? 'timer-warning' : '';
        
        let statusClass, statusText, statusIcon;
        if (order.status_id === 1) {
            statusClass = 'order-awaiting';
            statusText = 'Awaiting Validation';
            statusIcon = 'bi-hourglass-split';
        } else if (order.status_id === 3) {
            statusClass = 'order-new';
            statusText = 'Ready to Prepare';
            statusIcon = 'bi-play-circle';
        } else if (order.status_id === 4) {
            statusClass = 'order-ready';
            statusText = 'Ready to Serve';
            statusIcon = 'bi-check-circle-fill';
        } else if (order.status_id === 5) {
            statusClass = 'order-served';
            statusText = 'Served';
            statusIcon = 'bi-check-circle-fill';
        }
        
        return `
            <div class="order-card ${statusClass}" data-cy="kitchen-order-row" data-order-id="${order.order_id}">
                <div class="order-header">
                    <div class="order-info">
                        <h3 class="order-number">#${order.queue_number}</h3>
                        <div class="order-meta">
                            <span class="table-info">
                                <i class="bi bi-table"></i> Table ${order.table_number || 'Takeout'}
                            </span>
                            <span class="order-time">
                                <i class="bi bi-clock"></i> ${this.formatTime(order.created_at)}
                            </span>
                        </div>
                    </div>
                    <div class="order-status">
                        <span class="status-badge ${statusClass}">
                            <i class="bi ${statusIcon}"></i> ${statusText}
                        </span>
                        <div class="timer ${timeClass}">${order.time_elapsed}m ago</div>
                    </div>
                </div>
                
                <div class="order-body">
                    <div class="order-items">
                        ${order.items.map(item => `
                            <div class="item-row">
                                <div class="item-main">
                                    <span class="item-quantity">${item.quantity}x</span>
                                    <span class="item-name">${item.name}</span>
                                    ${item.variation ? `<span class="item-variation"> - ${item.variation}</span>` : ''}
                                </div>
                                ${item.addons.length > 0 ? `
                                    <div class="item-addons">
                                        + ${item.addons.join(', + ')}
                                    </div>
                                ` : ''}
                            </div>
                        `).join('')}
                    </div>
                    
                    ${order.notes ? `
                        <div class="order-notes">
                            <i class="bi bi-info-circle"></i>
                            <strong>Notes:</strong> ${order.notes}
                        </div>
                    ` : ''}
                    
                    <div class="order-actions">
                        ${order.status_id === 1 ? `
                            <div class="awaiting-status">
                                <i class="bi bi-hourglass-split text-warning"></i> Waiting for Counter Validation
                            </div>
                        ` : order.status_id === 3 ? `
                            <button class="action-btn ready-btn" data-cy="kitchen-prepared" data-action="mark_ready" data-order-id="${order.order_id}">
                                <i class="bi bi-check-circle"></i> Mark Ready
                            </button>
                        ` : order.status_id === 4 ? `
                            <button class="action-btn served-btn" data-cy="kitchen-complete" data-action="mark_served" data-order-id="${order.order_id}">
                                <i class="bi bi-check-circle-fill"></i> Mark as Served
                            </button>
                        ` : `
                            <div class="served-status">
                                <i class="bi bi-check-circle-fill text-success"></i> Order Served
                                <div class="status-note">Order completed and served to customer</div>
                            </div>
                        `}
                    </div>
                </div>
            </div>
        `;
    }
    
    bindOrderActions() {
        document.querySelectorAll('.action-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const action = e.target.dataset.action;
                const orderId = e.target.dataset.orderId;
                this.updateOrderStatus(orderId, action);
            });
        });
    }
    
    async updateOrderStatus(orderId, action) {
        try {
            const response = await fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=${action}&order_id=${orderId}`
            });
            
            if (response.ok) {
                this.showAlert(`Order ${action.replace('_', ' ')} successfully!`, 'success');
                this.loadOrders();
            } else {
                this.showAlert('Error updating order status', 'error');
            }
        } catch (error) {
            console.error('Error updating order:', error);
            this.showAlert('Error updating order status', 'error');
        }
    }
    
    checkForNewOrders(currentCount) {
        if (currentCount > this.lastOrderCount && this.soundEnabled) {
            this.playNotificationSound();
            this.showAlert('New order received!', 'info');
        }
        this.lastOrderCount = currentCount;
    }
    
    playNotificationSound() {
        // Create a simple notification sound
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.5);
    }
    
    showAlert(message, type) {
        const alertContainer = document.getElementById('alertContainer');
        const alertId = 'alert-' + Date.now();
        
        const alertHTML = `
            <div class="alert alert-${type} alert-dismissible fade show" id="${alertId}" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        alertContainer.insertAdjacentHTML('beforeend', alertHTML);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            const alert = document.getElementById(alertId);
            if (alert) {
                alert.remove();
            }
        }, 5000);
    }
    
    updateLastUpdate() {
        const now = new Date();
        document.getElementById('lastUpdate').textContent = now.toLocaleTimeString();
    }
    
    
    formatTime(timestamp) {
        const date = new Date(timestamp);
        return date.toLocaleTimeString('en-US', { 
            hour: 'numeric', 
            minute: '2-digit',
            hour12: true 
        });
    }
    
    startAutoRefresh() {
        this.refreshInterval = setInterval(() => {
            this.loadOrders();
        }, 5000); // Refresh every 5 seconds
    }
}

// Initialize the kitchen system when page loads
document.addEventListener('DOMContentLoaded', () => {
    new KitchenSystem();
});
</script>

<?php
// Function to display order card
function displayOrderCard($order, $conn) {
    $order_id = $order['order_id'];
    $status_class = $order['status_id'] == 2 ? 'order-paid' : 'order-preparing';
    $status_badge = $order['status_id'] == 2 ? 
                    '<span class="badge bg-primary">New Order</span>' : 
                    '<span class="badge bg-info">Preparing</span>';
    
    // Calculate time elapsed (same logic as AJAX endpoint)
    // For preparing/ready orders, use updated_at (when status changed)
    // For new orders, use created_at (when order was created)
    $timestamp_to_use = ($order['status_id'] >= 3) ? $order['updated_at'] : $order['created_at'];
    $timestamp_time = strtotime($timestamp_to_use);
    $current_time = time();
    $minutes_elapsed = round(($current_time - $timestamp_time) / 60);
    
    // Ensure positive value (handle timezone issues)
    $minutes_elapsed = max(0, $minutes_elapsed);
    
    $timer_class = '';
    if ($minutes_elapsed >= 15) {
        $timer_class = 'timer-danger';
    } elseif ($minutes_elapsed >= 10) {
        $timer_class = 'timer-warning';
    }
    ?>
    <div class="card order-card <?= $status_class ?>">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                Order #<?= $order['queue_number'] ?>
                <?php if ($order['table_number']) { ?>
                    <span class="badge bg-secondary">Table <?= $order['table_number'] ?></span>
                <?php } ?>
            </h5>
            <?= $status_badge ?>
        </div>
        <div class="card-body">
            <div class="d-flex justify-content-between mb-3">
                <div class="order-time">
                    Ordered at: <?= date('h:i A', strtotime($order['created_at'])) ?>
                </div>
                <div class="order-timer <?= $timer_class ?>">
                    <?= $minutes_elapsed ?> min
                </div>
            </div>
            
            <div class="order-items mb-3">
                <?php
                // Get order items
                $sql_items = "SELECT oi.*, mi.name as item_name, iv.name as variation_name 
                              FROM order_items oi 
                              JOIN menu_items mi ON oi.item_id = mi.item_id 
                              LEFT JOIN item_variations iv ON oi.variation_id = iv.variation_id 
                              WHERE oi.order_id = ?";
                
                $stmt_items = $conn->prepare($sql_items);
                $stmt_items->bind_param("i", $order_id);
                $stmt_items->execute();
                $result_items = $stmt_items->get_result();
                $stmt_items->close();
                
                while ($item = $result_items->fetch_assoc()) {
                    ?>
                    <div class="item-row">
                        <div>
                            <span class="item-quantity"><?= $item['quantity'] ?>x</span>
                            <span class="item-name"><?= htmlspecialchars($item['item_name']) ?></span>
                            <?php if ($item['variation_name']) { ?>
                                <span class="item-variation"> - <?= htmlspecialchars($item['variation_name']) ?></span>
                            <?php } ?>
                        </div>
                        
                        <?php
                        // Get add-ons for this item
                        $sql_addons = "SELECT oia.*, ia.name as addon_name 
                                      FROM order_item_addons oia 
                                      JOIN item_addons ia ON oia.addon_id = ia.addon_id 
                                      WHERE oia.order_item_id = ?";
                        
                        $stmt_addons = $conn->prepare($sql_addons);
                        $stmt_addons->bind_param("i", $item['order_item_id']);
                        $stmt_addons->execute();
                        $result_addons = $stmt_addons->get_result();
                        $stmt_addons->close();
                        
                        if ($result_addons->num_rows > 0) {
                            echo '<div class="item-addons">';
                            $addons = [];
                            while ($addon = $result_addons->fetch_assoc()) {
                                $addons[] = htmlspecialchars($addon['addon_name']);
                            }
                            echo '+ ' . implode(', + ', $addons);
                            echo '</div>';
                        }
                        ?>
                    </div>
                    <?php
                }
                ?>
            </div>
            
            <?php if (!empty($order['notes'])) { ?>
                <div class="order-notes">
                    <i class="bi bi-info-circle"></i> <strong>Notes:</strong>
                    <?= nl2br(htmlspecialchars($order['notes'])) ?>
                </div>
            <?php } ?>
            
            <div class="mt-3">
                <?php if ($order['status_id'] == 2) { // Paid status ?>
                    <form method="post" class="d-inline">
                        <input type="hidden" name="order_id" value="<?= $order_id ?>">
                        <input type="hidden" name="action" value="start_preparing">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-play-fill"></i> Start Preparing
                        </button>
                    </form>
                <?php } else { // Preparing status ?>
                    <form method="post" class="d-inline">
                        <input type="hidden" name="order_id" value="<?= $order_id ?>">
                        <input type="hidden" name="action" value="mark_ready">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Mark as Ready
                        </button>
                    </form>
                <?php } ?>
            </div>
        </div>
    </div>
    <?php
}

require_once 'includes/footer.php';
$conn->close();
?>