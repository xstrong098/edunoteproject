<?php

$pageTitle = 'Dettagli Gruppo';

$userId = $_SESSION['user_id'];

$groupId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$tab = $_GET['tab'] ?? 'notes'; 
$sharedNoteId = isset($_GET['shared_note']) ? intval($_GET['shared_note']) : 0;

if ($groupId <= 0) {
    setFlashMessage('error', 'ID gruppo non valido.');
    redirect('index.php?page=study_groups');
}

if (!isGroupMember($groupId, $userId)) {
    setFlashMessage('error', 'Non sei membro di questo gruppo.');
    redirect('index.php?page=study_groups');
}

$group = getStudyGroup($groupId);

if (!$group) {
    setFlashMessage('error', 'Gruppo non trovato.');
    redirect('index.php?page=study_groups');
}

$isAdmin = isGroupAdmin($groupId, $userId);

$members = getGroupMembers($groupId);

$sharedNotes = getGroupSharedNotes($groupId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_member') {
        if (!$isAdmin) {
            setFlashMessage('error', 'Solo gli amministratori possono aggiungere membri.');
            redirect('index.php?page=group_details&id=' . $groupId . '&tab=members');
        }
        
        $username = $_POST['username'] ?? '';
        
        if (empty($username)) {
            setFlashMessage('error', 'Username o email obbligatorio.');
        } else {
            $success = addGroupMember($groupId, $username);
            
            if ($success) {
                setFlashMessage('success', 'Membro aggiunto con successo!');
            } else {
                setFlashMessage('error', 'Impossibile aggiungere il membro. Verifica che l\'username o l\'email sia corretto e che l\'utente non sia già membro del gruppo.');
            }
        }
    } elseif ($action === 'remove_member') {
        if (!$isAdmin) {
            setFlashMessage('error', 'Solo gli amministratori possono rimuovere membri.');
            redirect('index.php?page=group_details&id=' . $groupId . '&tab=members');
        }
        
        $memberId = isset($_POST['member_id']) ? intval($_POST['member_id']) : 0;
        
        if ($memberId <= 0) {
            setFlashMessage('error', 'ID membro non valido.');
        } elseif ($memberId === $userId) {
            setFlashMessage('error', 'Non puoi rimuoverti da solo. Usa l\'opzione "Abbandona Gruppo" invece.');
        } else {
            $success = removeGroupMember($groupId, $memberId, $userId);
            
            if ($success) {
                setFlashMessage('success', 'Membro rimosso con successo!');
            } else {
                setFlashMessage('error', 'Impossibile rimuovere il membro. Potrebbe essere l\'unico amministratore rimasto.');
            }
        }
    } elseif ($action === 'promote_admin') {
        if (!$isAdmin) {
            setFlashMessage('error', 'Solo gli amministratori possono promuovere membri.');
            redirect('index.php?page=group_details&id=' . $groupId . '&tab=members');
        }
        
        $memberId = isset($_POST['member_id']) ? intval($_POST['member_id']) : 0;
        
        if ($memberId <= 0) {
            setFlashMessage('error', 'ID membro non valido.');
        } else {
            $success = update(
                'group_members',
                ['is_admin' => 1],
                'group_id = ? AND user_id = ?',
                [$groupId, $memberId]
            );
            
            if ($success) {
                setFlashMessage('success', 'Membro promosso ad amministratore!');
            } else {
                setFlashMessage('error', 'Impossibile promuovere il membro.');
            }
        }
    } elseif ($action === 'add_comment') {
        $sharedNoteId = isset($_POST['shared_note_id']) ? intval($_POST['shared_note_id']) : 0;
        $comment = $_POST['comment'] ?? '';
        
        if ($sharedNoteId <= 0) {
            setFlashMessage('error', 'ID appunto condiviso non valido.');
        } elseif (empty($comment)) {
            setFlashMessage('error', 'Il commento non può essere vuoto.');
        } else {
            $success = addNoteComment($sharedNoteId, $userId, $comment);
            
            if ($success) {
                setFlashMessage('success', 'Commento aggiunto con successo!');
            } else {
                setFlashMessage('error', 'Errore durante l\'aggiunta del commento.');
            }
        }
    }
    
    redirect('index.php?page=group_details&id=' . $groupId . '&tab=' . $tab . ($sharedNoteId ? '&shared_note=' . $sharedNoteId : ''));
}

