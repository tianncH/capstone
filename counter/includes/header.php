<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Counter Management System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --light-bg: #f8f9fa;
            --border-radius: 0.5rem;
            --box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            --box-shadow-lg: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        body {
            background: var(--light-bg);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .main-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow-lg);
            margin: 1rem;
            min-height: calc(100vh - 2rem);
            overflow: hidden;
        }

        .navbar {
            background: var(--primary-color) !important;
            border: none;
            box-shadow: var(--box-shadow);
            margin-bottom: 0;
        }

        .navbar-brand {
            font-weight: 600;
            font-size: 1.5rem;
            color: white;
            letter-spacing: -0.02em;
        }

        .navbar-nav .nav-link {
            padding: 0.75rem 1.25rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.9) !important;
            transition: all 0.3s ease;
            border-radius: var(--border-radius);
            margin: 0 0.25rem;
        }

        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: white !important;
            transform: translateY(-2px);
        }

        .navbar-nav .nav-link i {
            margin-right: 0.5rem;
            width: 16px;
            text-align: center;
        }

        .content-area {
            padding: 2rem;
            background: var(--light-bg);
            min-height: calc(100vh - 76px);
        }

        /* Modern Card Styling */
        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: var(--box-shadow-lg);
        }

        .card-header {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border-bottom: 1px solid #e9ecef;
            font-weight: 600;
            padding: 1.25rem;
        }

        .order-card {
            margin-bottom: 1.5rem;
            border-left: 4px solid #dee2e6;
            transition: all 0.3s ease;
        }

        .order-card:hover {
            transform: translateX(5px);
            box-shadow: var(--box-shadow-lg);
        }

        .order-pending {
            border-left-color: var(--warning-color);
            background: linear-gradient(135deg, #fff9e6 0%, #fff 100%);
        }

        .order-paid {
            border-left-color: var(--secondary-color);
            background: linear-gradient(135deg, #e6f3ff 0%, #fff 100%);
        }

        .order-ready {
            border-left-color: var(--success-color);
            background: linear-gradient(135deg, #e6f7e6 0%, #fff 100%);
        }

        .order-completed {
            border-left-color: #6c757d;
            background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
        }

        .order-cancelled {
            border-left-color: var(--danger-color);
            background: linear-gradient(135deg, #ffe6e6 0%, #fff 100%);
        }

        /* Status Badges */
        .badge {
            font-size: 0.75rem;
            padding: 0.5rem 0.75rem;
            border-radius: 2rem;
            font-weight: 500;
        }

        /* Buttons */
        .btn {
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: all 0.2s ease;
            border: none;
            position: relative;
            overflow: hidden;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--box-shadow);
        }

        .btn:active {
            transform: translateY(0);
            transition: transform 0.1s ease;
        }

        .btn:focus {
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            outline: none;
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success-color) 0%, #2ecc71 100%);
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger-color) 0%, #c0392b 100%);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #2980b9 100%);
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--warning-color) 0%, #e67e22 100%);
        }

        /* Fix for modal buttons and specific button types */
        .btn-lg {
            padding: 0.75rem 1.5rem;
            font-size: 1.1rem;
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }

        /* Prevent glitchy behavior on modal buttons */
        .modal .btn {
            transition: none !important;
            animation: none !important;
        }

        .modal .btn:hover {
            transform: none !important;
            box-shadow: none !important;
            background: inherit !important;
        }

        .modal .btn:focus {
            transform: none !important;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
        }

        .modal .btn:active {
            transform: none !important;
            transition: none !important;
        }

        /* Fix for Process Cash Payment buttons specifically */
        .btn-success.btn-lg {
            transition: none !important;
            animation: none !important;
        }

        .btn-success.btn-lg:hover {
            transform: none !important;
            box-shadow: none !important;
            filter: brightness(1.1) !important;
        }

        .btn-success.btn-lg:focus {
            transform: none !important;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25) !important;
        }

        /* Smooth button interactions */
        .btn:not(:disabled):not(.disabled) {
            cursor: pointer;
        }

        .btn:disabled,
        .btn.disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        /* NUCLEAR OPTION - COMPLETE BUTTON ANIMATION ELIMINATION */
        * {
            animation-duration: 0s !important;
            animation-delay: 0s !important;
            transition-duration: 0s !important;
            transition-delay: 0s !important;
        }

        .btn,
        .btn *,
        .btn:hover,
        .btn:focus,
        .btn:active,
        .btn:visited {
            animation: none !important;
            transition: none !important;
            transform: none !important;
            box-shadow: none !important;
        }

        /* Simple hover effect only */
        .btn:hover {
            opacity: 0.9 !important;
        }

        .btn:active {
            opacity: 0.8 !important;
        }

        .btn:focus {
            outline: 2px solid #007bff !important;
            outline-offset: 2px !important;
        }

        /* Daily Sales Summary */
        .daily-sales {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--box-shadow);
            border: 1px solid #e9ecef;
        }

        .daily-sales h4 {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .daily-sales h5 {
            color: var(--secondary-color);
            font-weight: 600;
        }

        /* Cash Float Widget */
        .cash-float-widget {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border-radius: var(--border-radius);
            border: 2px solid var(--success-color);
            box-shadow: var(--box-shadow);
            transition: all 0.3s ease;
        }

        .cash-float-widget:hover {
            transform: translateY(-2px);
            box-shadow: var(--box-shadow-lg);
        }

        .cash-float-header {
            background: linear-gradient(135deg, var(--success-color) 0%, #2ecc71 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }

        /* Notifications */
        .notification-card {
            border-left: 4px solid var(--warning-color);
            background: linear-gradient(135deg, #fff9e6 0%, #fff 100%);
        }

        .notification-item {
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s ease;
        }

        .notification-item:hover {
            background: rgba(255, 193, 7, 0.1);
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        /* Tabs */
        .nav-tabs {
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 1.5rem;
        }

        .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 500;
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            transition: all 0.3s ease;
        }

        .nav-tabs .nav-link:hover {
            background: var(--light-bg);
            color: var(--primary-color);
        }

        .nav-tabs .nav-link.active {
            background: white;
            color: var(--primary-color);
            border-bottom: 2px solid var(--secondary-color);
            font-weight: 600;
        }

        /* Tables */
        .table {
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
        }

        .table thead th {
            background: linear-gradient(135deg, var(--primary-color) 0%, #34495e 100%);
            color: white;
            border: none;
            font-weight: 600;
            padding: 1rem;
        }

        .table tbody td {
            padding: 1rem;
            border-color: #f0f0f0;
        }

        /* Alerts */
        .alert {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border-left: 4px solid;
        }

        .alert-success {
            border-left: 4px solid var(--success-color);
            background: #d4edda;
        }

        .alert-danger {
            border-left: 4px solid var(--danger-color);
            background: #f8d7da;
        }

        .alert-warning {
            border-left: 4px solid var(--warning-color);
            background: #fff3cd;
        }

        .alert-info {
            border-left: 4px solid var(--secondary-color);
            background: #d1ecf1;
        }

        /* Floating Action Button - No Blinking Version */
        .refresh-btn {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--secondary-color);
            border: none;
            color: white;
            font-size: 1.5rem;
            box-shadow: var(--box-shadow-lg);
            transition: all 0.2s ease;
            z-index: 1000;
            cursor: pointer;
        }

        .refresh-btn:hover {
            transform: none !important;
            filter: brightness(1.1) !important;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.3) !important;
        }

        .refresh-btn:active {
            transform: none !important;
            filter: brightness(0.9) !important;
            transition: none !important;
        }

        .refresh-btn:focus {
            outline: 2px solid rgba(52, 152, 219, 0.5) !important;
            outline-offset: 2px !important;
        }

        /* Loading States */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-container {
                margin: 0.5rem;
                border-radius: 0;
            }

            .content-area {
                padding: 1rem;
            }

            .navbar-nav .nav-link {
                padding: 0.5rem 1rem;
                margin: 0;
            }

        .daily-sales {
                padding: 1.5rem;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Status indicators */
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 0.5rem;
        }

        .status-active { background-color: var(--success-color); }
        .status-warning { background-color: var(--warning-color); }
        .status-danger { background-color: var(--danger-color); }
        .status-info { background-color: var(--secondary-color); }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #2980b9 100%);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #2980b9 0%, var(--secondary-color) 100%);
        }
    </style>
</head>
<body>
    <div class="main-container">
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="index.php">
                    <i class="bi bi-cash-register"></i> Counter Management
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link active" href="index.php">
                                <i class="bi bi-clipboard-check"></i> Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="cash_float.php">
                                <i class="bi bi-cash-coin"></i> Cash Float
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="menu_availability.php">
                                <i class="bi bi-menu-button-wide"></i> Menu
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="daily_sales.php">
                                <i class="bi bi-graph-up"></i> Daily Sales
                            </a>
                        </li>
                    </ul>
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link" href="counter_logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <div class="content-area">
