<?php
$page_title = 'Activity Log';
require_once '../includes/header.php';

$auth->checkAccess('admin');

$db = getDB();

$limit = 100;
$page = $_GET['page'] ?? 1;
$offset = ($page - 1) * $limit;

$total_logs = $db->query("SELECT COUNT(*) as count FROM activity_log")->fetch_assoc()['count'];
$total_pages = ceil($total_logs / $limit);

$logs = $db->query("
    SELECT al.*, u.full_name, p.project_name 
    FROM activity_log al 
    LEFT JOIN users u ON al.user_id = u.id 
    LEFT JOIN projects p ON al.project_id = p.id 
    ORDER BY al.created_at DESC 
    LIMIT $limit OFFSET $offset
")->fetch_all(MYSQLI_ASSOC);
?>

<style>
    /* MODERN PROFESSIONAL DESIGN - OPTIMIZED */
    
    :root {
        --primary: #6366f1;
        --primary-dark: #4f46e5;
        --secondary: #8b5cf6;
        --success: #10b981;
        --info: #3b82f6;
        --warning: #f59e0b;
        --danger: #ef4444;
        --dark: #1e293b;
        --light: #f8fafc;
        --border: #e2e8f0;
        --shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.07);
        --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
        --radius: 16px;
        --radius-sm: 10px;
    }
    
    .activity-log-container {
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
        margin: 0 0 16px 0;
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
    
    .activity-stats {
        display: flex;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }
    
    .stat-badge {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.08), rgba(139, 92, 246, 0.08));
        border: 1px solid rgba(99, 102, 241, 0.15);
        padding: 10px 18px;
        border-radius: var(--radius-sm);
        font-weight: 600;
        font-size: 13px;
        color: var(--dark);
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .stat-badge i {
        color: var(--primary);
    }
    
    .stat-value {
        font-weight: 700;
        color: var(--primary);
    }
    
    /* TABLE CARD */
    .table-card {
        background: white;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
        overflow: hidden;
    }
    
    .table-wrapper {
        overflow-x: auto;
    }
    
    .table-wrapper::-webkit-scrollbar {
        height: 8px;
    }
    
    .table-wrapper::-webkit-scrollbar-track {
        background: #f1f5f9;
    }
    
    .table-wrapper::-webkit-scrollbar-thumb {
        background: var(--primary);
        border-radius: 4px;
    }
    
    .table-wrapper::-webkit-scrollbar-thumb:hover {
        background: var(--primary-dark);
    }
    
    .activity-table {
        width: 100%;
        margin: 0;
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .activity-table thead {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    .activity-table thead th {
        color: white;
        font-weight: 700;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        padding: 18px 16px;
        border: none;
        white-space: nowrap;
        text-align: left;
    }
    
    .activity-table thead th i {
        margin-right: 6px;
        opacity: 0.9;
    }
    
    .activity-table tbody tr {
        transition: all 0.2s ease;
        background: white;
    }
    
    .activity-table tbody tr:hover {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.03), rgba(139, 92, 246, 0.03));
        transform: translateX(4px);
    }
    
    .activity-table tbody td {
        padding: 16px;
        border-bottom: 1px solid var(--border);
        color: var(--dark);
        font-weight: 500;
        font-size: 13px;
        vertical-align: middle;
    }
    
    .activity-table tbody tr:last-child td {
        border-bottom: none;
    }
    
    /* ACTION BADGES */
    .action-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }
    
    .action-badge i {
        font-size: 9px;
    }
    
    .action-badge.create {
        background: #d1fae5;
        color: #065f46;
    }
    
    .action-badge.update {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .action-badge.delete {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .action-badge.login,
    .action-badge.view {
        background: #e0e7ff;
        color: #3730a3;
    }
    
    /* PROJECT LINK */
    .project-link {
        color: var(--primary);
        text-decoration: none;
        font-weight: 600;
        transition: color 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    
    .project-link:hover {
        color: var(--primary-dark);
        text-decoration: underline;
    }
    
    .project-link i {
        font-size: 11px;
    }
    
    /* DATE & USER */
    .log-date {
        color: #64748b;
        font-weight: 600;
        white-space: nowrap;
        font-size: 12px;
    }
    
    .log-date i {
        margin-right: 6px;
        color: #94a3b8;
    }
    
    .log-user {
        font-weight: 600;
        color: var(--dark);
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        font-size: 12px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    /* IP ADDRESS */
    .log-ip {
        font-family: 'Courier New', monospace;
        background: #f1f5f9;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 11px;
        color: #64748b;
        font-weight: 600;
        display: inline-block;
    }
    
    /* EMPTY STATE */
    .empty-state {
        text-align: center;
        padding: 80px 20px;
        color: #94a3b8;
    }
    
    .empty-state i {
        font-size: 64px;
        margin-bottom: 16px;
        opacity: 0.3;
    }
    
    .empty-state p {
        font-size: 16px;
        font-weight: 600;
        margin: 0;
    }
    
    /* PAGINATION */
    .pagination-container {
        padding: 24px;
        background: white;
        border-top: 2px solid var(--border);
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 16px;
    }
    
    .pagination {
        display: flex;
        gap: 8px;
        list-style: none;
        padding: 0;
        margin: 0;
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .pagination li {
        margin: 0;
    }
    
    .pagination a {
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 40px;
        height: 40px;
        padding: 0 12px;
        border-radius: var(--radius-sm);
        background: white;
        color: #64748b;
        text-decoration: none;
        font-weight: 600;
        font-size: 13px;
        transition: all 0.2s ease;
        border: 2px solid var(--border);
    }
    
    .pagination a:hover {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.08), rgba(139, 92, 246, 0.08));
        border-color: var(--primary);
        color: var(--primary);
        transform: translateY(-2px);
    }
    
    .pagination li.active a {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        border-color: transparent;
        box-shadow: var(--shadow-md);
    }
    
    .pagination li.disabled a {
        pointer-events: none;
        opacity: 0.3;
        border: none;
    }
    
    /* RESPONSIVE DESIGN */
    @media (max-width: 1200px) {
        .activity-log-container {
            padding: 20px;
        }
        .page-header {
            padding: 32px;
        }
    }
    
    @media (max-width: 992px) {
        .activity-table thead th {
            padding: 16px 12px;
            font-size: 10px;
        }
        .activity-table tbody td {
            padding: 14px 12px;
            font-size: 12px;
        }
    }
    
    @media (max-width: 768px) {
        .activity-log-container {
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
        .activity-stats {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
            width: 100%;
        }
        .stat-badge {
            width: 100%;
            justify-content: space-between;
        }
        .activity-table {
            font-size: 12px;
        }
        .activity-table thead th {
            padding: 14px 10px;
            font-size: 9px;
        }
        .activity-table tbody td {
            padding: 12px 10px;
            font-size: 11px;
        }
        .user-avatar {
            width: 28px;
            height: 28px;
            font-size: 11px;
        }
        .action-badge {
            padding: 5px 10px;
            font-size: 9px;
        }
        .pagination a {
            min-width: 36px;
            height: 36px;
            font-size: 12px;
        }
    }
    
    @media (max-width: 480px) {
        .activity-log-container {
            padding: 12px;
        }
        .page-header {
            padding: 20px;
        }
        .page-header h1 {
            font-size: 20px;
        }
        .activity-table thead th {
            padding: 12px 8px;
            font-size: 8px;
        }
        .activity-table tbody td {
            padding: 10px 8px;
            font-size: 10px;
        }
        .pagination-container {
            padding: 16px;
        }
        .pagination {
            gap: 4px;
        }
        .pagination a {
            min-width: 32px;
            height: 32px;
            padding: 0 8px;
            font-size: 11px;
        }
    }
</style>

<div class="activity-log-container container-fluid">
    <div class="page-header">
        <h1>
            <i class="fa fa-history"></i> Activity Log
        </h1>
        <div class="activity-stats">
            <div class="stat-badge">
                <i class="fa fa-list"></i>
                <span><span class="stat-value counter" data-target="<?php echo $total_logs; ?>">0</span> Total Logs</span>
            </div>
            <div class="stat-badge">
                <i class="fa fa-file-text"></i>
                <span>Page <span class="stat-value"><?php echo $page; ?></span> of <span class="stat-value"><?php echo max(1, $total_pages); ?></span></span>
            </div>
        </div>
    </div>
    
    <div class="table-card">
        <div class="table-wrapper">
            <table class="activity-table">
                <thead>
                    <tr>
                        <th width="180"><i class="fa fa-clock-o"></i> Date/Time</th>
                        <th width="200"><i class="fa fa-user"></i> User</th>
                        <th width="120"><i class="fa fa-bolt"></i> Action</th>
                        <th><i class="fa fa-info-circle"></i> Description</th>
                        <th width="180"><i class="fa fa-folder"></i> Project</th>
                        <th width="130"><i class="fa fa-globe"></i> IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="fa fa-inbox"></i>
                                <p>No activity logs found</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                    <tr data-log-id="<?php echo $log['id']; ?>">
                        <td>
                            <div class="log-date">
                                <i class="fa fa-calendar"></i>
                                <?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?>
                            </div>
                        </td>
                        <td>
                            <div class="log-user">
                                <div class="user-avatar">
                                    <?php 
                                    $name = $log['full_name'] ?? 'Unknown';
                                    $initials = '';
                                    $parts = explode(' ', $name);
                                    foreach ($parts as $part) {
                                        $initials .= strtoupper(substr($part, 0, 1));
                                        if (strlen($initials) >= 2) break;
                                    }
                                    echo $initials;
                                    ?>
                                </div>
                                <span><?php echo htmlspecialchars($name); ?></span>
                            </div>
                        </td>
                        <td>
                            <span class="action-badge <?php echo strtolower($log['action']); ?>">
                                <?php 
                                $action_icons = [
                                    'create' => 'fa-plus',
                                    'update' => 'fa-edit',
                                    'delete' => 'fa-trash',
                                    'login' => 'fa-sign-in',
                                    'view' => 'fa-eye'
                                ];
                                $icon = $action_icons[strtolower($log['action'])] ?? 'fa-bolt';
                                ?>
                                <i class="fa <?php echo $icon; ?>"></i>
                                <?php echo htmlspecialchars($log['action']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($log['description']); ?></td>
                        <td>
                            <?php if ($log['project_name']): ?>
                            <a href="../project-detail.php?id=<?php echo $log['project_id']; ?>" class="project-link">
                                <i class="fa fa-external-link"></i>
                                <?php echo htmlspecialchars($log['project_name']); ?>
                            </a>
                            <?php else: ?>
                            <span style="color: #94a3b8;">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="log-ip"><?php echo htmlspecialchars($log['ip_address']); ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($total_pages > 1): ?>
        <div class="pagination-container">
            <ul class="pagination">
                <?php if ($page > 1): ?>
                <li>
                    <a href="?page=<?php echo $page - 1; ?>" title="Previous">
                        <i class="fa fa-chevron-left"></i>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                if ($start_page > 1): ?>
                    <li><a href="?page=1">1</a></li>
                    <?php if ($start_page > 2): ?>
                        <li class="disabled"><a>...</a></li>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                <li class="<?php echo $i == $page ? 'active' : ''; ?>">
                    <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
                
                <?php if ($end_page < $total_pages): ?>
                    <?php if ($end_page < $total_pages - 1): ?>
                        <li class="disabled"><a>...</a></li>
                    <?php endif; ?>
                    <li><a href="?page=<?php echo $total_pages; ?>"><?php echo $total_pages; ?></a></li>
                <?php endif; ?>
                
                <?php if ($page < $total_pages): ?>
                <li>
                    <a href="?page=<?php echo $page + 1; ?>" title="Next">
                        <i class="fa fa-chevron-right"></i>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// OPTIMIZED: Pure JavaScript counter animation
function initCounters() {
    const counters = document.querySelectorAll('.counter');
    const duration = 1500;
    
    counters.forEach((counter) => {
        const target = parseInt(counter.getAttribute('data-target'));
        const increment = target / (duration / 16);
        let current = 0;
        
        const updateCounter = () => {
            current += increment;
            if (current < target) {
                counter.textContent = Math.floor(current).toLocaleString();
                requestAnimationFrame(updateCounter);
            } else {
                counter.textContent = target.toLocaleString();
            }
        };
        updateCounter();
    });
}

// OPTIMIZED: Stagger animation for table rows
function animateTableRows() {
    const rows = document.querySelectorAll('.activity-table tbody tr');
    rows.forEach((row, index) => {
        row.style.animation = `fadeIn 0.3s ease ${index * 0.03}s both`;
    });
}

// OPTIMIZED: Highlight specific log from URL parameter
function highlightLog() {
    const urlParams = new URLSearchParams(window.location.search);
    const highlightId = urlParams.get('highlight');
    
    if (highlightId) {
        const row = document.querySelector(`tr[data-log-id="${highlightId}"]`);
        if (row) {
            row.style.background = 'linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(139, 92, 246, 0.15))';
            row.style.animation = 'pulse 1s ease 3';
            
            // Scroll to the highlighted row
            setTimeout(() => {
                row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 300);
            
            // Remove highlight after 5 seconds
            setTimeout(() => {
                row.style.background = '';
                row.style.animation = '';
            }, 5000);
        }
    }
}

// Pulse animation for highlighted rows
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.02); }
    }
`;
document.head.appendChild(style);

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initCounters();
        animateTableRows();
        highlightLog();
    });
} else {
    initCounters();
    animateTableRows();
    highlightLog();
}
</script>

<?php require_once '../includes/footer.php'; ?>