$pageTitle = 'Gruppo: ' . $group['name'];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="fas fa-users me-2"></i> <?php echo htmlspecialchars($group['name']); ?></h1>
    
    <a href="index.php?page=study_groups" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i> Indietro
    </a>
</div>

<div class="card shadow mb-4">
    <div class="card-body">
        <p><?php echo nl2br(htmlspecialchars($group['description'])); ?></p>
        
        <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted small">
                <div><i class="fas fa-calendar-alt me-1"></i> Creato: <?php echo formatDateTime($group['created_at']); ?></div>
                <div>
                    <i class="fas fa-user me-1"></i> Creato da:
                    <?php 
                    $creator = fetchOne("SELECT username FROM users WHERE id = ?", [$group['created_by']], 'i');
                    echo $creator ? htmlspecialchars($creator['username']) : 'Utente sconosciuto';
                    ?>
                </div>
            </div>
            
            <span class="badge bg-primary rounded-pill">
                <?php echo count($members); ?> membri
            </span>
        </div>
    </div>
</div>

<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link <?php echo $tab === 'notes' ? 'active' : ''; ?>" href="index.php?page=group_details&id=<?php echo $groupId; ?>&tab=notes">
            <i class="fas fa-sticky-note me-1"></i> Appunti Condivisi
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $tab === 'members' ? 'active' : ''; ?>" href="index.php?page=group_details&id=<?php echo $groupId; ?>&tab=members">
            <i class="fas fa-users me-1"></i> Membri
        </a>
    </li>
</ul>

