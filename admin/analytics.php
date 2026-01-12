<?php
$page_title = 'Analytics';
require_once '../includes/header.php';

$auth->checkAccess('admin');

$db = getDB();

// Get statistics
$total_projects = $db->query("SELECT COUNT(*) as count FROM projects")->fetch_assoc()['count'];
$total_users = $db->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'")->fetch_assoc()['count'];
$total_tasks = $db->query("SELECT COUNT(*) as count FROM tasks")->fetch_assoc()['count'];
$completed_tasks = $db->query("SELECT COUNT(*) as count FROM tasks WHERE status = 'completed'")->fetch_assoc()['count'];

// Projects by status
$projects_by_status = $db->query("SELECT status, COUNT(*) as count FROM projects GROUP BY status")->fetch_all(MYSQLI_ASSOC);

// Tasks by status
$tasks_by_status = $db->query("SELECT status, COUNT(*) as count FROM tasks GROUP BY status")->fetch_all(MYSQLI_ASSOC);

// Projects by priority
$projects_by_priority = $db->query("SELECT priority, COUNT(*) as count FROM projects GROUP BY priority")->fetch_all(MYSQLI_ASSOC);

// Recent activity
$recent_activity = $db->query("SELECT al.*, u.full_name, p.project_name 
    FROM activity_log al 
    LEFT JOIN users u ON al.user_id = u.id 
    LEFT JOIN projects p ON al.project_id = p.id 
    ORDER BY al.created_at DESC 
    LIMIT 20")->fetch_all(MYSQLI_ASSOC);

// Budget analysis
$budget_stats = $db->query("
    SELECT 
        SUM(budget) as total_budget,
        AVG(budget) as avg_budget,
        MAX(budget) as max_budget,
        MIN(budget) as min_budget
    FROM projects WHERE budget > 0
")->fetch_assoc();

// Pricing totals
$pricing_total = $db->query("SELECT SUM(total_price) as total FROM pricing")->fetch_assoc()['total'] ?? 0;
?>

<div class="container-fluid">
    <div class="page-header">
        <h1><i class="fa fa-bar-chart"></i> Analytics Dashboard</h1>
    </div>
    
    <div class="row">
        <div class="col-md-3">
            <div class="stat-box">
                <h3><?php echo $total_projects; ?></h3>
                <p>Total Projects</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-box">
                <h3><?php echo $total_users; ?></h3>
                <p>Active Users</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-box">
                <h3><?php echo $total_tasks; ?></h3>
                <p>Total Tasks</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-box">
                <h3><?php echo $completed_tasks ? round(($completed_tasks / $total_tasks) * 100) : 0; ?>%</h3>
                <p>Task Completion Rate</p>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="chart-container">
                <h4>Projects by Status</h4>
                <canvas id="projectStatusChart"></canvas>
            </div>
        </div>
        <div class="col-md-6">
            <div class="chart-container">
                <h4>Tasks by Status</h4>
                <canvas id="taskStatusChart"></canvas>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="chart-container">
                <h4>Projects by Priority</h4>
                <table class="table">
                    <?php foreach ($projects_by_priority as $stat): ?>
                    <tr>
                        <td>
                            <span class="badge-priority badge-<?php echo $stat['priority']; ?>">
                                <?php echo ucfirst($stat['priority']); ?>
                            </span>
                        </td>
                        <td class="text-right">
                            <strong><?php echo $stat['count']; ?> projects</strong>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="chart-container">
                <h4>Budget Statistics</h4>
                <table class="table">
                    <tr>
                        <td>Total Budget</td>
                        <td class="text-right"><strong>$<?php echo number_format($budget_stats['total_budget'] ?? 0, 2); ?></strong></td>
                    </tr>
                    <tr>
                        <td>Average Budget</td>
                        <td class="text-right"><strong>$<?php echo number_format($budget_stats['avg_budget'] ?? 0, 2); ?></strong></td>
                    </tr>
                    <tr>
                        <td>Total Pricing</td>
                        <td class="text-right"><strong>$<?php echo number_format($pricing_total, 2); ?></strong></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-history"></i> Recent Activity</h3>
                </div>
                <div class="panel-body">
                    <?php if (empty($recent_activity)): ?>
                    <p class="text-muted">No activity recorded.</p>
                    <?php else: ?>
                    <?php foreach ($recent_activity as $activity): ?>
                    <div class="activity-item">
                        <strong><?php echo htmlspecialchars($activity['full_name'] ?? 'Unknown'); ?></strong>
                        <?php echo htmlspecialchars($activity['description']); ?>
                        <?php if ($activity['project_name']): ?>
                        in <strong><?php echo htmlspecialchars($activity['project_name']); ?></strong>
                        <?php endif; ?>
                        <br>
                        <small class="text-muted">
                            <i class="fa fa-clock-o"></i> <?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?>
                        </small>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
// Projects by Status Chart
const projectStatusCtx = document.getElementById('projectStatusChart').getContext('2d');
new Chart(projectStatusCtx, {
    type: 'doughnut',
    data: {
        labels: [<?php echo implode(',', array_map(function($s) { return "'" . ucfirst(str_replace('_', ' ', $s['status'])) . "'"; }, $projects_by_status)); ?>],
        datasets: [{
            data: [<?php echo implode(',', array_column($projects_by_status, 'count')); ?>],
            backgroundColor: ['#f0ad4e', '#5bc0de', '#d9534f', '#5cb85c', '#777']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true
    }
});

// Tasks by Status Chart
const taskStatusCtx = document.getElementById('taskStatusChart').getContext('2d');
new Chart(taskStatusCtx, {
    type: 'bar',
    data: {
        labels: [<?php echo implode(',', array_map(function($s) { return "'" . ucfirst(str_replace('_', ' ', $s['status'])) . "'"; }, $tasks_by_status)); ?>],
        datasets: [{
            label: 'Tasks',
            data: [<?php echo implode(',', array_column($tasks_by_status, 'count')); ?>],
            backgroundColor: '#337ab7'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
