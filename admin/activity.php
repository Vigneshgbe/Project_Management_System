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
    .activity-log-container {
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
    
    .activity-log-header {
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
    
    .activity-log-header::before {
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
    
    .activity-log-header h1 {
        margin: 0 !important;
        font-weight: 800 !important;
        font-size: 32px !important;
        position: relative !important;
        z-index: 1 !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
        display: flex !important;
        align-items: center !important;
        gap: 15px !important;
    }
    
    .activity-log-header h1 i {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
    }
    
    .activity-log-stats {
        display: flex !important;
        align-items: center !important;
        gap: 20px !important;
        margin-top: 15px !important;
        flex-wrap: wrap !important;
    }
    
    .activity-log-stat {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%) !important;
        padding: 10px 20px !important;
        border-radius: 12px !important;
        font-weight: 600 !important;
        font-size: 14px !important;
        color: #667eea !important;
    }
    
    .activity-log-stat i {
        margin-right: 8px !important;
    }
    
    .log-table-card {
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(20px) !important;
        border-radius: 20px !important;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        overflow: hidden !important;
        animation: slideUp 0.5s ease !important;
    }
    
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .log-table-wrapper {
        overflow-x: auto !important;
    }
    
    .log-table-wrapper::-webkit-scrollbar {
        height: 8px !important;
    }
    
    .log-table-wrapper::-webkit-scrollbar-track {
        background: #f1f5f9 !important;
        border-radius: 10px !important;
    }
    
    .log-table-wrapper::-webkit-scrollbar-thumb {
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%) !important;
        border-radius: 10px !important;
    }
    
    .activity-log-table {
        width: 100% !important;
        margin: 0 !important;
        border-collapse: separate !important;
        border-spacing: 0 !important;
    }
    
    .activity-log-table thead {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        position: sticky !important;
        top: 0 !important;
        z-index: 10 !important;
    }
    
    .activity-log-table thead th {
        color: white !important;
        font-weight: 700 !important;
        font-size: 13px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        padding: 20px 15px !important;
        border: none !important;
        white-space: nowrap !important;
    }
    
    .activity-log-table thead th:first-child {
        border-top-left-radius: 20px !important;
    }
    
    .activity-log-table thead th:last-child {
        border-top-right-radius: 20px !important;
    }
    
    .activity-log-table tbody tr {
        transition: all 0.3s ease !important;
        background: white !important;
    }
    
    .activity-log-table tbody tr:hover {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%) !important;
        transform: scale(1.01) !important;
        box-shadow: 0 3px 15px rgba(102, 126, 234, 0.1) !important;
    }
    
    .activity-log-table tbody td {
        padding: 18px 15px !important;
        border-bottom: 1px solid #e2e8f0 !important;
        color: #1e293b !important;
        font-weight: 500 !important;
        font-size: 14px !important;
        vertical-align: middle !important;
    }
    
    .activity-log-table tbody tr:last-child td {
        border-bottom: none !important;
    }
    
    .activity-log-table tbody tr:last-child td:first-child {
        border-bottom-left-radius: 20px !important;
    }
    
    .activity-log-table tbody tr:last-child td:last-child {
        border-bottom-right-radius: 20px !important;
    }
    
    .log-action-badge {
        display: inline-flex !important;
        align-items: center !important;
        padding: 6px 14px !important;
        border-radius: 20px !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.15) 0%, rgba(118, 75, 162, 0.15) 100%) !important;
        color: #667eea !important;
        white-space: nowrap !important;
    }
    
    .log-action-badge.create {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(5, 150, 105, 0.15) 100%) !important;
        color: #059669 !important;
    }
    
    .log-action-badge.update {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(37, 99, 235, 0.15) 100%) !important;
        color: #2563eb !important;
    }
    
    .log-action-badge.delete {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(220, 38, 38, 0.15) 100%) !important;
        color: #dc2626 !important;
    }
    
    .log-project-link {
        color: #667eea !important;
        text-decoration: none !important;
        font-weight: 600 !important;
        transition: all 0.3s ease !important;
        position: relative !important;
    }
    
    .log-project-link::after {
        content: '' !important;
        position: absolute !important;
        width: 0 !important;
        height: 2px !important;
        bottom: -2px !important;
        left: 0 !important;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%) !important;
        transition: width 0.3s ease !important;
    }
    
    .log-project-link:hover {
        color: #764ba2 !important;
    }
    
    .log-project-link:hover::after {
        width: 100% !important;
    }
    
    .log-ip {
        font-family: 'Courier New', monospace !important;
        background: #f1f5f9 !important;
        padding: 4px 10px !important;
        border-radius: 6px !important;
        font-size: 12px !important;
        color: #64748b !important;
        font-weight: 600 !important;
    }
    
    .log-date {
        color: #64748b !important;
        font-weight: 600 !important;
        white-space: nowrap !important;
    }
    
    .log-user {
        font-weight: 600 !important;
        color: #1e293b !important;
    }
    
    .log-empty {
        text-align: center !important;
        padding: 60px 20px !important;
        color: #64748b !important;
        font-size: 16px !important;
        font-weight: 600 !important;
    }
    
    .log-empty i {
        font-size: 48px !important;
        margin-bottom: 15px !important;
        display: block !important;
        opacity: 0.5 !important;
    }
    
    .log-pagination {
        padding: 25px !important;
        background: white !important;
        border-top: 2px solid #e2e8f0 !important;
        display: flex !important;
        justify-content: center !important;
    }
    
    .pagination-modern {
        display: flex !important;
        gap: 8px !important;
        list-style: none !important;
        padding: 0 !important;
        margin: 0 !important;
        flex-wrap: wrap !important;
        justify-content: center !important;
    }
    
    .pagination-modern li {
        margin: 0 !important;
    }
    
    .pagination-modern li a {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 40px !important;
        height: 40px !important;
        border-radius: 10px !important;
        background: white !important;
        color: #64748b !important;
        text-decoration: none !important;
        font-weight: 700 !important;
        font-size: 14px !important;
        transition: all 0.3s ease !important;
        border: 2px solid #e2e8f0 !important;
    }
    
    .pagination-modern li a:hover {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%) !important;
        border-color: #667eea !important;
        color: #667eea !important;
        transform: translateY(-2px) !important;
    }
    
    .pagination-modern li.active a {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        border-color: transparent !important;
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3) !important;
    }
    
    /* RESPONSIVE */
    @media (max-width: 1200px) {
        .activity-log-container {
            padding: 15px !important;
        }
        .activity-log-header {
            padding: 25px 30px !important;
        }
        .activity-log-header h1 {
            font-size: 28px !important;
        }
    }
    
    @media (max-width: 992px) {
        .activity-log-table thead th {
            padding: 15px 12px !important;
            font-size: 12px !important;
        }
        .activity-log-table tbody td {
            padding: 15px 12px !important;
            font-size: 13px !important;
        }
    }
    
    @media (max-width: 768px) {
        .activity-log-container {
            padding: 10px !important;
        }
        .activity-log-header {
            padding: 20px !important;
            margin-bottom: 20px !important;
        }
        .activity-log-header h1 {
            font-size: 24px !important;
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 10px !important;
        }
        .activity-log-stats {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 10px !important;
        }
        .activity-log-table {
            font-size: 12px !important;
        }
        .activity-log-table thead th {
            padding: 12px 8px !important;
            font-size: 11px !important;
        }
        .activity-log-table tbody td {
            padding: 12px 8px !important;
            font-size: 12px !important;
        }
        .log-action-badge {
            padding: 4px 10px !important;
            font-size: 10px !important;
        }
        .pagination-modern li a {
            width: 35px !important;
            height: 35px !important;
            font-size: 13px !important;
        }
    }
    
    @media (max-width: 480px) {
        .activity-log-container {
            padding: 8px !important;
        }
        .activity-log-header h1 {
            font-size: 20px !important;
        }
        .log-pagination {
            padding: 15px !important;
        }
        .pagination-modern {
            gap: 5px !important;
        }
        .pagination-modern li a {
            width: 32px !important;
            height: 32px !important;
            font-size: 12px !important;
        }
    }