<?php if ($tab === 'notes'): ?>
    <?php if ($sharedNoteId > 0): ?>
        <?php
        $sharedNote = null;
        
        foreach ($sharedNotes as $note) {
            if ($note['id'] === $sharedNoteId) {
                $sharedNote = $note;
                break;
            }
        }
        
        if (!$sharedNote) {
            setFlashMessage('error', 'Appunto condiviso non trovato.');
            redirect('index.php?page=group_details&id=' . $groupId . '&tab=notes');
        }
        
        $comments = getNoteComments($sharedNoteId);
        ?>
        
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?php echo htmlspecialchars($sharedNote['title']); ?></h5>
                    
                    <a href="index.php?page=group_details&id=<?php echo $groupId; ?>&tab=notes" class="btn btn-sm btn-light">
                        <i class="fas fa-arrow-left me-1"></i> Torna agli appunti
                    </a>
                </div>
            </div>
            
            <div class="card-body">
                <?php if (!empty($sharedNote['summary'])): ?>
                    <div class="alert alert-info mb-4">
                        <h5 class="alert-heading"><i class="fas fa-lightbulb me-2"></i> Riassunto</h5>
                        <p class="mb-0"><?php echo htmlspecialchars($sharedNote['summary']); ?></p>
                    </div>
                <?php endif; ?>
                
                <div class="note-content">
                    <?php echo $sharedNote['content']; ?>
                </div>
                
                <div class="text-muted small mt-3">
                    <div>
                        <i class="fas fa-user me-1"></i> Condiviso da:
                        <?php echo htmlspecialchars($sharedNote['shared_by_username']); ?>
                    </div>
                    <div>
                        <i class="fas fa-calendar-alt me-1"></i> Condiviso il:
                        <?php echo formatDateTime($sharedNote['shared_at']); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card shadow">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-comments me-2"></i> Commenti (<?php echo count($comments); ?>)</h5>
            </div>
            
            <div class="card-body">
                <?php if (empty($comments)): ?>
                    <p class="text-muted">Nessun commento ancora. Sii il primo a commentare!</p>
                <?php else: ?>
                    <div class="comment-list mb-4">
                        <?php foreach ($comments as $comment): ?>
                            <div class="comment mb-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <?php if ($comment['profile_image']): ?>
                                            <img src="uploads/profiles/<?php echo $comment['profile_image']; ?>" alt="<?php echo htmlspecialchars($comment['username']); ?>" class="rounded-circle" width="40" height="40">
                                        <?php else: ?>
                                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <?php echo strtoupper(substr($comment['username'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="flex-grow-1 ms-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0"><?php echo htmlspecialchars($comment['username']); ?></h6>
                                            <small class="text-muted"><?php echo formatDateTime($comment['created_at']); ?></small>
                                        </div>
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <input type="hidden" name="action" value="add_comment">
                    <input type="hidden" name="shared_note_id" value="<?php echo $sharedNoteId; ?>">
                    
                    <div class="mb-3">
                        <label for="comment" class="form-label">Aggiungi un commento</label>
                        <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i> Invia commento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <?php if (empty($sharedNotes)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> Nessun appunto condiviso in questo gruppo. Condividi i tuoi appunti con il gruppo dalla pagina di visualizzazione di un appunto.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 g-4">
                <?php foreach ($sharedNotes as $note): ?>
                    <div class="col">
                        <div class="card h-100 shadow-sm">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><?php echo htmlspecialchars($note['title']); ?></h5>
                                    
                                    <?php
                                    $commentCount = fetchOne(
                                        "SELECT COUNT(*) as count FROM note_comments WHERE shared_note_id = ?",
                                        [$note['id']],
                                        'i'
                                    )['count'];
                                    ?>
                                    
                                    <span class="badge bg-secondary rounded-pill">
                                        <i class="fas fa-comments me-1"></i> <?php echo $commentCount; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="card-body">
                                <p class="card-text"><?php echo htmlspecialchars(substr($note['summary'], 0, 150)); ?>...</p>
                            </div>
                            
                            <div class="card-footer">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i> 
                                        <?php echo htmlspecialchars($note['shared_by_username']); ?>
                                        <i class="fas fa-calendar-alt ms-2 me-1"></i> 
                                        <?php echo formatDateTime($note['shared_at'], 'd M Y'); ?>
                                    </small>
                                    
                                    <a href="index.php?page=group_details&id=<?php echo $groupId; ?>&tab=notes&shared_note=<?php echo $note['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye me-1"></i> Visualizza
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
<?php elseif ($tab === 'members'): ?>
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i> Membri del Gruppo (<?php echo count($members); ?>)</h5>
                </div>
                
                <div class="list-group list-group-flush">
                    <?php foreach ($members as $member): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <?php if ($member['profile_image']): ?>
                                    <img src="uploads/profiles/<?php echo $member['profile_image']; ?>" alt="<?php echo htmlspecialchars($member['username']); ?>" class="rounded-circle me-3" width="40" height="40">
                                <?php else: ?>
                                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <?php echo strtoupper(substr($member['username'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($member['username']); ?></h6>
                                    <small class="text-muted"><?php echo htmlspecialchars($member['full_name']); ?></small>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-center">
                                <?php if ($member['is_admin']): ?>
                                    <span class="badge bg-primary me-3">Amministratore</span>
                                <?php endif; ?>
                                
                                <?php if ($isAdmin && $member['id'] !== $userId): ?>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <?php if (!$member['is_admin']): ?>
                                                <li>
                                                    <form method="post" action="">
                                                        <input type="hidden" name="action" value="promote_admin">
                                                        <input type="hidden" name="member_id" value="<?php echo $member['id']; ?>">
                                                        <button type="submit" class="dropdown-item text-primary">
                                                            <i class="fas fa-user-shield me-2"></i> Promuovi ad Admin
                                                        </button>
                                                    </form>
                                                </li>
                                            <?php endif; ?>
                                            <li>
                                                <form method="post" action="">
                                                    <input type="hidden" name="action" value="remove_member">
                                                    <input type="hidden" name="member_id" value="<?php echo $member['id']; ?>">
                                                    <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Sei sicuro di voler rimuovere questo membro dal gruppo?')">
                                                        <i class="fas fa-user-minus me-2"></i> Rimuovi
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <?php if ($isAdmin): ?>
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i> Aggiungi Membro</h5>
                    </div>
                    
                    <div class="card-body">
                        <form method="post" action="">
                            <input type="hidden" name="action" value="add_member">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Username o Email</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                                <div class="form-text">Inserisci l'username o l'email dell'utente che vuoi aggiungere al gruppo.</div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-user-plus me-2"></i> Aggiungi
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card shadow mt-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-share-alt me-2"></i> Invita Membri</h5>
                    </div>
                    
                    <div class="card-body">
                        <p>Condividi questo codice di invito con altri utenti per farli unire al gruppo:</p>
                        
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" value="<?php echo $groupId; ?>" id="invite_code" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="copyInviteCode()">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        
                        <div class="d-grid">
                            <button type="button" class="btn btn-info" onclick="copyInviteCode()">
                                <i class="fas fa-copy me-2"></i> Copia Codice
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <script>
                function copyInviteCode() {
                    var copyText = document.getElementById("invite_code");
                    copyText.select();
                    copyText.setSelectionRange(0, 99999);
                    document.execCommand("copy");
                    
                    alert("Codice di invito copiato: " + copyText.value);
                }
            </script>
        <?php endif; ?>
    </div>
<?php endif; ?>
