<?php

$pageTitle = 'Crea Nuovo Appunto';


$userId = $_SESSION['user_id'];


$subjects = getUserSubjects($userId);


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
        $noteId = createNote($userId, $subjectId, $title, $content, $isPublic);
        
        if ($noteId) {
            
            if (!empty($tags)) {
                $tagList = explode(',', $tags);
                
                foreach ($tagList as $tag) {
                    $tag = trim($tag);
                    
                    if (!empty($tag)) {
                        addNoteTag($noteId, $tag, $userId);
                    }
                }
            }
            
            setFlashMessage('success', 'Appunto creato con successo!');
            redirect('index.php?page=view_note&id=' . $noteId);
        } else {
            setFlashMessage('error', 'Errore durante la creazione dell\'appunto.');
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
        <h4 class="mb-0"><i class="fas fa-plus me-2"></i> Crea Nuovo Appunto</h4>
    </div>
    
    <div class="card-body">
        <form method="post" action="">
            <div class="mb-3">
                <label for="title" class="form-label">Titolo</label>
                <input type="text" class="form-control" id="title" name="title" required>
            </div>
            
            <div class="mb-3">
                <label for="subject_id" class="form-label">Materia</label>
                <select class="form-select" id="subject_id" name="subject_id" required>
                    <option value="">Seleziona una materia</option>
                    
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?php echo $subject['id']; ?>">
                            <?php echo htmlspecialchars($subject['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="tags" class="form-label">Tag (separati da virgola)</label>
                <input type="text" class="form-control" id="tags" name="tags" placeholder="es. importante, da rivedere, riassunto">
                <div class="form-text">Aggiungi tag per organizzare meglio i tuoi appunti.</div>
            </div>
            
            <div class="mb-3">
                <label for="content" class="form-label">Contenuto</label>
                <textarea class="form-control" id="content" name="content" rows="10" required></textarea>
            </div>
            
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="is_public" name="is_public" value="1">
                <label class="form-check-label" for="is_public">Rendi pubblico (visibile a tutti)</label>
                <div class="form-text">Gli appunti pubblici possono essere visualizzati da chiunque abbia il link.</div>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="index.php?page=notes" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i> Indietro
                </a>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i> Salva Appunto
                </button>
            </div>
        </form>
    </div>
</div>
