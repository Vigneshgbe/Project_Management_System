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

<style>
    .analytics-container {
        background: transparent !important;
        min-height: calc(100vh - 100px) !important;
        padding: 20px !important;
        margin: 0 !important;
        animation: fadeIn 0.5s ease !important;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .analytics-header {
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(20px) !important;
        color: #1e293b !important;
        padding: 35px 40px !important;
        border-radius: 20px !important;
        margin-bottom: 35px !important;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        animation: slideDown 0.6s ease !important;
        position: relative !important;
        overflow: hidden !important;
    }
    
    .analytics-header::before {
        content: '' !important;
        position: absolute !important;
        top: -50% !important;
        right: -50% !important;
        width: 200% !important;
        height: 200% !important;
        background: radial-gradient(circle, rgba(102, 126, 234, 0.1) 0%, transparent 70%) !important;
        animation: rotate 20s linear infinite !important;
    }
    
    @keyframes rotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .analytics-header h1 {
        margin: 0 !important;
        font-weight: 800 !important;
        font-size: 32px !important;
        position: relative !important;
        z-index: 1 !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
    }
    
    .analytics-header h1 i {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
    }
    
    .analytics-stat-box {
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(20px) !important;
        padding: 30px !important;
        border-radius: 20px !important;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15) !important;
        margin-bottom: 25px !important;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        position: relative !important;
        overflow: hidden !important;
        animation: scaleIn 0.5s ease !important;
    }
    
    @keyframes scaleIn {
        from { opacity: 0; transform: scale(0.9); }
        to { opacity: 1; transform: scale(1); }
    }
    
    .analytics-stat-box::before {
        content: '' !important;
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        width: 5px !important;
        height: 100% !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        transition: width 0.3s ease !important;
    }
    
    .analytics-stat-box:hover::before {
        width: 100% !important;
        opacity: 0.05 !important;
    }
    
    .analytics-stat-box:hover {
        transform: translateY(-8px) !important;
        box-shadow: 0 15px 45px rgba(102, 126, 234, 0.3) !important;
    }
    
    .analytics-stat-box h3 {
        margin-top: 0 !important;
        font-size: 42px !important;
        font-weight: 800 !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
        margin-bottom: 8px !important;
    }
    
    .analytics-stat-box p {
        color: #64748b !important;
        margin: 0 !important;
        font-weight: 600 !important;
        font-size: 14px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
    }
    
    .chart-card {
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(20px) !important;
        border-radius: 20px !important;
        padding: 30px !important;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15) !important;
        margin-bottom: 25px !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        transition: all 0.3s ease !important;
        animation: slideUp 0.5s ease !important;
    }
    
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .chart-card:hover {
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2) !important;
        transform: translateY(-3px) !important;
    }
    
    .chart-card h4 {
        margin-top: 0 !important;
        margin-bottom: 20px !important;
        font-size: 20px !important;
        font-weight: 700 !important;
        color: #1e293b !important;
        padding-bottom: 15px !important;
        border-bottom: 3px solid transparent !important;
        border-image: linear-gradient(90deg, #667eea 0%, #764ba2 100%) !important;
        border-image-slice: 1 !important;
    }
    
    .chart-canvas-wrapper {
        position: relative !important;
        width: 100% !important;
        height: 300px !important;
    }
    
    .chart-canvas-wrapper canvas {
        max-height: 300px !important;
    }
    
    .analytics-table {
        width: 100% !important;
        margin: 0 !important;
    }
    
    .analytics-table tr {
        transition: all 0.3s ease !important;
    }
    
    .analytics-table tr:hover {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%) !important;
    }
    
    .analytics-table td {
        padding: 15px 0 !important;
        border-bottom: 1px solid #e2e8f0 !important;
        color: #1e293b !important;
        font-weight: 600 !important;
    }
    
    .analytics-table tr:last-child td {
        border-bottom: none !important;
    }
    
    .badge-priority {
        padding: 6px 14px !important;
        border-radius: 20px !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        display: inline-block !important;
    }
    
    .badge-low { 
        background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
        color: white !important;
    }
    
    .badge-medium { 
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%) !important;
        color: white !important;
    }
    
    .badge-high { 
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%) !important;
        color: white !important;
    }
    
    .badge-critical { 
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        color: white !important;
        animation: pulse 2s infinite !important;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.8; transform: scale(1.05); }
    }
    
    .activity-panel {
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(20px) !important;
        border-radius: 20px !important;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        overflow: hidden !important;
        animation: slideUp 0.5s ease !important;
    }
    
    .activity-panel-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        padding: 20px 25px !important;
        font-weight: 700 !important;
        font-size: 18px !important;
    }
    
    .activity-panel-body {
        padding: 30px 25px !important;
        max-height: 600px !important;
        overflow-y: auto !important;
    }
    
    .activity-panel-body::-webkit-scrollbar {
        width: 8px !important;
    }
    
    .activity-panel-body::-webkit-scrollbar-track {
        background: #f1f5f9 !important;
        border-radius: 10px !important;
    }
    
    .activity-panel-body::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        border-radius: 10px !important;
    }
    
    .activity-item {
        padding: 18px 20px !important;
        background: white !important;
        border-left: 5px solid #667eea !important;
        margin-bottom: 16px !important;
        border-radius: 12px !important;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05) !important;
        transition: all 0.3s ease !important;
    }
    
    .activity-item:hover {
        transform: translateX(8px) !important;
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.15) !important;
    }
    
    .activity-item strong {
        color: #667eea !important;
        font-weight: 700 !important;
    }
    
    .activity-item small {
        color: #64748b !important;
        font-size: 12px !important;
    }
    
    /* RESPONSIVE */
    @media (max-width: 1200px) {
        .analytics-container {
            padding: 15px !important;
        }
        .analytics-header {
            padding: 25px 30px !important;
        }
        .analytics-header h1 {
            font-size: 28px !important;
        }
    }
    
    @media (max-width: 992px) {
        .analytics-stat-box {
            margin-bottom: 20px !important;
        }
        .chart-card {
            padding: 20px !important;
        }
    }
    
    @media (max-width: 768px) {
        .analytics-container {
            padding: 10px !important;
        }
        .analytics-header {
            padding: 20px !important;
            margin-bottom: 20px !important;
        }
        .analytics-header h1 {
            font-size: 24px !important;
        }
        .analytics-stat-box {
            padding: 20px !important;
            margin-bottom: 15px !important;
        }
        .analytics-stat-box h3 {
            font-size: 32px !important;
        }
        .chart-card {
            padding: 15px !important;
        }
        .chart-canvas-wrapper {
            height: 250px !important;
        }
        .activity-panel-body {
            max-height: 400px !important;
        }
    }
    
    @media (max-width: 480px) {
        .analytics-container {
            padding: 8px !important;
        }
        .analytics-stat-box {
            padding: 15px !important;
        }
        .chart-card h4 {
            font-size: 18px !important;
        }
        .activity-panel-header {
            padding: 15px 20px !important;
            font-size: 16px !important;
        }
        .activity-panel-body {
            padding: 15px !important;
        }
    }
