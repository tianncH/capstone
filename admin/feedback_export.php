<?php
require_once 'includes/db_connection.php';

// Check if this is an export request
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Set headers for CSV download
    $filename = 'feedback_export_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Expires: 0');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
    fputcsv($output, [
        'Feedback ID',
        'Customer Name',
        'Customer Email',
        'Customer Phone',
        'Table Number',
        'Order Number',
        'Food Quality Rating',
        'Food Quality Comments',
        'Service Quality Rating',
        'Service Quality Comments',
        'Venue Quality Rating',
        'Venue Quality Comments',
        'Overall Rating',
        'Reservation Experience',
        'Reservation Comments',
        'Is Anonymous',
        'Is Public',
        'Status',
        'Admin Notes',
        'Created At',
        'Updated At'
    ]);
    
    // Get feedback data
    $sql = "SELECT f.*, t.table_number, o.queue_number 
            FROM feedback f 
            LEFT JOIN tables t ON f.table_id = t.table_id 
            LEFT JOIN orders o ON f.order_id = o.order_id 
            ORDER BY f.created_at DESC";
    
    $result = $conn->query($sql);
    
    // Add data rows
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['feedback_id'],
            $row['customer_name'],
            $row['customer_email'],
            $row['customer_phone'],
            $row['table_number'],
            $row['queue_number'],
            $row['food_quality_rating'],
            $row['food_quality_comments'],
            $row['service_quality_rating'],
            $row['service_quality_comments'],
            $row['venue_quality_rating'],
            $row['venue_quality_comments'],
            $row['overall_rating'],
            $row['reservation_experience'],
            $row['reservation_comments'],
            $row['is_anonymous'] ? 'Yes' : 'No',
            $row['is_public'] ? 'Yes' : 'No',
            $row['status'],
            $row['admin_notes'],
            $row['created_at'],
            $row['updated_at']
        ]);
    }
    
    fclose($output);
    exit;
}

// If not an export request, show the export interface
require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Export Feedback Data</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="feedback_management.php" class="btn btn-sm btn-outline-secondary">Back to Management</a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold">Export Options</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="">
                    <input type="hidden" name="export" value="csv">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="date_from">From Date</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="<?= date('Y-m-01') ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="date_to">To Date</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status">Status Filter</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="reviewed">Reviewed</option>
                                    <option value="responded">Responded</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="rating">Rating Filter</label>
                                <select class="form-control" id="rating" name="rating">
                                    <option value="">All Ratings</option>
                                    <option value="excellent">Excellent (4.5+)</option>
                                    <option value="good">Good (3.5-4.4)</option>
                                    <option value="average">Average (2.5-3.4)</option>
                                    <option value="poor">Poor (<2.5)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="reservation">Reservation Filter</label>
                                <select class="form-control" id="reservation" name="reservation">
                                    <option value="">All</option>
                                    <option value="not_applicable">Not Applicable</option>
                                    <option value="did_not_use">Did Not Use</option>
                                    <option value="used_system">Used System</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="format">Export Format</label>
                                <select class="form-control" id="format" name="format" disabled>
                                    <option value="csv" selected>CSV (Comma Separated Values)</option>
                                </select>
                                <small class="form-text text-muted">More formats coming soon</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-download"></i> Export to CSV
                            </button>
                            <button type="button" class="btn btn-info" onclick="previewExport()">
                                <i class="bi bi-eye"></i> Preview Data
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold">Export Information</h6>
            </div>
            <div class="card-body">
                <h6>What's Included:</h6>
                <ul class="list-unstyled">
                    <li><i class="bi bi-check text-success"></i> Customer Information</li>
                    <li><i class="bi bi-check text-success"></i> All Rating Scores</li>
                    <li><i class="bi bi-check text-success"></i> Comments & Feedback</li>
                    <li><i class="bi bi-check text-success"></i> Reservation Experience</li>
                    <li><i class="bi bi-check text-success"></i> Status & Admin Notes</li>
                    <li><i class="bi bi-check text-success"></i> Timestamps</li>
                </ul>
                
                <hr>
                
                <h6>Export Tips:</h6>
                <ul class="list-unstyled small">
                    <li><i class="bi bi-info-circle text-info"></i> Use date filters to export specific periods</li>
                    <li><i class="bi bi-info-circle text-info"></i> CSV files can be opened in Excel or Google Sheets</li>
                    <li><i class="bi bi-info-circle text-info"></i> Large exports may take a few moments</li>
                    <li><i class="bi bi-info-circle text-info"></i> Anonymous feedback will show as "Anonymous"</li>
                </ul>
            </div>
        </div>
        
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold">Quick Export</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="?export=csv" class="btn btn-outline-primary">
                        <i class="bi bi-download"></i> Export All Data
                    </a>
                    <a href="?export=csv&date_from=<?= date('Y-m-01') ?>&date_to=<?= date('Y-m-d') ?>" class="btn btn-outline-success">
                        <i class="bi bi-calendar"></i> This Month
                    </a>
                    <a href="?export=csv&date_from=<?= date('Y-m-d', strtotime('-30 days')) ?>&date_to=<?= date('Y-m-d') ?>" class="btn btn-outline-info">
                        <i class="bi bi-clock"></i> Last 30 Days
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">Export Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="previewContent">
                <!-- Preview content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" onclick="confirmExport()">
                    <i class="bi bi-download"></i> Export Data
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function previewExport() {
    // Get form data
    const formData = new FormData(document.querySelector('form'));
    formData.append('preview', '1');
    
    // Show loading
    document.getElementById('previewContent').innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    
    // Show modal
    new bootstrap.Modal(document.getElementById('previewModal')).show();
    
    // Fetch preview data
    fetch('feedback_export_preview.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        document.getElementById('previewContent').innerHTML = data;
    })
    .catch(error => {
        document.getElementById('previewContent').innerHTML = '<div class="alert alert-danger">Error loading preview: ' + error + '</div>';
    });
}

function confirmExport() {
    // Submit the form for actual export
    document.querySelector('form').submit();
}
</script>

<?php
require_once 'includes/footer.php';
$conn->close();
?>
