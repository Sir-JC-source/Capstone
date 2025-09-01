<?php
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}
$admin_id = intval($_SESSION['user_id']);

// === Handle Approve/Decline POST actions ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    // CSRF note: Add token check in production
    $action_type = $_POST['action_type']; // 'account' or 'offer'
    $action      = $_POST['action'];      // 'approve' or 'decline'
    $id          = intval($_POST['id']);
    $comment     = $conn->real_escape_string(trim($_POST['comment'] ?? ''));

    if ($action_type === 'account' && $id > 0) {
        if ($action === 'approve') {
            $stmt = $conn->prepare("UPDATE users SET status='active' WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute(); $stmt->close();
        } else {
            $stmt = $conn->prepare("UPDATE users SET status='rejected' WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute(); $stmt->close();
        }
        $stmt = $conn->prepare("INSERT INTO action_logs (user_id, action, comment, admin_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issi", $id, $action, $comment, $admin_id);
        $stmt->execute(); $stmt->close();
    }

    if ($action_type === 'offer' && $id > 0) {
        if ($action === 'approve') {
            $stmt = $conn->prepare("UPDATE offers SET status='active' WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute(); $stmt->close();
        } else {
            $stmt = $conn->prepare("UPDATE offers SET status='rejected' WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute(); $stmt->close();
        }
        $stmt = $conn->prepare("INSERT INTO action_logs (offer_id, action, comment, admin_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issi", $id, $action, $comment, $admin_id);
        $stmt->execute(); $stmt->close();
    }

    // redirect back to avoid form re-submission
    header("Location: dashboard.php");
    exit;
}

// === Fetch metrics ===
$total_users = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'] ?? 0;
$total_owners = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='Business_Owner'")->fetch_assoc()['c'] ?? 0;
$pending_accounts_cnt = $conn->query("SELECT COUNT(*) as c FROM users WHERE status='pending'")->fetch_assoc()['c'] ?? 0;
$total_offers = $conn->query("SELECT COUNT(*) as c FROM offers")->fetch_assoc()['c'] ?? 0;

// === lists ===
$pending_accounts = $conn->query("SELECT id, name, email, role, created_at FROM users WHERE status='pending' ORDER BY created_at DESC LIMIT 50");
$pending_offers = $conn->query("SELECT o.id,o.title,o.description,o.created_at,u.name AS owner_name FROM offers o JOIN users u ON u.id = o.owner_id WHERE o.status='pending' ORDER BY o.created_at DESC LIMIT 50");

// Fetch pending accounts (Marketing Manager & Analytics only)
$pendingSql = "SELECT user_id, name, email, role, status, created_at 
               FROM users 
               WHERE status='Pending' 
               AND role IN ('Marketing_Manager','Analytics')";
$pendingRes = $conn->query($pendingSql);

// ✅ bilangin Pending Accounts
$pendingAccounts = $conn->query("SELECT COUNT(*) as total FROM users WHERE status='Pending'")->fetch_assoc()['total'];

// ✅ bilangin Pending Offers
$pendingOffers = $conn->query("SELECT COUNT(*) as total FROM offers WHERE status='Pending'")->fetch_assoc()['total'];

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin Dashboard</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/pro.css" rel="stylesheet">
</head>
<body>
  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="brand">
      <div class="d-flex align-items-center">
        <i class="bi bi-graph-up fs-3 text-primary me-2"></i>
        <div>
          <div class="fw-semibold"><h2>Admin Dashboard</h2></div>
          <small class="text-muted"><h3 style="color:#fff">Admin Panel</h3></small>
        </div>
      </div>
    </div>

    <nav class="nav flex-column mt-3 px-2">
      <a class="nav-link active" href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
      <a class="nav-link" href="admin/pending_accounts.php"><i class="bi bi-person-plus me-2"></i>Pending Accounts</a>
      <a class="nav-link" href="admin/pending_offers.php"><i class="bi bi-bag-check me-2"></i>Pending Offers</a>
      <a class="nav-link" href="admin/create_offer.php"><i class="bi bi-plus-circle me-2"></i>Create Offer</a>
      <a class="nav-link" href="admin/create_post.php"><i class="bi bi-megaphone me-2"></i>Create Post</a>
      <a class="nav-link" href="admin/notifications.php"><i class="bi bi-bell me-2"></i>Notifications</a>
      <a class="nav-link" href="admin/logs.php"><i class="bi bi-card-list me-2"></i>Logs</a>
    </nav>

    <div class="mt-auto px-3 pb-3">
      <a href="logout.php" class="btn btn-outline-danger w-100"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
    </div>
  </aside>

  <!-- Main -->
  <main class="main">
    <header class="topbar d-flex justify-content-between">
      <div class="ps-3"></br>
        <button class="btn btn-outline-light d-md-none" id="toggleSidebar"><i class="bi bi-list"></i></button>
        <span class="h5 mb-0">Dashboard</span>
      </div>

      <div class="d-flex align-items-center pe-3">
        <button class="btn btn-icon position-relative me-2" title="Notifications"><i class="bi bi-bell"></i>
          <?php if($pending_accounts_cnt>0): ?>
            <span class="badge bg-danger position-absolute translate-middle top-0 start-100"><?=$pending_accounts_cnt?></span>
          <?php endif; ?>
        </button>
        <div class="dropdown">
          <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle me-1"></i> Admin
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
          </ul>
        </div>
      </div>
    </header>
<br/>
    <section class="content">
      <div class="container-fluid">
        <div class="row g-3">
          <div class="col-12 col-sm-6 col-xl-3">
            <div class="card metric p-3">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <div class="label">Total Users</div>
                  <div class="value"><?=$total_users?></div>
                </div>
                <div class="icon-wrap"><i class="bi bi-people-fill"></i></div>
              </div>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-xl-3">
            <div class="card metric p-3">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <div class="label">Business Owners</div>
                  <div class="value"><?=$total_owners?></div>
                </div>
                <div class="icon-wrap"><i class="bi bi-person-badge-fill"></i></div>
              </div>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-xl-3">
            <div class="card metric p-3">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <div class="label">Pending Accounts</div>
                  <div class="value"><?=$pending_accounts_cnt?></div>
                </div>
                <div class="icon-wrap"><i class="bi bi-hourglass-split"></i></div>
              </div>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-xl-3">
            <div class="card metric p-3">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <div class="label">Total Offers</div>
                  <div class="value"><?=$total_offers?></div>
                </div>
                <div class="icon-wrap"><i class="bi bi-bag-fill"></i></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pending accounts table -->
        <div class="card mt-4">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Pending Owner Accounts</h6>
            <input class="form-control form-control-sm w-auto" id="filterAccounts" placeholder="Search...">
          </div>
          <div class="table-responsive">
            <table class="table table-dark mb-0" id="tblAccounts">
              <thead>
                <tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Action</th></tr>
              </thead>
              <tbody>
                <?php if($pending_accounts && $pending_accounts->num_rows): $i=0;
                  while($row = $pending_accounts->fetch_assoc()): $i++; ?>
                  <tr>
                    <td><?=$row['id']?></td>
                    <td><?=$row['name']?></td>
                    <td><?=$row['email']?></td>
                    <td><?=ucfirst($row['role'])?></td>
                    <td>
                      <!-- Approve form -->
                      <form method="post" style="display:inline;">
                        <input type="hidden" name="action_type" value="account">
                        <input type="hidden" name="id" value="<?=$row['id']?>">
                        <input type="hidden" name="action" value="approve">
                        <button class="btn btn-success btn-sm">Approve</button>
                      </form>

                      <!-- Decline triggers modal -->
                      <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#declineAccount<?=$row['id']?>">Decline</button>

                      <!-- Decline Modal -->
                      <div class="modal fade" id="declineAccount<?=$row['id']?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                          <div class="modal-content bg-dark text-white">
                            <div class="modal-header">
                              <h5 class="modal-title">Decline Account - <?=$row['name']?></h5>
                              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="post">
                              <div class="modal-body">
                                <input type="hidden" name="action_type" value="account">
                                <input type="hidden" name="id" value="<?=$row['id']?>">
                                <input type="hidden" name="action" value="decline">
                                <div class="mb-3">
                                  <label class="form-label">Reason / Checklist</label>
                                  <textarea name="comment" class="form-control" rows="4" required></textarea>
                                </div>
                              </div>
                              <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger">Decline Account</button>
                              </div>
                            </form>
                          </div>
                        </div>
                      </div>
                    </td>
                  </tr>
                <?php endwhile; else: ?>
                  <tr><td colspan="5" class="text-center text-muted">No pending accounts</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Pending offers table -->
        <div class="card mt-4">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Pending Offers</h6>
            <input class="form-control form-control-sm w-auto" id="filterOffers" placeholder="Search...">
          </div>
          <div class="table-responsive">
            <table class="table table-dark mb-0" id="tblOffers">
              <thead>
                <tr><th>#</th><th>Title</th><th>Owner</th><th>Created</th><th>Action</th></tr>
              </thead>
              <tbody>
                <?php if($pending_offers && $pending_offers->num_rows): while($r = $pending_offers->fetch_assoc()): ?>
                  <tr>
                    <td><?=$r['id']?></td>
                    <td><?=$r['title']?></td>
                    <td><?=$r['owner_name']?></td>
                    <td><?=$r['created_at']?></td>
                    <td>
                      <form method="post" style="display:inline;">
                        <input type="hidden" name="action_type" value="offer">
                        <input type="hidden" name="id" value="<?=$r['id']?>">
                        <input type="hidden" name="action" value="approve">
                        <button class="btn btn-success btn-sm">Approve</button>
                      </form>

                      <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#declineOffer<?=$r['id']?>">Decline</button>

                      <!-- Decline Offer Modal -->
                      <div class="modal fade" id="declineOffer<?=$r['id']?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                          <div class="modal-content bg-dark text-white">
                            <div class="modal-header">
                              <h5 class="modal-title">Decline Offer - <?=htmlspecialchars($r['title'])?></h5>
                              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="post">
                              <div class="modal-body">
                                <input type="hidden" name="action_type" value="offer">
                                <input type="hidden" name="id" value="<?=$r['id']?>">
                                <input type="hidden" name="action" value="decline">
                                <div class="mb-3">
                                  <label class="form-label">Reason / Comment</label>
                                  <textarea name="comment" class="form-control" rows="4" required></textarea>
                                </div>
                              </div>
                              <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger">Decline Offer</button>
                              </div>
                            </form>
                          </div>
                        </div>
                      </div>

                    </td>
                  </tr>
                <?php endwhile; else: ?>
                  <tr><td colspan="5" class="text-center text-muted">No pending offers</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div> <!-- /.container -->
    </section>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Sidebar toggle for mobile
    document.getElementById('toggleSidebar')?.addEventListener('click', function(){
      document.querySelector('.sidebar')?.classList.toggle('open');
    });

    // Simple client-side table filters
    function simpleFilter(inputId, tableId) {
      const input = document.getElementById(inputId);
      const table = document.getElementById(tableId);
      if(!input || !table) return;
      input.addEventListener('keyup', () => {
        const q = input.value.toLowerCase();
        table.querySelectorAll('tbody tr').forEach(tr => {
          tr.style.display = tr.innerText.toLowerCase().includes(q) ? '' : 'none';
        });
      });
    }
    simpleFilter('filterAccounts','tblAccounts');
    simpleFilter('filterOffers','tblOffers');
  </script>
</body>
</html>