</style>

<div class="analytics-container container-fluid">
    <div class="analytics-header">
        <h1><i class="fa fa-bar-chart"></i> Analytics Dashboard</h1>
    </div>
    
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="analytics-stat-box">
                <h3 class="counter" data-target="<?php echo $total_projects; ?>">0</h3>
                <p>Total Projects</p>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="analytics-stat-box">
                <h3 class="counter" data-target="<?php echo $total_users; ?>">0</h3>
                <p>Active Users</p>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="analytics-stat-box">
                <h3 class="counter" data-target="<?php echo $total_tasks; ?>">0</h3>
                <p>Total Tasks</p>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="analytics-stat-box">
                <h3 class="counter" data-target="<?php echo $completed_tasks ? round(($completed_tasks / $total_tasks) * 100) : 0; ?>">0</h3>
                <p>Completion Rate</p>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="chart-card">
                <h4><i class="fa fa-pie-chart"></i> Projects by Status</h4>
                <div class="chart-canvas-wrapper">
                    <canvas id="projectStatusChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="chart-card">
                <h4><i class="fa fa-bar-chart"></i> Tasks by Status</h4>
                <div class="chart-canvas-wrapper">
                    <canvas id="taskStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="chart-card">
                <h4><i class="fa fa-flag"></i> Projects by Priority</h4>
                <table class="analytics-table">
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
            <div class="chart-card">
                <h4><i class="fa fa-dollar"></i> Budget Statistics</h4>
                <table class="analytics-table">
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
            <div class="activity-panel">
                <div class="activity-panel-header">
                    <i class="fa fa-history"></i> Recent Activity
                </div>
                <div class="activity-panel-body">
                    <?php if (empty($recent_activity)): ?>
                    <p class="text-muted" style="text-align: center; color: #64748b !important;">No activity recorded.</p>
                    <?php else: ?>
                    <?php foreach ($recent_activity as $activity): ?>
                    <div class="activity-item">
                        <strong><?php echo htmlspecialchars($activity['full_name'] ?? 'Unknown'); ?></strong>
                        <?php echo htmlspecialchars($activity['description']); ?>
                        <?php if ($activity['project_name']): ?>
                        in <strong><?php echo htmlspecialchars($activity['project_name']); ?></strong>
                        <?php endif; ?>
                        <br>
                        <small>
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
$(document).ready(function() {
    // ANIMATED COUNTER
    $('.counter').each(function() {
        const $this = $(this);
        const countTo = parseInt($this.attr('data-target'));
        
        $({ countNum: 0 }).animate({
            countNum: countTo
        }, {
            duration: 2000,
            easing: 'swing',
            step: function() {
                $this.text(Math.floor(this.countNum));
            },
            complete: function() {
                $this.text(this.countNum + ($this.parent().find('p').text().includes('Rate') ? '%' : ''));
            }
        });
    });
    
    // STAGGERED CARD ANIMATIONS
    $('.chart-card, .activity-panel').each(function(index) {
        $(this).css({
            'animation': `slideUp 0.5s ease ${index * 0.1}s both`
        });
    });
});

