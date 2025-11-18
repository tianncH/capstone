<?php
require_once 'includes/db_connection.php';
require_once 'includes/header.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_venue':
                $venue_name = trim($_POST['venue_name']);
                $description = trim($_POST['description']);
                $capacity = (int)$_POST['capacity'];
                $hourly_rate = (float)$_POST['hourly_rate'];
                $display_order = (int)$_POST['display_order'];
                
                if (!empty($venue_name)) {
                    $sql = "INSERT INTO venue_types (venue_name, description, capacity, hourly_rate, display_order) VALUES (?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssidi", $venue_name, $description, $capacity, $hourly_rate, $display_order);
                    
                    if ($stmt->execute()) {
                        $success_message = "Venue type added successfully!";
                    } else {
                        $error_message = "Failed to add venue type: " . $stmt->error;
                    }
                } else {
                    $error_message = "Venue name is required!";
                }
                break;
                
            case 'edit_venue':
                $venue_type_id = (int)$_POST['venue_type_id'];
                $venue_name = trim($_POST['venue_name']);
                $description = trim($_POST['description']);
                $capacity = (int)$_POST['capacity'];
                $hourly_rate = (float)$_POST['hourly_rate'];
                $display_order = (int)$_POST['display_order'];
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                if (!empty($venue_name)) {
                    $sql = "UPDATE venue_types SET venue_name = ?, description = ?, capacity = ?, hourly_rate = ?, display_order = ?, is_active = ? WHERE venue_type_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssidiii", $venue_name, $description, $capacity, $hourly_rate, $display_order, $is_active, $venue_type_id);
                    
                    if ($stmt->execute()) {
                        $success_message = "Venue type updated successfully!";
                    } else {
                        $error_message = "Failed to update venue type: " . $stmt->error;
                    }
                } else {
                    $error_message = "Venue name is required!";
                }
                break;
                
            case 'delete_venue':
                $venue_type_id = (int)$_POST['venue_type_id'];
                
                // Check if venue has ratings
                $check_sql = "SELECT COUNT(*) as count FROM venue_ratings WHERE venue_type = (SELECT venue_name FROM venue_types WHERE venue_type_id = ?)";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("i", $venue_type_id);
                $check_stmt->execute();
                $result = $check_stmt->get_result()->fetch_assoc();
                
                if ($result['count'] > 0) {
                    $error_message = "Cannot delete venue type that has ratings. Deactivate it instead.";
                } else {
                    $sql = "DELETE FROM venue_types WHERE venue_type_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $venue_type_id);
                    
                    if ($stmt->execute()) {
                        $success_message = "Venue type deleted successfully!";
                    } else {
                        $error_message = "Failed to delete venue type: " . $stmt->error;
                    }
                }
                break;
        }
    }
}

// Get all venue types
$sql = "SELECT * FROM venue_types ORDER BY display_order, venue_name";
$venue_types = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// Get venue statistics
$stats_sql = "
    SELECT 
        vt.venue_name,
        COUNT(vr.rating_id) as total_ratings,
        AVG(vr.venue_quality_rating) as avg_rating
    FROM venue_types vt
    LEFT JOIN venue_ratings vr ON vt.venue_name = vr.venue_type
    GROUP BY vt.venue_type_id, vt.venue_name
    ORDER BY avg_rating DESC
