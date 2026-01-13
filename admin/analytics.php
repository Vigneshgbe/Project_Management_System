<?php
$page_title = 'Analytics Dashboard';
require_once '../includes/header.php';

$auth->checkAccess('admin');

$db = getDB();

// Get statistics
$total_projects = $db->query("SELECT COUNT(*) as count FROM projects")->fetch_assoc()['count'];
$total_users = $db->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'")->fetch_assoc()['count'];
$total_tasks = $db->query("SELECT COUNT(*) as count FROM tasks")->fetch_assoc()['count'];
$completed_tasks = $db->query("SELECT COUNT(*) as count FROM tasks WHERE status = 'completed'")->fetch_assoc()['count'];

// Get additional statistics
$pending_projects = $db->query("SELECT COUNT(*) as count FROM projects WHERE status = 'planning'")->fetch_assoc()['count'];
$in_progress_projects = $db->query("SELECT COUNT(*) as count FROM projects WHERE status = 'active'")->fetch_assoc()['count'];
$overdue_tasks = $db->query("SELECT COUNT(*) as count FROM tasks WHERE due_date < CURDATE() AND status != 'completed'")->fetch_assoc()['count'];

// Projects by status
$projects_by_status = $db->query("SELECT status, COUNT(*) as count FROM projects GROUP BY status")->fetch_all(MYSQLI_ASSOC);

// Tasks by status
$tasks_by_status = $db->query("SELECT status, COUNT(*) as count FROM tasks GROUP BY status")->fetch_all(MYSQLI_ASSOC);

// Tasks by priority
$tasks_by_priority = $db->query("SELECT priority, COUNT(*) as count FROM tasks GROUP BY priority")->fetch_all(MYSQLI_ASSOC);

// Projects by priority
$projects_by_priority = $db->query("SELECT priority, COUNT(*) as count FROM projects GROUP BY priority")->fetch_all(MYSQLI_ASSOC);

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

// Performance metrics
$completion_rate = $total_tasks > 0 ? round(($completed_tasks / $total_tasks) * 100, 1) : 0;
$project_completion_rate = $db->query("SELECT COUNT(*) as count FROM projects WHERE status = 'completed'")->fetch_assoc()['count'];
$project_completion_percentage = $total_projects > 0 ? round(($project_completion_rate / $total_projects) * 100, 1) : 0;

