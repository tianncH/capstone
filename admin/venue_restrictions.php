<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add_restriction':
                    $venue_id = (int)$_POST['venue_id'];
                    $restriction_type = $_POST['restriction_type'];
                    $title = trim($_POST['title']);
                    $description = trim($_POST['description']);
                    $start_date = $_POST['start_date'];
                    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
                    $start_time = !empty($_POST['start_time']) ? $_POST['start_time'] : null;
                    $end_time = !empty($_POST['end_time']) ? $_POST['end_time'] : null;
                    $recurring = $_POST['recurring'];
                    
                    $sql = "INSERT INTO venue_restrictions (venue_id, restriction_type, title, description, start_date, end_date, start_time, end_time, recurring) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('issssssss', $venue_id, $restriction_type, $title, $description, $start_date, $end_date, $start_time, $end_time, $recurring);
                    
                    if ($stmt->execute()) {
                        $success_message = "Restriction added successfully!";
                    } else {
                        throw new Exception("Failed to add restriction: " . $stmt->error);
                    }
                    break;
                    
                case 'edit_restriction':
                    $restriction_id = (int)$_POST['restriction_id'];
                    $venue_id = (int)$_POST['venue_id'];
                    $restriction_type = $_POST['restriction_type'];
                    $title = trim($_POST['title']);
                    $description = trim($_POST['description']);
                    $start_date = $_POST['start_date'];
                    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
                    $start_time = !empty($_POST['start_time']) ? $_POST['start_time'] : null;
                    $end_time = !empty($_POST['end_time']) ? $_POST['end_time'] : null;
                    $recurring = $_POST['recurring'];
                    $is_active = isset($_POST['is_active']) ? 1 : 0;
                    
                    $sql = "UPDATE venue_restrictions SET venue_id=?, restriction_type=?, title=?, description=?, start_date=?, end_date=?, start_time=?, end_time=?, recurring=?, is_active=? WHERE restriction_id=?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('issssssssii', $venue_id, $restriction_type, $title, $description, $start_date, $end_date, $start_time, $end_time, $recurring, $is_active, $restriction_id);
                    
                    if ($stmt->execute()) {
                        $success_message = "Restriction updated successfully!";
                    } else {
                        throw new Exception("Failed to update restriction: " . $stmt->error);
                    }
                    break;
                    
                case 'delete_restriction':
                    $restriction_id = (int)$_POST['restriction_id'];
                    
                    $sql = "DELETE FROM venue_restrictions WHERE restriction_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('i', $restriction_id);
                    
                    if ($stmt->execute()) {
                        $success_message = "Restriction deleted successfully!";
                    } else {
                        throw new Exception("Failed to delete restriction: " . $stmt->error);
                    }
                    break;
            }
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get all restrictions
$restrictions_sql = "SELECT vr.*, v.venue_name 
                     FROM venue_restrictions vr 
                     JOIN venues v ON vr.venue_id = v.venue_id 
                     ORDER BY vr.start_date DESC, vr.start_time ASC";
$restrictions_result = $conn->query($restrictions_sql);

// Get venues for forms
$venues_sql = "SELECT * FROM venues WHERE is_active = 1 ORDER BY venue_name";
$venues_result = $conn->query($venues_sql);

