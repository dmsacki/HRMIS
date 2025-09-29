<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . APP_NAME : APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php if (isLoggedIn()): ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-building me-2"></i><?php echo APP_NAME; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    
                    <?php if (hasRole(1) || hasRole(2)): // Admin or Manager ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="performanceDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-chart-line me-1"></i>Performance
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="appraisal_cycles.php">Appraisal Cycles</a></li>
                            <li><a class="dropdown-item" href="appraisals.php">Appraisals</a></li>
                            <li><a class="dropdown-item" href="agreements.php">Yearly Agreements</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="tasksDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-tasks me-1"></i>Tasks
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="tasks.php">My Tasks</a></li>
                            <?php if (hasRole(1) || hasRole(2)): ?>
                            <li><a class="dropdown-item" href="assign_tasks.php">Assign Tasks</a></li>
                            <li><a class="dropdown-item" href="all_tasks.php">All Tasks</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    
                    <?php if (hasRole(1) || hasRole(2)): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar me-1"></i>Reports
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (hasRole(1)): // Admin only ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cog me-1"></i>Admin
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="users.php">Users</a></li>
                            <li><a class="dropdown-item" href="departments.php">Departments</a></li>
                            <li><a class="dropdown-item" href="roles.php">Roles</a></li>
                            <li><a class="dropdown-item" href="audit_logs.php">Audit Logs</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo $_SESSION['name']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <main class="<?php echo isLoggedIn() ? 'container-fluid mt-4' : ''; ?>">