// Monthly task trends (last 6 months)
$monthly_tasks = $db->query("
    SELECT 
        MONTH(created_at) as month,
        YEAR(created_at) as year,
        COUNT(*) as count 
    FROM tasks 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY YEAR(created_at), MONTH(created_at)
    ORDER BY YEAR(created_at), MONTH(created_at)
")->fetch_all(MYSQLI_ASSOC);
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
        padding: 40px 50px !important;
        border-radius: 24px !important;
        margin-bottom: 40px !important;
        box-shadow: 0 15px 50px rgba(0, 0, 0, 0.25) !important;
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
        background: conic-gradient(from 0deg, 
            rgba(102, 126, 234, 0.1) 0deg,
            rgba(118, 75, 162, 0.1) 60deg,
            rgba(59, 130, 246, 0.1) 120deg,
            rgba(139, 92, 246, 0.1) 180deg,
            rgba(236, 72, 153, 0.1) 240deg,
            rgba(102, 126, 234, 0.1) 300deg,
            rgba(102, 126, 234, 0.1) 360deg) !important;
        animation: rotate 25s linear infinite !important;
    }
    
    @keyframes rotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-40px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .analytics-header h1 {
        margin: 0 !important;
        font-weight: 900 !important;
        font-size: 38px !important;
        position: relative !important;
        z-index: 1 !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #667eea 100%) !important;
        background-size: 200% 200% !important;
        animation: gradient 3s ease infinite !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
        display: flex !important;
        align-items: center !important;
        gap: 20px !important;
    }
    
    .analytics-header h1 i {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
        animation: pulse 2s ease-in-out infinite !important;
    }
    
    @keyframes gradient {
        0%, 100% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
    }
    
    .analytics-subtitle {
        margin-top: 10px !important;
        position: relative !important;
        z-index: 1 !important;
        color: #64748b !important;
        font-size: 16px !important;
        font-weight: 600 !important;
    }
    
    .stat-grid {
        display: grid !important;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)) !important;
        gap: 25px !important;
        margin-bottom: 40px !important;
    }
    
    .stat-card {
        background: rgba(255, 255, 255, 0.98) !important;
        backdrop-filter: blur(25px) !important;
        padding: 35px 30px !important;
        border-radius: 24px !important;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12) !important;
        border: 1px solid rgba(255, 255, 255, 0.5) !important;
        position: relative !important;
        overflow: hidden !important;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
        animation: scaleIn 0.6s ease !important;
    }
    
    @keyframes scaleIn {
        from { opacity: 0; transform: scale(0.9) translateY(20px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }
    
    .stat-card::before {
        content: '' !important;
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        width: 6px !important;
        height: 100% !important;
        background: linear-gradient(135deg, var(--accent-color, #667eea) 0%, var(--accent-color-alt, #764ba2) 100%) !important;
        transition: all 0.4s ease !important;
    }
    
    .stat-card::after {
        content: '' !important;
        position: absolute !important;
        top: 50% !important;
        right: 30px !important;
        width: 60px !important;
        height: 60px !important;
        background: linear-gradient(135deg, var(--accent-color, #667eea) 0%, var(--accent-color-alt, #764ba2) 100%) !important;
        border-radius: 50% !important;
        opacity: 0.1 !important;
        transform: translateY(-50%) scale(0) !important;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
    
    .stat-card:hover::before {
        width: 100% !important;
        opacity: 0.05 !important;
    }
    
    .stat-card:hover::after {
        transform: translateY(-50%) scale(1) !important;
        opacity: 0.15 !important;
    }
    
    .stat-card:hover {
        transform: translateY(-12px) !important;
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.2) !important;
    }
    
    .stat-card.projects { --accent-color: #3b82f6; --accent-color-alt: #2563eb; }
    .stat-card.users { --accent-color: #10b981; --accent-color-alt: #059669; }
    .stat-card.tasks { --accent-color: #f59e0b; --accent-color-alt: #d97706; }
    .stat-card.completion { --accent-color: #8b5cf6; --accent-color-alt: #7c3aed; }
    .stat-card.pending { --accent-color: #6b7280; --accent-color-alt: #4b5563; }
    .stat-card.progress { --accent-color: #06b6d4; --accent-color-alt: #0891b2; }
    .stat-card.overdue { --accent-color: #ef4444; --accent-color-alt: #dc2626; }
    .stat-card.projects-complete { --accent-color: #22c55e; --accent-color-alt: #16a34a; }
    
    .stat-number {
        font-size: 48px !important;
        font-weight: 900 !important;
        background: linear-gradient(135deg, var(--accent-color, #667eea) 0%, var(--accent-color-alt, #764ba2) 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
        margin-bottom: 12px !important;
        position: relative !important;
        z-index: 1 !important;
        line-height: 1 !important;
    }
    
    .stat-label {
        color: #64748b !important;
        margin: 0 !important;
        font-weight: 700 !important;
        font-size: 14px !important;
        text-transform: uppercase !important;
        letter-spacing: 1px !important;
        position: relative !important;
        z-index: 1 !important;
    }
    
    .stat-icon {
        position: absolute !important;
        top: 30px !important;
        right: 30px !important;
        font-size: 24px !important;
        color: var(--accent-color, #667eea) !important;
        opacity: 0.7 !important;
        z-index: 1 !important;
        transition: all 0.3s ease !important;
    }
    
    .stat-card:hover .stat-icon {
        opacity: 1 !important;
        transform: scale(1.1) !important;
    }
    
    .chart-grid {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 25px !important;
        margin-bottom: 40px !important;
    }
    
    .chart-card {
        background: rgba(255, 255, 255, 0.98) !important;
        backdrop-filter: blur(25px) !important;
        border-radius: 24px !important;
        padding: 35px !important;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12) !important;
        border: 1px solid rgba(255, 255, 255, 0.5) !important;
        transition: all 0.4s ease !important;
        animation: slideUp 0.7s ease !important;
        position: relative !important;
        overflow: hidden !important;
    }
    
    .chart-card::before {
        content: '' !important;
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        height: 4px !important;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 50%, #667eea 100%) !important;
        background-size: 200% 200% !important;
        animation: gradient 3s ease infinite !important;
    }
    
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .chart-card:hover {
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.18) !important;
        transform: translateY(-8px) !important;
    }
    
    .chart-card h4 {
        margin-top: 0 !important;
        margin-bottom: 25px !important;
        font-size: 22px !important;
        font-weight: 800 !important;
        color: #1e293b !important;
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        padding-bottom: 20px !important;
        border-bottom: 2px solid #f1f5f9 !important;
    }
    
    .chart-card h4 i {
        color: #667eea !important;
        font-size: 20px !important;
    }
    
    .chart-canvas-wrapper {
        position: relative !important;
        width: 100% !important;
        height: 350px !important;
        margin-bottom: 20px !important;
    }
    
    .chart-canvas-wrapper canvas {
        max-height: 350px !important;
    }
    
    .analytics-table {
        width: 100% !important;
        margin: 0 !important;
        border-collapse: separate !important;
        border-spacing: 0 8px !important;
    }
    
    .analytics-table tr {
        transition: all 0.3s ease !important;
    }
    
    .analytics-table tr:hover {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.08) 0%, rgba(118, 75, 162, 0.08) 100%) !important;
        transform: translateX(8px) !important;
        border-radius: 12px !important;
    }
    
    .analytics-table td {
        padding: 18px 20px !important;
        background: #ffffff !important;
        color: #1e293b !important;
        font-weight: 600 !important;
        border: none !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06) !important;
    }
    
    .analytics-table tr td:first-child {
        border-radius: 12px 0 0 12px !important;
    }
    
    .analytics-table tr td:last-child {
        border-radius: 0 12px 12px 0 !important;
        text-align: right !important;
    }
    
    .badge-priority {
        padding: 8px 16px !important;
        border-radius: 25px !important;
        font-size: 11px !important;
        font-weight: 800 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.8px !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        color: white !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
        transition: all 0.3s ease !important;
    }
    
    .badge-priority:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25) !important;
    }
    
    .badge-priority i {
        font-size: 10px !important;
    }
    
    .badge-low { 
        background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
    }
    
    .badge-medium { 
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%) !important;
    }
    
    .badge-high { 
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%) !important;
    }
    
    .badge-critical { 
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        animation: criticalPulse 2s infinite !important;
    }
    
    @keyframes criticalPulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.9; transform: scale(1.05); }
    }
    
    .trend-card {
        background: rgba(255, 255, 255, 0.98) !important;
        backdrop-filter: blur(25px) !important;
        border-radius: 24px !important;
        padding: 35px !important;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12) !important;
        border: 1px solid rgba(255, 255, 255, 0.5) !important;
        margin-bottom: 30px !important;
        animation: slideUp 0.8s ease !important;
        position: relative !important;
        overflow: hidden !important;
    }
    
    .trend-card::before {
        content: '' !important;
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        height: 4px !important;
        background: linear-gradient(90deg, #22c55e 0%, #16a34a 100%) !important;
    }
    
    /* ENHANCED LOADING ANIMATIONS */
    .stat-card {
        animation-delay: calc(var(--delay, 0) * 0.1s) !important;
    }
    
    /* RESPONSIVE DESIGN */
    @media (max-width: 1400px) {
        .chart-grid {
            grid-template-columns: 1fr !important;
        }
    }
    
    @media (max-width: 1200px) {
        .analytics-container {
            padding: 15px !important;
        }
        .analytics-header {
            padding: 30px 35px !important;
        }
        .analytics-header h1 {
            font-size: 32px !important;
        }
        .stat-grid {
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)) !important;
        }
    }
    
    @media (max-width: 992px) {
        .chart-grid {
            grid-template-columns: 1fr !important;
            gap: 20px !important;
        }
        .stat-grid {
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) !important;
            gap: 20px !important;
        }
    }
    
    @media (max-width: 768px) {
        .analytics-container {
            padding: 10px !important;
        }
        .analytics-header {
            padding: 25px 20px !important;
            margin-bottom: 25px !important;
        }
        .analytics-header h1 {
            font-size: 28px !important;
            flex-direction: column !important;
            text-align: center !important;
            gap: 10px !important;
        }
        .stat-card {
            padding: 25px 20px !important;
        }
        .stat-number {
            font-size: 36px !important;
        }
        .chart-card {
            padding: 25px 20px !important;
        }
        .chart-canvas-wrapper {
            height: 280px !important;
        }
        .stat-grid {
            grid-template-columns: 1fr 1fr !important;
        }
    }
    
    @media (max-width: 480px) {
        .analytics-container {
            padding: 8px !important;
        }
        .analytics-header {
            padding: 20px 15px !important;
        }
        .analytics-header h1 {
            font-size: 24px !important;
        }
        .stat-grid {
            grid-template-columns: 1fr !important;
        }
        .stat-card {
            padding: 20px 15px !important;
        }
        .chart-card {
            padding: 20px 15px !important;
        }
        .chart-canvas-wrapper {
            height: 250px !important;
        }
    }
    
    /* PERFORMANCE OPTIMIZATIONS */
    .analytics-container * {
        will-change: transform !important;
    }
    
    /* ACCESSIBILITY */
    @media (prefers-reduced-motion: reduce) {
        * {
            animation: none !important;
            transition: none !important;
        }
    }
</style>

<div class="analytics-container container-fluid">
    <div class="analytics-header">
        <h1><i class="fa fa-bar-chart"></i> Analytics Dashboard</h1>
        <div class="analytics-subtitle">
            Real-time insights and performance metrics for your projects
        </div>
    </div>
    
    <!-- MAIN STATISTICS GRID -->
    <div class="stat-grid">
        <div class="stat-card projects" style="--delay: 0;">
            <i class="fa fa-folder-open stat-icon"></i>
            <div class="stat-number counter" data-target="<?php echo $total_projects; ?>">0</div>
            <p class="stat-label">Total Projects</p>
        </div>
        <div class="stat-card users" style="--delay: 1;">
            <i class="fa fa-users stat-icon"></i>
            <div class="stat-number counter" data-target="<?php echo $total_users; ?>">0</div>
            <p class="stat-label">Active Users</p>
        </div>
        <div class="stat-card tasks" style="--delay: 2;">
            <i class="fa fa-tasks stat-icon"></i>
            <div class="stat-number counter" data-target="<?php echo $total_tasks; ?>">0</div>
            <p class="stat-label">Total Tasks</p>
        </div>
        <div class="stat-card completion" style="--delay: 3;">
            <i class="fa fa-check-circle stat-icon"></i>
            <div class="stat-number counter" data-target="<?php echo $completion_rate; ?>">0</div>
            <p class="stat-label">Task Completion Rate</p>
        </div>
    </div>
    
    <!-- SECONDARY METRICS -->
    <div class="stat-grid">
        <div class="stat-card pending" style="--delay: 4;">
            <i class="fa fa-clock-o stat-icon"></i>
            <div class="stat-number counter" data-target="<?php echo $pending_projects; ?>">0</div>
            <p class="stat-label">Pending Projects</p>
        </div>
        <div class="stat-card progress" style="--delay: 5;">
            <i class="fa fa-spinner stat-icon"></i>
            <div class="stat-number counter" data-target="<?php echo $in_progress_projects; ?>">0</div>
            <p class="stat-label">Active Projects</p>
        </div>
        <div class="stat-card overdue" style="--delay: 6;">
            <i class="fa fa-exclamation-triangle stat-icon"></i>
            <div class="stat-number counter" data-target="<?php echo $overdue_tasks; ?>">0</div>
            <p class="stat-label">Overdue Tasks</p>
        </div>
        <div class="stat-card projects-complete" style="--delay: 7;">
            <i class="fa fa-trophy stat-icon"></i>
            <div class="stat-number counter" data-target="<?php echo $project_completion_percentage; ?>">0</div>
            <p class="stat-label">Project Success Rate</p>
        </div>
    </div>
    
    <!-- CHARTS SECTION -->
    <div class="chart-grid">
        <div class="chart-card">
            <h4><i class="fa fa-pie-chart"></i> Projects by Status</h4>
            <div class="chart-canvas-wrapper">
                <canvas id="projectStatusChart"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <h4><i class="fa fa-bar-chart"></i> Tasks by Status</h4>
            <div class="chart-canvas-wrapper">
                <canvas id="taskStatusChart"></canvas>
            </div>
        </div>
    </div>
    
    <div class="chart-grid">
        <div class="chart-card">
            <h4><i class="fa fa-line-chart"></i> Task Trends (6 Months)</h4>
            <div class="chart-canvas-wrapper">
                <canvas id="taskTrendChart"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <h4><i class="fa fa-pie-chart"></i> Tasks by Priority</h4>
            <div class="chart-canvas-wrapper">
                <canvas id="taskPriorityChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- DETAILED ANALYSIS -->
    <div class="chart-grid">
        <div class="chart-card">
            <h4><i class="fa fa-flag"></i> Projects by Priority</h4>
            <table class="analytics-table">
                <?php foreach ($projects_by_priority as $stat): ?>
                <tr>
                    <td>
                        <span class="badge-priority badge-<?php echo $stat['priority']; ?>">
                            <i class="fa fa-flag"></i>
                            <?php echo ucfirst($stat['priority']); ?>
                        </span>
                    </td>
                    <td>
                        <strong><?php echo $stat['count']; ?> projects</strong>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        
        <div class="chart-card">
            <h4><i class="fa fa-dollar"></i> Financial Overview</h4>
            <table class="analytics-table">
                <tr>
                    <td><strong>Total Budget</strong></td>
                    <td><strong>$<?php echo number_format($budget_stats['total_budget'] ?? 0, 2); ?></strong></td>
                </tr>
                <tr>
                    <td><strong>Average Budget</strong></td>
                    <td><strong>$<?php echo number_format($budget_stats['avg_budget'] ?? 0, 2); ?></strong></td>
                </tr>
                <tr>
                    <td><strong>Total Revenue</strong></td>
                    <td><strong>$<?php echo number_format($pricing_total, 2); ?></strong></td>
                </tr>
                <tr>
                    <td><strong>Project ROI</strong></td>
                    <td><strong><?php echo $budget_stats['total_budget'] > 0 ? round(($pricing_total / $budget_stats['total_budget']) * 100, 1) : 0; ?>%</strong></td>
                </tr>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
$(document).ready(function() {
    // ENHANCED ANIMATED COUNTER WITH EASING
    $('.counter').each(function(index) {
        const $this = $(this);
        const countTo = parseFloat($this.attr('data-target'));
        const isPercentage = $this.closest('.stat-card').find('.stat-label').text().toLowerCase().includes('rate') || 
                            $this.closest('.stat-card').find('.stat-label').text().toLowerCase().includes('success');
        
        // Delay animation based on index
        setTimeout(() => {
            $({ countNum: 0 }).animate({
                countNum: countTo
            }, {
                duration: 2500,
                easing: 'easeOutCubic',
                step: function() {
                    const current = Math.floor(this.countNum);
                    $this.text(current + (isPercentage && current > 0 ? '%' : ''));
                },
                complete: function() {
                    const final = this.countNum + (isPercentage ? '%' : '');
                    $this.text(final);
                    
                    // Add completion animation
                    $this.closest('.stat-card').addClass('completed');
                }
            });
        }, index * 150);
    });
    
    // STAGGERED CARD ANIMATIONS
    $('.chart-card').each(function(index) {
        $(this).css({
            'animation': `slideUp 0.7s ease ${(index + 4) * 0.1}s both`
        });
    });
    
    // INTERSECTION OBSERVER FOR SCROLL ANIMATIONS
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animationPlayState = 'running';
            }
        });
    }, observerOptions);
    
    document.querySelectorAll('.chart-card, .stat-card').forEach(el => {
        observer.observe(el);
    });
});

