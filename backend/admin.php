<?php
// Initialize SQLite connection
$dbPath = __DIR__ . '/appointments.db';
$db = new SQLite3($dbPath);

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $id = intval($_POST['id']);
    $status = $_POST['status'];
    $allowed_statuses = ['Pending', 'In Progress', 'Completed'];
    
    if (in_array($status, $allowed_statuses)) {
        $stmt = $db->prepare("UPDATE appointments SET status = :status WHERE id = :id");
        $stmt->bindValue(':status', $status, SQLITE3_TEXT);
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();
    }
    
    // Redirect to prevent form double-submission
    header("Location: admin.php" . (isset($_POST['query_string']) ? '?' . $_POST['query_string'] : ''));
    exit();
}

// Handle Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = intval($_POST['id']);
    $stmt = $db->prepare("DELETE FROM appointments WHERE id = :id");
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmt->execute();
    
    header("Location: admin.php" . (isset($_POST['query_string']) ? '?' . $_POST['query_string'] : ''));
    exit();
}

// Fetch KPIs
$totalCount = $db->querySingle("SELECT COUNT(*) FROM appointments") ?? 0;
$pendingCount = $db->querySingle("SELECT COUNT(*) FROM appointments WHERE status = 'Pending'") ?? 0;
$progressCount = $db->querySingle("SELECT COUNT(*) FROM appointments WHERE status = 'In Progress'") ?? 0;
$completedCount = $db->querySingle("SELECT COUNT(*) FROM appointments WHERE status = 'Completed'") ?? 0;

// Filters & Search logic
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status_filter'] ?? '';
$service_filter = $_GET['service_filter'] ?? '';

// Build Query
$queryStr = "SELECT * FROM appointments WHERE 1=1";
$binds = [];

if (!empty($search)) {
    $queryStr .= " AND (name LIKE :search OR email LIKE :search OR vehicle_model LIKE :search OR custom_vehicle_model LIKE :search OR service_type LIKE :search OR custom_service_type LIKE :search OR notes LIKE :search)";
    $binds[':search'] = '%' . $search . '%';
}
if (!empty($status_filter)) {
    $queryStr .= " AND status = :status_filter";
    $binds[':status_filter'] = $status_filter;
}
if (!empty($service_filter)) {
    $queryStr .= " AND service_type = :service_filter";
    $binds[':service_filter'] = $service_filter;
}

$queryStr .= " ORDER BY created_at DESC";

$stmt = $db->prepare($queryStr);
foreach ($binds as $key => $val) {
    $stmt->bindValue($key, $val, SQLITE3_TEXT);
}
$results = $stmt->execute();

