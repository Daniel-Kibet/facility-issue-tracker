<?php
require_once 'db.php'; // Database connection file

// Function to fetch all issues
function fetchIssues($pdo) {
    $stmt = $pdo->query("
        SELECT i.*, f.name as facility_name, f.mfl_code 
        FROM issues i 
        LEFT JOIN facilities f ON i.facility_id = f.id 
        ORDER BY i.request_date DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch all issues
$issues = fetchIssues($pdo);

// Calculate summary metrics
$totalIssues = count($issues);
$resolvedIssues = array_filter($issues, fn($issue) => $issue['status'] === 'Resolved');
$pendingIssues = array_filter($issues, fn($issue) => $issue['status'] === 'Pending');
$inProgressIssues = array_filter($issues, fn($issue) => $issue['status'] === 'In Progress');
$resolvedCount = count($resolvedIssues);
$pendingCount = count($pendingIssues);
$inProgressCount = count($inProgressIssues);
$resolutionRate = $totalIssues > 0 ? round(($resolvedCount / $totalIssues) * 100, 2) : 0;

// Get trend data (last 5 months)
$trendData = [];
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May']; // Use fixed months for demo
$createdData = [12, 19, 15, 17, 14]; // Sample data for created issues
$resolvedData = [7, 11, 13, 14, 12]; // Sample data for resolved issues

// In a real implementation, you would calculate this from your database
for ($i = 0; $i < 5; $i++) {
    $trendData[] = [
        'month' => $months[$i],
        'created' => $createdData[$i],
        'resolved' => $resolvedData[$i]
    ];
}

// Fetch issue types (for future implementation)
$issueTypes = ['Login Issues', 'Claims and Payment Issues', 'Resubmission Issues', 'Portal/System Functionality', 'Credential/Access Requests'];
$issueTypeCounts = [9, 8, 3, 4, 5];

// Fetch issues by day (for future implementation)
$dailyData = [
    ['date' => 'Mon', 'count' => 5],
    ['date' => 'Tue', 'count' => 8],
    ['date' => 'Wed', 'count' => 12],
    ['date' => 'Thu', 'count' => 7],
    ['date' => 'Fri', 'count' => 10],
    ['date' => 'Sat', 'count' => 3],
    ['date' => 'Sun', 'count' => 2]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Tracker Dashboard - <?php echo date('F j, Y'); ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --info: #4895ef;
            --warning: #f72585;
            --danger: #e63946;
            --light: #f8f9fa;
            --dark: #212529;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7ff;
            color: #333;
        }
        
        .dashboard {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .header p {
            opacity: 0.8;
        }
        
        /* Main content */
        .content {
            padding: 1.5rem;
            flex-grow: 1;
        }
        
        /* Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card h3 {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        
        .stat-card .value {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: var(--primary);
        }
        
        .stat-card .icon {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            opacity: 0.2;
        }
        
        /* Charts */
        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .chart-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .chart-card h3 {
            margin-bottom: 1rem;
            color: var(--dark);
            font-size: 1.25rem;
        }
        
        .chart-container {
            height: 350px;
            position: relative;
        }
        
        /* Table */
        .table-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            overflow-x: auto;
            margin-top: 1.5rem;
        }
        
        .table-container h3 {
            margin-bottom: 1rem;
            color: var(--dark);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
        }
        
        th {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            font-weight: 600;
        }
        
        tr {
            border-bottom: 1px solid #f1f1f1;
        }
        
        tr:last-child {
            border-bottom: none;
        }
        
        tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        /* Status badges */
        .badge {
            padding: 0.35rem 0.65rem;
            border-radius: 50rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-pending {
            background-color: rgba(247, 37, 133, 0.1);
            color: var(--warning);
        }
        
        .badge-progress {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
        }
        
        .badge-resolved {
            background-color: rgba(76, 201, 240, 0.1);
            color: var(--success);
        }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 0.75rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            text-decoration: none;
            margin-right: 0.5rem;
        }
        
        .btn i {
            margin-right: 0.25rem;
        }
        
        .btn-view {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
        }
        
        .btn-view:hover {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-delete {
            background-color: rgba(230, 57, 70, 0.1);
            color: var(--danger);
        }
        
        .btn-delete:hover {
            background-color: var(--danger);
            color: white;
        }
        
        /* Additional Charts */
        .additional-charts {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .additional-chart-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .additional-chart-card h3 {
            margin-bottom: 1rem;
            color: var(--dark);
            font-size: 1.25rem;
        }
        
        /* Footer */
        .footer {
            background-color: var(--light);
            padding: 1rem;
            text-align: center;
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Header Section -->
        <header class="header">
            <h1>Issue Tracker Dashboard</h1>
            <p>Comprehensive overview and analysis - Updated <?php echo date('F j, Y'); ?></p>
        </header>
        
        <!-- Main Content -->
        <main class="content">
            <!-- Stats Cards -->
            <section class="stats-container">
                <div class="stat-card">
                    <h3>Total Issues</h3>
                    <div class="value" id="total-issues"><?php echo $totalIssues; ?></div>
                    <i class="fas fa-ticket-alt icon"></i>
                </div>
                
                <div class="stat-card">
                    <h3>Pending Issues</h3>
                    <div class="value" id="pending-issues"><?php echo $pendingCount; ?></div>
                    <i class="fas fa-clock icon"></i>
                </div>
                
                <div class="stat-card">
                    <h3>In Progress</h3>
                    <div class="value" id="in-progress-issues"><?php echo $inProgressCount; ?></div>
                    <i class="fas fa-spinner icon"></i>
                </div>
                
                <div class="stat-card">
                    <h3>Resolved Issues</h3>
                    <div class="value" id="resolved-issues"><?php echo $resolvedCount; ?></div>
                    <i class="fas fa-check-circle icon"></i>
                </div>
                
                <div class="stat-card">
                    <h3>Resolution Rate</h3>
                    <div class="value" id="resolution-rate"><?php echo $resolutionRate; ?>%</div>
                    <i class="fas fa-chart-line icon"></i>
                </div>
            </section>
            
            <!-- Charts Section -->
            <section class="charts-container">
                <div class="chart-card">
                    <h3>Issues by Status</h3>
                    <div class="chart-container">
                        <canvas id="status-chart"></canvas>
                    </div>
                </div>
                
                <div class="chart-card">
                    <h3>Resolution Trend</h3>
                    <div class="chart-container">
                        <canvas id="trend-chart"></canvas>
                    </div>
                </div>
            </section>
            
            <!-- Additional Charts -->
            <section class="additional-charts">
                <div class="additional-chart-card">
                    <h3>Issues by Type</h3>
                    <div class="chart-container">
                        <canvas id="type-chart"></canvas>
                    </div>
                </div>
                
                <div class="additional-chart-card">
                    <h3>Issues per Day</h3>
                    <div class="chart-container">
                        <canvas id="daily-chart"></canvas>
                    </div>
                </div>
            </section>
            
            <!-- Table Section -->
            <section class="table-container">
                <h3>All Issues</h3>
                <table id="issues-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Facility</th>
                            <th>Code</th>
                            <th>Issue Type</th>
                            <th>Email</th>
                            <th>Request Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($issues as $issue): ?>
                            <tr>
                                <td><?php echo $issue['id']; ?></td>
                                <td><?php echo $issue['facility_name']; ?></td>
                                <td><?php echo $issue['mfl_code']; ?></td>
                                <td><?php echo $issue['issue_type']; ?></td>
                                <td><?php echo $issue['requester_email']; ?></td>
                                <td><?php echo date('M j, Y', strtotime($issue['request_date'])); ?></td>
                                <td>
                                    <?php if ($issue['status'] === 'Resolved'): ?>
                                        <span class="badge badge-resolved">Resolved</span>
                                    <?php elseif ($issue['status'] === 'Pending'): ?>
                                        <span class="badge badge-pending">Pending</span>
                                    <?php elseif ($issue['status'] === 'In Progress'): ?>
                                        <span class="badge badge-progress">In Progress</span>
                                    <?php else: ?>
                                        <?php echo $issue['status']; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="view_issue.php?id=<?php echo $issue['id']; ?>" class="btn btn-view">
                                        <i class="fas fa-eye"></i> View
                                        
                                    </a>
                                    <form action="delete_issue.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this issue?');">
                                        <input type="hidden" name="id" value="<?php echo $issue['id']; ?>">
                                        <button type="submit" class="btn btn-delete">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </main>
        
        <!-- Footer -->
        <footer class="footer">
            <p>&copy; 2025 Issue Tracker System. All rights reserved.</p>
        </footer>
    </div>
    
    <script>
        // Get the PHP data for charts
        const issuesData = <?php echo json_encode($issues); ?>;
        
        // Chart.js Configuration
        Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
        Chart.defaults.color = "#555";
        
        // Status Doughnut Chart (matching the image style)
        const statusCtx = document.getElementById('status-chart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Resolved', 'Pending', 'In Progress'],
                datasets: [{
                    data: [<?php echo $resolvedCount; ?>, <?php echo $pendingCount; ?>, <?php echo $inProgressCount; ?>],
                    backgroundColor: [
                        '#4cc9f0', // Light blue for Resolved
                        '#f72585', // Pink for Pending
                        '#4361ee'  // Blue for In Progress
                    ],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                layout: {
                    padding: 20
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                size: 12
                            }
                        }
                    },
                    title: {
                        display: false
                    }
                }
            }
        });
        
        // Trend Line Chart (matching the image style)
        const trendCtx = document.getElementById('trend-chart').getContext('2d');
        const trendChart = new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($trendData, 'month')); ?>,
                datasets: [{
                    label: 'Issues Created',
                    data: <?php echo json_encode(array_column($trendData, 'created')); ?>,
                    borderColor: '#f72585', // Pink
                    backgroundColor: 'rgba(247, 37, 133, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#f72585'
                }, {
                    label: 'Issues Resolved',
                    data: <?php echo json_encode(array_column($trendData, 'resolved')); ?>,
                    borderColor: '#4cc9f0', // Light blue
                    backgroundColor: 'rgba(76, 201, 240, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#4cc9f0'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });
        
        // Issue Type Bar Chart
        const typeCtx = document.getElementById('type-chart').getContext('2d');
        const typeChart = new Chart(typeCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($issueTypes); ?>,
                datasets: [{
                    label: 'Issues by Type',
                    data: <?php echo json_encode($issueTypeCounts); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
        
        // Daily Issues Line Chart
        const dailyCtx = document.getElementById('daily-chart').getContext('2d');
        const dailyChart = new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($dailyData, 'date')); ?>,
                datasets: [{
                    label: 'Issues per Day',
                    data: <?php echo json_encode(array_column($dailyData, 'count')); ?>,
                    fill: false,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    tension: 0.1,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>
</body>
<a href="javascript:history.back()" style="display:inline-block; margin:10px; padding:10px 15px; background-color:#007bff; color:white; text-decoration:none; border-radius:5px;">← Back</a>

</html>