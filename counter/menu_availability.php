<?php
require_once 'includes/db_connection.php';
require_once 'includes/header.php';

// Process menu item availability updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_availability'])) {
    foreach ($_POST['availability'] as $item_id => $is_available) {
        $item_id = intval($item_id);
        $is_available = $is_available ? 1 : 0;

        $sql = "UPDATE menu_items SET is_available = $is_available WHERE item_id = $item_id";
        $conn->query($sql);
    }

    // Redirect to prevent form resubmission
    header("Location: menu_availability.php?success=1");
    exit;
}

// Get all menu categories
$sql_categories = "SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order";
$result_categories = $conn->query($sql_categories);
?>

<div class="row">
    <div class="col-md-12">
        <?php if (isset($_GET['success'])) { ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Menu availability updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php } ?>

        <h2>Menu Availability Management</h2>
        <p>Update the availability of menu items. Unavailable items will not be shown to customers.</p>

        <form method="post" action="">
            <div class="accordion" id="menuAccordion">
                <?php
                if ($result_categories->num_rows > 0) {
                    while ($category = $result_categories->fetch_assoc()) {
                        $category_id = $category['category_id'];
                        ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading<?= $category_id ?>">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapse<?= $category_id ?>" aria-expanded="true"
                                        aria-controls="collapse<?= $category_id ?>">
                                    <?= htmlspecialchars($category['name']) ?>
                                </button>
                            </h2>
                            <div id="collapse<?= $category_id ?>" class="accordion-collapse collapse show"
                                 aria-labelledby="heading<?= $category_id ?>" data-bs-parent="#menuAccordion">
                                <div class="accordion-body">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Item</th>
                                                <th>Price</th>
                                                <th>Availability</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Get menu items for this category
                                            $sql_items = "SELECT * FROM menu_items WHERE category_id = $category_id ORDER BY display_order";
                                            $result_items = $conn->query($sql_items);

                                            if ($result_items->num_rows > 0) {
                                                while ($item = $result_items->fetch_assoc()) {
                                                    $item_id = $item['item_id'];
                                                    $is_available = $item['is_available'];
                                                    ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($item['name']) ?></td>
                                                        <td>₱<?= number_format($item['price'], 2) ?></td>
                                                        <td>
                                                            <div class="form-check form-switch">
                                                              <!-- Hidden input ensures a value is sent even if checkbox is unchecked -->
                                                              <input type="hidden" name="availability[<?= $item_id ?>]" value="0">
                                                              <input class="form-check-input" type="checkbox"
                                                              name="availability[<?= $item_id ?>]" value="1"
                                                              id="availability<?= $item_id ?>"
                                                              <?= $is_available ? 'checked' : '' ?>>

                                                                <label class="form-check-label" for="availability<?= $item_id ?>">
                                                                    <?= $is_available ? 'Available' : 'Unavailable' ?>
                                                                </label>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                }
                                            } else {
                                                echo '<tr><td colspan="3">No items in this category.</td></tr>';
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div class="alert alert-info">No menu categories available.</div>';
                }
                ?>
            </div>

            <div class="mt-4 mb-5">
                <button type="submit" name="update_availability" class="btn btn-primary">
                    <i class="bi bi-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update label text when switch is toggled
    document.querySelectorAll('.form-check-input').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const label = this.nextElementSibling;
            label.textContent = this.checked ? 'Available' : 'Unavailable';
        });
    });
});
</script>

<?php
require_once 'includes/footer.php';
$conn->close();
?>
