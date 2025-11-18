<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';

$success_message = '';
$error_message = '';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Add new menu item
        if (isset($_POST['add_menu_item'])) {
            $name = trim($_POST['name']);
            $category_id = intval($_POST['category_id']);
            $price = floatval(str_replace(',', '', $_POST['price']));
            $description = trim($_POST['description']);
            $is_available = isset($_POST['is_available']) ? 1 : 0;
            
            // Validate required fields
            if (empty($name) || empty($category_id) || $price <= 0) {
                throw new Exception('Please fill in all required fields with valid values.');
            }
            
            // Get the highest display order for this category and add 1
            $sql_order = "SELECT MAX(display_order) as max_order FROM menu_items WHERE category_id = ?";
            $stmt_order = $conn->prepare($sql_order);
            $stmt_order->bind_param("i", $category_id);
            $stmt_order->execute();
            $result_order = $stmt_order->get_result();
            $row_order = $result_order->fetch_assoc();
            $display_order = ($row_order['max_order'] !== null) ? $row_order['max_order'] + 1 : 0;
            $stmt_order->close();
            
            // Handle image upload
            $image_url = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $upload_dir = '../uploads/menu_items/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Get file info
                $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                // Validate file type
                if (!in_array($file_extension, $allowed_extensions)) {
                    throw new Exception('Invalid file type. Only JPG, JPEG, PNG, GIF, and WebP files are allowed.');
                }
                
                // Validate file size (max 5MB)
                if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                    throw new Exception('File size too large. Maximum size is 5MB.');
                }
                
                // Validate that it's actually an image
                $check = getimagesize($_FILES['image']['tmp_name']);
                if ($check === false) {
                    throw new Exception('File is not a valid image.');
                }
                
                // Generate clean filename
                $clean_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
                $clean_name = strtolower($clean_name);
                $file_name = $clean_name . '_' . uniqid() . '.' . $file_extension;
                $target_file = $upload_dir . $file_name;
                
                // Move uploaded file
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $image_url = '/capstone/uploads/menu_items/' . $file_name;
                } else {
                    throw new Exception('Failed to upload image file.');
                }
            }
            
            // Insert the new menu item
            $sql = "INSERT INTO menu_items (category_id, name, description, price, image_url, is_available, display_order, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issdsii", $category_id, $name, $description, $price, $image_url, $is_available, $display_order);
            
            if ($stmt->execute()) {
                $success_message = "Menu item '{$name}' added successfully!";
            } else {
                throw new Exception('Failed to add menu item to database: ' . $stmt->error);
            }
            $stmt->close();
        }
        
        // Update menu item
        if (isset($_POST['update_menu_item'])) {
            $item_id = intval($_POST['item_id']);
            $name = trim($_POST['name']);
            $category_id = intval($_POST['category_id']);
            $price = floatval(str_replace(',', '', $_POST['price']));
            $description = trim($_POST['description']);
            $is_available = isset($_POST['is_available']) ? 1 : 0;
            
            // Validate required fields
            if (empty($name) || empty($category_id) || $price <= 0) {
                throw new Exception('Please fill in all required fields with valid values.');
            }
            
            // Handle image upload if new image is provided
            $image_url = $_POST['current_image_url']; // Keep current image by default
            
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $upload_dir = '../uploads/menu_items/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Get file info
                $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                // Validate file type
                if (!in_array($file_extension, $allowed_extensions)) {
                    throw new Exception('Invalid file type. Only JPG, JPEG, PNG, GIF, and WebP files are allowed.');
                }
                
                // Validate file size (max 5MB)
                if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                    throw new Exception('File size too large. Maximum size is 5MB.');
                }
                
                // Validate that it's actually an image
                $check = getimagesize($_FILES['image']['tmp_name']);
                if ($check === false) {
                    throw new Exception('File is not a valid image.');
                }
                
                // Generate clean filename
                $clean_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
                $clean_name = strtolower($clean_name);
                $file_name = $clean_name . '_' . uniqid() . '.' . $file_extension;
                $target_file = $upload_dir . $file_name;
                
                // Move uploaded file
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    // Delete old image if it exists
                    if (!empty($_POST['current_image_url'])) {
                        $old_file_path = $_SERVER['DOCUMENT_ROOT'] . $_POST['current_image_url'];
                        if (file_exists($old_file_path)) {
                            unlink($old_file_path);
                        }
                    }
                    $image_url = '/capstone/uploads/menu_items/' . $file_name;
                } else {
                    throw new Exception('Failed to upload new image file.');
                }
            }
            
            // Update the menu item
            $sql = "UPDATE menu_items SET category_id = ?, name = ?, description = ?, price = ?, image_url = ?, is_available = ?, updated_at = NOW() WHERE item_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issdsii", $category_id, $name, $description, $price, $image_url, $is_available, $item_id);
            
            if ($stmt->execute()) {
                $success_message = "Menu item '{$name}' updated successfully!";
            } else {
                throw new Exception('Failed to update menu item: ' . $stmt->error);
            }
            $stmt->close();
        }
        
        // Delete menu item
        if (isset($_POST['delete_menu_item'])) {
            $item_id = intval($_POST['item_id']);
            
            // Get image URL before deleting
            $sql_image = "SELECT image_url FROM menu_items WHERE item_id = ?";
            $stmt_image = $conn->prepare($sql_image);
            $stmt_image->bind_param("i", $item_id);
            $stmt_image->execute();
            $result_image = $stmt_image->get_result();
            $row_image = $result_image->fetch_assoc();
            $stmt_image->close();
            
            // Delete the menu item
            $sql = "DELETE FROM menu_items WHERE item_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $item_id);
            
            if ($stmt->execute()) {
                // Delete associated image file
                if (!empty($row_image['image_url'])) {
                    $image_path = $_SERVER['DOCUMENT_ROOT'] . $row_image['image_url'];
                    if (file_exists($image_path)) {
                        unlink($image_path);
                    }
                }
                $success_message = "Menu item deleted successfully!";
            } else {
                throw new Exception('Failed to delete menu item: ' . $stmt->error);
            }
            $stmt->close();
        }
        
        // Add new category
        if (isset($_POST['add_category'])) {
            $name = trim($_POST['category_name']);
            $description = trim($_POST['category_description']);
            $is_active = isset($_POST['category_is_active']) ? 1 : 0;
            
            if (empty($name)) {
                throw new Exception('Category name is required.');
            }
            
            // Get the highest display order and add 1
            $sql_order = "SELECT MAX(display_order) as max_order FROM categories";
            $result_order = $conn->query($sql_order);
            $row_order = $result_order->fetch_assoc();
            $display_order = ($row_order['max_order'] !== null) ? $row_order['max_order'] + 1 : 0;
            
            // Insert the new category
            $sql = "INSERT INTO categories (name, description, display_order, is_active) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssii", $name, $description, $display_order, $is_active);
            
            if ($stmt->execute()) {
                $success_message = "Category '{$name}' added successfully!";
            } else {
                throw new Exception('Failed to add category: ' . $stmt->error);
            }
            $stmt->close();
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get all categories
$categories_sql = "SELECT * FROM categories ORDER BY display_order, name";
$categories_result = $conn->query($categories_sql);