// ENHANCED CHART CONFIGURATION
Chart.defaults.font.family = "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
Chart.defaults.font.weight = '600';
Chart.defaults.plugins.legend.labels.usePointStyle = true;
Chart.defaults.plugins.legend.labels.pointStyle = 'circle';

// GRADIENT HELPER FUNCTION
function createGradient(ctx, color1, color2, vertical = true) {
    const gradient = vertical 
        ? ctx.createLinearGradient(0, 0, 0, ctx.canvas.height)
        : ctx.createLinearGradient(0, 0, ctx.canvas.width, 0);
    gradient.addColorStop(0, color1);
    gradient.addColorStop(1, color2);
    return gradient;
}

// PROJECTS BY STATUS CHART
const projectStatusCtx = document.getElementById('projectStatusChart').getContext('2d');
new Chart(projectStatusCtx, {
    type: 'doughnut',
    data: {
        labels: [<?php echo implode(',', array_map(function($s) { return "'" . ucfirst(str_replace('_', ' ', $s['status'])) . "'"; }, $projects_by_status)); ?>],
        datasets: [{
            data: [<?php echo implode(',', array_column($projects_by_status, 'count')); ?>],
            backgroundColor: [
                createGradient(projectStatusCtx, '#3b82f6', '#2563eb'),
                createGradient(projectStatusCtx, '#10b981', '#059669'),
                createGradient(projectStatusCtx, '#f59e0b', '#d97706'),
                createGradient(projectStatusCtx, '#ef4444', '#dc2626'),
                createGradient(projectStatusCtx, '#8b5cf6', '#7c3aed')
            ],
            borderWidth: 0,
            hoverOffset: 20,
            hoverBorderWidth: 4,
            hoverBorderColor: '#ffffff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 20,
                    font: { size: 13, weight: '700' },
                    color: '#475569'
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.9)',
                padding: 16,
                titleFont: { size: 16, weight: 'bold' },
                bodyFont: { size: 14 },
                borderColor: '#667eea',
                borderWidth: 2,
                cornerRadius: 12,
                displayColors: true,
                usePointStyle: true
            }
        },
        animation: {
            animateRotate: true,
            animateScale: true,
            duration: 2000
        }
    }
});