</style>

<div class="activity-log-container container-fluid">
    <div class="activity-log-header">
        <h1>
            <i class="fa fa-history"></i> Activity Log
        </h1>
        <div class="activity-log-stats">
            <div class="activity-log-stat">
                <i class="fa fa-list"></i>
                <span class="counter" data-target="<?php echo $total_logs; ?>">0</span> Total Logs
            </div>
            <div class="activity-log-stat">
                <i class="fa fa-file-text"></i>
                Page <?php echo $page; ?> of <?php echo max(1, $total_pages); ?>
            </div>
        </div>
    </div>
    
    <div class="log-table-card">
        <div class="log-table-wrapper">
            <table class="activity-log-table">
                <thead>
                    <tr>
                        <th width="150"><i class="fa fa-clock-o"></i> Date/Time</th>
                        <th width="150"><i class="fa fa-user"></i> User</th>
                        <th width="150"><i class="fa fa-bolt"></i> Action</th>
                        <th><i class="fa fa-info-circle"></i> Description</th>
                        <th width="200"><i class="fa fa-folder"></i> Project</th>
                        <th width="120"><i class="fa fa-globe"></i> IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6" class="log-empty">
                            <i class="fa fa-inbox"></i>
                            No activity logs found.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td class="log-date">
                            <?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?>
                        </td>
                        <td class="log-user">
                            <?php echo htmlspecialchars($log['full_name'] ?? 'Unknown'); ?>
                        </td>
                        <td>
                            <span class="log-action-badge <?php echo strtolower($log['action']); ?>">
                                <?php echo htmlspecialchars($log['action']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($log['description']); ?></td>
                        <td>
                            <?php if ($log['project_name']): ?>
                            <a href="../project-detail.php?id=<?php echo $log['project_id']; ?>" class="log-project-link">
                                <?php echo htmlspecialchars($log['project_name']); ?>
                            </a>
                            <?php else: ?>
                            <span style="color: #94a3b8 !important;">N/A</span>
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
        <div class="log-pagination">
            <ul class="pagination-modern">
                <?php if ($page > 1): ?>
                <li><a href="?page=<?php echo $page - 1; ?>"><i class="fa fa-chevron-left"></i></a></li>
                <?php endif; ?>
                
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                if ($start_page > 1): ?>
                    <li><a href="?page=1">1</a></li>
                    <?php if ($start_page > 2): ?>
                        <li><a href="#" style="pointer-events: none; border: none;">...</a></li>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                <li class="<?php echo $i == $page ? 'active' : ''; ?>">
                    <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
                
                <?php if ($end_page < $total_pages): ?>
                    <?php if ($end_page < $total_pages - 1): ?>
                        <li><a href="#" style="pointer-events: none; border: none;">...</a></li>
                    <?php endif; ?>
                    <li><a href="?page=<?php echo $total_pages; ?>"><?php echo $total_pages; ?></a></li>
                <?php endif; ?>
                
                <?php if ($page < $total_pages): ?>
                <li><a href="?page=<?php echo $page + 1; ?>"><i class="fa fa-chevron-right"></i></a></li>
                <?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    // ANIMATED COUNTER
    $('.counter').each(function() {
        const $this = $(this);
        const countTo = parseInt($this.attr('data-target'));
        
        $({ countNum: 0 }).animate({
            countNum: countTo
        }, {
            duration: 1500,
            easing: 'swing',
            step: function() {
                $this.text(Math.floor(this.countNum).toLocaleString());
            },
            complete: function() {
                $this.text(this.countNum.toLocaleString());
            }
        });
    });
    
    // TABLE ROW STAGGER ANIMATION
    $('.activity-log-table tbody tr').each(function(index) {
        $(this).css({
            'animation': `slideUp 0.3s ease ${index * 0.03}s both`
        });
    });
    
    // HIGHLIGHT NEW LOGS (if coming from notification)
    const urlParams = new URLSearchParams(window.location.search);
    const highlightId = urlParams.get('highlight');
    if (highlightId) {
        $(`tr[data-log-id="${highlightId}"]`).css({
            'background': 'linear-gradient(135deg, rgba(102, 126, 234, 0.2) 0%, rgba(118, 75, 162, 0.2) 100%)',
            'animation': 'pulse 1s ease 3'
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>