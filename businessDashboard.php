<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: businessDasboard.php");
    exit;
}

// Allow only Business_Owner
if ($_SESSION['role'] !== 'Business_Owner') {
    echo "<h1 style='color:red;text-align:center;margin-top:20%'>❌ Access Denied: Only Business Owners can view this dashboard.</h1>";
    exit;
}

$host = "localhost";
$user = "root";
$pass = "";
$db   = "marketing_dashboard";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT user_id, name, role FROM users WHERE user_id=$user_id AND role='Business_Owner' LIMIT 1";
$res = $conn->query($sql);
$user = $res->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Business Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-900 text-gray-200">
<div class="flex h-screen">

    <!-- Sidebar -->
    <aside class="w-64 bg-gray-800 p-4">
        <h2 class="text-xl font-bold mb-4 text-green-400">
            <?php echo htmlspecialchars($user['name']); ?>'s Dashboard
        </h2>
        <p class="mb-6 text-sm text-gray-400">Role: <?php echo $user['role']; ?></p>
        <nav class="space-y-2">
            <a href="#" class="block p-2 rounded hover:bg-gray-700">📊 Dashboard</a>
            <a href="#" class="block p-2 rounded hover:bg-gray-700">📈 Campaign Analytics</a>
            <a href="#" class="block p-2 rounded hover:bg-gray-700">👥 Customer Behavior</a>
            <a href="#" class="block p-2 rounded hover:bg-gray-700">📋 Pipeline Management</a>
            <a href="#" class="block p-2 rounded hover:bg-gray-700">🔔 Notifications</a>
            <a href="logout.php" class="block p-2 rounded hover:bg-red-600">🚪 Logout</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-6 overflow-y-auto">
        <h1 class="text-3xl font-bold mb-6">
            Welcome, <?php echo htmlspecialchars($user['name']); ?> 👋
        </h1>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-gray-800 p-4 rounded shadow">
                <h3 class="text-lg font-semibold mb-2">Marketing Campaign Analytics</h3>
                <canvas id="campaignChart" height="150"></canvas>
            </div>
            <div class="bg-gray-800 p-4 rounded shadow">
                <h3 class="text-lg font-semibold mb-2">Pipeline Management</h3>
                <canvas id="pipelineChart" height="150"></canvas>
            </div>
        </div>
    </main>
</div>

<script>
// Example chart data
const ctx1 = document.getElementById('campaignChart');
new Chart(ctx1, {
    type: 'bar',
    data: {
        labels: ['Campaign A', 'Campaign B', 'Campaign C'],
        datasets: [{
            label: 'Conversions',
            data: [120, 90, 150],
            backgroundColor: ['#10B981','#3B82F6','#F59E0B']
        }]
    },
    options: { responsive: true }
});

const ctx2 = document.getElementById('pipelineChart');
new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: ['Prospects','Qualified','Won','Lost'],
        datasets: [{
            data: [40, 25, 20, 15],
            backgroundColor: ['#3B82F6','#10B981','#F59E0B','#EF4444']
        }]
    },
    options: { responsive: true }
});
</script>
</body>
</html>