// TASKS BY STATUS CHART
const taskStatusCtx = document.getElementById('taskStatusChart').getContext('2d');
new Chart(taskStatusCtx, {
    type: 'bar',
    data: {
        labels: [<?php echo implode(',', array_map(function($s) { return "'" . ucfirst(str_replace('_', ' ', $s['status'])) . "'"; }, $tasks_by_status)); ?>],
        datasets: [{
            label: 'Tasks',
            data: [<?php echo implode(',', array_column($tasks_by_status, 'count')); ?>],
            backgroundColor: createGradient(taskStatusCtx, '#667eea', '#764ba2'),
            borderRadius: 15,
            borderSkipped: false,
            hoverBackgroundColor: createGradient(taskStatusCtx, '#764ba2', '#667eea')
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
                    color: 'rgba(0, 0, 0, 0.04)',
                    drawBorder: false
                },
                ticks: {
                    font: { size: 13, weight: '600' },
                    color: '#64748b',
                    padding: 12
                }
            },
            x: {
                grid: { display: false, drawBorder: false },
                ticks: {
                    font: { size: 13, weight: '600' },
                    color: '#64748b',
                    padding: 12
                }
            }
        },
        animation: {
            duration: 2000,
            easing: 'easeOutQuart'
        }
    }
});

// TASK TRENDS CHART
const taskTrendCtx = document.getElementById('taskTrendChart').getContext('2d');
const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
const trendData = <?php echo json_encode($monthly_tasks); ?>;
const trendLabels = trendData.map(item => monthNames[item.month - 1] + ' ' + item.year);
const trendCounts = trendData.map(item => item.count);