// Keep track of active query string to maintain state after post requests
$active_query = $_SERVER['QUERY_STRING'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apex Auto Care - Control Panel</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #050507;
            --bg-light: #0d0e12;
            --accent-primary: #ff5a1f;
            --text-main: #ffffff;
            --text-muted: #6b7280;
            --border-subtle: rgba(255, 255, 255, 0.1);
            
            --status-pending-text: #f59e0b;
            --status-progress-text: #3b82f6;
            --status-completed-text: #10b981;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', 'Inter', sans-serif;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            min-height: 100vh;
            padding: 4rem 2rem;
            line-height: 1.5;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1000 400'%3E%3Cpath d='M100 280 L140 200 C160 160 220 130 300 130 L450 100 C520 80 600 80 680 90 L850 140 C900 160 950 190 970 240 L1020 280' fill='none' stroke='%23ff5a1f' stroke-width='4' stroke-opacity='0.05' stroke-linecap='round' stroke-linejoin='round'/%3E%3Ccircle cx='280' cy='280' r='60' fill='none' stroke='%23ff5a1f' stroke-width='4' stroke-opacity='0.05'/%3E%3Ccircle cx='820' cy='280' r='60' fill='none' stroke='%23ff5a1f' stroke-width='4' stroke-opacity='0.05'/%3E%3Cpath d='M450 100 L580 160 L300 130 Z' fill='none' stroke='%23ff5a1f' stroke-width='2' stroke-opacity='0.03'/%3E%3Cpath d='M680 90 L580 160 L850 140 Z' fill='none' stroke='%23ff5a1f' stroke-width='2' stroke-opacity='0.03'/%3E%3C/svg%3E");
            background-size: 800px;
            background-position: bottom right;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header Layout */
        header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 4rem;
            border-bottom: 1px solid var(--border-subtle);
            padding-bottom: 2rem;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .brand-logo-svg {
            width: 48px;
            height: 48px;
            color: var(--text-main);
            flex-shrink: 0;
        }

        .brand h1 {
            font-size: 3.5rem;
            font-weight: 700;
            color: var(--text-main);
            letter-spacing: -2px;
            line-height: 1;
            text-transform: uppercase;
        }

        .brand p {
            color: var(--text-muted);
            font-size: 1rem;
            margin-top: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .badge-django {
            display: none;
        }

        /* Metric Grid */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 2rem;
            margin-bottom: 4rem;
        }

        .metric-card {
            background: transparent;
            border-left: 2px solid var(--border-subtle);
            padding: 1.5rem 2rem;
            transition: border-color 0.3s ease;
        }

        .metric-card:hover {
            border-left-color: var(--accent-primary);
        }

        .metric-label {
            color: var(--text-muted);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .metric-value {
            font-size: 3.5rem;
            font-weight: 300;
            color: var(--text-main);
            margin-top: 0.5rem;
            line-height: 1;
        }

        /* Controls Block */
        .controls-card {
            margin-bottom: 2rem;
            border-bottom: 1px solid var(--border-subtle);
            padding-bottom: 2rem;
        }

        .filter-form {
            display: flex;
            gap: 2rem;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            flex: 1;
            min-width: 200px;
        }

        .form-group label {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .form-control {
            background: transparent;
            border: none;
            border-bottom: 1px solid var(--border-subtle);
            color: var(--text-main);
            padding: 0.75rem 0;
            font-size: 1.1rem;
            outline: none;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            border-bottom-color: var(--accent-primary);
        }

        /* Date filter color tweak */
        input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(1);
            opacity: 0.5;
            cursor: pointer;
        }

        .btn {
            padding: 1rem 2rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            font-size: 0.85rem;
        }

        .btn-primary {
            background: var(--accent-primary);
            color: white;
        }

        .btn-primary:hover {
            background: #e04b16;
        }

        .btn-secondary {
            background: transparent;
            color: var(--text-main);
            border: 1px solid var(--border-subtle);
            text-decoration: none;
        }

        .btn-secondary:hover {
            border-color: var(--text-muted);
        }

        /* Database Table */
        .table-card {
            background: transparent;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            padding: 1.5rem 1rem;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border-subtle);
        }

        td {
            padding: 1.5rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            vertical-align: middle;
            font-size: 1rem;
            color: var(--text-main);
        }

        tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        /* Status Badge styles */
        .status-badge {
            display: inline-block;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .status-badge.pending { color: var(--status-pending-text); }
        .status-badge.in-progress { color: var(--status-progress-text); }
        .status-badge.completed { color: var(--status-completed-text); }

        /* Action Forms */
        .action-cell {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .status-selector {
            background: transparent;
            border: 1px solid var(--border-subtle);
            color: var(--text-main);
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            outline: none;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .status-selector:focus {
            border-color: var(--text-muted);
        }

        .btn-delete {
            background: transparent;
            color: var(--text-muted);
            padding: 0.5rem;
            border: none;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
        }

        .btn-delete:hover {
            color: #ef4444;
        }

        /* Customer Block */
        .customer-info {
            display: flex;
            flex-direction: column;
        }

        .customer-name {
            font-weight: 500;
        }

        .customer-email {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }

        /* Empty state */
        .empty-state {
            padding: 6rem 2rem;
            text-align: center;
            color: var(--text-muted);
            font-size: 1.2rem;
            font-weight: 300;
        }

        /* Media link formatting */
        .media-link {
            color: var(--text-main);
            text-decoration: none;
            border-bottom: 1px solid var(--accent-primary);
            padding-bottom: 2px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .media-link:hover {
            color: var(--accent-primary);
        }

        .notes-text {
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: var(--text-muted);
            font-size: 0.9rem;
            cursor: pointer;
        }

        .notes-text:hover {
            white-space: normal;
        }

        /* Animations */
        .fade-in {
            animation: fadeIn 0.8s ease-out forwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="fade-in">
    <div class="container">
        <!-- Header -->
        <header>
            <div class="brand">
                <svg class="brand-logo-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke="rgba(255,90,31,0.5)" stroke-width="1" />
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" stroke-width="1.5" />
                </svg>
                <div>
                    <h1>APEX</h1>
                    <p>Administrative Control Panel</p>
                </div>
            </div>
        </header>

        <!-- KPI Metrics Grid -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-label">Total Bookings</div>
                <div class="metric-value"><?= $totalCount ?></div>
            </div>
            <div class="metric-card pending">
                <div class="metric-label">Pending Intake</div>
                <div class="metric-value"><?= $pendingCount ?></div>
            </div>
            <div class="metric-card progress">
                <div class="metric-label">In Progress</div>
                <div class="metric-value"><?= $progressCount ?></div>
            </div>
            <div class="metric-card completed">
                <div class="metric-label">Completed</div>
                <div class="metric-value"><?= $completedCount ?></div>
            </div>
        </div>

        <!-- Filters & Control Area -->
        <div class="controls-card">
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label for="search">Search Bookings</label>
                    <input type="text" id="search" name="search" class="form-control" placeholder="Search by name, email, vehicle, notes..." value="<?= htmlspecialchars($search) ?>">
                </div>

                <div class="form-group">
                    <label for="status_filter">Filter Status</label>
                    <select id="status_filter" name="status_filter" class="form-control">
                        <option value="">-- All Statuses --</option>
                        <option value="Pending" <?= $status_filter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="In Progress" <?= $status_filter === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="Completed" <?= $status_filter === 'Completed' ? 'selected' : '' ?>>Completed</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="service_filter">Filter Service</label>
                    <select id="service_filter" name="service_filter" class="form-control">
                        <option value="">-- All Services --</option>
                        <option value="Oil Change" <?= $service_filter === 'Oil Change' ? 'selected' : '' ?>>Oil Change</option>
                        <option value="Diagnostic" <?= $service_filter === 'Diagnostic' ? 'selected' : '' ?>>Diagnostic</option>
                        <option value="Repair" <?= $service_filter === 'Repair' ? 'selected' : '' ?>>Repair</option>
                        <option value="Other" <?= $service_filter === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>

                <div class="action-cell">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <?php if (!empty($search) || !empty($status_filter) || !empty($service_filter)): ?>
                        <a href="admin.php" class="btn btn-secondary">Clear</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Table Database -->
        <div class="table-card">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Service Date</th>
                            <th>Vehicle Model</th>
                            <th>Service Needed</th>
                            <th>Notes & Attachments</th>
                            <th>Status Badge</th>
                            <th style="width: 250px;">Administrative Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $hasRows = false;
                        while ($row = $results->fetchArray(SQLITE3_ASSOC)): 
                            $hasRows = true;
                            $statusClass = strtolower(str_replace(' ', '-', $row['status']));
                        ?>
                            <tr>
                                <td><strong>#<?= $row['id'] ?></strong></td>
                                <td>
                                    <div class="customer-info">
                                        <span class="customer-name"><?= htmlspecialchars($row['name']) ?></span>
                                        <span class="customer-email"><?= htmlspecialchars($row['email']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars(date("M d, Y", strtotime($row['service_date']))) ?></strong>
                                </td>
                                <td>
                                    <?php 
                                    $model = $row['vehicle_model'];
                                    if ($model === 'Other' && !empty($row['custom_vehicle_model'])) {
                                        echo htmlspecialchars($row['custom_vehicle_model']) . ' <span style="font-size:0.75rem; opacity:0.6;">(Custom)</span>';
                                    } else {
                                        echo htmlspecialchars($model);
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span style="font-weight: 500;">
                                        <?php 
                                        $srv = $row['service_type'];
                                        if ($srv === 'Other' && !empty($row['custom_service_type'])) {
                                            echo htmlspecialchars($row['custom_service_type']) . ' <span style="font-size:0.75rem; opacity:0.6;">(Custom)</span>';
                                        } else {
                                            echo htmlspecialchars($srv);
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($row['notes'])): ?>
                                        <div class="notes-text" title="<?= htmlspecialchars($row['notes']) ?>">
                                            <?= htmlspecialchars($row['notes']) ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="opacity: 0.3;">--</span>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($row['media_url'])): ?>
                                        <div style="margin-top: 0.25rem;">
                                            <a href="<?= htmlspecialchars($row['media_url']) ?>" target="_blank" rel="noopener noreferrer" class="media-link">
                                                🔗 Attached Media
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($row['status']) ?></span>
                                </td>
                                <td>
                                    <div class="action-cell">
                                        <!-- Inline Status Update Form -->
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="query_string" value="<?= htmlspecialchars($active_query) ?>">
                                            <select name="status" class="status-selector" onchange="this.form.submit()">
                                                <option value="Pending" <?= $row['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="In Progress" <?= $row['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                                <option value="Completed" <?= $row['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                            </select>
                                        </form>

                                        <!-- Delete Action Form -->
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this booking record?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="query_string" value="<?= htmlspecialchars($active_query) ?>">
                                            <button type="submit" class="btn-delete">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>

                        <?php if (!$hasRows): ?>
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <div class="empty-icon">📂</div>
                                        <h3>No bookings found</h3>
                                        <p>Try clearing your filters or make an intake submission from the frontend.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
