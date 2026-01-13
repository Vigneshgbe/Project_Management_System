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
    .analytics-container {
        background: transparent !important;
        min-height: calc(100vh - 100px) !important;
        padding: 20px !important;
        margin: 0 !important;
        animation: fadeIn 0.6s ease !important;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .analytics-header {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.95) 100%) !important;
        backdrop-filter: blur(25px) !important;
        color: #1e293b !important;
        padding: 50px 60px !important;
        border-radius: 28px !important;
        margin-bottom: 50px !important;
        box-shadow: 0 25px 70px rgba(0, 0, 0, 0.15) !important;
        border: 2px solid rgba(255, 255, 255, 0.5) !important;
        animation: slideDown 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
        position: relative !important;
        overflow: hidden !important;
        text-align: center !important;
    }
    
    .analytics-header::before {
        content: '' !important;
        position: absolute !important;
        top: -50% !important;
        left: -50% !important;
        width: 200% !important;
        height: 200% !important;
        background: conic-gradient(from 0deg, 
            rgba(102, 126, 234, 0.12), 
            rgba(139, 92, 246, 0.08), 
            rgba(59, 130, 246, 0.12), 
            rgba(16, 185, 129, 0.08),
            rgba(102, 126, 234, 0.12)) !important;
        animation: rotate 30s linear infinite !important;
        z-index: 0 !important;
    }
    
    @keyframes rotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-50px) scale(0.95); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    
    .analytics-header h1 {
        margin: 0 0 15px 0 !important;
        font-weight: 900 !important;
        font-size: 48px !important;
        position: relative !important;
        z-index: 1 !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #59C173 100%) !important;
        background-size: 300% 300% !important;
        animation: gradientMove 4s ease infinite !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
    }
    
    .analytics-header h1 i {
        background: inherit !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
        margin-right: 20px !important;
        animation: bounce 3s ease-in-out infinite !important;
    }
    
    @keyframes gradientMove {
        0%, 100% { background-position: 0% 50%; }
        33% { background-position: 100% 50%; }
        66% { background-position: 50% 100%; }
    }
    
    @keyframes bounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-8px); }
    }
    
    .analytics-subtitle {
        color: #64748b !important;
        font-size: 20px !important;
        font-weight: 600 !important;
        position: relative !important;
        z-index: 1 !important;
        margin: 0 !important;
    }
    
    .kpi-grid {
        display: grid !important;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)) !important;
        gap: 30px !important;
        margin-bottom: 50px !important;
    }
    
    .kpi-card {
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.95) 100%) !important;
        backdrop-filter: blur(30px) !important;
        padding: 40px 35px !important;
        border-radius: 28px !important;
        box-shadow: 0 15px 50px rgba(0, 0, 0, 0.08) !important;
        border: 2px solid rgba(255, 255, 255, 0.6) !important;
        position: relative !important;
        overflow: hidden !important;
        transition: all 0.5s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
        animation: cardAppear 0.6s ease !important;
        text-align: center !important;
    }
    
    @keyframes cardAppear {
        from { opacity: 0; transform: translateY(40px) scale(0.95); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    
    .kpi-card::before {
        content: '' !important;
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        height: 8px !important;
        background: linear-gradient(135deg, var(--card-color, #667eea) 0%, var(--card-color-alt, #764ba2) 100%) !important;
        transform: scaleX(0) !important;
        transition: transform 0.5s ease !important;
        transform-origin: left !important;
    }
    
    .kpi-card::after {
        content: '' !important;
        position: absolute !important;
        top: 50% !important;
        right: -50px !important;
        width: 120px !important;
        height: 120px !important;
        background: radial-gradient(circle, var(--card-color, #667eea) 0%, transparent 70%) !important;
        border-radius: 50% !important;
        opacity: 0 !important;
        transform: translateY(-50%) scale(0) !important;
        transition: all 0.6s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
        z-index: 0 !important;
    }
    
    .kpi-card:hover::before {
        transform: scaleX(1) !important;
    }
    
    .kpi-card:hover::after {
        opacity: 0.1 !important;
        transform: translateY(-50%) scale(1) !important;
        right: -30px !important;
    }
    
    .kpi-card:hover {
        transform: translateY(-15px) scale(1.03) !important;
        box-shadow: 0 30px 70px rgba(0, 0, 0, 0.15) !important;
    }
    
    .kpi-card.projects { --card-color: #3b82f6; --card-color-alt: #2563eb; }
    .kpi-card.users { --card-color: #10b981; --card-color-alt: #059669; }
    .kpi-card.tasks { --card-color: #f59e0b; --card-color-alt: #d97706; }
    .kpi-card.completion { --card-color: #8b5cf6; --card-color-alt: #7c3aed; }
    .kpi-card.overdue { --card-color: #ef4444; --card-color-alt: #dc2626; }
    .kpi-card.active { --card-color: #06b6d4; --card-color-alt: #0891b2; }
    
    .kpi-icon {
        width: 90px !important;
        height: 90px !important;
        border-radius: 50% !important;
        background: linear-gradient(135deg, var(--card-color, #667eea) 0%, var(--card-color-alt, #764ba2) 100%) !important;
        color: white !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 36px !important;
        margin: 0 auto 25px !important;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2) !important;
        position: relative !important;
        z-index: 1 !important;
        transition: all 0.4s ease !important;
    }
    
    .kpi-card:hover .kpi-icon {
        transform: scale(1.1) rotate(5deg) !important;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3) !important;
    }
    
    .kpi-value {
        font-size: 56px !important;
        font-weight: 900 !important;
        background: linear-gradient(135deg, #1e293b 0%, #475569 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
        margin-bottom: 12px !important;
        position: relative !important;
        z-index: 1 !important;
        line-height: 1 !important;
    }
    
    .kpi-label {
        color: #64748b !important;
        font-size: 18px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 1.2px !important;
        position: relative !important;
        z-index: 1 !important;
        margin-bottom: 8px !important;
    }
    
    .kpi-trend {
        font-size: 14px !important;
        font-weight: 600 !important;
        position: relative !important;
        z-index: 1 !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        padding: 6px 12px !important;
        border-radius: 20px !important;
        background: rgba(255, 255, 255, 0.7) !important;
        border: 1px solid rgba(0, 0, 0, 0.1) !important;
    }
    
    .kpi-trend.positive { color: #059669 !important; }
    .kpi-trend.negative { color: #dc2626 !important; }
    .kpi-trend.neutral { color: #64748b !important; }
    
    .chart-section {
        display: grid !important;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)) !important;
        gap: 40px !important;
        margin-bottom: 50px !important;
    }
    
    .chart-card {
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.95) 100%) !important;
        backdrop-filter: blur(30px) !important;
        border-radius: 28px !important;
        padding: 40px !important;
        box-shadow: 0 15px 50px rgba(0, 0, 0, 0.08) !important;
        border: 2px solid rgba(255, 255, 255, 0.6) !important;
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
        animation: chartAppear 0.8s ease !important;
        position: relative !important;
        overflow: hidden !important;
    }
    
    @keyframes chartAppear {
        from { opacity: 0; transform: translateY(50px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .chart-card:hover {
        transform: translateY(-8px) !important;
        box-shadow: 0 25px 70px rgba(0, 0, 0, 0.12) !important;
    }
    
    .chart-header {
        margin-bottom: 35px !important;
        text-align: center !important;
        padding-bottom: 20px !important;
        border-bottom: 3px solid transparent !important;
        border-image: linear-gradient(90deg, #667eea 0%, #764ba2 100%) !important;
        border-image-slice: 1 !important;
    }
    
    .chart-title {
        font-size: 24px !important;
        font-weight: 800 !important;
        color: #1e293b !important;
        margin: 0 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 15px !important;
    }
    
    .chart-title i {
        color: #667eea !important;
        font-size: 22px !important;
    }
    
    .chart-canvas {
        position: relative !important;
        width: 100% !important;
        height: 300px !important;
    }
    
    .data-table {
        width: 100% !important;
    }
    
    .data-row {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        padding: 20px 0 !important;
        border-bottom: 2px solid #f1f5f9 !important;
        transition: all 0.3s ease !important;
    }
    
    .data-row:last-child {
        border-bottom: none !important;
    }
    
    .data-row:hover {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.03) 0%, rgba(118, 75, 162, 0.03) 100%) !important;
        padding-left: 20px !important;
        border-radius: 16px !important;
    }
    
    .priority-badge {
        padding: 10px 18px !important;
        border-radius: 30px !important;
        font-size: 13px !important;
        font-weight: 800 !important;
        text-transform: uppercase !important;
        letter-spacing: 1px !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 8px !important;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15) !important;
        transition: all 0.3s ease !important;
    }
    
    .priority-badge:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2) !important;
    }
    
    .priority-badge.low { 
        background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
        color: white !important;
    }
    
    .priority-badge.medium { 
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%) !important;
        color: white !important;
    }
    
    .priority-badge.high { 
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%) !important;
        color: white !important;
    }
    
    .priority-badge.critical { 
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        color: white !important;
        animation: criticalPulse 2s ease-in-out infinite !important;
    }
    
    @keyframes criticalPulse {
        0%, 100% { 
            transform: scale(1); 
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.15), 0 0 0 0 rgba(239, 68, 68, 0.5); 
        }
        50% { 
            transform: scale(1.05); 
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.25), 0 0 0 12px rgba(239, 68, 68, 0); 
        }
    }
    
    .data-value {
        font-size: 18px !important;
        font-weight: 800 !important;
        color: #1e293b !important;
    }
    
    .financial-summary {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.08) 0%, rgba(5, 150, 105, 0.08) 100%) !important;
        border: 2px solid rgba(16, 185, 129, 0.2) !important;
        border-radius: 20px !important;
        padding: 25px !important;
        margin-top: 20px !important;
        text-align: center !important;
    }
    
    .financial-highlight {
        font-size: 32px !important;
        font-weight: 900 !important;
        color: #059669 !important;
        margin-bottom: 8px !important;
    }
    
    .financial-label {
        color: #064e3b !important;
        font-weight: 700 !important;
        font-size: 14px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.8px !important;
    }
    
    /* RESPONSIVE */
    @media (max-width: 1200px) {
        .analytics-container { padding: 15px !important; }
        .analytics-header { 
            padding: 35px 40px !important; 
            margin-bottom: 35px !important;
        }
        .analytics-header h1 { font-size: 40px !important; }
        .kpi-grid { gap: 25px !important; }
        .chart-section { gap: 30px !important; }
    }
    
    @media (max-width: 768px) {
        .analytics-container { padding: 10px !important; }
        .analytics-header { 
            padding: 25px 30px !important; 
            margin-bottom: 25px !important;
        }
        .analytics-header h1 { font-size: 32px !important; }
        .analytics-subtitle { font-size: 18px !important; }
        .kpi-grid { 
            grid-template-columns: 1fr !important; 
            gap: 20px !important;
        }
        .kpi-card { padding: 30px 25px !important; }
        .kpi-value { font-size: 48px !important; }
        .chart-section { 
            grid-template-columns: 1fr !important;
            gap: 25px !important;
        }
        .chart-card { padding: 30px !important; }
    }
    
    @media (max-width: 480px) {
        .analytics-header h1 { font-size: 26px !important; }
        .analytics-subtitle { font-size: 16px !important; }
        .kpi-card { padding: 20px !important; }
        .kpi-value { font-size: 36px !important; }
        .kpi-icon { 
            width: 70px !important; 
            height: 70px !important; 
            font-size: 28px !important; 
        }
        .chart-card { padding: 20px !important; }
        .chart-title { font-size: 20px !important; }
    }
</style>

<div class="analytics-container container-fluid">
    <div class="analytics-header">
        <h1><i class="fa fa-bar-chart"></i>Analytics Dashboard</h1>
        <p class="analytics-subtitle">Comprehensive insights into your project management ecosystem</p>
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
                    <div><strong>Total Budget</strong></div>
                    <div class="data-value">$<?php echo number_format($budget_stats['total_budget'] ?? 0, 0); ?></div>
                </div>
                <div class="data-row">
                    <div><strong>Average Budget</strong></div>
                    <div class="data-value">$<?php echo number_format($budget_stats['avg_budget'] ?? 0, 0); ?></div>
                </div>
                <div class="data-row">
                    <div><strong>Total Pricing</strong></div>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
$(document).ready(function() {
    // ANIMATED COUNTERS
    $('.counter').each(function(index) {
        const $this = $(this);
        const countTo = parseInt($this.attr('data-target'));
        
        setTimeout(() => {
            $({ countNum: 0 }).animate({
                countNum: countTo
            }, {
                duration: 2000,
                easing: 'easeOutCubic',
                step: function() {
                    $this.text(Math.floor(this.countNum));
                },
                complete: function() {
                    $this.text(this.countNum + ($this.siblings('.kpi-label').text().includes('%') ? '%' : ''));
                }
            });
        }, index * 200);
    });
    
    // STAGGERED ANIMATIONS
    $('.kpi-card').each(function(index) {
        $(this).css({
            'animation-delay': `${index * 0.1}s`
        });
    });
    
    $('.chart-card').each(function(index) {
        $(this).css({
            'animation-delay': `${index * 0.15 + 0.5}s`
        });
    });
});

// CHART UTILITIES
function createGradient(ctx, color1, color2) {
    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, color1);
    gradient.addColorStop(1, color2);
    return gradient;
}

// PROJECT STATUS CHART
const projectStatusCtx = document.getElementById('projectStatusChart').getContext('2d');
new Chart(projectStatusCtx, {
    type: 'doughnut',
    data: {
        labels: [<?php echo implode(',', array_map(function($s) { return "'" . ucfirst(str_replace('_', ' ', $s['status'])) . "'"; }, $projects_by_status)); ?>],
        datasets: [{
            data: [<?php echo implode(',', array_column($projects_by_status, 'count')); ?>],
            backgroundColor: [
                '#667eea', '#3b82f6', '#10b981', '#f59e0b', '#ef4444'
            ].slice(0, <?php echo count($projects_by_status); ?>),
            borderWidth: 0,
            hoverOffset: 20
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '60%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 25,
                    font: { size: 14, weight: '700' },
                    usePointStyle: true
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.9)',
                padding: 16,
                titleFont: { size: 16, weight: 'bold' },
                bodyFont: { size: 14 },
                borderColor: '#667eea',
                borderWidth: 2,
                cornerRadius: 12
            }
        }
    }
});

// TASK STATUS CHART
const taskStatusCtx = document.getElementById('taskStatusChart').getContext('2d');
new Chart(taskStatusCtx, {
    type: 'bar',
    data: {
        labels: [<?php echo implode(',', array_map(function($s) { return "'" . ucfirst(str_replace('_', ' ', $s['status'])) . "'"; }, $tasks_by_status)); ?>],
        datasets: [{
            label: 'Tasks',
            data: [<?php echo implode(',', array_column($tasks_by_status, 'count')); ?>],
            backgroundColor: createGradient(taskStatusCtx, '#667eea', '#764ba2'),
            borderRadius: 20,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.9)',
                padding: 16,
                titleFont: { size: 16, weight: 'bold' },
                bodyFont: { size: 14 },
                borderColor: '#667eea',
                borderWidth: 2,
                cornerRadius: 12
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
                    font: { size: 13, weight: '600' },
                    color: '#64748b'
                }
            },
            x: {
                grid: { display: false },
                ticks: {
                    font: { size: 13, weight: '600' },
                    color: '#64748b'
                }
            }
        }
    }
});

<?php if (!empty($user_roles)): ?>
// USER ROLES CHART
const userRolesCtx = document.getElementById('userRolesChart').getContext('2d');
new Chart(userRolesCtx, {
    type: 'polarArea',
    data: {
        labels: [<?php echo implode(',', array_map(function($u) { return "'" . ucfirst($u['role']) . "'"; }, $user_roles)); ?>],
        datasets: [{
            data: [<?php echo implode(',', array_column($user_roles, 'count')); ?>],
            backgroundColor: [
                'rgba(102, 126, 234, 0.8)',
                'rgba(59, 130, 246, 0.8)', 
                'rgba(16, 185, 129, 0.8)',
                'rgba(245, 158, 11, 0.8)'
            ],
            borderColor: ['#667eea', '#3b82f6', '#10b981', '#f59e0b'],
            borderWidth: 3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 25,
                    font: { size: 14, weight: '700' }
                }
            }
        },
        scales: {
            r: {
                beginAtZero: true,
                ticks: {
                    font: { size: 12, weight: '600' },
                    color: '#64748b'
                }
            }
        }
    }
});
<?php endif; ?>

