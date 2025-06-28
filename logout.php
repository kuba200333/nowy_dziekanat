<?php
// Plik: logout.php
session_start();
$_SESSION = array(); // Usuń wszystkie zmienne sesji
session_destroy(); // Zniszcz sesję
header("Location: login.php?status=wylogowano"); // Przekieruj na stronę logowania
exit();
?>