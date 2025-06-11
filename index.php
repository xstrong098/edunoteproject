<?php
session_start();
ob_start();
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

$page = isset($_GET['page']) ? $_GET['page'] : 'home';

$restricted_pages = ['notes', 'subjects', 'study_groups', 'review', 'profile', 'create_note', 'edit_note', 'view_note'];

$requires_auth = in_array($page, $restricted_pages);

if ($requires_auth && !$isLoggedIn) {
    $actual_page = 'login';
    $show_auth_message = true;
} else {
    $actual_page = $page;
    $show_auth_message = false;
}

if ($page === 'logout') {
    $_SESSION = array();
    session_destroy();
    $actual_page = 'login';
    $logout_message = "Hai effettuato il logout con successo.";
}

include 'includes/header.php';
include 'includes/navigation.php';

if (isset($show_auth_message) && $show_auth_message) {
    echo "<div class='alert alert-warning'>
            <i class='fas fa-exclamation-triangle me-2'></i>
            Devi effettuare il login per accedere a questa pagina.
          </div>";
}
if (isset($logout_message)) {
    echo "<div class='alert alert-success'>
            <i class='fas fa-check-circle me-2'></i>
            $logout_message
          </div>";
}

$page_file = 'pages/' . basename($actual_page) . '.php';

if (file_exists($page_file)) {
    include $page_file;
} else {
    include 'pages/404.php';
}

include 'includes/footer.php';

ob_end_flush();
?>