<?php if (!empty($project_timeline)): ?>
// PROJECT TIMELINE CHART
const projectTimelineCtx = document.getElementById('projectTimelineChart').getContext('2d');
new Chart(projectTimelineCtx, {
    type: 'line',
    data: {
        labels: [<?php echo implode(',', array_map(function($t) { return "'" . date('M Y', strtotime($t['month'] . '-01')) . "'"; }, $project_timeline)); ?>],
        datasets: [{
            label: 'Projects Created',
            data: [<?php echo implode(',', array_column($project_timeline, 'count')); ?>],
            borderColor: '#667eea',
            backgroundColor: createGradient(projectTimelineCtx, 'rgba(102, 126, 234, 0.3)', 'rgba(102, 126, 234, 0.1)'),
            borderWidth: 4,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#667eea',
            pointBorderColor: '#fff',
            pointBorderWidth: 3,
            pointRadius: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.9)',
                padding: 16,
                titleFont: { size: 16, weight: 'bold' },
                bodyFont: { size: 14 },
                borderColor: '#667eea',
                borderWidth: 2,
                cornerRadius: 12
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
                    font: { size: 13, weight: '600' },
                    color: '#64748b'
                }
            },
            x: {
                grid: { display: false },
                ticks: {
                    font: { size: 13, weight: '600' },
                    color: '#64748b'
                }
            }
        }
    }
});
<?php endif; ?>
</script>

<?php require_once '../includes/footer.php'; ?>