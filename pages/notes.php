<?php
require_once 'includes/init.php'; 

if (!isset($_SESSION['user_id'])) {
    redirect('index.php?page=login');
    exit;
}

$userId = intval($_SESSION['user_id']);
$noteId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($noteId <= 0) {
    setFlashMessage('error', 'ID appunto non valido.');
    redirect('index.php?page=notes');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'share') {
    $groupId = intval($_POST['group_id'] ?? 0);
    if ($groupId > 0) {
        $sharedId = shareNoteWithGroup($noteId, $groupId, $userId);
        if ($sharedId) {
            setFlashMessage('success', 'Appunto condiviso con il gruppo!');
        } else {
            setFlashMessage('error', 'Errore durante la condivisione.');
        }
    } else {
        setFlashMessage('error', 'Seleziona un gruppo valido.');
    }

    redirect("index.php?page=view_note&id=$noteId");
    exit;
}

$note = getNote($noteId, $userId);
if (!$note) {
    setFlashMessage('error', 'Appunto non trovato o accesso negato.');
    redirect('index.php?page=notes');
    exit;
}

if (isset($_GET['complete_review']) && $_GET['complete_review'] === '1') {
    $reviewId = intval($_GET['review_id'] ?? 0);
    if ($reviewId > 0 && completeReview($reviewId)) {
        setFlashMessage('success', 'Ripasso completato!');
    } else {
        setFlashMessage('error', 'Errore nel completare il ripasso.');
    }

    redirect("index.php?page=view_note&id=$noteId");
    exit;
}

$tags = getNoteTags($noteId);
$pageTitle = htmlspecialchars($note['title']);

$sharedGroups = fetchAll(
    "SELECT sg.id, sg.name, sg.description, sn.id as shared_note_id
     FROM shared_notes sn
     JOIN study_groups sg ON sn.group_id = sg.id
     WHERE sn.note_id = ? AND sn.shared_by = ?
     ORDER BY sg.name",
    [$noteId, $userId],
    'ii'
);

$review = fetchOne(
    "SELECT * FROM review_schedule WHERE user_id = ? AND note_id = ?",
    [$userId, $noteId],
    'ii'
);

$groups = getUserStudyGroups($userId);
$isReviewDue = $review && strtotime($review['next_review_date']) <= time();
?>
<?php include 'includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <span class="badge me-2" style="background-color: <?= htmlspecialchars($note['subject_color']); ?>;">
            <?= htmlspecialchars($note['subject_name']); ?>
        </span>
        <?= htmlspecialchars($note['title']); ?>
    </h1>
    <div class="btn-group">
        <a href="index.php?page=notes" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i> Indietro
        </a>
        <?php if ($note['user_id'] === $userId): ?>
            <a href="index.php?page=edit_note&id=<?= $note['id']; ?>" class="btn btn-primary">
                <i class="fas fa-edit me-2"></i> Modifica
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-lg-9">
        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <div>
                        <?php foreach ($tags as $tag): ?>
                            <span class="badge bg-secondary me-1">
                                <i class="fas fa-tag me-1"></i> <?= htmlspecialchars($tag['name']); ?>
                            </span>
                        <?php endforeach; ?>
                        <span class="badge bg-<?= $note['is_public'] ? 'success' : 'secondary'; ?>">
                            <i class="fas <?= $note['is_public'] ? 'fa-globe' : 'fa-lock'; ?> me-1"></i>
                            <?= $note['is_public'] ? 'Pubblico' : 'Privato'; ?>
                        </span>
                    </div>
                    <div class="text-muted small text-end">
                        <div><i class="fas fa-clock me-1"></i> Creato: <?= formatDateTime($note['created_at']); ?></div>
                        <div><i class="fas fa-edit me-1"></i> Modificato: <?= formatDateTime($note['updated_at']); ?></div>
                    </div>
                </div>

                <?php if (!empty($note['summary'])): ?>
                    <div class="alert alert-info mb-4">
                        <h5 class="alert-heading"><i class="fas fa-lightbulb me-2"></i> Riassunto</h5>
                        <p class="mb-0"><?= htmlspecialchars($note['summary']); ?></p>
                    </div>
                <?php endif; ?>

                <div class="note-content">
                    <?= $note['content']; ?>
                </div>
            </div>
        </div>

        <?php if (!empty($sharedGroups)): ?>
            <div class="card shadow mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-share-alt me-2"></i> Condiviso nei Gruppi</h5>
                </div>
                <div class="list-group list-group-flush">
                    <?php foreach ($sharedGroups as $group): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><?= htmlspecialchars($group['name']); ?></h6>
                                <a href="index.php?page=group_details&id=<?= $group['id']; ?>&shared_note=<?= $group['shared_note_id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-comments me-1"></i> Vedi commenti
                                </a>
                            </div>
                            <p class="text-muted small mb-0"><?= htmlspecialchars($group['description']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-3">
        <?php if ($review): ?>
            <div class="card shadow mb-4 <?= $isReviewDue ? 'border-warning' : ''; ?>">
                <div class="card-header <?= $isReviewDue ? 'bg-warning' : 'bg-info'; ?> text-white">
                    <h5 class="mb-0"><i class="fas fa-sync me-2"></i> Programma di Ripasso</h5>
                </div>
                <div class="card-body">
                    <?php if ($isReviewDue): ?>
                        <p><strong>Da ripassare oggi!</strong></p>
                        <p>Ripasso numero: <?= $review['review_count'] + 1; ?></p>
                        <a href="index.php?page=view_note&id=<?= $noteId; ?>&complete_review=1&review_id=<?= $review['id']; ?>" class="btn btn-success w-100">
                            <i class="fas fa-check me-2"></i> Segna come ripassato
                        </a>
                    <?php else: ?>
                        <p>Prossimo ripasso: <strong><?= formatDateTime($review['next_review_date'], 'd M Y'); ?></strong></p>
                        <p>Ripasso numero: <?= $review['review_count'] + 1; ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($note['user_id'] === $userId): ?>
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-share-alt me-2"></i> Condividi</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($groups)): ?>
                        <p>Non sei membro di nessun gruppo. <a href="index.php?page=study_groups">Crea o unisciti</a>.</p>
                    <?php else: ?>
                        <form method="post">
                            <input type="hidden" name="action" value="share">
                            <div class="mb-3">
                                <label for="group_id" class="form-label">Seleziona gruppo:</label>
                                <select class="form-select" id="group_id" name="group_id" required>
                                    <option value="">-- Seleziona --</option>
                                    <?php foreach ($groups as $group): ?>
                                        <option value="<?= $group['id']; ?>">
                                            <?= htmlspecialchars($group['name']); ?> (<?= $group['member_count']; ?> membri)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-share-alt me-2"></i> Condividi
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>