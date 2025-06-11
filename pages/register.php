<?php
$pageTitle = 'Registrazione';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $fullName = $_POST['full_name'] ?? '';
    
    $errors = [];
    
    if (empty($username)) {
        $errors[] = 'Username obbligatorio.';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Username troppo corto (min. 3 caratteri).';
    }
    
    if (empty($email)) {
        $errors[] = 'Email obbligatoria.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Formato email non valido.';
    }
    
    if (empty($password)) {
        $errors[] = 'Password obbligatoria.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password troppo corta (min. 6 caratteri).';
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = 'Le password non corrispondono.';
    }
    
    if (empty($fullName)) {
        $errors[] = 'Nome completo obbligatorio.';
    }
    
    if (empty($errors)) {
        $userId = registerUser($username, $email, $password, $fullName);
        
        if ($userId) {
            $_SESSION['user_id'] = $userId;
            
            $subjects = [
                ['name' => 'Matematica', 'color' => '#3498db'],
                ['name' => 'Storia', 'color' => '#e74c3c'],
                ['name' => 'Scienze', 'color' => '#2ecc71'],
                ['name' => 'Letteratura', 'color' => '#f39c12'],
                ['name' => 'Inglese', 'color' => '#9b59b6']
            ];

            foreach ($subjects as $subject) {
                $sql = "INSERT INTO subjects (user_id, name, color) VALUES (?, ?, ?)";
                executeQuery($sql, [$userId, $subject['name'], $subject['color']], 'iss');
            }
            
            setFlashMessage('success', 'Registrazione completata con successo!');
            redirect('index.php?page=notes');
        } else {
            setFlashMessage('error', 'Username o email già in uso.');
        }
    } else {
        setFlashMessage('error', implode('<br>', $errors));
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-user-plus me-2"></i> Registrati</h4>
            </div>
            
            <div class="card-body">
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="form-text">Scegli un username unico (min. 3 caratteri).</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Nome completo</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="form-text">Minimo 6 caratteri.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Conferma Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i> Registrati
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="card-footer text-center">
                <p class="mb-0">Hai già un account? <a href="index.php?page=login">Accedi</a></p>
            </div>
        </div>
    </div>
</div>