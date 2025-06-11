<?php
session_start();
$_SESSION = array();
session_destroy();
setFlashMessage('success', 'Logout effettuato con successo.');
redirect('index.php?page=login');
?>