<?php
// Include auth check at the beginning of header
require_once 'includes/auth_check.php';
require_once 'includes/currency_functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #e74c3c;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --light-bg: #f8f9fa;
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
            --border-color: #ecf0f1;
            --shadow-light: 0 2px 10px rgba(0,0,0,0.1);
            --shadow-medium: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        body {
            padding-top: 20px;
            padding-bottom: 20px;
            background: var(--light-bg);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--text-dark);
        }
        .navbar {
            margin-bottom: 20px;
        }
        .order-card {
            margin-bottom: 20px;
            border-left: 5px solid var(--border-color);
            background: white;
            border-radius: 8px;
            box-shadow: var(--shadow-light);
        }
        .order-pending {
            border-left-color: var(--warning-color);
        }
        .order-paid {
            border-left-color: var(--primary-color);
        }
        .order-ready {
            border-left-color: var(--success-color);
        }
        .order-completed {
            border-left-color: var(--text-light);
        }
        .refresh-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
        .daily-sales {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .user-dropdown {
            margin-left: auto;
        }
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        .notification-dropdown {
            min-width: 350px;
        }
        .notification-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .notification-item:last-child {
            border-bottom: none;
        }
        .notification-time {
            font-size: 0.8rem;
            color: #666;
        }
        
        /* Enhanced Navbar Styling */
        .navbar-nav .nav-link {
            padding: 0.75rem 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .navbar-nav .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 0.375rem;
        }
        
        .navbar-nav .dropdown-toggle::after {
            margin-left: 0.5em;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border-radius: 0.5rem;
            margin-top: 0.5rem;
            padding: 0.5rem 0;
        }
        
        .dropdown-item {
            padding: 0.5rem 1.5rem;
            font-weight: 400;
            transition: all 0.2s ease;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fa;
            color: #0d6efd;
        }
        
        .dropdown-item i {
            margin-right: 0.5rem;
            width: 16px;
            text-align: center;
        }
        
        .dropdown-header {
            font-weight: 600;
            color: #6c757d;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 0.5rem 1.5rem 0.25rem;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.25rem;
        }
        
        /* Mobile Responsive */
        @media (max-width: 991.98px) {
            .navbar-nav .nav-link {
                padding: 0.5rem 1rem;
            }
            
            .dropdown-menu {
                border-radius: 0;
                box-shadow: none;
                background-color: rgba(0, 0, 0, 0.05);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark rounded">
            <div class="container-fluid">
                <a class="navbar-brand" href="index.php">Admin Dashboard</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav">
                        <!-- Core Operations -->
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">
                                <i class="bi bi-clipboard-check"></i> Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="menu_management.php">
                                <i class="bi bi-menu-button-wide"></i> Menu
                            </a>
                        </li>
                        
                        <!-- Management Dropdown -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="managementDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-gear"></i> Management
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="managementDropdown">
                                <li><h6 class="dropdown-header">Tables & Orders</h6></li>
                                <li><a class="dropdown-item" href="table_management.php"><i class="bi bi-grid-3x3"></i> Table Management</a></li>
                                <li><a class="dropdown-item" href="table_sessions.php"><i class="bi bi-table"></i> Table Sessions</a></li>
                                <li><a class="dropdown-item" href="qr_session_management.php"><i class="bi bi-shield-check"></i> QR Session Management</a></li>
                                <li><a class="dropdown-item" href="order_management_new.php"><i class="bi bi-list-check"></i> Order Management</a></li>
                                <li><a class="dropdown-item" href="qr_management.php"><i class="bi bi-qr-code"></i> QR Code Management</a></li>
                                <li><a class="dropdown-item" href="venue_feedback_qr.php"><i class="bi bi-building"></i> Venue Feedback QR</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><h6 class="dropdown-header">Reservations</h6></li>
                                <li><a class="dropdown-item" href="reservation_management.php"><i class="bi bi-calendar-check"></i> Manage Reservations</a></li>
                                <li><a class="dropdown-item" href="reservation_calendar.php"><i class="bi bi-calendar-week"></i> Time Slots</a></li>
                                <li><a class="dropdown-item" href="reservation_calendar_view.php"><i class="bi bi-calendar3"></i> Calendar View</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><h6 class="dropdown-header">Venue</h6></li>
                                <li><a class="dropdown-item" href="venue_management.php"><i class="bi bi-building"></i> Venue Management</a></li>
                                <li><a class="dropdown-item" href="venue_restrictions.php"><i class="bi bi-shield-check"></i> Venue Restrictions</a></li>
                            </ul>
                        </li>
                        
                        <!-- Cash Float -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="cashFloatDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-cash-coin"></i> Cash Float
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="cashFloatDropdown">
                                <li><a class="dropdown-item" href="cash_float_assignment.php"><i class="bi bi-cash-coin"></i> Assign Cash Float</a></li>
                                <li><a class="dropdown-item" href="cash_float_admin.php"><i class="bi bi-graph-up"></i> Admin Reports</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="fix_cash_float_schema.php"><i class="bi bi-tools"></i> Fix Schema</a></li>
                                <li><a class="dropdown-item" href="setup_cash_float_system.php"><i class="bi bi-gear"></i> Setup System</a></li>
                            </ul>
                        </li>
                        
                        <!-- Reports & Analytics -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="reportsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-graph-up"></i> Reports
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="reportsDropdown">
                                <li><a class="dropdown-item" href="daily_sales.php"><i class="bi bi-currency-dollar"></i> Daily Sales</a></li>
                                <li><a class="dropdown-item" href="generate_reports.php"><i class="bi bi-file-earmark-text"></i> Generate Reports</a></li>
                                <li><a class="dropdown-item" href="reservation_reports.php"><i class="bi bi-calendar-event"></i> Reservation Reports</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="fix_daily_sales.php"><i class="bi bi-tools"></i> Fix Daily Sales</a></li>
                                <li><a class="dropdown-item" href="test_table_sessions.php"><i class="bi bi-bug"></i> Test Table Sessions</a></li>
                                <li><a class="dropdown-item" href="debug_qr_api.php"><i class="bi bi-tools"></i> Debug QR API</a></li>
                            </ul>
                        </li>
                        
                        <!-- Customer Experience -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="customerDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-people"></i> Customer
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="customerDropdown">
                                <li><a class="dropdown-item" href="feedback_management.php"><i class="bi bi-chat-dots"></i> Feedback Management</a></li>
                                <li><a class="dropdown-item" href="feedback_analytics.php"><i class="bi bi-graph-up"></i> Feedback Analytics</a></li>
                                <li><a class="dropdown-item" href="venue_management.php"><i class="bi bi-building"></i> Venue Management</a></li>
                                <li><a class="dropdown-item" href="feedback_export.php"><i class="bi bi-download"></i> Export Feedback</a></li>
                            </ul>
                        </li>
                    </ul>
                    <ul class="navbar-nav user-dropdown">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle position-relative" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-bell"></i>
                                <span class="notification-badge" id="feedbackBadge" style="display: none;">0</span>
                                <span class="notification-badge" id="bookingBadge" style="display: none; background: #e74c3c; right: -8px;">0</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown" style="min-width: 400px;">
                                <li><h6 class="dropdown-header">Recent Activity</h6></li>
                                
                                <!-- Feedback Section -->
                                <li><h6 class="dropdown-header text-primary">
                                    <i class="bi bi-chat-heart"></i> Feedback
                                    <span class="badge bg-warning ms-2" id="feedbackCount">0</span>
                                </h6></li>
                                <li><div id="feedbackList" style="max-height: 200px; overflow-y: auto;">
                                    <div class="notification-item text-center text-muted">
                                        <i class="bi bi-hourglass-split"></i> Checking for new feedback...
                                    </div>
                                </div></li>
                                
                                <li><hr class="dropdown-divider"></li>
                                
                                <!-- Booking Section -->
                                <li><h6 class="dropdown-header text-success">
                                    <i class="bi bi-calendar-check"></i> Bookings
                                    <span class="badge bg-warning ms-2" id="bookingCount">0</span>
                                </h6></li>
                                <li><div id="bookingList" style="max-height: 200px; overflow-y: auto;">
                                    <div class="notification-item text-center text-muted">
                                        <i class="bi bi-hourglass-split"></i> Checking for new bookings...
                                    </div>
                                </div></li>
                                
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-center" href="feedback_management.php">
                                    <i class="bi bi-chat-dots"></i> View All Feedback
                                </a></li>
                                <li><a class="dropdown-item text-center" href="reservation_management.php">
                                    <i class="bi bi-calendar-check"></i> View All Bookings
                                </a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION["admin_name"] ?? $_SESSION["admin_username"] ?? 'Admin'); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="change_password.php"><i class="bi bi-key"></i> Change Password</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

    <script>
    // Feedback notification system
    let notificationCheckInterval;
    
    function checkForNewNotifications() {
        // Check feedback notifications
        fetch('feedback_notifications.php')
            .then(response => response.json())
            .then(data => {
                updateFeedbackBadge(data.pending_count);
                updateFeedbackList(data.recent_feedback);
            })
            .catch(error => {
                console.error('Error checking feedback notifications:', error);
            });
        
        // Check booking notifications
        fetch('booking_notifications.php')
            .then(response => response.json())
            .then(data => {
                updateBookingBadge(data.pending_count);
                updateBookingList(data.recent_bookings);
            })
            .catch(error => {
                console.error('Error checking booking notifications:', error);
            });
    }
    
    function updateFeedbackBadge(count) {
        const badge = document.getElementById('feedbackBadge');
        const countBadge = document.getElementById('feedbackCount');
        if (badge) {
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }
        if (countBadge) {
            countBadge.textContent = count;
        }
    }
    
    function updateBookingBadge(count) {
        const badge = document.getElementById('bookingBadge');
        const countBadge = document.getElementById('bookingCount');
        if (badge) {
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }
        if (countBadge) {
            countBadge.textContent = count;
        }
    }
    
    function updateFeedbackList(feedback) {
        const feedbackList = document.getElementById('feedbackList');
        if (!feedbackList) return;
        
        if (feedback.length === 0) {
            feedbackList.innerHTML = `
                <div class="notification-item text-center text-muted">
                    <i class="bi bi-check-circle"></i> No new feedback
                </div>
            `;
            return;
        }
        
        let html = '';
        feedback.forEach(item => {
            const statusColor = item.status === 'pending' ? 'warning' : 
                               item.status === 'reviewed' ? 'info' : 
                               item.status === 'responded' ? 'success' : 'secondary';
            
            html += `
                <div class="notification-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong>${item.customer_name}</strong>
                            ${item.table_number ? `<small class="text-muted"> - Table #${item.table_number}</small>` : ''}
                            <div class="mt-1">
                                ${generateStars(item.overall_rating)}
                                <span class="ms-2">${item.overall_rating}/5</span>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-${statusColor}">${item.status}</span>
                            <div class="notification-time">${item.created_at}</div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        feedbackList.innerHTML = html;
    }
    
    function updateBookingList(bookings) {
        const bookingList = document.getElementById('bookingList');
        if (!bookingList) return;
        
        if (bookings.length === 0) {
            bookingList.innerHTML = `
                <div class="notification-item text-center text-muted">
                    <i class="bi bi-check-circle"></i> No new bookings
                </div>
            `;
            return;
        }
        
        let html = '';
        bookings.forEach(item => {
            const statusColor = item.status === 'pending' ? 'warning' : 
                               item.status === 'confirmed' ? 'success' : 
                               item.status === 'cancelled' ? 'danger' : 'secondary';
            
            html += `
                <div class="notification-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong>${item.customer_name}</strong>
                            <small class="text-muted"> - ${item.venue_name}</small>
                            <div class="mt-1">
                                <i class="bi bi-calendar3"></i> ${item.reservation_date} at ${item.start_time}
                                <br><i class="bi bi-people"></i> ${item.party_size} people
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-${statusColor}">${item.status}</span>
                            <div class="notification-time">${item.created_at}</div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        bookingList.innerHTML = html;
    }
    
    function generateStars(rating) {
        let stars = '';
        for (let i = 1; i <= 5; i++) {
            stars += `<i class="bi bi-star${i <= rating ? '-fill' : ''} text-warning" style="font-size: 0.8rem;"></i>`;
        }
        return stars;
    }
    
    // Start checking for notifications when page loads
    document.addEventListener('DOMContentLoaded', function() {
        checkForNewNotifications();
        // Check every 30 seconds
        notificationCheckInterval = setInterval(checkForNewNotifications, 30000);
    });
    
    // Clean up interval when page unloads
    window.addEventListener('beforeunload', function() {
        if (notificationCheckInterval) {
            clearInterval(notificationCheckInterval);
        }
    });
    </script>