";
$venue_stats = $conn->query($stats_sql)->fetch_all(MYSQLI_ASSOC);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-building"></i> Venue Management</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVenueModal">
                    <i class="bi bi-plus-circle"></i> Add Venue Type
                </button>
            </div>
        </div>
    </div>

    <!-- Messages -->
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Venue Types Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-list"></i> Venue Types</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Venue Name</th>
                                    <th>Description</th>
                                    <th>Capacity</th>
                                    <th>Hourly Rate</th>
                                    <th>Status</th>
                                    <th>Ratings</th>
                                    <th>Avg Rating</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($venue_types as $venue): ?>
                                    <?php 
                                    $venue_stat = array_filter($venue_stats, function($stat) use ($venue) {
                                        return $stat['venue_name'] === $venue['venue_name'];
                                    });
                                    $venue_stat = reset($venue_stat);
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary"><?= $venue['display_order'] ?></span>
                                        </td>
                                        <td><strong><?= htmlspecialchars($venue['venue_name']) ?></strong></td>
                                        <td><?= htmlspecialchars($venue['description']) ?></td>
                                        <td><?= $venue['capacity'] ? $venue['capacity'] . ' people' : 'N/A' ?></td>
                                        <td>₱<?= number_format($venue['hourly_rate'], 2) ?></td>
                                        <td>
                                            <?php if ($venue['is_active']): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?= $venue_stat['total_ratings'] ?? 0 ?></span>
                                        </td>
                                        <td>
                                            <?php if ($venue_stat && $venue_stat['avg_rating']): ?>
                                                <div class="d-flex align-items-center">
                                                    <span class="me-2"><?= number_format($venue_stat['avg_rating'], 1) ?></span>
                                                    <div class="text-warning">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="bi bi-star<?= $i <= $venue_stat['avg_rating'] ? '-fill' : '' ?>"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">No ratings</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="editVenue(<?= htmlspecialchars(json_encode($venue)) ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="deleteVenue(<?= $venue['venue_type_id'] ?>, '<?= htmlspecialchars($venue['venue_name']) ?>')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Venue Modal -->
<div class="modal fade" id="addVenueModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Add Venue Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_venue">
                    
                    <div class="mb-3">
                        <label for="venue_name" class="form-label">Venue Name *</label>
                        <input type="text" class="form-control" id="venue_name" name="venue_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="capacity" class="form-label">Capacity</label>
                                <input type="number" class="form-control" id="capacity" name="capacity" min="1">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="hourly_rate" class="form-label">Hourly Rate (₱)</label>
                                <input type="number" class="form-control" id="hourly_rate" name="hourly_rate" min="0" step="0.01">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="display_order" class="form-label">Display Order</label>
                        <input type="number" class="form-control" id="display_order" name="display_order" min="0" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Venue Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Venue Modal -->
<div class="modal fade" id="editVenueModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Venue Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_venue">
                    <input type="hidden" name="venue_type_id" id="edit_venue_type_id">
                    
                    <div class="mb-3">
                        <label for="edit_venue_name" class="form-label">Venue Name *</label>
                        <input type="text" class="form-control" id="edit_venue_name" name="venue_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_capacity" class="form-label">Capacity</label>
                                <input type="number" class="form-control" id="edit_capacity" name="capacity" min="1">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_hourly_rate" class="form-label">Hourly Rate (₱)</label>
                                <input type="number" class="form-control" id="edit_hourly_rate" name="hourly_rate" min="0" step="0.01">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_display_order" class="form-label">Display Order</label>
                                <input type="number" class="form-control" id="edit_display_order" name="display_order" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active" checked>
                                    <label class="form-check-label" for="edit_is_active">
                                        Active
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Venue Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteVenueModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete_venue">
                    <input type="hidden" name="venue_type_id" id="delete_venue_type_id">
                    
                    <p>Are you sure you want to delete the venue type <strong id="delete_venue_name"></strong>?</p>
                    <p class="text-danger"><small>This action cannot be undone.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Venue Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editVenue(venue) {
    document.getElementById('edit_venue_type_id').value = venue.venue_type_id;
    document.getElementById('edit_venue_name').value = venue.venue_name;
    document.getElementById('edit_description').value = venue.description || '';
    document.getElementById('edit_capacity').value = venue.capacity || '';
    document.getElementById('edit_hourly_rate').value = venue.hourly_rate || '';
    document.getElementById('edit_display_order').value = venue.display_order || 0;
    document.getElementById('edit_is_active').checked = venue.is_active == 1;
    
    new bootstrap.Modal(document.getElementById('editVenueModal')).show();
}

function deleteVenue(venueTypeId, venueName) {
    document.getElementById('delete_venue_type_id').value = venueTypeId;
    document.getElementById('delete_venue_name').textContent = venueName;
    
    new bootstrap.Modal(document.getElementById('deleteVenueModal')).show();
}
</script>

<?php require_once 'includes/footer.php'; ?>