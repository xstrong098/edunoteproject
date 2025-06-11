<?php
$pageTitle = 'Home';

if (isLoggedIn()) {
    $userId = $_SESSION['user_id'];
    $stats = getUserStats($userId);
    $recentNotes = getRecentNotes($userId, 3);
}
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-body">
                <h1 class="card-title display-5">Benvenuto su <?php echo APP_NAME; ?></h1>
                <p class="lead">L'app intelligente per prendere, organizzare e rivedere i tuoi appunti in modo efficace.</p>
                
                <hr class="my-4">
                
                <?php if (!isLoggedIn()): ?>
                    <p>EduNote è progettata per gli studenti che vogliono un'app che vada oltre la semplice scrittura di appunti.</p>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                        <a href="index.php?page=register" class="btn btn-primary btn-lg">
                            <i class="fas fa-user-plus me-2"></i> Registrati ora
                        </a>
                        <a href="index.php?page=login" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i> Accedi
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row gy-4">
                        <div class="col-sm-6 col-md-3">
                            <div class="card bg-primary text-white text-center">
                                <div class="card-body">
                                    <h3 class="display-4"><?php echo $stats['total_notes']; ?></h3>
                                    <p class="mb-0">Appunti totali</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-sm-6 col-md-3">
                            <div class="card bg-success text-white text-center">
                                <div class="card-body">
                                    <h3 class="display-4"><?php echo $stats['total_subjects']; ?></h3>
                                    <p class="mb-0">Materie</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-sm-6 col-md-3">
                            <div class="card bg-info text-white text-center">
                                <div class="card-body">
                                    <h3 class="display-4"><?php echo $stats['notes_this_month']; ?></h3>
                                    <p class="mb-0">Appunti questo mese</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-sm-6 col-md-3">
                            <div class="card bg-warning text-white text-center">
                                <div class="card-body">
                                    <h3 class="display-4"><?php echo $stats['notes_for_review']; ?></h3>
                                    <p class="mb-0">Da ripassare</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <h5><i class="fas fa-lightbulb me-2"></i> Inizia rapidamente</h5>
                        <div class="list-group">
                            <a href="index.php?page=create_note" class="list-group-item list-group-item-action">
                                <i class="fas fa-plus me-2"></i> Crea un nuovo appunto
                            </a>
                            <a href="index.php?page=review" class="list-group-item list-group-item-action">
                                <i class="fas fa-sync me-2"></i> Ripassa gli appunti in scadenza
                            </a>
                            <a href="index.php?page=study_groups" class="list-group-item list-group-item-action">
                                <i class="fas fa-users me-2"></i> Gestisci i tuoi gruppi di studio
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <?php if (isLoggedIn() && isset($recentNotes) && count($recentNotes) > 0): ?>
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i> Appunti recenti</h5>
                </div>
                
                <div class="list-group list-group-flush">
                    <?php foreach ($recentNotes as $note): ?>
                        <a href="index.php?page=view_note&id=<?php echo $note['id']; ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">
                                    <span class="badge" style="background-color: <?php echo $note['subject_color']; ?>;">
                                        <?php echo htmlspecialchars($note['subject_name']); ?>
                                    </span>
                                    <?php echo htmlspecialchars($note['title']); ?>
                                </h6>
                                <small><?php echo formatDateTime($note['updated_at'], 'd M'); ?></small>
                            </div>
                            <p class="mb-1 text-muted small"><?php echo htmlspecialchars(substr($note['summary'] ?? '', 0, 100)); ?>...</p>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <div class="card-footer text-center">
                    <a href="index.php?page=notes" class="btn btn-sm btn-outline-primary">Vedi tutti gli appunti</a>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="card shadow">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i> Funzionalità principali</h5>
            </div>
            
            <div class="card-body">
                <ul class="list-group list-group-flush mb-0">
                    <li class="list-group-item">
                        <i class="fas fa-check-circle text-success me-2"></i> 
                        <strong>Organizzazione intuitiva</strong>
                        <p class="mb-0 small text-muted">Organizza gli appunti per materia, argomento e data.</p>
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-check-circle text-success me-2"></i> 
                        <strong>Riassunti automatici</strong>
                        <p class="mb-0 small text-muted">Genera riassunti sintetici delle informazioni principali.</p>
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-check-circle text-success me-2"></i> 
                        <strong>Condivisione con i compagni</strong>
                        <p class="mb-0 small text-muted">Crea gruppi di studio e collabora con i tuoi compagni.</p>
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-check-circle text-success me-2"></i> 
                        <strong>Ripasso intelligente</strong>
                        <p class="mb-0 small text-muted">Sistema di ripasso che ottimizza i tempi di studio.</p>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>