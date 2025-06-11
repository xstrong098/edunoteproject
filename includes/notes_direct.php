<?php
if (!isset($_SESSION['user_id'])) {
    echo "<div class='alert alert-danger'>Accesso non autorizzato.</div>";
    return;
}

$userId = $_SESSION['user_id'];

$subjectId = isset($_GET['subject']) ? intval($_GET['subject']) : null;
$searchQuery = $_GET['search'] ?? '';
$tagId = isset($_GET['tag']) ? intval($_GET['tag']) : null;

$subjects = fetchAll("SELECT * FROM subjects WHERE user_id = ? ORDER BY name", [$userId], 'i');

if (!empty($searchQuery)) {
    $keyword = "%$searchQuery%";
    $notes = fetchAll(
        "SELECT n.*, s.name as subject_name, s.color as subject_color
        FROM notes n
        JOIN subjects s ON n.subject_id = s.id
        WHERE n.user_id = ? AND (n.title LIKE ? OR n.content LIKE ? OR n.summary LIKE ?)
        ORDER BY n.updated_at DESC",
        [$userId, $keyword, $keyword, $keyword],
        'isss'
    );
} elseif ($tagId) {
    $notes = fetchAll(
        "SELECT n.*, s.name as subject_name, s.color as subject_color
        FROM notes n
        JOIN note_tags nt ON n.id = nt.note_id
        JOIN subjects s ON n.subject_id = s.id
        WHERE nt.tag_id = ? AND n.user_id = ?
        ORDER BY n.created_at DESC",
        [$tagId, $userId],
        'ii'
    );
} else {
    $params = [$userId];
    $types = 'i';
    $sql = "SELECT n.*, s.name as subject_name, s.color as subject_color
            FROM notes n
            JOIN subjects s ON n.subject_id = s.id
            WHERE n.user_id = ?";
    
    if ($subjectId !== null) {
        $sql .= " AND n.subject_id = ?";
        $params[] = $subjectId;
        $types .= 'i';
    }
    
    $sql .= " ORDER BY n.updated_at DESC";
    $notes = fetchAll($sql, $params, $types);
}

$tags = fetchAll("SELECT * FROM tags WHERE user_id = ? ORDER BY name", [$userId], 'i');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="fas fa-sticky-note me-2"></i> I Miei Appunti</h1>
    
    <a href="index.php?page=create_note" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i> Nuovo Appunto
    </a>
</div>

