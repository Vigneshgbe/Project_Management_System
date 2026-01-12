<?php 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PM System</title>
    <link rel="stylesheet" href="maxcdn.bootstrapcdn.com">
    <style>
        .navbar { border-radius: 0; }
        .dashboard-header { margin-bottom: 30px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-inverse">
        <div class="container">
            <a class="navbar-brand" href="#">Startup PM</a>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1>Project Dashboard</h1>
        </div>

        <!-- Analytics Row -->
        <div class="row">
            <?php 
            $stats = getProjectStats($db);
            while($row = $stats->fetch_assoc()) {
                renderComponent('stats-widget', $row);
            }
            ?>
        </div>

        <!-- Projects Grid -->
        <div class="row">
            <h3>Active Projects</h3>
            <?php 
            $projects = getProjects($db);
            while($project = $projects->fetch_assoc()) {
                renderComponent('project-card', $project);
            }
            ?>
        </div>
    </div>
</body>
</html>
