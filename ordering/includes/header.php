<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Ordering System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <style>
        body {
            padding-top: 20px;
            padding-bottom: 20px;
        }
        .menu-item {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .addon-checkbox {
            margin-right: 5px;
        }
        .cart-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        #orderSummary {
            position: sticky;
            top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="mb-4">
            <h1 class="text-center">Restaurant Menu</h1>
            <p class="text-center">Scan QR code to order</p>
        </header>