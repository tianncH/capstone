<?php
require_once 'includes/db_connection.php';
require_once 'includes/header.php';

// Check if user has admin role
if ($_SESSION["admin_role"] !== "admin") {
    // Redirect to dashboard with error message
    header("Location: index.php?error=unauthorized");
    exit;
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new user
    if (isset($_POST['add_user'])) {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $role = trim($_POST['role']);
        
        // Validate input
        $errors = [];
        
        if (empty($username)) {
            $errors[] = "Username cannot be empty.";
        } else {
            // Check if username exists
            $sql_check = "SELECT admin_id FROM admin_users WHERE username = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("s", $username);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $errors[] = "Username already exists.";
            }
            $stmt_check->close();
        }
        
        if (empty($password)) {
            $errors[] = "Password cannot be empty.";
        } elseif (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters.";
        }
        
        if (empty($full_name)) {
            $errors[] = "Full name cannot be empty.";
        }
        
        if (empty($email)) {
            $errors[] = "Email cannot be empty.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        } else {
            // Check if email exists
            $sql_check = "SELECT admin_id FROM admin_users WHERE email = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("s", $email);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $errors[] = "Email already exists.";
            }
            $stmt_check->close();
        }
        
        if (empty($errors)) {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $sql = "INSERT INTO admin_users (username, password, full_name, email, role) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $username, $hashed_password, $full_name, $email, $role);
            
            if ($stmt->execute()) {
                $success = "User added successfully!";
                // Redirect to prevent form resubmission
                header("Location: admin_users.php?success=added");
                exit;
            } else {
                $error = "Error adding user: " . $conn->error;
            }
            $stmt->close();
        } else {
            $error = implode("<br>", $errors);
        }
    }
    
    // Update user
    if (isset($_POST['update_user'])) {
        $admin_id = intval($_POST['admin_id']);
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $role = trim($_POST['role']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Validate input
        $errors = [];
        
        if (empty($full_name)) {
            $errors[] = "Full name cannot be empty.";
        }
        
        if (empty($email)) {
            $errors[] = "Email cannot be empty.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        } else {
            // Check if email exists (excluding current user)
            $sql_check = "SELECT admin_id FROM admin_users WHERE email = ? AND admin_id != ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("si", $email, $admin_id);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $errors[] = "Email already exists.";
            }
            $stmt_check->close();
        }
        
        if (empty($errors)) {
            // Update user
            $sql = "UPDATE admin_users 
                    SET full_name = ?, email = ?, role = ?, is_active = ? 
                    WHERE admin_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssii", $full_name, $email, $role, $is_active, $admin_id);
            
            if ($stmt->execute()) {
                $success = "User updated successfully!";
                // Redirect to prevent form resubmission
                header("Location: admin_users.php?success=updated");
                exit;
            } else {
                $error = "Error updating user: " . $conn->error;
            }
            $stmt->close();
        } else {
            $error = implode("<br>", $errors);
        }
    }
    
    // Reset password
    if (isset($_POST['reset_password'])) {
        $admin_id = intval($_POST['admin_id']);
        $new_password = trim($_POST['new_password']);
        
        // Validate input
        if (empty($new_password)) {
            $error = "Password cannot be empty.";
        } elseif (strlen($new_password) < 6) {
            $error = "Password must be at least 6 characters.";
        } else {
            // Hash password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password
            $sql = "UPDATE admin_users SET password = ? WHERE admin_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $hashed_password, $admin_id);
            
            if ($stmt->execute()) {
                $success = "Password reset successfully!";
                // Redirect to prevent form resubmission
                header("Location: admin_users.php?success=reset");
                exit;
            } else {
                $error = "Error resetting password: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Get all users
$sql_users = "SELECT admin_id, username, full_name, email, role, last_login, is_active 
              FROM admin_users 
              ORDER BY username";
$result_users = $conn->query($sql_users);

// Get user for editing if ID is provided
$edit_user = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $admin_id = intval($_GET['edit']);
    $sql_edit = "SELECT admin_id, username, full_name, email, role, is_active 
                FROM admin_users 
                WHERE admin_id = ?";
    $stmt_edit = $conn->prepare($sql_edit);
    $stmt_edit->bind_param("i", $admin_id);
    $stmt_edit->execute();
    $result_edit = $stmt_edit->get_result();
    if ($result_edit->num_rows > 0) {
        $edit_user = $result_edit->fetch_assoc();
    }
    $stmt_edit->close();
}
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Admin Users Management</h2>
        <?php if ($edit_user): ?>
            <a href="admin_users.php" class="btn btn-outline-primary">
                <i class="bi bi-plus-circle"></i> Add New User
            </a>
        <?php endif; ?>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php
            $message = "";
            switch ($_GET['success']) {
                case 'added':
                    $message = "User added successfully!";
                    break;
                case 'updated':
                    $message = "User updated successfully!";
                    break;
                case 'reset':
                    $message = "Password reset successfully!";
                    break;
            }
            echo $message;
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- User Form -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><?= $edit_user ? 'Edit User' : 'Add New User' ?></h5>
                </div>
                <div class="card-body">
                    <?php if ($edit_user): ?>
                        <!-- Edit User Form -->
                        <form method="post" action="">
                            <input type="hidden" name="admin_id" value="<?= $edit_user['admin_id'] ?>">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" value="<?= htmlspecialchars($edit_user['username']) ?>" disabled>
                                <div class="form-text">Username cannot be changed.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?= htmlspecialchars($edit_user['full_name']) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($edit_user['email']) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="admin" <?= $edit_user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    <option value="manager" <?= $edit_user['role'] === 'manager' ? 'selected' : '' ?>>Manager</option>
                                    <option value="staff" <?= $edit_user['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                                </select>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" <?= $edit_user['is_active'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_active">Active</label>
                                <div class="form-text">Inactive users cannot log in.</div>
                            </div>
                            
                            <button type="submit" name="update_user" class="btn btn-primary w-100 mb-2">
                                <i class="bi bi-save"></i> Update User
                            </button>
                            
                            <button type="button" class="btn btn-outline-secondary w-100" data-bs-toggle="modal" data-bs-target="#resetPasswordModal">
                                <i class="bi bi-key"></i> Reset Password
                            </button>
                            
                            <!-- Reset Password Modal -->
                            <div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="resetPasswordModalLabel">Reset Password</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form method="post" action="">
                                            <div class="modal-body">
                                                <input type="hidden" name="admin_id" value="<?= $edit_user['admin_id'] ?>">
                                                <p>Enter a new password for user <strong><?= htmlspecialchars($edit_user['username']) ?></strong>:</p>
                                                <div class="mb-3">
                                                    <label for="new_password" class="form-label">New Password</label>
                                                    <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                                                    <div class="form-text">Password must be at least 6 characters long.</div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="reset_password" class="btn btn-primary">Reset Password</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </form>
                    <?php else: ?>
                        <!-- Add User Form -->
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required minlength="6">
                                <div class="form-text">Password must be at least 6 characters long.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="admin">Admin</option>
                                    <option value="manager">Manager</option>
                                    <option value="staff" selected>Staff</option>
                                </select>
                                <div class="form-text">
                                    <strong>Admin:</strong> Full access to all features<br>
                                    <strong>Manager:</strong> Access to most features<br>
                                    <strong>Staff:</strong> Limited access
                                </div>
                            </div>
                            
                            <button type="submit" name="add_user" class="btn btn-success w-100">
                                <i class="bi bi-plus-circle"></i> Add User
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Users List -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">All Users</h5>
                </div>
                <div class="card-body">
                    <?php if ($result_users && $result_users->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Full Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Last Login</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($user = $result_users->fetch_assoc()): ?>
                                        <tr class="<?= $user['is_active'] ? '' : 'table-secondary' ?>">
                                            <td><?= htmlspecialchars($user['username']) ?></td>
                                            <td><?= htmlspecialchars($user['full_name']) ?></td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td>
                                                <?php
                                                switch ($user['role']) {
                                                    case 'admin':
                                                        echo '<span class="badge bg-danger">Admin</span>';
                                                        break;
                                                    case 'manager':
                                                        echo '<span class="badge bg-warning">Manager</span>';
                                                        break;
                                                    case 'staff':
                                                        echo '<span class="badge bg-info">Staff</span>';
                                                        break;
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?= $user['last_login'] ? date("M j, Y, g:i a", strtotime($user['last_login'])) : 'Never' ?>
                                            </td>
                                            <td>
                                                <?php if ($user['is_active']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($user['admin_id'] != $_SESSION['admin_id']): ?>
                                                    <a href="admin_users.php?edit=<?= $user['admin_id'] ?>" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </a>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Current User</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            No users found. Add your first user using the form.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>