<div class="row">
    <div class="col-lg-3">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-filter me-2"></i> Filtri</h5>
            </div>
            
            <div class="card-body">
                <form action="" method="get">
                    <input type="hidden" name="page" value="notes">
                    
                    <div class="mb-3">
                        <label for="search" class="form-label">Cerca</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
                
                <hr>
                
                <h6 class="mb-2">Materie</h6>
                <div class="list-group mb-3">
                    <a href="index.php?page=notes" class="list-group-item list-group-item-action <?php echo $subjectId === null && empty($searchQuery) && $tagId === null ? 'active' : ''; ?>">
                        Tutte le materie
                    </a>
                    
                    <?php if (is_array($subjects)): ?>
                        <?php foreach ($subjects as $subject): ?>
                            <a href="index.php?page=notes&subject=<?php echo $subject['id']; ?>" 
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $subjectId === $subject['id'] ? 'active' : ''; ?>">
                                <span>
                                    <i class="fas fa-circle me-2" style="color: <?php echo $subject['color']; ?>;"></i>
                                    <?php echo htmlspecialchars($subject['name']); ?>
                                </span>
                                
                                <?php
                                // Count notes for this subject
                                $count = fetchOne("SELECT COUNT(*) as count FROM notes WHERE user_id = ? AND subject_id = ?", [$userId, $subject['id']], 'ii');
                                $noteCount = $count ? $count['count'] : 0;
                                ?>
                                
                                <span class="badge bg-secondary rounded-pill"><?php echo $noteCount; ?></span>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($tags)): ?>
                    <h6 class="mb-2">Tag</h6>
                    <div class="list-group">
                        <?php foreach ($tags as $tag): ?>
                            <a href="index.php?page=notes&tag=<?php echo $tag['id']; ?>" 
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $tagId === $tag['id'] ? 'active' : ''; ?>">
                                <span>
                                    <i class="fas fa-tag me-2"></i>
                                    <?php echo htmlspecialchars($tag['name']); ?>
                                </span>
                                
                                <?php
                                $count = fetchOne(
                                    "SELECT COUNT(*) as count FROM note_tags nt JOIN notes n ON nt.note_id = n.id WHERE n.user_id = ? AND nt.tag_id = ?", 
                                    [$userId, $tag['id']], 
                                    'ii'
                                );
                                $tagNoteCount = $count ? $count['count'] : 0;
                                ?>
                                
                                <span class="badge bg-secondary rounded-pill"><?php echo $tagNoteCount; ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-9">
        <?php if (empty($notes)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> Nessun appunto trovato.
                <?php if (!empty($searchQuery) || $subjectId !== null || $tagId !== null): ?>
                    <a href="index.php?page=notes" class="alert-link">Rimuovi i filtri</a>
                <?php else: ?>
                    <a href="index.php?page=create_note" class="alert-link">Crea il tuo primo appunto</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 g-4">
                <?php foreach ($notes as $note): ?>
                    <div class="col">
                        <div class="card h-100 shadow-sm">
                            <div class="card-header" style="border-left: 5px solid <?php echo $note['subject_color'] ?? '#333'; ?>;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><?php echo htmlspecialchars($note['title']); ?></h5>
                                    
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-link text-dark" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="index.php?page=view_note&id=<?php echo $note['id']; ?>">
                                                    <i class="fas fa-eye me-2"></i> Visualizza
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="index.php?page=edit_note&id=<?php echo $note['id']; ?>">
                                                    <i class="fas fa-edit me-2"></i> Modifica
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form method="post" action="index.php?page=notes&action=delete">
                                                    <input type="hidden" name="note_id" value="<?php echo $note['id']; ?>">
                                                    <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Sei sicuro di voler eliminare questo appunto?')">
                                                        <i class="fas fa-trash-alt me-2"></i> Elimina
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="mt-1">
                                    <span class="badge" style="background-color: <?php echo $note['subject_color'] ?? '#333'; ?>;">
                                        <?php echo htmlspecialchars($note['subject_name'] ?? 'Senza materia'); ?>
                                    </span>
                                    
                                    <?php
                                    $noteTags = fetchAll(
                                        "SELECT t.* FROM tags t JOIN note_tags nt ON t.id = nt.tag_id WHERE nt.note_id = ? ORDER BY t.name",
                                        [$note['id']], 
                                        'i'
                                    );
                                    
                                    if (is_array($noteTags)):
                                        foreach ($noteTags as $noteTag):
                                    ?>
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-tag me-1"></i>
                                            <?php echo htmlspecialchars($noteTag['name']); ?>
                                        </span>
                                    <?php 
                                        endforeach;
                                    endif; 
                                    ?>
                                </div>
                            </div>
                            
                            <div class="card-body">
                                <p class="card-text"><?php echo htmlspecialchars(substr($note['summary'] ?? '', 0, 150)); ?>...</p>
                            </div>
                            
                            <div class="card-footer text-muted">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small>
                                        <i class="fas fa-clock me-1"></i> 
                                        <?php echo isset($note['updated_at']) ? formatDateTime($note['updated_at']) : 'N/A'; ?>
                                    </small>
                                    
                                    <a href="index.php?page=view_note&id=<?php echo $note['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        Leggi tutto
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_POST['note_id'])) {
    $noteId = intval($_POST['note_id']);
    
    if (deleteNote($noteId, $userId)) {
        echo "<script>
                alert('Appunto eliminato con successo.');
                window.location.href = 'index.php?page=notes';
              </script>";
    } else {
        echo "<script>
                alert('Impossibile eliminare l\'appunto.');
                window.location.href = 'index.php?page=notes';
              </script>";
    }
}
?>