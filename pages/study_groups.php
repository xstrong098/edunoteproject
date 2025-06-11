<?php
$pageTitle = 'Gruppi di Studio';

$userId = $_SESSION['user_id'];

$groups = getUserStudyGroups($userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        
        if (empty($name)) {
            setFlashMessage('error', 'Il nome del gruppo è obbligatorio.');
        } else {
            $groupId = createStudyGroup($userId, $name, $description);
            
            if ($groupId) {
                setFlashMessage('success', 'Gruppo di studio creato con successo!');
            } else {
                setFlashMessage('error', 'Errore durante la creazione del gruppo di studio.');
            }
        }
    } elseif ($action === 'join') {
        $inviteCode = $_POST['invite_code'] ?? '';
        
        if (empty($inviteCode)) {
            setFlashMessage('error', 'Il codice di invito è obbligatorio.');
        } else {
         
            $groupId = intval($inviteCode);
            
            if ($groupId > 0) {
                $group = getStudyGroup($groupId);
                
                if ($group) {
                    $success = addGroupMember($groupId, $userId);
                    
                    if ($success) {
                        setFlashMessage('success', 'Ti sei unito al gruppo di studio con successo!');
                    } else {
                        setFlashMessage('error', 'Errore durante l\'adesione al gruppo di studio.');
                    }
                } else {
                    setFlashMessage('error', 'Gruppo di studio non trovato con il codice di invito fornito.');
                }
            } else {
                setFlashMessage('error', 'Codice di invito non valido.');
            }
        }
    } elseif ($action === 'leave') {
        $groupId = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
        
        if ($groupId <= 0) {
            setFlashMessage('error', 'ID gruppo non valido.');
        } else {
            $isAdmin = isGroupAdmin($groupId, $userId);
            
            if ($isAdmin) {
                $adminCount = fetchOne(
                    "SELECT COUNT(*) as count FROM group_members WHERE group_id = ? AND is_admin = 1",
                    [$groupId],
                    'i'
                )['count'];
                
                if ($adminCount <= 1) {
                    setFlashMessage('error', 'Non puoi lasciare il gruppo: sei l\'unico amministratore. Nomina un altro membro come amministratore prima di lasciare il gruppo.');
                } else {
                    $success = delete('group_members', 'group_id = ? AND user_id = ?', [$groupId, $userId]);
                    
                    if ($success) {
                        setFlashMessage('success', 'Hai lasciato il gruppo di studio.');
                    } else {
                        setFlashMessage('error', 'Errore durante l\'abbandono del gruppo di studio.');
                    }
                }
            } else {
                $success = delete('group_members', 'group_id = ? AND user_id = ?', [$groupId, $userId]);
                
                if ($success) {
                    setFlashMessage('success', 'Hai lasciato il gruppo di studio.');
                } else {
                    setFlashMessage('error', 'Errore durante l\'abbandono del gruppo di studio.');
                }
            }
        }
    }
    
    redirect('index.php?page=study_groups');
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="fas fa-users me-2"></i> Gruppi di Studio</h1>
    
    <div class="btn-group">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createGroupModal">
            <i class="fas fa-plus me-2"></i> Nuovo Gruppo
        </button>
        
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#joinGroupModal">
            <i class="fas fa-sign-in-alt me-2"></i> Unisciti
        </button>
    </div>
</div>

<?php if (empty($groups)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i> Non sei membro di nessun gruppo di studio. Crea un nuovo gruppo o unisciti a uno esistente tramite codice di invito.
    </div>
<?php else: ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php foreach ($groups as $group): ?>
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <div class="card-header bg-info text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><?php echo htmlspecialchars($group['name']); ?></h5>
                            
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="index.php?page=group_details&id=<?php echo $group['id']; ?>">
                                            <i class="fas fa-info-circle me-2"></i> Dettagli
                                        </a>
                                    </li>
                                    <?php if ($group['is_admin']): ?>
                                        <li>
                                            <a class="dropdown-item" href="index.php?page=group_details&id=<?php echo $group['id']; ?>&tab=members">
                                                <i class="fas fa-user-cog me-2"></i> Gestisci Membri
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#leaveGroupModal<?php echo $group['id']; ?>">
                                            <i class="fas fa-sign-out-alt me-2"></i> Abbandona Gruppo
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <p class="card-text"><?php echo htmlspecialchars($group['description']); ?></p>
                    </div>
                    
                    <div class="card-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">
                                <i class="fas fa-users me-1"></i> <?php echo $group['member_count']; ?> membri
                                <?php if ($group['is_admin']): ?>
                                    <span class="badge bg-primary ms-1">Admin</span>
                                <?php endif; ?>
                            </span>
                            
                            <a href="index.php?page=group_details&id=<?php echo $group['id']; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye me-1"></i> Visualizza
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal fade" id="leaveGroupModal<?php echo $group['id']; ?>" tabindex="-1" aria-labelledby="leaveGroupModalLabel<?php echo $group['id']; ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="post" action="">
                            <input type="hidden" name="action" value="leave">
                            <input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
                            
                            <div class="modal-header">
                                <h5 class="modal-title" id="leaveGroupModalLabel<?php echo $group['id']; ?>">Conferma Abbandono</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            
                            <div class="modal-body">
                                <p>Sei sicuro di voler abbandonare il gruppo <strong><?php echo htmlspecialchars($group['name']); ?></strong>?</p>
                                <?php if ($group['is_admin']): ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i> Sei un amministratore di questo gruppo. Se sei l'unico amministratore, non potrai abbandonare il gruppo finché non nominerai un altro amministratore.
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                                <button type="submit" class="btn btn-danger">Abbandona</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="modal fade" id="createGroupModal" tabindex="-1" aria-labelledby="createGroupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="">
                <input type="hidden" name="action" value="create">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="createGroupModalLabel">Nuovo Gruppo di Studio</h5>
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
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">Crea Gruppo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="joinGroupModal" tabindex="-1" aria-labelledby="joinGroupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="">
                <input type="hidden" name="action" value="join">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="joinGroupModalLabel">Unisciti a un Gruppo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="invite_code" class="form-label">Codice di Invito</label>
                        <input type="text" class="form-control" id="invite_code" name="invite_code" required>
                        <div class="form-text">Inserisci il codice di invito che hai ricevuto per unirti a un gruppo esistente.</div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">Unisciti</button>
                </div>
            </form>
        </div>
    </div>
</div>