// CHART COLORS
const gradientColors = {
    purple: ['#667eea', '#764ba2'],
    blue: ['#4facfe', '#00f2fe'],
    green: ['#43e97b', '#38f9d7'],
    orange: ['#fa709a', '#fee140'],
    red: ['#ff6b6b', '#ee5a6f']
};

// PROJECTS BY STATUS CHART
const projectStatusCtx = document.getElementById('projectStatusChart').getContext('2d');
const projectGradient1 = projectStatusCtx.createLinearGradient(0, 0, 0, 300);
projectGradient1.addColorStop(0, '#fbbf24');
projectGradient1.addColorStop(1, '#f59e0b');

const projectGradient2 = projectStatusCtx.createLinearGradient(0, 0, 0, 300);
projectGradient2.addColorStop(0, '#3b82f6');
projectGradient2.addColorStop(1, '#2563eb');

const projectGradient3 = projectStatusCtx.createLinearGradient(0, 0, 0, 300);
projectGradient3.addColorStop(0, '#ef4444');
projectGradient3.addColorStop(1, '#dc2626');

const projectGradient4 = projectStatusCtx.createLinearGradient(0, 0, 0, 300);
projectGradient4.addColorStop(0, '#10b981');
projectGradient4.addColorStop(1, '#059669');

const projectGradient5 = projectStatusCtx.createLinearGradient(0, 0, 0, 300);
projectGradient5.addColorStop(0, '#6b7280');
projectGradient5.addColorStop(1, '#4b5563');

new Chart(projectStatusCtx, {
    type: 'doughnut',
    data: {
        labels: [<?php echo implode(',', array_map(function($s) { return "'" . ucfirst(str_replace('_', ' ', $s['status'])) . "'"; }, $projects_by_status)); ?>],
        datasets: [{
            data: [<?php echo implode(',', array_column($projects_by_status, 'count')); ?>],
            backgroundColor: [projectGradient1, projectGradient2, projectGradient3, projectGradient4, projectGradient5],
            borderWidth: 0,
            hoverOffset: 15
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    font: {
                        size: 12,
                        weight: '600'
                    },
                    usePointStyle: true,
                    pointStyle: 'circle'
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                titleFont: { size: 14, weight: 'bold' },
                bodyFont: { size: 13 },
                borderColor: '#667eea',
                borderWidth: 1
            }
        }
    }
});

// TASKS BY STATUS CHART
const taskStatusCtx = document.getElementById('taskStatusChart').getContext('2d');
const taskGradient = taskStatusCtx.createLinearGradient(0, 0, 0, 300);
taskGradient.addColorStop(0, '#667eea');
taskGradient.addColorStop(1, '#764ba2');

new Chart(taskStatusCtx, {
    type: 'bar',
    data: {
        labels: [<?php echo implode(',', array_map(function($s) { return "'" . ucfirst(str_replace('_', ' ', $s['status'])) . "'"; }, $tasks_by_status)); ?>],
        datasets: [{
            label: 'Tasks',
            data: [<?php echo implode(',', array_column($tasks_by_status, 'count')); ?>],
            backgroundColor: taskGradient,
            borderRadius: 10,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                titleFont: { size: 14, weight: 'bold' },
                bodyFont: { size: 13 },
                borderColor: '#667eea',
                borderWidth: 1
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)',
                    drawBorder: false
                },
                ticks: {
                    font: { size: 12, weight: '600' },
                    color: '#64748b'
                }
            },
            x: {
                grid: {
                    display: false,
                    drawBorder: false
                },
                ticks: {
                    font: { size: 12, weight: '600' },
                    color: '#64748b'
                }
            }
        }
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>