// Get all menu items with category names
$menu_items_sql = "SELECT mi.*, c.name as category_name 
                   FROM menu_items mi 
                   LEFT JOIN categories c ON mi.category_id = c.category_id 
                   ORDER BY c.display_order, mi.display_order, mi.name";
$menu_items_result = $conn->query($menu_items_sql);

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">🍽️ Menu Management</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="bi bi-plus-circle"></i> Add Category
            </button>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMenuItemModal">
                <i class="bi bi-plus-circle"></i> Add Menu Item
            </button>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Categories Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">📂 Categories</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <?php if ($categories_result && $categories_result->num_rows > 0): ?>
                    <?php $categories_result->data_seek(0); ?>
                    <?php while ($category = $categories_result->fetch_assoc()): ?>
                        <div class="col-md-4 col-lg-3 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <h6 class="card-title"><?= htmlspecialchars($category['name']) ?></h6>
                                    <?php if ($category['description']): ?>
                                        <p class="card-text small text-muted"><?= htmlspecialchars($category['description']) ?></p>
                                    <?php endif; ?>
                                    <span class="badge bg-<?= $category['is_active'] ? 'success' : 'secondary' ?>">
                                        <?= $category['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <p class="text-muted text-center">No categories found. <a href="#" data-bs-toggle="modal" data-bs-target="#addCategoryModal">Add your first category</a></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Menu Items Section -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">🍽️ Menu Items</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <?php if ($menu_items_result && $menu_items_result->num_rows > 0): ?>
                    <?php while ($item = $menu_items_result->fetch_assoc()): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 menu-item-card">
                                <div class="card-img-container">
                                    <?php if ($item['image_url']): ?>
                                        <img src="<?= htmlspecialchars($item['image_url']) ?>" 
                                             class="card-img-top" 
                                             alt="<?= htmlspecialchars($item['name']) ?>"
                                             style="height: 200px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" 
                                             style="height: 200px;">
                                            <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="card-body d-flex flex-column">
                                    <h6 class="card-title"><?= htmlspecialchars($item['name']) ?></h6>
                                    <small class="text-muted mb-2"><?= htmlspecialchars($item['category_name'] ?? 'No Category') ?></small>
                                    <?php if ($item['description']): ?>
                                        <p class="card-text small"><?= htmlspecialchars($item['description']) ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="mt-auto">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="h6 text-primary mb-0">₱<?= number_format($item['price'], 2, '.', ',') ?></span>
                                            <span class="badge bg-<?= $item['is_available'] ? 'success' : 'secondary' ?>">
                                                <?= $item['is_available'] ? 'Available' : 'Unavailable' ?>
                                            </span>
                                        </div>
                                        
                                        <div class="btn-group w-100" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="editMenuItem(<?= htmlspecialchars(json_encode($item)) ?>)">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteMenuItem(<?= $item['item_id'] ?>, '<?= htmlspecialchars($item['name']) ?>')">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <p class="text-muted text-center">No menu items found. <a href="#" data-bs-toggle="modal" data-bs-target="#addMenuItemModal">Add your first menu item</a></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCategoryModalLabel">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="category_name" class="form-label">Category Name *</label>
                        <input type="text" class="form-control" id="category_name" name="category_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="category_description" class="form-label">Description</label>
                        <textarea class="form-control" id="category_description" name="category_description" rows="3"></textarea>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="category_is_active" name="category_is_active" checked>
                        <label class="form-check-label" for="category_is_active">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_category" class="btn btn-success">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Menu Item Modal -->
<div class="modal fade" id="addMenuItemModal" tabindex="-1" aria-labelledby="addMenuItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="addMenuItemModalLabel">Add New Menu Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Item Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Category *</label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php if ($categories_result): ?>
                                        <?php $categories_result->data_seek(0); ?>
                                        <?php while ($category = $categories_result->fetch_assoc()): ?>
                                            <option value="<?= $category['category_id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="price" class="form-label">Price (₱) *</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="text" class="form-control" id="price" name="price" placeholder="0.00" required>
                                </div>
                                <div class="form-text">Enter amount (e.g., 1,000.00)</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="image" class="form-label">Image</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                <div class="form-text">JPG, PNG, GIF, WebP (max 5MB)</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_available" name="is_available" checked>
                        <label class="form-check-label" for="is_available">Available for ordering</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_menu_item" class="btn btn-primary">Add Menu Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Menu Item Modal -->
<div class="modal fade" id="editMenuItemModal" tabindex="-1" aria-labelledby="editMenuItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="item_id" id="edit_item_id">
                <input type="hidden" name="current_image_url" id="edit_current_image_url">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="editMenuItemModalLabel">Edit Menu Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_name" class="form-label">Item Name *</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_category_id" class="form-label">Category *</label>
                                <select class="form-select" id="edit_category_id" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php if ($categories_result): ?>
                                        <?php $categories_result->data_seek(0); ?>
                                        <?php while ($category = $categories_result->fetch_assoc()): ?>
                                            <option value="<?= $category['category_id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_price" class="form-label">Price (₱) *</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="text" class="form-control" id="edit_price" name="price" placeholder="0.00" required>
                                </div>
                                <div class="form-text">Enter amount (e.g., 1,000.00)</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_image" class="form-label">New Image</label>
                                <input type="file" class="form-control" id="edit_image" name="image" accept="image/*">
                                <div class="form-text">Leave empty to keep current image</div>
                                <div id="current_image_preview" class="mt-2"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="edit_is_available" name="is_available">
                        <label class="form-check-label" for="edit_is_available">Available for ordering</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_menu_item" class="btn btn-primary">Update Menu Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteMenuItemModal" tabindex="-1" aria-labelledby="deleteMenuItemModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="item_id" id="delete_item_id">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteMenuItemModalLabel">Delete Menu Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the menu item <strong id="delete_item_name"></strong>?</p>
                    <p class="text-danger">This action cannot be undone and will also delete the associated image file.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_menu_item" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editMenuItem(item) {
    document.getElementById('edit_item_id').value = item.item_id;
    document.getElementById('edit_name').value = item.name;
    document.getElementById('edit_category_id').value = item.category_id;
    document.getElementById('edit_description').value = item.description || '';
    document.getElementById('edit_price').value = formatCurrencyInput(item.price);
    document.getElementById('edit_is_available').checked = item.is_available == 1;
    document.getElementById('edit_current_image_url').value = item.image_url || '';
    
    // Show current image preview
    const preview = document.getElementById('current_image_preview');
    if (item.image_url) {
        preview.innerHTML = '<img src="' + item.image_url + '" style="max-width: 100px; height: auto; border-radius: 4px;" alt="Current image">';
    } else {
        preview.innerHTML = '<div class="text-muted">No current image</div>';
    }
    
    const modal = new bootstrap.Modal(document.getElementById('editMenuItemModal'));
    modal.show();
}

