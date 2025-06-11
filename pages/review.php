<?php

$pageTitle = 'Ripasso Intelligente';

$userId = $_SESSION['user_id'];

$reviewNotes = getTodayReviewNotes($userId);

$stats = [];

$stats['total_for_review'] = count($reviewNotes);

$stats['future_review'] = fetchOne(
    "SELECT COUNT(*) as count FROM review_schedule 
     WHERE user_id = ? AND next_review_date > CURDATE()",
    [$userId],
    'i'
)['count'];

$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

$stats['completed_today'] = fetchOne(
    "SELECT COUNT(*) as count FROM review_schedule 
     WHERE user_id = ? AND 
           next_review_date > ? AND
           next_review_date <= ?",
    [$userId, $yesterday, $today],
    'iss'
)['count'];

if (isset($_GET['action']) && $_GET['action'] === 'complete') {
    $reviewId = isset($_GET['review_id']) ? intval($_GET['review_id']) : 0;
    
    if ($reviewId > 0) {
        if (completeReview($reviewId)) {
            setFlashMessage('success', 'Ripasso completato! Il prossimo ripasso è stato programmato.');
        } else {
            setFlashMessage('error', 'Errore durante il completamento del ripasso.');
        }
    }
    
    redirect('index.php?page=review');
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="fas fa-sync me-2"></i> Ripasso Intelligente</h1>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-warning text-white text-center mb-3">
            <div class="card-body">
                <h2 class="display-4"><?php echo $stats['total_for_review']; ?></h2>
                <p class="mb-0">Appunti da ripassare oggi</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card bg-success text-white text-center mb-3">
            <div class="card-body">
                <h2 class="display-4"><?php echo $stats['completed_today']; ?></h2>
                <p class="mb-0">Ripassi completati oggi</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card bg-info text-white text-center mb-3">
            <div class="card-body">
                <h2 class="display-4"><?php echo $stats['future_review']; ?></h2>
                <p class="mb-0">Appunti programmati per il futuro</p>
            </div>
        </div>
    </div>
</div>

<div class="card shadow">
    <div class="card-header bg-primary text-white">
        <h4 class="mb-0"><i class="fas fa-sync me-2"></i> Appunti da Ripassare Oggi</h4>
    </div>
    
    <div class="card-body">
        <?php if (empty($reviewNotes)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i> Grande! Hai completato tutti i ripassi per oggi. Continua così!
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Materia</th>
                            <th>Titolo</th>
                            <th>Creato il</th>
                            <th>Ripasso #</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviewNotes as $note): ?>
                            <tr>
                                <td>
                                    <span class="badge" style="background-color: <?php echo $note['subject_color']; ?>;">
                                        <?php echo htmlspecialchars($note['subject_name']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($note['title']); ?></td>
                                <td><?php echo formatDateTime($note['created_at'], 'd M Y'); ?></td>
                                <td><?php echo $note['review_count'] + 1; ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="index.php?page=view_note&id=<?php echo $note['id']; ?>" class="btn btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i> Visualizza
                                        </a>
                                        <a href="index.php?page=review&action=complete&review_id=<?php echo $note['review_id']; ?>" class="btn btn-success">
                                            <i class="fas fa-check me-1"></i> Completato
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle me-2"></i> <strong>Come funziona il ripasso intelligente?</strong>
                <p class="mb-0 mt-2">Il ripasso intelligente usa la tecnica della ripetizione spaziata per aiutarti a memorizzare meglio le informazioni nel lungo termine. Gli intervalli di ripasso aumentano progressivamente: 1 giorno, 3 giorni, 1 settimana, 2 settimane, 1 mese, 2 mesi e 4 mesi.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
