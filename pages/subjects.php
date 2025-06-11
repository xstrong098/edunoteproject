<?php
$pageTitle = 'Materie';

$userId = $_SESSION['user_id'];

$subjects = getUserSubjects($userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $color = $_POST['color'] ?? '#3498db';
        
        if (empty($name)) {
            setFlashMessage('error', 'Il nome della materia è obbligatorio.');
        } else {
            $subjectId = createSubject($userId, $name, $description, $color);
            
            if ($subjectId) {
                setFlashMessage('success', 'Materia creata con successo!');
            } else {
                setFlashMessage('error', 'Errore durante la creazione della materia.');
            }
        }
    } elseif ($action === 'update') {
        $subjectId = isset($_POST['subject_id']) ? intval($_POST['subject_id']) : 0;
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $color = $_POST['color'] ?? '#3498db';
        
        if (empty($name)) {
            setFlashMessage('error', 'Il nome della materia è obbligatorio.');
        } elseif ($subjectId <= 0) {
            setFlashMessage('error', 'ID materia non valido.');
        } else {
            $success = update(
                'subjects',
                [
                    'name' => $name,
                    'description' => $description,
                    'color' => $color
                ],
                'id = ? AND user_id = ?',
                [$subjectId, $userId]
            );
            
            if ($success) {
                setFlashMessage('success', 'Materia aggiornata con successo!');
            } else {
                setFlashMessage('error', 'Errore durante l\'aggiornamento della materia.');
            }
        }
    } elseif ($action === 'delete') {
        $subjectId = isset($_POST['subject_id']) ? intval($_POST['subject_id']) : 0;
        
        if ($subjectId <= 0) {
            setFlashMessage('error', 'ID materia non valido.');
        } else {
            $noteCount = fetchOne(
                "SELECT COUNT(*) as count FROM notes WHERE subject_id = ? AND user_id = ?",
                [$subjectId, $userId],
                'ii'
            )['count'];
            
            if ($noteCount > 0) {
                setFlashMessage('error', 'Impossibile eliminare la materia: contiene degli appunti. Elimina prima gli appunti o spostali in un\'altra materia.');
            } else {
                $success = delete('subjects', 'id = ? AND user_id = ?', [$subjectId, $userId]);
                
                if ($success) {
                    setFlashMessage('success', 'Materia eliminata con successo!');
                } else {
                    setFlashMessage('error', 'Errore durante l\'eliminazione della materia.');
                }
            }
        }
    }
    
    redirect('index.php?page=subjects');
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="fas fa-book me-2"></i> Materie</h1>
    
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createSubjectModal">
        <i class="fas fa-plus me-2"></i> Nuova Materia
    </button>
</div>

<?php if (empty($subjects)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i> Non hai ancora creato nessuna materia. Crea la tua prima materia per iniziare a organizzare i tuoi appunti.
    </div>
<?php else: ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php foreach ($subjects as $subject): ?>
            <?php
            $noteCount = fetchOne(
                "SELECT COUNT(*) as count FROM notes WHERE subject_id = ? AND user_id = ?",
                [$subject['id'], $userId],
                'ii'
            )['count'];
            ?>
            
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <div class="card-header" style="background-color: <?php echo $subject['color']; ?>; color: white;">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><?php echo htmlspecialchars($subject['name']); ?></h5>
                            
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editSubjectModal<?php echo $subject['id']; ?>">
                                            <i class="fas fa-edit me-2"></i> Modifica
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="index.php?page=notes&subject=<?php echo $subject['id']; ?>">
                                            <i class="fas fa-sticky-note me-2"></i> Visualizza Appunti
                                        </a>
                                    </li>
                                    <?php if ($noteCount === 0): ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#deleteSubjectModal<?php echo $subject['id']; ?>">
                                                <i class="fas fa-trash-alt me-2"></i> Elimina
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body">
                    <p class="card-text"><?php echo isset($subject['description']) ? htmlspecialchars($subject['description']) : ''; ?></p>

                    </div> 
                    
                    <div class="card-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">
                                <i class="fas fa-sticky-note me-1"></i> <?php echo $noteCount; ?> appunti
                            </span>
                            
                            <a href="index.php?page=create_note&subject=<?php echo $subject['id']; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-plus me-1"></i> Nuovo Appunto
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal fade" id="editSubjectModal<?php echo $subject['id']; ?>" tabindex="-1" aria-labelledby="editSubjectModalLabel<?php echo $subject['id']; ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="post" action="">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="subject_id" value="<?php echo $subject['id']; ?>">
                            
                            <div class="modal-header">
                                <h5 class="modal-title" id="editSubjectModalLabel<?php echo $subject['id']; ?>">Modifica Materia</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="edit_name<?php echo $subject['id']; ?>" class="form-label">Nome</label>
                                    <input type="text" class="form-control" id="edit_name<?php echo $subject['id']; ?>" name="name" value="<?php echo htmlspecialchars($subject['name']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edit_description<?php echo $subject['id']; ?>" class="form-label">Descrizione</label>
                                    <textarea class="form-control" id="edit_description<?php echo $subject['id']; ?>" name="description" rows="3"><?php echo htmlspecialchars($subject['description']); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edit_color<?php echo $subject['id']; ?>" class="form-label">Colore</label>
                                    <input type="color" class="form-control form-control-color" id="edit_color<?php echo $subject['id']; ?>" name="color" value="<?php echo $subject['color']; ?>">
                                </div>
                            </div>
                            
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                                <button type="submit" class="btn btn-primary">Salva Modifiche</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <?php if ($noteCount === 0): ?>
                <div class="modal fade" id="deleteSubjectModal<?php echo $subject['id']; ?>" tabindex="-1" aria-labelledby="deleteSubjectModalLabel<?php echo $subject['id']; ?>" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="post" action="">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="subject_id" value="<?php echo $subject['id']; ?>">
                                
                                <div class="modal-header">
                                    <h5 class="modal-title" id="deleteSubjectModalLabel<?php echo $subject['id']; ?>">Conferma Eliminazione</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                
                                <div class="modal-body">
                                    <p>Sei sicuro di voler eliminare la materia <strong><?php echo htmlspecialchars($subject['name']); ?></strong>?</p>
                                    <p class="text-danger">Questa azione non può essere annullata.</p>
                                </div>
                                
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                                    <button type="submit" class="btn btn-danger">Elimina</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="modal fade" id="createSubjectModal" tabindex="-1" aria-labelledby="createSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="">
                <input type="hidden" name="action" value="create">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="createSubjectModalLabel">Nuova Materia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nome</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Descrizione</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="color" class="form-label">Colore</label>
                        <input type="color" class="form-control form-control-color" id="color" name="color" value="#3498db">
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">Crea Materia</button>
                </div>
            </form>
        </div>
    </div>
</div>