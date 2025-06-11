<?php
$pageTitle = 'Visualizza Appunto';

$userId = $_SESSION['user_id'];

$noteId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($noteId <= 0) {
    setFlashMessage('error', 'ID appunto non valido.');
    redirect('index.php?page=notes');
}

$note = getNote($noteId, $userId);

if (!$note) {
    setFlashMessage('error', 'Appunto non trovato o non hai il permesso di visualizzarlo.');
    redirect('index.php?page=notes');
}

$tags = getNoteTags($noteId);

$pageTitle = $note['title'];

if (isset($_GET['complete_review']) && $_GET['complete_review'] === '1') {
    $reviewId = intval($_GET['review_id'] ?? 0);
    
    if ($reviewId > 0 && completeReview($reviewId)) {
        setFlashMessage('success', 'Ripasso completato! Il prossimo ripasso è stato programmato.');
    }
    
    redirect('index.php?page=view_note&id=' . $noteId);
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <span class="badge me-2" style="background-color: <?php echo $note['subject_color']; ?>;">
            <?php echo htmlspecialchars($note['subject_name']); ?>
        </span>
        <?php echo htmlspecialchars($note['title']); ?>
    </h1>
    
    <div class="btn-group">
        <a href="index.php?page=notes" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i> Indietro
        </a>
        
        <?php if ($note['user_id'] === $userId): ?>
            <a href="index.php?page=edit_note&id=<?php echo $note['id']; ?>" class="btn btn-primary">
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
                                <i class="fas fa-tag me-1"></i>
                                <?php echo htmlspecialchars($tag['name']); ?>
                            </span>
                        <?php endforeach; ?>
                        
                        <?php if ($note['is_public']): ?>
                            <span class="badge bg-success">
                                <i class="fas fa-globe me-1"></i> Pubblico
                            </span>
                        <?php else: ?>
                            <span class="badge bg-secondary">
                                <i class="fas fa-lock me-1"></i> Privato
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="text-muted small">
                        <div><i class="fas fa-clock me-1"></i> Creato: <?php echo formatDateTime($note['created_at']); ?></div>
                        <div><i class="fas fa-edit me-1"></i> Modificato: <?php echo formatDateTime($note['updated_at']); ?></div>
                    </div>
                </div>
                
                <?php if (!empty($note['summary'])): ?>
                    <div class="alert alert-info mb-4">
                        <h5 class="alert-heading"><i class="fas fa-lightbulb me-2"></i> Riassunto</h5>
                        <p class="mb-0"><?php echo htmlspecialchars($note['summary']); ?></p>
                    </div>
                <?php endif; ?>
                
                <div class="note-content">
                    <?php echo $note['content']; ?>
                </div>
            </div>
        </div>
        
        <?php
        $sharedGroups = fetchAll(
            "SELECT sg.id, sg.name, sg.description, sn.id as shared_note_id
             FROM shared_notes sn
             JOIN study_groups sg ON sn.group_id = sg.id
             WHERE sn.note_id = ? AND sn.shared_by = ?
             ORDER BY sg.name",
            [$noteId, $userId],
            'ii'
        );
        
        if (!empty($sharedGroups)):
        ?>
            <div class="card shadow mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-share-alt me-2"></i> Condiviso nei Gruppi</h5>
                </div>
                
                <div class="list-group list-group-flush">
                    <?php foreach ($sharedGroups as $group): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><?php echo htmlspecialchars($group['name']); ?></h6>
                                
                                <a href="index.php?page=group_details&id=<?php echo $group['id']; ?>&shared_note=<?php echo $group['shared_note_id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-comments me-1"></i> Vedi commenti
                                </a>
                            </div>
                            <p class="text-muted small mb-0"><?php echo htmlspecialchars($group['description']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="col-lg-3">
        <?php
        $review = fetchOne(
            "SELECT * FROM review_schedule WHERE user_id = ? AND note_id = ?",
            [$userId, $noteId],
            'ii'
        );
        
        if ($review):
            $isReviewDue = strtotime($review['next_review_date']) <= time();
        ?>
            <div class="card shadow mb-4 <?php echo $isReviewDue ? 'border-warning' : ''; ?>">
                <div class="card-header <?php echo $isReviewDue ? 'bg-warning' : 'bg-info'; ?> text-white">
                    <h5 class="mb-0"><i class="fas fa-sync me-2"></i> Programma di Ripasso</h5>
                </div>
                
                <div class="card-body">
                    <?php if ($isReviewDue): ?>
                        <p><strong>Da ripassare oggi!</strong></p>
                        <p>Ripasso numero: <?php echo $review['review_count'] + 1; ?></p>
                        
                        <div class="d-grid">
                            <a href="index.php?page=view_note&id=<?php echo $noteId; ?>&complete_review=1&review_id=<?php echo $review['id']; ?>" class="btn btn-success">
                                <i class="fas fa-check me-2"></i> Segna come ripassato
                            </a>
                        </div>
                    <?php else: ?>
                        <p>Prossimo ripasso programmato per:</p>
                        <p><strong><?php echo formatDateTime($review['next_review_date'], 'd M Y'); ?></strong></p>
                        <p>Ripasso numero: <?php echo $review['review_count'] + 1; ?></p>
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
                    <?php
                    $groups = getUserStudyGroups($userId);
                    
                    if (empty($groups)):
                    ?>
                        <p class="mb-0">Non sei membro di nessun gruppo di studio. <a href="index.php?page=study_groups">Crea o unisciti a un gruppo</a> per condividere i tuoi appunti.</p>
                    <?php else: ?>
                        <form method="post" action="index.php?page=view_note&id=<?php echo $noteId; ?>">
                            <input type="hidden" name="action" value="share">
                            
                            <div class="mb-3">
                                <label for="group_id" class="form-label">Condividi con il gruppo:</label>
                                <select class="form-select" id="group_id" name="group_id" required>
                                    <option value="">Seleziona un gruppo</option>
                                    
                                    <?php foreach ($groups as $group): ?>
                                        <option value="<?php echo $group['id']; ?>">
                                            <?php echo htmlspecialchars($group['name']); ?> (<?php echo $group['member_count']; ?> membri)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-share-alt me-2"></i> Condividi
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'share') {
    $groupId = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
    
    if ($groupId > 0) {
        $sharedId = shareNoteWithGroup($noteId, $groupId, $userId);
        
        if ($sharedId) {
            setFlashMessage('success', 'Appunto condiviso con il gruppo!');
        } else {
            setFlashMessage('error', 'Errore durante la condivisione dell\'appunto.');
        }
    } else {
        setFlashMessage('error', 'Seleziona un gruppo valido.');
    }
    
    redirect('index.php?page=view_note&id=' . $noteId);
}
?>

<?php

$pageTitle = 'Modifica Appunto';

$userId = $_SESSION['user_id'];

$noteId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($noteId <= 0) {
    setFlashMessage('error', 'ID appunto non valido.');
    redirect('index.php?page=notes');
}

$note = fetchOne(
    "SELECT * FROM notes WHERE id = ? AND user_id = ?",
    [$noteId, $userId],
    'ii'
);

if (!$note) {
    setFlashMessage('error', 'Appunto non trovato o non hai il permesso di modificarlo.');
    redirect('index.php?page=notes');
}

$subjects = getUserSubjects($userId);


$noteTags = getNoteTags($noteId);
$tagString = implode(', ', array_column($noteTags, 'name'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $subjectId = isset($_POST['subject_id']) ? intval($_POST['subject_id']) : 0;
    $content = $_POST['content'] ?? '';
    $isPublic = isset($_POST['is_public']) && $_POST['is_public'] === '1';
    $tags = $_POST['tags'] ?? '';
    
    $errors = [];
    
    if (empty($title)) {
        $errors[] = 'Il titolo è obbligatorio.';
    }
    
    if ($subjectId <= 0 || !in_array($subjectId, array_column($subjects, 'id'))) {
        $errors[] = 'Seleziona una materia valida.';
    }
    
    if (empty($content)) {
        $errors[] = 'Il contenuto è obbligatorio.';
    }
    
    if (empty($errors)) {
        $data = [
            'title' => $title,
            'subject_id' => $subjectId,
            'content' => $content,
            'is_public' => $isPublic ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if (updateNote($noteId, $userId, $data)) {
            delete('note_tags', 'note_id = ?', [$noteId]);
            
            if (!empty($tags)) {
                $tagList = explode(',', $tags);
                
                foreach ($tagList as $tag) {
                    $tag = trim($tag);
                    
                    if (!empty($tag)) {
                        addNoteTag($noteId, $tag, $userId);
                    }
                }
            }
            
            setFlashMessage('success', 'Appunto aggiornato con successo!');
            redirect('index.php?page=view_note&id=' . $noteId);
        } else {
            setFlashMessage('error', 'Errore durante l\'aggiornamento dell\'appunto.');
        }
    } else {
        setFlashMessage('error', implode('<br>', $errors));
    }
}

$extraScripts = '
<script>
    $(document).ready(function() {
        $("#content").summernote({
            height: 300,
            toolbar: [
                ["style", ["style"]],
                ["font", ["bold", "italic", "underline", "clear"]],
                ["color", ["color"]],
                ["para", ["ul", "ol", "paragraph"]],
                ["table", ["table"]],
                ["insert", ["link", "picture"]],
                ["view", ["fullscreen", "codeview", "help"]]
            ]
        });
    });
</script>
';
?>

<div class="card shadow">
    <div class="card-header bg-primary text-white">
        <h4 class="mb-0"><i class="fas fa-edit me-2"></i> Modifica Appunto</h4>
    </div>
    
    <div class="card-body">
        <form method="post" action="">
            <div class="mb-3">
                <label for="title" class="form-label">Titolo</label>
                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($note['title']); ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="subject_id" class="form-label">Materia</label>
                <select class="form-select" id="subject_id" name="subject_id" required>
                    <option value="">Seleziona una materia</option>
                    
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?php echo $subject['id']; ?>" <?php echo $subject['id'] === $note['subject_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($subject['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="tags" class="form-label">Tag (separati da virgola)</label>
                <input type="text" class="form-control" id="tags" name="tags" value="<?php echo htmlspecialchars($tagString); ?>" placeholder="es. importante, da rivedere, riassunto">
                <div class="form-text">Aggiungi tag per organizzare meglio i tuoi appunti.</div>
            </div>
            
            <div class="mb-3">
                <label for="content" class="form-label">Contenuto</label>
                <textarea class="form-control" id="content" name="content" rows="10" required><?php echo htmlspecialchars($note['content']); ?></textarea>
            </div>
            
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="is_public" name="is_public" value="1" <?php echo $note['is_public'] ? 'checked' : ''; ?>>
                <label class="form-check-label" for="is_public">Rendi pubblico (visibile a tutti)</label>
                <div class="form-text">Gli appunti pubblici possono essere visualizzati da chiunque abbia il link.</div>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="index.php?page=view_note&id=<?php echo $noteId; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i> Annulla
                </a>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i> Salva Modifiche
                </button>
            </div>
        </form>
    </div>
</div>
