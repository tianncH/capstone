<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen System</title>
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
            --gradient-primary: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            --gradient-success: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            --gradient-warning: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            background: #f4f6f9;
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .navbar {
            background: var(--dark-color) !important;
            box-shadow: var(--shadow);
            margin-bottom: 0;
        }

        .navbar-brand {
            font-weight: 600;
            font-size: 1.5rem;
            letter-spacing: -0.02em;
        }

        /* Kitchen Dashboard */
        .kitchen-dashboard {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .dashboard-header {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: var(--shadow-lg);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .dashboard-title {
            color: var(--dark-color);
            margin: 0;
            font-size: 2.5rem;
            font-weight: 700;
        }

        .dashboard-subtitle {
            color: #6c757d;
            margin: 5px 0 0 0;
            font-size: 1.1rem;
        }

        .live-stats {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .stat-card {
            background: var(--gradient-primary);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            min-width: 120px;
            box-shadow: var(--shadow);
        }

        .stat-card.preparing-orders {
            background: var(--gradient-warning);
        }

        .stat-card.total-orders {
            background: var(--gradient-success);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .connection-status {
            text-align: right;
        }

        .status-indicator {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .status-indicator.text-success {
            color: var(--success-color);
        }
        
        .status-indicator.text-danger {
            color: var(--danger-color);
        }
        
        .status-indicator.text-muted {
            color: #6c757d;
        }

        .last-update {
            color: #6c757d;
            font-size: 0.9rem;
        }

        /* Filter Section */
        .filter-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
        }

        .filter-btn {
            background: white;
            border: 2px solid #e9ecef;
            padding: 12px 20px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .filter-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .filter-btn.active {
            background: var(--gradient-primary);
            border-color: var(--primary-color);
            color: white;
        }

        .view-controls {
            display: flex;
            gap: 10px;
        }

        .control-btn {
            background: white;
            border: 2px solid #e9ecef;
            padding: 12px;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .control-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        /* Orders Grid */
        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .order-card {
            background: white;
            border-radius: 15px;
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: all 0.3s ease;
            border-left: 5px solid var(--primary-color);
        }

        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .order-card.order-awaiting {
            border-left-color: var(--warning-color);
            background: linear-gradient(135deg, #fff3cd 0%, #f8f9fa 100%);
        }
        
        .order-card.order-new {
            border-left-color: var(--info-color);
        }
        
        .order-card.order-preparing {
            border-left-color: var(--info-color);
        }
        
        .order-card.order-ready {
            border-left-color: var(--success-color);
            background: linear-gradient(135deg, #d4edda 0%, #f8f9fa 100%);
        }
        
        .order-card.order-served {
            border-left-color: var(--info-color);
            background: linear-gradient(135deg, #cce7ff 0%, #f8f9fa 100%);
            opacity: 0.8;
        }

        .order-header {
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .order-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--dark-color);
            margin: 0 0 10px 0;
        }

        .order-meta {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .table-info, .order-time {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .order-status {
            text-align: right;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .status-badge.order-new {
            background: var(--info-color);
            color: white;
        }

        .status-badge.order-preparing {
            background: var(--warning-color);
            color: var(--dark-color);
        }

        .timer {
            font-weight: bold;
            font-size: 1.1rem;
        }

        .timer-warning {
            color: var(--warning-color);
        }

        .timer-danger {
            color: var(--danger-color);
        }

        .order-body {
            padding: 20px;
        }

        .order-items {
            margin-bottom: 15px;
        }

        .item-row {
            padding: 10px 0;
            border-bottom: 1px solid #f1f3f4;
        }

        .item-row:last-child {
            border-bottom: none;
        }

        .item-main {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .item-quantity {
            background: var(--primary-color);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-weight: bold;
            font-size: 0.9rem;
        }

        .item-name {
            font-weight: 600;
            color: var(--dark-color);
        }

        .item-variation {
            color: #6c757d;
            font-style: italic;
        }

        .item-addons {
            color: #6c757d;
            font-size: 0.85rem;
            margin-left: 30px;
            margin-top: 5px;
        }

        .order-notes {
            background: #fff3cd;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid var(--warning-color);
        }

        .order-actions {
            margin-top: 20px;
        }

        .action-btn {
            width: 100%;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .start-btn {
            background: var(--gradient-primary);
            color: white;
        }

        .start-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .ready-btn {
            background: var(--gradient-success);
            color: white;
        }

        .ready-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .served-btn {
            background: var(--gradient-info);
            color: white;
        }
        
        .served-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .ready-status {
            text-align: center;
            padding: 15px;
            background: linear-gradient(135deg, #d4edda 0%, #f8f9fa 100%);
            border-radius: 8px;
            border: 2px solid var(--success-color);
        }
        
        .ready-status i {
            font-size: 1.5rem;
            margin-bottom: 8px;
        }
        
        .served-status {
            text-align: center;
            padding: 15px;
            background: linear-gradient(135deg, #cce7ff 0%, #f8f9fa 100%);
            border-radius: 8px;
            border: 2px solid var(--info-color);
        }
        
        .served-status i {
            font-size: 1.5rem;
            margin-bottom: 8px;
        }
        
        .status-note {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 5px;
            font-style: italic;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: var(--shadow);
        }

        .empty-icon {
            font-size: 4rem;
            color: #6c757d;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: var(--dark-color);
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #6c757d;
            font-size: 1.1rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .kitchen-dashboard {
                padding: 15px;
            }

            .header-content {
                flex-direction: column;
                text-align: center;
            }

            .live-stats {
                justify-content: center;
            }

            .orders-grid {
                grid-template-columns: 1fr;
            }

            .filter-section {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-buttons {
                justify-content: center;
            }
        }

        /* Animations */
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .order-card {
            animation: slideInUp 0.5s ease-out;
        }

        /* Alert Styles */
        .alert {
            border-radius: 10px;
            border: none;
            box-shadow: var(--shadow);
        }
    </style>
</head>
<body>
    <div class="container">
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark rounded">
            <div class="container-fluid">
                <a class="navbar-brand" href="index.php">Kitchen System</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">Orders</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="completed.php">Completed Orders</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>