// Get restriction for editing
$edit_restriction = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_sql = "SELECT * FROM venue_restrictions WHERE restriction_id = ?";
    $edit_stmt = $conn->prepare($edit_sql);
    $edit_stmt->bind_param('i', $edit_id);
    $edit_stmt->execute();
    $edit_restriction = $edit_stmt->get_result()->fetch_assoc();
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Venue Restrictions</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addRestrictionModal">
                    <i class="bi bi-plus-circle"></i> Add Restriction
                </button>
            </div>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $success_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $error_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Restrictions Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Restrictions</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Venue</th>
                            <th>Type</th>
                            <th>Date Range</th>
                            <th>Time Range</th>
                            <th>Recurring</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($restriction = $restrictions_result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($restriction['title']) ?></strong>
                                    <?php if ($restriction['description']): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($restriction['description']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($restriction['venue_name']) ?></td>
                                <td>
                                    <?php
                                    $type_colors = [
                                        'blackout' => 'danger',
                                        'maintenance' => 'warning',
                                        'special_event' => 'info',
                                        'holiday' => 'primary',
                                        'custom' => 'secondary'
                                    ];
                                    $color = $type_colors[$restriction['restriction_type']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $color ?>"><?= ucfirst(str_replace('_', ' ', $restriction['restriction_type'])) ?></span>
                                </td>
                                <td>
                                    <?= date('M j, Y', strtotime($restriction['start_date'])) ?>
                                    <?php if ($restriction['end_date'] && $restriction['end_date'] != $restriction['start_date']): ?>
                                        - <?= date('M j, Y', strtotime($restriction['end_date'])) ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($restriction['start_time'] && $restriction['end_time']): ?>
                                        <?= date('g:i A', strtotime($restriction['start_time'])) ?> - <?= date('g:i A', strtotime($restriction['end_time'])) ?>
                                    <?php else: ?>
                                        <span class="text-muted">All day</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($restriction['recurring'] != 'none'): ?>
                                        <span class="badge bg-info"><?= ucfirst($restriction['recurring']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">One-time</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($restriction['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="?edit=<?= $restriction['restriction_id'] ?>" class="btn btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger" onclick="deleteRestriction(<?= $restriction['restriction_id'] ?>, '<?= htmlspecialchars($restriction['title']) ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Restriction Modal -->
<div class="modal fade" id="addRestrictionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add_restriction">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Restriction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="venue_id" class="form-label">Venue *</label>
                                <select class="form-control" id="venue_id" name="venue_id" required>
                                    <option value="">Select Venue</option>
                                    <?php 
                                    $venues_result->data_seek(0);
                                    while ($venue = $venues_result->fetch_assoc()): 
                                    ?>
                                        <option value="<?= $venue['venue_id'] ?>"><?= htmlspecialchars($venue['venue_name']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="restriction_type" class="form-label">Restriction Type *</label>
                                <select class="form-control" id="restriction_type" name="restriction_type" required>
                                    <option value="blackout">Blackout</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="special_event">Special Event</option>
                                    <option value="holiday">Holiday</option>
                                    <option value="custom">Custom</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">Title *</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="start_date" class="form-label">Start Date *</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date">
                                <small class="form-text text-muted">Leave empty for single day restriction</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="start_time" class="form-label">Start Time</label>
                                <input type="time" class="form-control" id="start_time" name="start_time">
                                <small class="form-text text-muted">Leave empty for all-day restriction</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="end_time" class="form-label">End Time</label>
                                <input type="time" class="form-control" id="end_time" name="end_time">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="recurring" class="form-label">Recurring</label>
                        <select class="form-control" id="recurring" name="recurring">
                            <option value="none">None (One-time)</option>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Restriction</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Restriction Modal -->
<?php if ($edit_restriction): ?>
<div class="modal fade show" id="editRestrictionModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="edit_restriction">
                <input type="hidden" name="restriction_id" value="<?= $edit_restriction['restriction_id'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Restriction</h5>
                    <a href="venue_restrictions.php" class="btn-close"></a>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_venue_id" class="form-label">Venue *</label>
                                <select class="form-control" id="edit_venue_id" name="venue_id" required>
                                    <?php 
                                    $venues_result->data_seek(0);
                                    while ($venue = $venues_result->fetch_assoc()): 
                                    ?>
                                        <option value="<?= $venue['venue_id'] ?>" <?= $edit_restriction['venue_id'] == $venue['venue_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($venue['venue_name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_restriction_type" class="form-label">Restriction Type *</label>
                                <select class="form-control" id="edit_restriction_type" name="restriction_type" required>
                                    <option value="blackout" <?= $edit_restriction['restriction_type'] == 'blackout' ? 'selected' : '' ?>>Blackout</option>
                                    <option value="maintenance" <?= $edit_restriction['restriction_type'] == 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                                    <option value="special_event" <?= $edit_restriction['restriction_type'] == 'special_event' ? 'selected' : '' ?>>Special Event</option>
                                    <option value="holiday" <?= $edit_restriction['restriction_type'] == 'holiday' ? 'selected' : '' ?>>Holiday</option>
                                    <option value="custom" <?= $edit_restriction['restriction_type'] == 'custom' ? 'selected' : '' ?>>Custom</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_title" class="form-label">Title *</label>
                        <input type="text" class="form-control" id="edit_title" name="title" value="<?= htmlspecialchars($edit_restriction['title']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"><?= htmlspecialchars($edit_restriction['description']) ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_start_date" class="form-label">Start Date *</label>
                                <input type="date" class="form-control" id="edit_start_date" name="start_date" value="<?= $edit_restriction['start_date'] ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="edit_end_date" name="end_date" value="<?= $edit_restriction['end_date'] ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_start_time" class="form-label">Start Time</label>
                                <input type="time" class="form-control" id="edit_start_time" name="start_time" value="<?= $edit_restriction['start_time'] ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_end_time" class="form-label">End Time</label>
                                <input type="time" class="form-control" id="edit_end_time" name="end_time" value="<?= $edit_restriction['end_time'] ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_recurring" class="form-label">Recurring</label>
                        <select class="form-control" id="edit_recurring" name="recurring">
                            <option value="none" <?= $edit_restriction['recurring'] == 'none' ? 'selected' : '' ?>>None (One-time)</option>
                            <option value="daily" <?= $edit_restriction['recurring'] == 'daily' ? 'selected' : '' ?>>Daily</option>
                            <option value="weekly" <?= $edit_restriction['recurring'] == 'weekly' ? 'selected' : '' ?>>Weekly</option>
                            <option value="monthly" <?= $edit_restriction['recurring'] == 'monthly' ? 'selected' : '' ?>>Monthly</option>
                            <option value="yearly" <?= $edit_restriction['recurring'] == 'yearly' ? 'selected' : '' ?>>Yearly</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active" <?= $edit_restriction['is_active'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="edit_is_active">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="venue_restrictions.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Restriction</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Delete Restriction Form -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete_restriction">
    <input type="hidden" name="restriction_id" id="delete_restriction_id">
</form>

<script>
function deleteRestriction(restrictionId, title) {
    if (confirm(`Are you sure you want to delete "${title}"? This action cannot be undone.`)) {
        document.getElementById('delete_restriction_id').value = restrictionId;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
