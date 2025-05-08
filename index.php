<?php
require_once 'db.php';

// Get all issues from issues table (with LEFT JOIN for facility data)
$issuesStmt = $pdo->query("SELECT i.*, f.name as facility_name, f.mfl_code FROM issues i 
                          LEFT JOIN facilities f ON i.facility_id = f.id 
                          ORDER BY i.request_date DESC");
$issues = $issuesStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch facilities without any issues (optional: to show imported-only data)
$unlinkedFacilitiesStmt = $pdo->query("SELECT f.id, f.name as facility_name, f.mfl_code FROM facilities f
                                       LEFT JOIN issues i ON f.id = i.facility_id
                                       WHERE i.id IS NULL");
$unlinkedFacilities = $unlinkedFacilitiesStmt->fetchAll(PDO::FETCH_ASSOC);

// Merge facilities with no issues into the main issue list for display
foreach ($unlinkedFacilities as $facility) {
    $issues[] = [
        'id' => '',
        'facility_name' => $facility['facility_name'],
        'codes' => $facility['mfl_code'],
        'issue_type' => '',
        'description' => '',
        'requester' => '',
        'request_date' => '',
        'status' => 'No Issues'
    ];
}

// Categorize issues by status
$resolvedIssues = array_filter($issues, fn($issue) => $issue['status'] === 'Resolved');
$pendingIssues = array_filter($issues, fn($issue) => $issue['status'] === 'Pending');
$inProgressIssues = array_filter($issues, fn($issue) => $issue['status'] === 'In Progress');

// Count issues
$issueCount = count(array_filter($issues, fn($i) => $i['status'] !== 'No Issues'));
$resolvedIssueCount = count($resolvedIssues);
$pendingCount = count($pendingIssues);
$inProgressCount = count($inProgressIssues);

// Facility counts
$facilityCount = 0;
$resolvedFacilityCount = 0;
try {
    $facilityCount = $pdo->query("SELECT COUNT(*) FROM facilities")->fetchColumn();
    $resolvedFacilityCount = $pdo->query("SELECT COUNT(*) FROM facilities WHERE status = 'Resolved'")->fetchColumn();
} catch (Exception $e) {
    $facilityCount = 0;
    $resolvedFacilityCount = 0;
}

// Combined counts
$totalCount = $issueCount + $facilityCount;
$resolvedCount = $resolvedIssueCount + $resolvedFacilityCount;

// Resolution rate
$resolutionRate = $totalCount > 0 ? round(($resolvedCount / $totalCount) * 100, 2) : 0;
?>
    

    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facility Issue Tracker</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --dark-color: #34495e;
            --light-color: #ecf0f1;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            position: relative;
        }
        
        /* Video Background */
        #bg-video {
            position: fixed;
            right: 0;
            bottom: 0;
            min-width: 100%;
            min-height: 100%;
            z-index: -1;
            filter: blur(5px);
        }
        
        /* Or Image Background */
        .bg-image {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('background.jpg');
            background-size: cover;
            background-position: center;
            z-index: -1;
            filter: blur(5px);
        }
        
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.3);
            z-index: -1;
        }
        
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
        }
        
        h1 {
            color: var(--dark-color);
            font-size: 28px;
        }
        
        /* Summary Cards */
        .summary-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .summary-card {
            flex: 1;
            min-width: 200px;
            background-color: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .summary-card:hover {
            transform: translateY(-5px);
        }
        
        .summary-card.total {
            border-left: 5px solid var(--primary-color);
        }
        
        .summary-card.pending {
            border-left: 5px solid var(--warning-color);
        }
        
        .summary-card.in-progress {
            border-left: 5px solid var(--dark-color);
        }
        
        .summary-card.resolved {
            border-left: 5px solid var(--secondary-color);
        }
        
        .summary-card.resolution-rate {
            border-left: 5px solid var(--danger-color);
        }
        
        .summary-card h3 {
            font-size: 16px;
            margin-bottom: 10px;
            color: #555;
        }
        
        .summary-card .count {
            font-size: 24px;
            font-weight: bold;
            color: var(--dark-color);
        }
        
        /* Navigation Tabs */
        .tabs-container {
            margin: 20px 0;
        }
        
        .tabs {
            display: flex;
            border-bottom: 2px solid #ddd;
            overflow-x: auto;
            scrollbar-width: thin;
        }
        
        .tab {
            padding: 12px 24px;
            cursor: pointer;
            background-color: #f5f5f5;
            margin-right: 5px;
            border-radius: 5px 5px 0 0;
            font-weight: 600;
            color: #777;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        
        .tab:hover {
            background-color: #e9e9e9;
        }
        
        .tab.active {
            background-color: var(--primary-color);
            color: white;
            border-bottom: 3px solid var(--primary-color);
        }
        
        .tab-content {
            display: none;
            padding: 20px 0;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* Table Styling */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
            display: block;
        }
        
        .data-table th, 
        .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .data-table th {
            background-color: var(--dark-color);
            color: white;
            position: sticky;
            top: 0;
        }
        
        .data-table tbody tr:hover {
            background-color: #f5f5f5;
        }
        
        .data-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .data-table1{
            max-width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
            display: block;

        }
        .data-table1 th, 
        .data-table1 td {
            padding: 12px 7px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .data-table1 th {
            background-color: var(--dark-color);
            color: white;
            position: sticky;
            top: 0;
        }
        
        .data-table1 tbody tr:hover {
            background-color: #f5f5f5;
        }
        
        .data-table1 tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        /* Status Styling */
        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            display: inline-block;
            text-align: center;
            min-width: 100px;
        }
        
        .status-pending {
            background-color: #ffeaa7;
            color: #f39c12;
        }
        
        .status-in-progress {
            background-color: #a0cfff;
            color: #3498db;
        }
        
        .status-resolved {
            background-color: #c4e6c3;
            color: #27ae60;
        }
        
        /* Buttons */
        .btn {
            display: inline-block;
            padding: 10px 15px;
            font-size: 14px;
            cursor: pointer;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            transition: background-color 0.3s, transform 0.2s;
            font-weight: 600;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-success {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .btn-view {
            background-color: var(--dark-color);
            color: white;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        /* Search and Filter */
        .search-filter {
            display: flex;
            margin-bottom: 20px;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .search-filter input,
        .search-filter select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .search-filter input {
            flex-grow: 1;
            min-width: 200px;
        }


        
        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 10px;
                margin: 10px;
            }
            
            .summary-cards {
                flex-direction: column;
            }
            
            .tab {
                padding: 10px 15px;
            }
            
            .data-table th, 
            .data-table td {
                padding: 8px;
                font-size: 14px;
            }
            
            .search-filter {
                flex-direction: column;
            }
        }
        
        /* Footer */
        footer {
            margin-top: 20px;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
        
        /* Additional Utilities */
        .text-center {
            text-align: center;
        }
        
        .mb-20 {
            margin-bottom: 20px;
        }
        
        .description-truncate {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>
<body>
    <!-- Content Goes Here -->
</body>
</html>

</head>
<body>
    <!-- Video Background (uncomment to use) -->
    <!-- 
    <video autoplay muted loop id="bg-video">
        <source src="background.mp4" type="video/mp4">
    </video>
    -->
    
    <!-- Image Background (comment out if using video) -->
    <div class="bg-image"></div>
    <div class="overlay"></div>
    
    <div class="container">

        <!-- ✅ Zoom Buttons -->
        <div style="text-align: right; margin-bottom: 10px;">
            <button onclick="zoomIn()">Zoom In</button>
            <button onclick="zoomOut()">Zoom Out</button>
            <button onclick="resetZoom()">Reset</button>
        </div>

        <header>
            <h1><i class="fas fa-tasks"></i> Issue Tracker</h1>
            <div>
                <a href="add_issue.php" class="btn btn-success"><i class="fas fa-plus"></i> Add New Issue</a>
                <a href="export_issues.php" class="btn btn-success">Export Issues</a>
                <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
            </div>
        </header>

        <!-- Summary Cards Section -->
        <div class="summary-cards">
            <div class="summary-card total">
                <h3>Total Issues</h3>
                <div class="count"><?= $totalCount ?></div>
            </div>
            <div class="summary-card pending">
                <h3>Pending</h3>
                <div class="count"><?= $pendingCount ?></div>
            </div>
            <div class="summary-card in-progress">
                <h3>In Progress</h3>
                <div class="count"><?= $inProgressCount ?></div>
            </div>
            <div class="summary-card resolved">
                <h3>Resolved</h3>
                <div class="count"><?= $resolvedCount ?></div>
            </div>
            <div class="summary-card resolution-rate">
                <h3>Resolution Rate</h3>
                <div class="count"><?= $resolutionRate ?>%</div>
            </div>
        </div>

    </div>

    <!-- ✅ Zoom Script -->
    <script>
        let zoomLevel = 1;

        function zoomIn() {
            zoomLevel += 0.1;
            document.body.style.transform = `scale(${zoomLevel})`;
            document.body.style.transformOrigin = 'top left';
        }

        function zoomOut() {
            if (zoomLevel > 0.2) {
                zoomLevel -= 0.1;
                document.body.style.transform = `scale(${zoomLevel})`;
                document.body.style.transformOrigin = 'top left';
            }
        }

        function resetZoom() {
            zoomLevel = 1;
            document.body.style.transform = 'scale(1)';
        }
    </script>
</body>

        
 <!-- Import Form -->
<button onclick="window.location.href='facilities.php'" class="btn btn-primary">
    <i class="fas fa-file-import"></i> Import Facilities
</button>

<!-- Export JSON Button -->
<button onclick="window.location.href='export_json.php'" class="btn btn-success">
    <i class="fas fa-file-export"></i> Export JSON
</button>


        
        <!-- Tab Navigation -->
        <div class="tabs-container">
    <div class="tabs">
        <div class="tab active" data-tab="all">
            <i class="fas fa-list"></i> All Issues
        </div>
        <div class="tab" data-tab="pending">
            <i class="fas fa-clock"></i> Pending
        </div>
        <div class="tab" data-tab="in-progress">
            <i class="fas fa-spinner"></i> In Progress
        </div>
        <div class="tab" data-tab="resolved">
            <i class="fas fa-check-circle"></i> Resolved
        </div>
        <div class="tab" data-tab="summary">
            <i class="fas fa-chart-pie"></i> Summary
        </div>
       
    </div>
</div>

   <!-- Search & Filter (for each tab content) -->
   <div class="search-filter">
    <!-- Search Input with Button -->
    <div class="search-container" style="display: flex; align-items: center; gap: 5px;">
        <input type="text" id="search-input" placeholder="Search issues..." 
            style="padding: 6px 10px; border-radius: 4px; border: 1px solid #ccc; width: 200px; font-size: 14px;">
        <button id="search-button" 
            style="padding: 6px 10px; background-color: #007BFF; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">
            <i class="fas fa-search"></i> Search
        </button>
    </div>
</div>


    <!-- Date Range Filter (Start Date and End Date) -->
    <input type="date" id="filter-start-date" placeholder="Start Date">
    <input type="date" id="filter-end-date" placeholder="End Date">
</div>

    <!-- Facility Filter Dropdown -->
    <select id="filter-facility">
        <option value="">All Facilities</option>
        <!-- PHP could populate facility options here -->
    </select>

           <!-- All Issues Tab Content -->
<div id="all" class="tab-content active">
    <h2><i class="fas fa-list"></i> All Issues</h2>
    <table class="data-table1">
        <thead>
            <tr>
                <th>ID</th>
                <th>Facility</th>
                <th>Codes</th>
                <th>Issue Type</th>
                <th>Email Address</th> <!-- ✅ Added Email Column -->
                <th>Request Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>   
        </thead>
        <tbody>
            <?php foreach ($issues as $issue): ?>
            <tr>
                <td><?= htmlspecialchars($issue['id']) ?></td>
                <td><?= htmlspecialchars($issue['facility_name'] ?? $issue['facility'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($issue['codes'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($issue['issue_type']) ?></td>
                <td><?= htmlspecialchars($issue['requester_email'] ?? 'N/A') ?></td> <!-- ✅ Display Email -->
                <td><?= htmlspecialchars($issue['request_date']) ?></td>
                <td>
                    <span class="status status-<?= strtolower(str_replace(' ', '-', $issue['status'])) ?>">
                        <?= htmlspecialchars($issue['status']) ?>
                    </span>
                                <td class="action-buttons">
    <a href="view_issue.php?id=<?= $issue['id'] ?>" class="btn btn-view">
        <i class="fas fa-eye"></i> View
    </a>
    <form action="delete_issue.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this issue?');">
        <input type="hidden" name="id" value="<?= $issue['id'] ?>">
        <button type="submit" class="btn btn-delete" style="background-color: red;">
            <i class="fas fa-trash"></i> Delete
        </button>
    </form>
</td>

                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pending Issues Tab Content -->
            <div id="pending" class="tab-content">
                <h2><i class="fas fa-clock"></i> Pending Issues</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Facility</th>
                            <th>Issue Type</th>
                            <th>Description</th>
                            <th>Requester</th>
                            <th>Request Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingIssues as $issue): ?>
                        <tr>
                            <td><?= htmlspecialchars($issue['id']) ?></td>
                           <td><?= htmlspecialchars($issue['facility_name'] ?? $issue['facility'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($issue['issue_type']) ?></td>
                            <td class="description-truncate" title="<?= htmlspecialchars($issue['description']) ?>">
                                <?= htmlspecialchars(substr($issue['description'], 0, 50)) ?>...
                            </td>
                            <td><?= htmlspecialchars($issue['requester']) ?></td>
                            <td><?= htmlspecialchars($issue['request_date']) ?></td>
                            <td>
                                <span class="status status-pending">
                                    <?= htmlspecialchars($issue['status']) ?>
                                </span>
                            </td>
                            <td class="action-buttons">
                                <a href="view_issue.php?id=<?= $issue['id'] ?>" class="btn btn-view">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- In Progress Issues Tab Content -->
            <div id="in-progress" class="tab-content">
                <h2><i class="fas fa-spinner"></i> In Progress Issues</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Facility</th>
                            <th>Issue Type</th>
                            <th>Description</th>
                            <th>Requester</th>
                            <th>Request Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inProgressIssues as $issue): ?>
                        <tr>
                            <td><?= htmlspecialchars($issue['id']) ?></td>
                            <td><?= htmlspecialchars($issue['facility_name'] ?? $issue['facility'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($issue['issue_type']) ?></td>
                            <td class="description-truncate" title="<?= htmlspecialchars($issue['description']) ?>">
                                <?= htmlspecialchars(substr($issue['description'], 0, 50)) ?>...
                            </td>
                            <td><?= htmlspecialchars($issue['requester']) ?></td>
                            <td><?= htmlspecialchars($issue['request_date']) ?></td>
                            <td>
                                <span class="status status-in-progress">
                                    <?= htmlspecialchars($issue['status']) ?>
                                </span>
                            </td>
                            <td class="action-buttons">
                                <a href="view_issue.php?id=<?= $issue['id'] ?>" class="btn btn-view">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Resolved Issues Tab Content -->
            <div id="resolved" class="tab-content">
                <h2><i class="fas fa-check-circle"></i> Resolved Issues</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Facility</th>
                            <th>Issue Type</th>
                            <th>Description</th>
                            <th>Requester</th>
                            <th>Request Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($resolvedIssues as $issue): ?>
    <tr>
        <td><?= htmlspecialchars($issue['id'] ?? 'N/A') ?></td>
        <td><?= htmlspecialchars($issue['facility_name'] ?? $issue['facility'] ?? 'N/A') ?></td>
        <td><?= htmlspecialchars($issue['issue_type'] ?? 'N/A') ?></td>
        <td class="description-truncate" title="<?= htmlspecialchars($issue['description'] ?? '') ?>">
            <?= htmlspecialchars(substr($issue['description'] ?? '', 0, 50)) ?>...
        </td>
        <td><?= htmlspecialchars($issue['requester'] ?? 'N/A') ?></td>
        <td><?= htmlspecialchars($issue['request_date'] ?? 'N/A') ?></td>
        <td>
            <span class="status status-resolved">
                <?= htmlspecialchars($issue['status'] ?? 'Resolved') ?>
            </span>
        </td>
        <td class="action-buttons">
            <a href="view_issue.php?id=<?= $issue['id'] ?>" class="btn btn-view">
                <i class="fas fa-eye"></i> View
            </a>
        </td>
    </tr>
<?php endforeach; ?>

                    </tbody>
                </table>
            </div>
            
            <!-- Summary Tab Content -->
            <div id="summary" class="tab-content">
                <h2><i class="fas fa-chart-pie"></i> Summary Dashboard</h2>
                
                <div class="summary-cards">
                    <div class="summary-card total">
                        <h3>Total Issues</h3>
                        <div class="count"><?= $totalCount ?></div>
                    </div>
                    <div class="summary-card pending">
                        <h3>Pending</h3>
                        <div class="count"><?= $pendingCount ?></div>
                    </div>
                    <div class="summary-card in-progress">
                        <h3>In Progress</h3>
                        <div class="count"><?= $inProgressCount ?></div>
                    </div>
                    <div class="summary-card resolved">
                        <h3>Resolved</h3>
                        <div class="count"><?= $resolvedCount ?></div>
                    </div>
                </div>
                
                <!-- Resolution Rate Bar -->
                <div style="margin: 30px 0; background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                    <h3>Resolution Rate: <?= $resolutionRate ?>%</h3>
                    <div style="background-color: #eee; height: 30px; border-radius: 15px; margin-top: 10px; overflow: hidden;">
                        <div style="background-color: var(--secondary-color); height: 100%; width: <?= $resolutionRate ?>%"></div>
                    </div>
                </div>
                
                <!-- Issues by Type Visualization (Placeholder) -->
                <div style="margin: 30px 0; display: flex; gap: 20px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 300px; background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                        <h3>Issues by Type</h3>
                        <div style="height: 200px; display: flex; align-items: flex-end; justify-content: space-around; margin-top: 20px;">
                            <!-- This would be replaced by actual data visualization -->
                            <div style="text-align: center;">
                                <div style="width: 40px; background-color: var(--primary-color); height: 120px;"></div>
                                <div>Type A</div>
                            </div>
                            <div style="text-align: center;">
                                <div style="width: 40px; background-color: var(--primary-color); height: 80px;"></div>
                                <div>Type B</div>
                            </div>
                            <div style="text-align: center;">
                                <div style="width: 40px; background-color: var(--primary-color); height: 150px;"></div>
                                <div>Type C</div>
                            </div>
                            <div style="text-align: center;">
                                <div style="width: 40px; background-color: var(--primary-color); height: 60px;"></div>
                                <div>Type D</div>
                            </div>
                        </div>
                    </div>
                    
                    <div style="flex: 1; min-width: 300px; background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                        <h3>Issues by Facility</h3>
                        <div style="height: 200px; display: flex; align-items: flex-end; justify-content: space-around; margin-top: 20px;">
                            <!-- This would be replaced by actual data visualization -->
                            <div style="text-align: center;">
                                <div style="width: 40px; background-color: var(--secondary-color); height: 90px;"></div>
                                <div>Facility A</div>
                            </div>
                            <div style="text-align: center;">
                                <div style="width: 40px; background-color: var(--secondary-color); height: 150px;"></div>
                                <div>Facility B</div>
                            </div>
                            <div style="text-align: center;">
                                <div style="width: 40px; background-color: var(--secondary-color); height: 70px;"></div>
                                <div>Facility C</div>
                            </div>
                            <div style="text-align: center;">
                                <div style="width: 40px; background-color: var(--secondary-color); height: 120px;"></div>
                                <div>Facility D</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div style="background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                    <h3>Recent Activity</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Facility</th>
                                <th>Issue Type</th>
                                <th>Status</th>
                                <th>Last Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Get the 5 most recent issues
                            $recentIssues = array_slice($issues, 0, 5);
                            foreach ($recentIssues as $issue): 
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($issue['id']) ?></td>
                                <td><?= htmlspecialchars($issue['facility_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($issue['issue_type']) ?></td>
                                <td>
                                    <span class="status status-<?= strtolower(str_replace(' ', '-', $issue['status'])) ?>">
                                        <?= htmlspecialchars($issue['status']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($issue['request_date']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <footer>
            <p>Facility Issue Tracker &copy; <?= date('Y') ?> | All Rights Reserved</p>
        </footer>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab Navigation
        const tabs = document.querySelectorAll('.tab');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));
                
                tab.classList.add('active');
                const tabId = tab.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
            });
        });

        // Search Functionality
        const searchInput = document.getElementById('search-input');
        const tableRows = document.querySelectorAll('.data-table tbody tr');
        
        searchInput.addEventListener('keyup', function() {
            const searchTerm = searchInput.value.toLowerCase();
            tableRows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                row.style.display = rowText.includes(searchTerm) ? '' : 'none';
            });
        });

        // Facility Filter and Issue Type Filter
        const facilityFilter = document.getElementById('filter-facility');
        const issueTypeFilter = document.getElementById('filter-issue-type');
        const applyFiltersBtn = document.getElementById('apply-filters');

        const facilities = new Set();
        const issueTypes = new Set();

        tableRows.forEach(row => {
            const facility = row.cells[1].textContent.trim();
            const issueType = row.cells[2].textContent.trim();
            if (facility) facilities.add(facility);
            if (issueType) issueTypes.add(issueType);
        });

        facilities.forEach(facility => {
            const option = document.createElement('option');
            option.value = facility;
            option.textContent = facility;
            facilityFilter.appendChild(option);
        });

        issueTypes.forEach(type => {
            const option = document.createElement('option');
            option.value = type;
            option.textContent = type;
            issueTypeFilter.appendChild(option);
        });

        applyFiltersBtn.addEventListener('click', function() {
            const selectedFacility = facilityFilter.value.toLowerCase();
            const selectedIssueType = issueTypeFilter.value.toLowerCase();
            const searchTerm = searchInput.value.toLowerCase();
            
            tableRows.forEach(row => {
                const facilityCell = row.cells[1].textContent.toLowerCase();
                const issueTypeCell = row.cells[2].textContent.toLowerCase();
                const rowText = row.textContent.toLowerCase();
                
                const facilityMatch = !selectedFacility || facilityCell.includes(selectedFacility);
                const issueTypeMatch = !selectedIssueType || issueTypeCell.includes(selectedIssueType);
                const searchMatch = !searchTerm || rowText.includes(searchTerm);
                
                row.style.display = (facilityMatch && issueTypeMatch && searchMatch) ? '' : 'none';
            });
        });

        // Tooltips for truncated descriptions
        const descriptions = document.querySelectorAll('.description-truncate');
        descriptions.forEach(desc => {
            desc.addEventListener('mouseover', function() {
                const tooltip = this.getAttribute('title');
                if (tooltip) {
                    const tooltipEl = document.createElement('div');
                    tooltipEl.className = 'tooltip';
                    tooltipEl.textContent = tooltip;
                    tooltipEl.style.position = 'absolute';
                    tooltipEl.style.backgroundColor = '#333';
                    tooltipEl.style.color = '#fff';
                    tooltipEl.style.padding = '5px 10px';
                    tooltipEl.style.borderRadius = '4px';
                    tooltipEl.style.zIndex = '100';
                    tooltipEl.style.maxWidth = '300px';
                    tooltipEl.style.top = (this.getBoundingClientRect().bottom + window.scrollY + 5) + 'px';
                    tooltipEl.style.left = this.getBoundingClientRect().left + 'px';

                    document.body.appendChild(tooltipEl);

                    this.addEventListener('mouseout', function() {
                        document.body.removeChild(tooltipEl);
                    });
                }
            });
        });

        // Video background controls
        const videoBackground = document.getElementById('bg-video');
        if (videoBackground) {
            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    videoBackground.pause();
                } else {
                    videoBackground.play();
                }
            });
        }

        // Quick Actions
        function setupQuickActions() {
            const quickActionBtns = document.querySelectorAll('.quick-action');
            quickActionBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const action = this.getAttribute('data-action');
                    const issueId = this.getAttribute('data-id');
                    
                    if (action === 'mark-in-progress') {
                        updateIssueStatus(issueId, 'In Progress');
                    } else if (action === 'mark-resolved') {
                        updateIssueStatus(issueId, 'Resolved');
                    }
                });
            });
        }

        function updateIssueStatus(issueId, newStatus) {
            console.log(`Updating issue ${issueId} to status: ${newStatus}`);
            // Uncomment and implement the backend functionality for updating status.
            /*
            fetch('update_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `issue_id=${issueId}&status=${newStatus}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Error updating status');
                }
            });
            */
        }

        // Mobile responsiveness
        function handleResponsiveLayout() {
            const isMobile = window.innerWidth < 768;
            const tables = document.querySelectorAll('.data-table');

            tables.forEach(table => {
                table.style.display = isMobile ? 'block' : '';
                table.style.overflowX = isMobile ? 'auto' : '';
            });
        }

        handleResponsiveLayout();
        window.addEventListener('resize', handleResponsiveLayout);

        // Export functionality
        function exportTableToCSV(tableId, filename) {
            const table = document.querySelector(tableId);
            let csv = [];
            const rows = table.querySelectorAll('tr');

            for (let i = 0; i < rows.length; i++) {
                const row = [], cols = rows[i].querySelectorAll('td, th');
                for (let j = 0; j < cols.length; j++) {
                    let data = cols[j].textContent.replace(/(\r\n|\n|\r)/gm, '').replace(/(\s\s)/gm, ' ').trim();
                    data = data.replace(/"/g, '""');
                    row.push(`"${data}"`);
                }
                csv.push(row.join(','));
            }

            return csv.join('\n');
        }
    });
</script>
