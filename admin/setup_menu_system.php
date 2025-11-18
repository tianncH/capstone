<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';

echo "<h2>🚀 Menu System Setup</h2>";
echo "<div style='font-family: Arial; line-height: 1.6; max-width: 800px; margin: 0 auto;'>";

try {
    // Clean up problematic data first
    echo "<h3>🧹 Cleaning Up Database...</h3>";
    
    // Delete problematic menu items
    $delete_items = "DELETE FROM menu_items WHERE name LIKE '%asd%' OR name = '' OR name IS NULL OR name LIKE '%shel%'";
    if ($conn->query($delete_items)) {
        $deleted_items = $conn->affected_rows;
        echo "✅ Deleted {$deleted_items} problematic menu items<br>";
    }
    
    // Check if we have categories
    $category_check = $conn->query("SELECT COUNT(*) as count FROM categories");
    $category_count = $category_check->fetch_assoc()['count'];
    
    if ($category_count == 0) {
        echo "<h3>📂 Creating Sample Categories...</h3>";
        
        $categories = [
            ['name' => 'Seafood', 'description' => 'Fresh seafood dishes', 'is_active' => 1],
            ['name' => 'Appetizers', 'description' => 'Starters and appetizers', 'is_active' => 1],
            ['name' => 'Main Courses', 'description' => 'Main dishes and entrees', 'is_active' => 1],
            ['name' => 'Desserts', 'description' => 'Sweet treats and desserts', 'is_active' => 1],
            ['name' => 'Beverages', 'description' => 'Drinks and beverages', 'is_active' => 1]
        ];
        
        $category_sql = "INSERT INTO categories (name, description, display_order, is_active) VALUES (?, ?, ?, ?)";
        $category_stmt = $conn->prepare($category_sql);
        
        foreach ($categories as $index => $category) {
            $category_stmt->bind_param('ssii', $category['name'], $category['description'], $index, $category['is_active']);
            if ($category_stmt->execute()) {
                echo "✅ Created category: {$category['name']}<br>";
            }
        }
        $category_stmt->close();
    } else {
        echo "✅ Categories already exist ({$category_count} found)<br>";
    }
    
    // Check if we have menu items
    $item_check = $conn->query("SELECT COUNT(*) as count FROM menu_items");
    $item_count = $item_check->fetch_assoc()['count'];
    
    if ($item_count == 0) {
        echo "<h3>🍽️ Creating Sample Menu Items...</h3>";
        
        // Get the first category (Seafood)
        $seafood_category = $conn->query("SELECT category_id FROM categories WHERE name = 'Seafood' LIMIT 1")->fetch_assoc();
        
        if ($seafood_category) {
            $sample_items = [
                [
                    'name' => 'Grilled Salmon',
                    'description' => 'Fresh Atlantic salmon grilled to perfection',
                    'price' => 450.00,
                    'category_id' => $seafood_category['category_id']
                ],
                [
                    'name' => 'Shrimp Scampi',
                    'description' => 'Jumbo shrimp in garlic butter sauce',
                    'price' => 380.00,
                    'category_id' => $seafood_category['category_id']
                ],
                [
                    'name' => 'Fish and Chips',
                    'description' => 'Beer-battered fish with crispy fries',
                    'price' => 320.00,
                    'category_id' => $seafood_category['category_id']
                ]
            ];
            
            $item_sql = "INSERT INTO menu_items (category_id, name, description, price, is_available, display_order) VALUES (?, ?, ?, ?, 1, ?)";
            $item_stmt = $conn->prepare($item_sql);
            
            foreach ($sample_items as $index => $item) {
                $item_stmt->bind_param('issdi', $item['category_id'], $item['name'], $item['description'], $item['price'], $index);
                if ($item_stmt->execute()) {
                    echo "✅ Created menu item: {$item['name']}<br>";
                }
            }
            $item_stmt->close();
        }
    } else {
        echo "✅ Menu items already exist ({$item_count} found)<br>";
    }
    
    echo "<br><div class='alert alert-success'>";
    echo "<h4>🎉 Setup Complete!</h4>";
    echo "<p>Your menu system is now ready to use!</p>";
    echo "</div>";
    
    echo "<div class='d-grid gap-2 d-md-flex justify-content-md-center'>";
    echo "<a href='menu_management.php' class='btn btn-primary btn-lg'>Manage Menu</a>";
    echo "<a href='../ordering/index.php' class='btn btn-success btn-lg' target='_blank'>View Customer Interface</a>";
    echo "<a href='index.php' class='btn btn-secondary btn-lg'>Dashboard</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ Error:</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</div>";
?>









