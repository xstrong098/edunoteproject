<?php
$pageTitle = 'Profilo Utente';

$userId = $_SESSION['user_id'];

$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $fullName = $_POST['full_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        $errors = [];
        
        if (empty($fullName)) {
            $errors[] = 'Il nome completo è obbligatorio.';
        }
        
        if (empty($email)) {
            $errors[] = 'L\'email è obbligatoria.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Formato email non valido.';
        }
        
        if ($email !== $user['email']) {
            $existingUser = fetchOne("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $userId], 'si');
            
            if ($existingUser) {
                $errors[] = 'Email già in uso da un altro account.';
            }
        }
        
        if (!empty($newPassword) || !empty($confirmPassword)) {
            if (empty($currentPassword)) {
                $errors[] = 'La password attuale è obbligatoria per cambiare password.';
            } elseif (!password_verify($currentPassword, $user['password'])) {
                $errors[] = 'Password attuale non corretta.';
            }
            
            if (empty($newPassword)) {
                $errors[] = 'La nuova password è obbligatoria.';
            } elseif (strlen($newPassword) < 6) {
                $errors[] = 'La nuova password deve essere lunga almeno 6 caratteri.';
            }
            
            if ($newPassword !== $confirmPassword) {
                $errors[] = 'La conferma della password non corrisponde.';
            }
        }
        
        $profileImage = $user['profile_image'];
        
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $fileName = $_FILES['profile_image']['name'];
            $fileSize = $_FILES['profile_image']['size'];
            $fileTmp = $_FILES['profile_image']['tmp_name'];
            $fileType = $_FILES['profile_image']['type'];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (!in_array($fileExt, $allowedExt)) {
                $errors[] = 'Estensione file non consentita. Sono consentiti solo JPG, JPEG, PNG e GIF.';
            }
            
            if ($fileSize > 5 * 1024 * 1024) {
                $errors[] = 'La dimensione del file deve essere inferiore a 5MB.';
            }
            
            if (empty($errors)) {
                $newFileName = 'profile_' . $userId . '_' . time() . '.' . $fileExt;
                $uploadPath = 'uploads/profiles/';
                
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }
                
                if (move_uploaded_file($fileTmp, $uploadPath . $newFileName)) {
                    if ($profileImage && file_exists($uploadPath . $profileImage)) {
                        unlink($uploadPath . $profileImage);
                    }
                    
                    $profileImage = $newFileName;
                } else {
                    $errors[] = 'Errore durante il caricamento dell\'immagine del profilo.';
                }
            }
        }
        
        if (empty($errors)) {
            $data = [
                'full_name' => $fullName,
                'email' => $email,
                'profile_image' => $profileImage
            ];
            
            if (!empty($newPassword)) {
                $data['password'] = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
            }
            
            $success = update('users', $data, 'id = ?', [$userId]);
            
            if ($success) {
                setFlashMessage('success', 'Profilo aggiornato con successo!');
            } else {
                setFlashMessage('error', 'Errore durante l\'aggiornamento del profilo.');
            }
        } else {
            setFlashMessage('error', implode('<br>', $errors));
        }
    }
    
    redirect('index.php?page=profile');
}

$stats = getUserStats($userId);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="fas fa-user-cog me-2"></i> Profilo Utente</h1>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user me-2"></i> Informazioni Personali</h5>
            </div>
            
            <div class="card-body">
                <form method="post" action="" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="mb-4 text-center">
                        <?php if ($user['profile_image']): ?>
                            <img src="uploads/profiles/<?php echo $user['profile_image']; ?>" alt="<?php echo htmlspecialchars($user['username']); ?>" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                        <?php else: ?>
                            <div class="mx-auto rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center mb-3" style="width: 150px; height: 150px; font-size: 4rem;">
                                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="profile_image" class="form-label">Immagine Profilo</label>
                            <input class="form-control" type="file" id="profile_image" name="profile_image" accept="image/*">
                            <div class="form-text">Immagine del profilo (max 5MB, formati: JPG, PNG, GIF)</div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                            <div class="form-text">L'username non può essere modificato.</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="full_name" class="form-label">Nome Completo</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h5>Cambia Password</h5>
                    <p class="text-muted small mb-3">Lascia vuoto per mantenere la password attuale.</p>
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Password Attuale</label>
                        <input type="password" class="form-control" id="current_password" name="current_password">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="new_password" class="form-label">Nuova Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                            <div class="form-text">Minimo 6 caratteri.</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label">Conferma Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Aggiorna Profilo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i> Statistiche</h5>
            </div>
            
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Appunti totali
                        <span class="badge bg-primary rounded-pill"><?php echo $stats['total_notes']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Materie
                        <span class="badge bg-success rounded-pill"><?php echo $stats['total_subjects']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Appunti questo mese
                        <span class="badge bg-info rounded-pill"><?php echo $stats['notes_this_month']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Appunti da ripassare
                        <span class="badge bg-warning rounded-pill"><?php echo $stats['notes_for_review']; ?></span>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="card shadow">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i> Informazioni Account</h5>
            </div>
            
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>API Key</span>
                        <span class="text-muted"><?php echo substr($user['api_key'], 0, 8) . '...'; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Registrato il</span>
                        <span class="text-muted"><?php echo formatDateTime($user['registration_date'], 'd M Y'); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Ultimo accesso</span>
                        <span class="text-muted"><?php echo formatDateTime($user['last_login'], 'd M Y H:i'); ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
