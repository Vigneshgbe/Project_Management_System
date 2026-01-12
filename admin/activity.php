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

<div class="container-fluid">
    <div class="page-header">
        <h1><i class="fa fa-history"></i> Activity Log</h1>
    </div>
    
    <div class="panel panel-default">
        <div class="panel-body">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th width="150">Date/Time</th>
                        <th width="150">User</th>
                        <th width="150">Action</th>
                        <th>Description</th>
                        <th width="200">Project</th>
                        <th width="120">IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">No activity logs found.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?></td>
                        <td><?php echo htmlspecialchars($log['full_name'] ?? 'Unknown'); ?></td>
                        <td>
                            <span class="label label-default">
                                <?php echo htmlspecialchars($log['action']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($log['description']); ?></td>
                        <td>
                            <?php if ($log['project_name']): ?>
                            <a href="../project-detail.php?id=<?php echo $log['project_id']; ?>">
                                <?php echo htmlspecialchars($log['project_name']); ?>
                            </a>
                            <?php else: ?>
                            <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td><small><?php echo htmlspecialchars($log['ip_address']); ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if ($total_pages > 1): ?>
            <nav>
                <ul class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="<?php echo $i == $page ? 'active' : ''; ?>">
                        <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
