<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Counter System' ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            padding: 20px;
        }
        
        .navbar {
            background: #2c3e50;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            text-decoration: none;
        }
        
        .navbar-nav {
            display: flex;
            gap: 20px;
            margin: 0;
            padding: 0;
            list-style: none;
        }
        
        .nav-link {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            transition: background-color 0.2s;
        }
        
        .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
            color: white;
        }
        
        .nav-link.active {
            background-color: #3498db;
            color: white;
        }
        
        .navbar-right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .btn-logout {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            transition: background-color 0.2s;
        }
        
        .btn-logout:hover {
            background-color: #c0392b;
            color: white;
        }
        
        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        /* General text improvements for better readability */
        h1, h2, h3, h4, h5, h6 {
            font-weight: 700;
            color: #2c3e50;
        }
        
        .text-muted {
            color: #6c757d !important;
        }
        
        .small {
            font-weight: 500;
        }
        
        .stats-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .order-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 15px;
            border-left: 4px solid #dee2e6;
        }
        
        .order-pending { border-left-color: #ffc107; }
        .order-paid { border-left-color: #007bff; }
        .order-ready { border-left-color: #28a745; }
        .order-completed { border-left-color: #6c757d; }
        .order-cancelled { border-left-color: #dc3545; }
        
        .btn {
            border-radius: 6px;
            padding: 8px 16px;
            margin: 2px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            opacity: 0.8;
        }
        
        .btn-success { background-color: #28a745; color: white; }
        .btn-danger { background-color: #dc3545; color: white; }
        .btn-warning { background-color: #ffc107; color: black; }
        .btn-primary { background-color: #007bff; color: white; }
        .btn-secondary { background-color: #6c757d; color: white; }
        
        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge-warning { background-color: #ffc107; color: black; }
        .badge-success { background-color: #28a745; color: white; }
        .badge-primary { background-color: #007bff; color: white; }
        .badge-secondary { background-color: #6c757d; color: white; }
        .badge-danger { background-color: #dc3545; color: white; }
        
        .form-control {
            border-radius: 6px;
            border: 1px solid #ddd;
            padding: 10px;
        }
        
        .table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        /* Payment Modal Styles */
        .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .modal-header.bg-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
            border-radius: 12px 12px 0 0;
        }
        
        .payment-detail-card {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .payment-detail-card:hover {
            border-color: var(--primary-color);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .change-display {
            font-size: 1.5rem;
            font-weight: bold;
            padding: 10px;
            border-radius: 6px;
            text-align: center;
        }
        
        .change-positive {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border: 2px solid #28a745;
        }
        
        .change-negative {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border: 2px solid #dc3545;
        }
        
        .input-group-text {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            border-color: #007bff;
            font-weight: bold;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            font-weight: bold;
            padding: 12px 24px;
            border-radius: 8px;
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #218838 0%, #1ea080 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        /* Table Group Cards */
        .table-group-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border: 1px solid #e9ecef;
            overflow: hidden;
        }
        
        .table-header {
            background: #2c3e50;
            color: white;
            padding: 20px;
        }
        
        .table-header h4 {
            color: white;
            margin: 0;
            font-weight: bold;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }
        
        .table-stats .badge {
            font-size: 0.8rem;
            padding: 0.4em 0.8em;
            font-weight: 600;
            text-shadow: none;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        /* Specific badge colors for better contrast */
        .table-stats .bg-primary {
            background-color: #ffffff !important;
            color: #667eea !important;
            border-color: rgba(255,255,255,0.3) !important;
        }
        
        .table-stats .bg-success {
            background-color: #ffffff !important;
            color: #28a745 !important;
            border-color: rgba(255,255,255,0.3) !important;
        }
        
        .table-stats .bg-warning {
            background-color: #ffffff !important;
            color: #ffc107 !important;
            border-color: rgba(255,255,255,0.3) !important;
        }
        
        .table-stats .bg-info {
            background-color: #ffffff !important;
            color: #17a2b8 !important;
            border-color: rgba(255,255,255,0.3) !important;
        }
        
        /* View Orders button - make it more visible */
        .btn-outline-primary {
            background-color: rgba(255,255,255,0.9) !important;
            color: #667eea !important;
            border: 2px solid rgba(255,255,255,0.8) !important;
            font-weight: 600 !important;
            text-shadow: none !important;
        }
        
        .btn-outline-primary:hover {
            background-color: #ffffff !important;
            color: #764ba2 !important;
            border-color: #ffffff !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .table-orders-list {
            padding: 0;
            background: #f8f9fa;
        }
        
        .order-item {
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            background: white;
            margin: 0;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-item:hover {
            background: #f8f9fa;
        }
        
        .collapse .table-orders-list {
            border-top: 1px solid rgba(255,255,255,0.2);
        }
        
        /* Order item badges - better contrast */
        .order-item .badge {
            font-weight: 600;
            font-size: 0.75rem;
            padding: 0.4em 0.6em;
            border-radius: 0.3rem;
        }
        
        .order-item .bg-warning {
            background-color: #fff3cd !important;
            color: #856404 !important;
            border: 1px solid #ffeaa7;
        }
        
        .order-item .bg-info {
            background-color: #d1ecf1 !important;
            color: #0c5460 !important;
            border: 1px solid #bee5eb;
        }
        
        .order-item .bg-primary {
            background-color: #cce5ff !important;
            color: #004085 !important;
            border: 1px solid #99d6ff;
        }
        
        .order-item .bg-success {
            background-color: #d4edda !important;
            color: #155724 !important;
            border: 1px solid #c3e6cb;
        }
        
        .order-item .bg-secondary {
            background-color: #e2e3e5 !important;
            color: #383d41 !important;
            border: 1px solid #d6d8db;
        }
        
        /* Action buttons in order items */
        .order-item .btn-outline-danger {
            border-color: #dc3545;
            color: #dc3545;
            font-weight: 500;
        }
        
        .order-item .btn-outline-danger:hover {
            background-color: #dc3545;
            border-color: #dc3545;
            color: white;
        }
        
        .order-item .btn-outline-success {
            border-color: #28a745;
            color: #28a745;
            font-weight: 500;
        }
        
        .order-item .btn-outline-success:hover {
            background-color: #28a745;
            border-color: #28a745;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="d-flex justify-content-between align-items-center w-100">
            <div class="d-flex align-items-center">
                <a href="index.php" class="navbar-brand">
                    <i class="bi bi-cash-register"></i> Counter Management
                </a>
                <ul class="navbar-nav ms-4">
                    <li><a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                        <i class="bi bi-clipboard-check"></i> Orders
                    </a></li>
                    <li><a href="cash_float.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'cash_float.php' ? 'active' : '' ?>">
                        <i class="bi bi-cash-coin"></i> Cash Float
                    </a></li>
                    <li><a href="daily_sales.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'daily_sales.php' ? 'active' : '' ?>">
                        <i class="bi bi-graph-up"></i> Daily Sales
                    </a></li>
                </ul>
            </div>
            <div class="navbar-right">
                <a href="logout.php" class="btn-logout">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>
