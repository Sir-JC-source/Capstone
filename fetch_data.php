<?php
header('Content-Type: application/json');

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db   = "marketing_dashboard";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode(['error' => $conn->connect_error]));
}

// KPI Data
$campaigns = $conn->query("SELECT COUNT(*) AS total_campaigns FROM campaigns")->fetch_assoc()['total_campaigns'];
$leads     = $conn->query("SELECT COUNT(*) AS total_leads FROM pipeline")->fetch_assoc()['total_leads'];
$engagement= $conn->query("SELECT COUNT(*) AS total_engagement FROM customer_behavior")->fetch_assoc()['total_engagement'];

// Campaign Performance
$campaignRes = $conn->query("SELECT name, COUNT(cb.id) AS engagement 
                             FROM campaigns c 
                             LEFT JOIN customer_behavior cb ON c.id = cb.campaign_id 
                             GROUP BY c.id");
$campaignLabels = [];
$campaignData = [];
while($row = $campaignRes->fetch_assoc()){
    $campaignLabels[] = $row['name'];
    $campaignData[] = $row['engagement'];
}

// Pipeline Stages
$pipelineRes = $conn->query("SELECT stage, COUNT(*) AS count FROM pipeline GROUP BY stage");
$pipelineLabels = [];
$pipelineData = [];
while($row = $pipelineRes->fetch_assoc()){
    $pipelineLabels[] = $row['stage'];
    $pipelineData[] = $row['count'];
}

// Return JSON
echo json_encode([
    'kpis' => [
        'campaigns' => $campaigns,
        'leads' => $leads,
        'engagement' => $engagement
    ],
    'campaignChart' => [
        'labels' => $campaignLabels,
        'data' => $campaignData
    ],
    'pipelineChart' => [
        'labels' => $pipelineLabels,
        'data' => $pipelineData
    ]
]);