new Chart(taskTrendCtx, {
    type: 'line',
    data: {
        labels: trendLabels,
        datasets: [{
            label: 'Tasks Created',
            data: trendCounts,
            borderColor: '#22c55e',
            backgroundColor: createGradient(taskTrendCtx, 'rgba(34, 197, 94, 0.2)', 'rgba(34, 197, 94, 0.05)'),
            borderWidth: 4,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#22c55e',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 3,
            pointRadius: 8,
            pointHoverRadius: 12
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
                borderColor: '#22c55e',
                borderWidth: 2,
                cornerRadius: 12
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0, 0, 0, 0.04)',
                    drawBorder: false
                },
                ticks: {
                    font: { size: 13, weight: '600' },
                    color: '#64748b',
                    padding: 12
                }
            },
            x: {
                grid: { display: false, drawBorder: false },
                ticks: {
                    font: { size: 13, weight: '600' },
                    color: '#64748b',
                    padding: 12
                }
            }
        },
        animation: {
            duration: 2500,
            easing: 'easeOutQuart'
        }
    }
});

// TASKS BY PRIORITY CHART
const taskPriorityCtx = document.getElementById('taskPriorityChart').getContext('2d');
new Chart(taskPriorityCtx, {
    type: 'polarArea',
    data: {
        labels: [<?php echo implode(',', array_map(function($s) { return "'" . ucfirst($s['priority']) . "'"; }, $tasks_by_priority)); ?>],
        datasets: [{
            data: [<?php echo implode(',', array_column($tasks_by_priority, 'count')); ?>],
            backgroundColor: [
                'rgba(16, 185, 129, 0.8)',
                'rgba(251, 191, 36, 0.8)',
                'rgba(249, 115, 22, 0.8)',
                'rgba(239, 68, 68, 0.8)'
            ],
            borderColor: [
                '#10b981',
                '#fbbf24',
                '#f97316',
                '#ef4444'
            ],
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
                    padding: 20,
                    font: { size: 13, weight: '700' },
                    color: '#475569'
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
        },
        scales: {
            r: {
                beginAtZero: true,
                grid: { color: 'rgba(0, 0, 0, 0.1)' },
                pointLabels: {
                    font: { size: 13, weight: '600' },
                    color: '#64748b'
                }
            }
        },
        animation: {
            animateRotate: true,
            animateScale: true,
            duration: 2500
        }
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>