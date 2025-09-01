<?php
include 'db.php';
session_start();

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT user_id, name, role, password, status FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $db_pass = $row['password'];
        $is_hashed = preg_match('/^\$2y\$/', $db_pass);

        // ✅ Check account status first
        if ($row['status'] === 'Pending') {
            $error = "⏳ Your account is pending approval. Please wait for admin confirmation.";
        } elseif ($row['status'] === 'Declined') {
            $error = "❌ Your account has been declined. Contact support for details.";
        } elseif ($row['status'] !== 'Active') {
            $error = "⚠️ Your account is not active. Please contact admin.";
        } elseif (
            ($is_hashed && password_verify($password, $db_pass)) || 
            (!$is_hashed && $password === $db_pass)
        ) {
            // ✅ Auto-update to hashed kung plaintext pa
            if (!$is_hashed) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $upd = $conn->prepare("UPDATE users SET password=? WHERE user_id=?");
                $upd->bind_param("si", $newHash, $row['user_id']);
                $upd->execute();
            }

            // ✅ Login success
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['name'] = $row['name'];
            $_SESSION['role'] = $row['role'];

            // ✅ Role-based redirect
            switch ($row['role']) {
                case 'Admin':
                    header("Location: index.php"); break;
                case 'Business_Owner':
                    header("Location: businessDashboard.php"); break;
                case 'Customer':
                    header("Location: customerDashboard.php"); break;
                case 'Marketing_Manager':
                    header("Location: marketingDashboard.php"); break;
                case 'Analytics':
                    header("Location: analyticsDashboard.php"); break;
                default:
                    header("Location: index.php");
            }
            exit;
        } else {
            $error = "Invalid credentials.";
        }
    } else {
        $error = "Account not found.";
    }
}
?>

<!doctype html>
<html lang='en'>
<head>
<meta charset='utf-8'>
<meta name='viewport' content='width=device-width, initial-scale=1'>
<title>Sign in • Dashboard</title>
<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
<link href='assets/css/pro.css' rel='stylesheet'>
</head>
<body class='auth-bg d-flex align-items-center justify-content-center vh-100'>

<div class='card card-glass p-4' style='width:380px;'>
  <h4 class='mb-1 fw-semibold'>Welcome back</h4>
  <p class='text-muted mb-3'>Sign in to your dashboard</p>

  <?php if(isset($error)): ?>
    <div class='alert alert-danger py-2'><?=$error?></div>
  <?php endif; ?>

  <form method='post' class='vstack gap-3'>
    <div>
      <label class='form-label'>Email</label>
      <input type='email' name='email' class='form-control form-control-lg' required>
    </div>
    <div>
      <label class='form-label'>Password</label>
      <input type='password' name='password' class='form-control form-control-lg' required>
    </div>
    <button class='btn btn-primary btn-lg w-100' name='login' value='1'>Sign in</button>
  </form>
</div>

<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>
