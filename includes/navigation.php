<?php
$currentUser = isLoggedIn() ? getCurrentUser() : null;
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-book-open me-2"></i> <?php echo APP_NAME; ?>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $page === 'home' ? 'active' : ''; ?>" href="index.php">
                        <i class="fas fa-home me-1"></i> Home
                    </a>
                </li>
                
                <?php if ($isLoggedIn): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'notes' ? 'active' : ''; ?>" href="index.php?page=notes">
                            <i class="fas fa-sticky-note me-1"></i> I Miei Appunti
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'subjects' ? 'active' : ''; ?>" href="index.php?page=subjects">
                            <i class="fas fa-book me-1"></i> Materie
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'study_groups' ? 'active' : ''; ?>" href="index.php?page=study_groups">
                            <i class="fas fa-users me-1"></i> Gruppi di Studio
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'review' ? 'active' : ''; ?>" href="index.php?page=review">
                            <i class="fas fa-sync me-1"></i> Ripasso
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <div class="d-flex">
                <?php if ($isLoggedIn): ?>
                    <?php
                    $today = date('Y-m-d');
                    $reviewCount = fetchOne(
                        "SELECT COUNT(*) as count FROM review_schedule WHERE user_id = ? AND next_review_date <= ?",
                        [$_SESSION['user_id'], $today],
                        'is'
                    )['count'];
                    ?>
                    
                    <?php if ($reviewCount > 0): ?>
                        <a href="index.php?page=review" class="btn btn-warning btn-sm me-2 mt-1">
                            <i class="fas fa-bell me-1"></i> <?php echo $reviewCount; ?> da ripassare
                        </a>
                    <?php endif; ?>
                    
                    <div class="dropdown">
                        <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i> <?php echo isset($currentUser['username']) ? htmlspecialchars($currentUser['username']) : 'Utente'; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="index.php?page=profile">
                                    <i class="fas fa-user-cog me-1"></i> Profilo
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="index.php?page=logout">
                                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="index.php?page=login" class="btn btn-outline-light me-2">
                        <i class="fas fa-sign-in-alt me-1"></i> Login
                    </a>
                    <a href="index.php?page=register" class="btn btn-light">
                        <i class="fas fa-user-plus me-1"></i> Registrati
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>