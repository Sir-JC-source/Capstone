<?php
include 'db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_POST['register'])) {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $role     = $_POST['role'];

    // Hash password
    $hashed = password_hash($password, PASSWORD_DEFAULT);

    // Default status
    $status = ($role === 'customer') ? 'pending' : 'active';

    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, status, created_at) VALUES (?,?,?,?,?,NOW())");
    $stmt->bind_param("sssss", $name, $email, $hashed, $role, $status);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Account created successfully. Please login.";
        header("Location: login.php");
        exit;
    } else {
        $error = "Registration failed: " . $stmt->error;
    }
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Register</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/pro.css" rel="stylesheet">
</head>
<body class="auth-bg d-flex align-items-center justify-content-center vh-100">
<div class="card card-glass p-4" style="width:420px;">
  <h4 class="mb-1 fw-semibold">Create Account</h4>
  <p class="text-muted mb-3">Register below</p>
  <?php if(isset($error)): ?><div class="alert alert-danger py-2"><?=$error?></div><?php endif; ?>
  <form method="post" class="vstack gap-3">
    <div>
      <label class="form-label">Name</label>
      <input type="text" name="name" class="form-control form-control-lg" required>
    </div>
    <div>
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control form-control-lg" required>
    </div>
    <div>
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control form-control-lg" required>
    </div>
    <div>
      <label class="form-label">Role</label>
      <select name="role" class="form-select form-select-lg" required>
        <option value="customer">Customer</option>
        <option value="business_owner">Business Owner</option>
        <!-- Admin accounts dapat manual i-insert ni super admin -->
      </select>
    </div>
    <button class="btn btn-primary btn-lg w-100" name="register" value="1">Register</button>
  </form>
</div>
</body>
</html>
