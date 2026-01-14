<?php
$page_title = 'Analytics';
require_once '../includes/header.php';

$auth->checkAccess('admin');

$db = getDB();

// Get core statistics
$total_projects = $db->query("SELECT COUNT(*) as count FROM projects")->fetch_assoc()['count'];
$total_users = $db->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'")->fetch_assoc()['count'];
$total_tasks = $db->query("SELECT COUNT(*) as count FROM tasks")->fetch_assoc()['count'];
$completed_tasks = $db->query("SELECT COUNT(*) as count FROM tasks WHERE status = 'completed'")->fetch_assoc()['count'];

// Enhanced statistics  
$overdue_tasks = $db->query("SELECT COUNT(*) as count FROM tasks WHERE due_date < CURDATE() AND status != 'completed'")->fetch_assoc()['count'];
$active_projects = $db->query("SELECT COUNT(*) as count FROM projects WHERE status = 'active'")->fetch_assoc()['count'];

// Distribution data
$projects_by_status = $db->query("SELECT status, COUNT(*) as count FROM projects GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$tasks_by_status = $db->query("SELECT status, COUNT(*) as count FROM tasks GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$tasks_by_priority = $db->query("SELECT priority, COUNT(*) as count FROM tasks GROUP BY priority")->fetch_all(MYSQLI_ASSOC);
$projects_by_priority = $db->query("SELECT priority, COUNT(*) as count FROM projects GROUP BY priority")->fetch_all(MYSQLI_ASSOC);

// Financial data
$budget_stats = $db->query("
    SELECT 
        SUM(budget) as total_budget,
        AVG(budget) as avg_budget,
        COUNT(*) as project_count
    FROM projects WHERE budget > 0
")->fetch_assoc();

$pricing_total = $db->query("SELECT SUM(total_price) as total FROM pricing")->fetch_assoc()['total'] ?? 0;

// Team distribution
$user_roles = $db->query("SELECT role, COUNT(*) as count FROM users WHERE status = 'active' GROUP BY role")->fetch_all(MYSQLI_ASSOC);

// Timeline data (last 6 months)
$project_timeline = $db->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count 
    FROM projects 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month
")->fetch_all(MYSQLI_ASSOC);

// Calculate completion rates
$task_completion_rate = $total_tasks > 0 ? round(($completed_tasks / $total_tasks) * 100) : 0;
$project_completion_rate = $total_projects > 0 ? round((count(array_filter($projects_by_status, function($p) { return $p['status'] === 'completed'; })) / $total_projects) * 100) : 0;
?>

<style>
    /* MODERN PROFESSIONAL DESIGN - OPTIMIZED */
    
    :root {
        --primary: #6366f1;
        --primary-dark: #4f46e5;
        --secondary: #8b5cf6;
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
        --info: #06b6d4;
        --dark: #1e293b;
        --light: #f8fafc;
        --border: #e2e8f0;
        --shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.07);
        --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
        --radius: 16px;
        --radius-sm: 10px;
        --radius-lg: 20px;
    }
    
    * {
        box-sizing: border-box;
    }
    
    .analytics-container {
        padding: 24px;
        max-width: 1400px;
        margin: 0 auto;
        animation: fadeIn 0.4s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* PAGE HEADER */
    .page-header {
        background: white;
        padding: 40px;
        border-radius: var(--radius);
        margin-bottom: 32px;
        box-shadow: var(--shadow-md);
        border: 1px solid var(--border);
        position: relative;
        overflow: hidden;
    }
    
    .page-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
    }
    
    .page-header h1 {
        margin: 0 0 8px 0;
        font-weight: 700;
        font-size: 32px;
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .page-header h1 i {
        color: var(--primary);
        font-size: 28px;
    }
    
    .page-subtitle {
        color: #64748b;
        font-size: 15px;
        font-weight: 500;
        margin: 0;
    }
    
    /* KPI GRID - OPTIMIZED */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 24px;
        margin-bottom: 40px;
    }
    
    .kpi-card {
        background: white;
        padding: 32px 28px;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
        position: relative;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-align: center;
    }
    
    .kpi-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--card-color), var(--card-color-alt));
        transform: scaleX(0);
        transition: transform 0.3s ease;
        transform-origin: left;
    }
    
    .kpi-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--shadow-lg);
    }
    
    .kpi-card:hover::before {
        transform: scaleX(1);
    }
    
    .kpi-card.projects { --card-color: #3b82f6; --card-color-alt: #2563eb; }
    .kpi-card.users { --card-color: #10b981; --card-color-alt: #059669; }
    .kpi-card.tasks { --card-color: #f59e0b; --card-color-alt: #d97706; }
    .kpi-card.completion { --card-color: #8b5cf6; --card-color-alt: #7c3aed; }
    .kpi-card.overdue { --card-color: #ef4444; --card-color-alt: #dc2626; }
    .kpi-card.active { --card-color: #06b6d4; --card-color-alt: #0891b2; }
    
    .kpi-icon {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--card-color), var(--card-color-alt));
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        margin: 0 auto 20px;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        transition: transform 0.3s ease;
    }
    
    .kpi-card:hover .kpi-icon {
        transform: scale(1.1);
    }
    
    .kpi-value {
        font-size: 40px;
        font-weight: 800;
        color: var(--dark);
        margin-bottom: 8px;
        line-height: 1;
    }
    
    .kpi-label {
        color: #64748b;
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        margin-bottom: 12px;
    }
    
    .kpi-trend {
        font-size: 12px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px;
        background: rgba(0, 0, 0, 0.03);
        border: 1px solid rgba(0, 0, 0, 0.06);
    }
    
    .kpi-trend.positive { color: #059669; }
    .kpi-trend.negative { color: #dc2626; }
    .kpi-trend.neutral { color: #64748b; }
    
    /* CHART SECTION - OPTIMIZED */
    .chart-section {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
        gap: 32px;
        margin-bottom: 40px;
    }
    
    .chart-card {
        background: white;
        border-radius: var(--radius);
        padding: 32px;
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
        transition: all 0.3s ease;
    }
    
    .chart-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-lg);
    }
    
    .chart-header {
        margin-bottom: 28px;
        padding-bottom: 16px;
        border-bottom: 2px solid var(--border);
    }
    
    .chart-title {
        font-size: 18px;
        font-weight: 700;
        color: var(--dark);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .chart-title i {
        color: var(--primary);
        font-size: 16px;
    }
    
    .chart-canvas {
        position: relative;
        width: 100%;
        height: 280px;
    }
    
    /* DATA TABLE */
    .data-table {
        width: 100%;
    }
    
    .data-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 0;
        border-bottom: 1px solid #f1f5f9;
        transition: all 0.2s ease;
    }
    
    .data-row:last-child {
        border-bottom: none;
    }
    
    .data-row:hover {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.02), rgba(139, 92, 246, 0.02));
        padding-left: 12px;
        padding-right: 12px;
        border-radius: var(--radius-sm);
    }
    
    .data-row strong {
        color: var(--dark);
        font-weight: 600;
        font-size: 14px;
    }
    
    .data-value {
        font-size: 16px;
        font-weight: 700;
        color: var(--dark);
    }
    
    /* PRIORITY BADGES */
    .priority-badge {
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
    }
    
    .priority-badge:hover {
        transform: translateY(-2px);
    }
    
    .priority-badge.low { 
        background: #d1fae5;
        color: #065f46;
    }
    
    .priority-badge.medium { 
        background: #fef3c7;
        color: #92400e;
    }
    
    .priority-badge.high { 
        background: #fed7aa;
        color: #9a3412;
    }
    
    .priority-badge.critical { 
        background: #fee2e2;
        color: #991b1b;
        animation: pulse 2s ease-in-out infinite;
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    
    /* FINANCIAL SUMMARY */
    .financial-summary {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.05), rgba(5, 150, 105, 0.05));
        border: 2px solid rgba(16, 185, 129, 0.2);
        border-radius: var(--radius-sm);
        padding: 20px;
        margin-top: 20px;
        text-align: center;
    }
    
    .financial-highlight {
        font-size: 28px;
        font-weight: 800;
        color: #059669;
        margin-bottom: 6px;
    }
    
    .financial-label {
        color: #064e3b;
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    /* LOADING STATE */
    .chart-loading {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 280px;
        color: #94a3b8;
        font-size: 14px;
        font-weight: 600;
    }
    
    .chart-loading i {
        margin-right: 8px;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    /* SMOOTH SCROLLBAR */
    ::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }
    
    ::-webkit-scrollbar-track {
        background: #f1f5f9;
    }
    
    ::-webkit-scrollbar-thumb {
        background: var(--primary);
        border-radius: 5px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
        background: var(--primary-dark);
    }
    
    /* RESPONSIVE DESIGN */
    @media (max-width: 1200px) {
        .analytics-container {
            padding: 20px;
        }
        .page-header {
            padding: 32px;
        }
        .kpi-grid {
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        }
        .chart-section {
            grid-template-columns: 1fr;
        }
    }
    
    @media (max-width: 768px) {
        .analytics-container {
            padding: 16px;
        }
        .page-header {
            padding: 24px 20px;
            margin-bottom: 24px;
        }
        .page-header h1 {
            font-size: 24px;
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }
        .kpi-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        .kpi-card {
            padding: 24px 20px;
        }
        .kpi-value {
            font-size: 32px;
        }
        .chart-card {
            padding: 24px;
        }
        .chart-section {
            gap: 24px;
        }
    }
    
    @media (max-width: 480px) {
        .analytics-container {
            padding: 12px;
        }
        .page-header {
            padding: 20px;
        }
        .page-header h1 {
            font-size: 20px;
        }
        .page-subtitle {
            font-size: 13px;
        }
        .kpi-card {
            padding: 20px;
        }
        .kpi-value {
            font-size: 28px;
        }
        .kpi-icon {
            width: 56px;
            height: 56px;
            font-size: 24px;
        }
        .chart-card {
            padding: 20px;
        }
        .chart-title {
            font-size: 16px;
        }
        .chart-canvas {
            height: 240px;
        }
    }
</style>

<div class="analytics-container container-fluid">
    <div class="page-header">
        <h1>
            <i class="fa fa-bar-chart"></i> Analytics Dashboard
        </h1>
        <p class="page-subtitle">Comprehensive insights into your project management ecosystem</p>
    </div>
    
    <!-- KPI GRID -->
    <div class="kpi-grid">
        <div class="kpi-card projects">
            <div class="kpi-icon"><i class="fa fa-folder"></i></div>
            <div class="kpi-value counter" data-target="<?php echo $total_projects; ?>">0</div>
            <div class="kpi-label">Total Projects</div>
            <div class="kpi-trend neutral">
                <i class="fa fa-building"></i> Active: <?php echo $active_projects; ?>
            </div>
        </div>
        
        <div class="kpi-card users">
            <div class="kpi-icon"><i class="fa fa-users"></i></div>
            <div class="kpi-value counter" data-target="<?php echo $total_users; ?>">0</div>
            <div class="kpi-label">Active Users</div>
            <div class="kpi-trend positive">
                <i class="fa fa-user-plus"></i> Team Members
            </div>
        </div>
        
        <div class="kpi-card tasks">
            <div class="kpi-icon"><i class="fa fa-tasks"></i></div>
            <div class="kpi-value counter" data-target="<?php echo $total_tasks; ?>">0</div>
            <div class="kpi-label">Total Tasks</div>
            <div class="kpi-trend <?php echo $overdue_tasks > 0 ? 'negative' : 'positive'; ?>">
                <i class="fa fa-clock-o"></i> <?php echo $overdue_tasks; ?> overdue
            </div>
        </div>
        
        <div class="kpi-card completion">
            <div class="kpi-icon"><i class="fa fa-check-circle"></i></div>
            <div class="kpi-value counter" data-target="<?php echo $task_completion_rate; ?>">0</div>
            <div class="kpi-label">Completion Rate %</div>
            <div class="kpi-trend <?php echo $task_completion_rate >= 75 ? 'positive' : ($task_completion_rate >= 50 ? 'neutral' : 'negative'); ?>">
                <i class="fa fa-chart-line"></i> <?php echo $completed_tasks; ?>/<?php echo $total_tasks; ?> done
            </div>
        </div>
    </div>
    
    <!-- CHART SECTION -->
    <div class="chart-section">
        <!-- Project Status Distribution -->
        <div class="chart-card">
            <div class="chart-header">
                <h4 class="chart-title"><i class="fa fa-pie-chart"></i>Project Status</h4>
            </div>
            <div class="chart-canvas">
                <canvas id="projectStatusChart"></canvas>
            </div>
        </div>
        
        <!-- Task Status Overview -->
        <div class="chart-card">
            <div class="chart-header">
                <h4 class="chart-title"><i class="fa fa-bar-chart"></i>Task Overview</h4>
            </div>
            <div class="chart-canvas">
                <canvas id="taskStatusChart"></canvas>
            </div>
        </div>
        
        <!-- Priority Distribution -->
        <div class="chart-card">
            <div class="chart-header">
                <h4 class="chart-title"><i class="fa fa-flag"></i>Task Priorities</h4>
            </div>
            <div class="data-table">
                <?php foreach ($tasks_by_priority as $priority): ?>
                <div class="data-row">
                    <div class="priority-badge <?php echo $priority['priority']; ?>">
                        <i class="fa fa-flag"></i>
                        <?php echo ucfirst($priority['priority']); ?>
                    </div>
                    <div class="data-value"><?php echo $priority['count']; ?> tasks</div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Financial Overview -->
        <div class="chart-card">
            <div class="chart-header">
                <h4 class="chart-title"><i class="fa fa-dollar"></i>Financial Overview</h4>
            </div>
            <div class="data-table">
                <div class="data-row">
                    <strong>Total Budget</strong>
                    <div class="data-value">$<?php echo number_format($budget_stats['total_budget'] ?? 0, 0); ?></div>
                </div>
                <div class="data-row">
                    <strong>Average Budget</strong>
                    <div class="data-value">$<?php echo number_format($budget_stats['avg_budget'] ?? 0, 0); ?></div>
                </div>
                <div class="data-row">
                    <strong>Total Pricing</strong>
                    <div class="data-value">$<?php echo number_format($pricing_total, 0); ?></div>
                </div>
            </div>
            <div class="financial-summary">
                <div class="financial-highlight">
                    $<?php echo number_format(($budget_stats['total_budget'] ?? 0) + $pricing_total, 0); ?>
                </div>
                <div class="financial-label">Combined Portfolio Value</div>
            </div>
        </div>
        
        <!-- Team Distribution -->
        <?php if (!empty($user_roles)): ?>
        <div class="chart-card">
            <div class="chart-header">
                <h4 class="chart-title"><i class="fa fa-user-circle"></i>Team Roles</h4>
            </div>
            <div class="chart-canvas">
                <canvas id="userRolesChart"></canvas>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Project Timeline -->
        <?php if (!empty($project_timeline)): ?>
        <div class="chart-card">
            <div class="chart-header">
                <h4 class="chart-title"><i class="fa fa-line-chart"></i>Project Timeline</h4>
            </div>
            <div class="chart-canvas">
                <canvas id="projectTimelineChart"></canvas>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- OPTIMIZED: Defer Chart.js loading -->
<script>
// Load Chart.js asynchronously
(function() {
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js';
    script.defer = true;
    script.onload = initCharts;
    document.head.appendChild(script);
})();

// Initialize all functionality once DOM and Chart.js are ready
function initCharts() {
    // OPTIMIZED: Use requestAnimationFrame for smoother animations
    requestAnimationFrame(() => {
        initCounters();
        createCharts();
    });
}

// OPTIMIZED COUNTER ANIMATION
function initCounters() {
    const counters = document.querySelectorAll('.counter');
    const duration = 1500; // Reduced from 2000ms
    
    counters.forEach((counter, index) => {
        const target = parseInt(counter.getAttribute('data-target'));
        const increment = target / (duration / 16); // 60fps
        let current = 0;
        
        setTimeout(() => {
            const updateCounter = () => {
                current += increment;
                if (current < target) {
                    counter.textContent = Math.floor(current);
                    requestAnimationFrame(updateCounter);
                } else {
                    counter.textContent = target + (counter.closest('.kpi-card.completion') ? '%' : '');
                }
            };
            updateCounter();
        }, index * 100); // Stagger animation
    });
}

// CREATE ALL CHARTS
function createCharts() {
    // Common chart options for consistency
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: {
                    padding: 20,
                    font: { size: 13, weight: '600' },
                    usePointStyle: true
                }
            },
            tooltip: {
                backgroundColor: 'rgba(30, 41, 59, 0.95)',
                padding: 14,
                titleFont: { size: 14, weight: 'bold' },
                bodyFont: { size: 13 },
                borderColor: '#6366f1',
                borderWidth: 1,
                cornerRadius: 8,
                displayColors: true
            }
        }
    };
    
    // PROJECT STATUS CHART
    const projectStatusCtx = document.getElementById('projectStatusChart');
    if (projectStatusCtx) {
        new Chart(projectStatusCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: [<?php echo implode(',', array_map(function($s) { return "'" . ucfirst(str_replace('_', ' ', $s['status'])) . "'"; }, $projects_by_status)); ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_column($projects_by_status, 'count')); ?>],
                    backgroundColor: ['#6366f1', '#3b82f6', '#10b981', '#f59e0b', '#ef4444'].slice(0, <?php echo count($projects_by_status); ?>),
                    borderWidth: 0,
                    hoverOffset: 15
                }]
            },
            options: {
                ...commonOptions,
                cutout: '65%',
                plugins: {
                    ...commonOptions.plugins,
                    legend: {
                        ...commonOptions.plugins.legend,
                        position: 'bottom'
                    }
                }
            }
        });
    }
    
    // TASK STATUS CHART
    const taskStatusCtx = document.getElementById('taskStatusChart');
    if (taskStatusCtx) {
        new Chart(taskStatusCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: [<?php echo implode(',', array_map(function($s) { return "'" . ucfirst(str_replace('_', ' ', $s['status'])) . "'"; }, $tasks_by_status)); ?>],
                datasets: [{
                    label: 'Tasks',
                    data: [<?php echo implode(',', array_column($tasks_by_status, 'count')); ?>],
                    backgroundColor: '#6366f1',
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                ...commonOptions,
                plugins: {
                    ...commonOptions.plugins,
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.04)',
                            drawBorder: false
                        },
                        ticks: {
                            font: { size: 12, weight: '600' },
                            color: '#64748b'
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { size: 12, weight: '600' },
                            color: '#64748b'
                        }
                    }
                }
            }
        });
    }
    
    <?php if (!empty($user_roles)): ?>
    // USER ROLES CHART
    const userRolesCtx = document.getElementById('userRolesChart');
    if (userRolesCtx) {
        new Chart(userRolesCtx.getContext('2d'), {
            type: 'polarArea',
            data: {
                labels: [<?php echo implode(',', array_map(function($u) { return "'" . ucfirst($u['role']) . "'"; }, $user_roles)); ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_column($user_roles, 'count')); ?>],
                    backgroundColor: [
                        'rgba(99, 102, 241, 0.7)',
                        'rgba(59, 130, 246, 0.7)', 
                        'rgba(16, 185, 129, 0.7)',
                        'rgba(245, 158, 11, 0.7)'
                    ],
                    borderColor: ['#6366f1', '#3b82f6', '#10b981', '#f59e0b'],
                    borderWidth: 2
                }]
            },
            options: {
                ...commonOptions,
                plugins: {
                    ...commonOptions.plugins,
                    legend: {
                        ...commonOptions.plugins.legend,
                        position: 'bottom'
                    }
                },
                scales: {
                    r: {
                        beginAtZero: true,
                        ticks: {
                            font: { size: 11, weight: '600' },
                            color: '#64748b'
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>
    
    <?php if (!empty($project_timeline)): ?>
    // PROJECT TIMELINE CHART
    const projectTimelineCtx = document.getElementById('projectTimelineChart');
    if (projectTimelineCtx) {
        new Chart(projectTimelineCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: [<?php echo implode(',', array_map(function($t) { return "'" . date('M Y', strtotime($t['month'] . '-01')) . "'"; }, $project_timeline)); ?>],
                datasets: [{
                    label: 'Projects Created',
                    data: [<?php echo implode(',', array_column($project_timeline, 'count')); ?>],
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                ...commonOptions,
                plugins: {
                    ...commonOptions.plugins,
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.04)',
                            drawBorder: false
                        },
                        ticks: {
                            font: { size: 12, weight: '600' },
                            color: '#64748b'
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { size: 12, weight: '600' },
                            color: '#64748b'
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>
}

// Initialize counters immediately on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        // Start counters even if Chart.js hasn't loaded yet
        if (typeof Chart === 'undefined') {
            initCounters();
        }
    });
} else {
    // DOM already loaded
    if (typeof Chart === 'undefined') {
        initCounters();
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>