function deleteMenuItem(itemId, itemName) {
    document.getElementById('delete_item_id').value = itemId;
    document.getElementById('delete_item_name').textContent = itemName;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteMenuItemModal'));
    modal.show();
}

// Currency formatting functions
function formatCurrencyInput(value) {
    // Remove any existing formatting
    let cleanValue = value.toString().replace(/[^\d.]/g, '');
    
    // Convert to number and format
    let numValue = parseFloat(cleanValue) || 0;
    
    // Format with commas and 2 decimal places
    return numValue.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function parseCurrencyInput(value) {
    // Remove commas and convert to float
    return parseFloat(value.replace(/,/g, '')) || 0;
}

// Add event listeners for currency formatting
document.addEventListener('DOMContentLoaded', function() {
    const priceInputs = ['price', 'edit_price'];
    
    priceInputs.forEach(inputId => {
        const input = document.getElementById(inputId);
        if (input) {
            // Format on input
            input.addEventListener('input', function(e) {
                let cursorPos = e.target.selectionStart;
                let value = e.target.value;
                
                // Remove non-numeric characters except decimal point
                let cleanValue = value.replace(/[^\d.]/g, '');
                
                // Ensure only one decimal point
                let parts = cleanValue.split('.');
                if (parts.length > 2) {
                    cleanValue = parts[0] + '.' + parts.slice(1).join('');
                }
                
                // Format the number
                if (cleanValue && !isNaN(parseFloat(cleanValue))) {
                    let formatted = formatCurrencyInput(cleanValue);
                    e.target.value = formatted;
                    
                    // Restore cursor position
                    setTimeout(() => {
                        e.target.setSelectionRange(cursorPos, cursorPos);
                    }, 0);
                }
            });
            
            // Format on blur
            input.addEventListener('blur', function(e) {
                if (e.target.value) {
                    e.target.value = formatCurrencyInput(e.target.value);
                }
            });
        }
    });
    
    // Handle form submission - convert formatted values back to numbers
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            priceInputs.forEach(inputId => {
                const input = document.getElementById(inputId);
                if (input && input.value) {
                    // Create a hidden input with the parsed value
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = input.name;
                    hiddenInput.value = parseCurrencyInput(input.value);
                    
                    // Replace the formatted input with the hidden input
                    input.parentNode.insertBefore(hiddenInput, input);
                    input.remove();
                }
            });
        });
    });
});
</script>

<style>
.menu-item-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border: 1px solid #e9ecef;
}

.menu-item-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.card-img-container {
    overflow: hidden;
    border-radius: 0.375rem 0.375rem 0 0;
}

.menu-item-card img {
    transition: transform 0.3s ease;
}

.menu-item-card:hover img {
    transform: scale(1.05);
}
</style>

<?php include 'includes/footer.php'; ?>
