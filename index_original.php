<?php
ob_start(); 

require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';
session_start();

$isLoggedIn = isset($_SESSION['user_id']);

$page = isset($_GET['page']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['page']) : 'home';

$restricted_pages = ['notes', 'subjects', 'study_groups', 'review', 'profile', 'create_note', 'edit_note', 'view_note'];

$auth_pages = ['login', 'register'];

if (in_array($page, $restricted_pages) && !$isLoggedIn) {
    header('Location: index.php?page=login');
    exit;
    exit;
} elseif (in_array($page, $auth_pages) && $isLoggedIn) {
    header('Location: index.php?page=notes');
    exit;
}

include 'includes/header.php';
include 'includes/navigation.php';

switch ($page) {
    case 'home':
        include 'pages/home.php';
        break;
    case 'login':
        include 'pages/login.php';
        break;
    case 'register':
        include 'pages/register.php';
        break;
    case 'notes':
        include 'pages/notes.php';
        break;
    case 'create_note':
        include 'pages/create_note.php';
        break;
    case 'edit_note':
        include 'pages/edit_note.php';
        break;
    case 'view_note':
        include 'pages/view_note.php';
        break;
    case 'subjects':
        include 'pages/subjects.php';
        break;
    case 'study_groups':
        include 'pages/study_groups.php';
        break;
    case 'group_details':
        include 'pages/group_details.php';
        break;
    case 'review':
        include 'pages/review.php';
        break;
    case 'profile':
        include 'pages/profile.php';
        break;
    case 'logout':
        include 'pages/logout.php';
        break;
    default:
        include 'pages/404.php';
        break;
}

$possible_footer_paths = [
    'includes/footer.php',
    'footer.php',
    __DIR__ . '/includes/footer.php',
    __DIR__ . '/footer.php',
    'pages/footer.php'
];

$footer_included = false;
foreach ($possible_footer_paths as $path) {
    if (file_exists($path)) {
        include $path;
        $footer_included = true;
        break;
    }
}

if (!$footer_included) {
    echo '
        </div><!-- End main container -->
        <footer class="bg-light text-center text-muted py-4 mt-5">
            <div class="container">
                <p>&copy; ' . date('Y') . ' ' . (defined('APP_NAME') ? APP_NAME : 'EduNote') . ' - L\'app intelligente per gli appunti</p>
                <p class="small">Versione ' . (defined('APP_VERSION') ? APP_VERSION : '1.0.0') . '</p>
            </div>
        </footer>
        
        <!-- Bootstrap Bundle with Popper -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
        <script src="assets/js/script.js"></script>
    ';
    
    if (isset($extraScripts)) {
        echo $extraScripts;
    }
    
    echo '
    </body>
    </html>
    ';
}

ob_end_flush();
?>